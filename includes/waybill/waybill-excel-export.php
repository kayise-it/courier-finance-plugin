<?php
/**
 * Download the waybills chosen in the All Waybills action menu as Excel.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Waybill_Excel_Export
{
    public static function download_url(array $tokens): string
    {
        return add_query_arg(
            [
                'action' => 'kit_export_waybills_excel',
                'bulk_ids' => implode(',', $tokens),
                'bulk_nonce' => wp_create_nonce('bulk_action_nonce'),
            ],
            admin_url('admin-post.php')
        );
    }

    public static function handle(): void
    {
        if (!is_user_logged_in() || !current_user_can('kit_view_waybills')) {
            wp_die('Unauthorized');
        }

        $nonce = isset($_REQUEST['bulk_nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['bulk_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'bulk_action_nonce')) {
            wp_die('Invalid bulk action nonce.');
        }

        $raw = isset($_REQUEST['bulk_ids']) ? wp_unslash($_REQUEST['bulk_ids']) : '';
        $tokens = self::tokens_from_request(is_string($raw) ? $raw : '');
        if ($tokens === []) {
            wp_die('Select at least one waybill.');
        }

        $rows = self::rows_for_tokens($tokens);
        if ($rows === []) {
            wp_die('No waybills matched the selection.');
        }

        if (class_exists('KIT_Bulk_Action_Log')) {
            KIT_Bulk_Action_Log::record('export_excel', $tokens, 'waybill', 'waybill-manage');
        }

        $show_total = class_exists('KIT_User_Roles') && KIT_User_Roles::can_see_prices();
        $columns = self::columns($show_total);
        $sheet_rows = [];
        foreach ($rows as $row) {
            $sheet_rows[] = self::sheet_row($row, $show_total);
        }

        $filename = 'waybills-' . gmdate('Y-m-d') . '.xlsx';
        $binary = self::build_xlsx($columns, $sheet_rows);
        if ($binary === null) {
            $filename = 'waybills-' . gmdate('Y-m-d') . '.xls';
            $binary = self::build_spreadsheetml($columns, $sheet_rows);
            $content_type = 'application/vnd.ms-excel';
        } else {
            $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        echo $binary;
        exit;
    }

    /**
     * @return string[]
     */
    private static function tokens_from_request(string $raw): array
    {
        $parts = preg_split('/\s*,\s*/', $raw) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $token = sanitize_text_field(trim((string) $part));
            if ($token !== '') {
                $tokens[$token] = $token;
            }
        }

        return array_slice(array_values($tokens), 0, 2000);
    }

    /**
     * @param string[] $tokens Checkbox values: waybill numbers, with row id as a fallback.
     * @return object[]
     */
    private static function rows_for_tokens(array $tokens): array
    {
        $by_number = self::query_waybills($tokens, 'w.waybill_no');

        $found_numbers = [];
        foreach ($by_number as $row) {
            $found_numbers[(string) $row->waybill_no] = true;
        }

        $missing_ids = [];
        foreach ($tokens as $token) {
            if (isset($found_numbers[$token])) {
                continue;
            }
            $id = (int) $token;
            if ($id > 0 && (string) $id === $token) {
                $missing_ids[] = $id;
            }
        }

        $by_id = $missing_ids === [] ? [] : self::query_waybills($missing_ids, 'w.id');
        $indexed = [];
        foreach (array_merge($by_number, $by_id) as $row) {
            $indexed[(int) $row->id] = $row;
        }

        $ordered = [];
        $used = [];
        foreach ($tokens as $token) {
            foreach ($indexed as $id => $row) {
                if (isset($used[$id])) {
                    continue;
                }
                $matches_number = (string) $row->waybill_no === $token;
                $matches_id = (string) $id === $token;
                if ($matches_number || $matches_id) {
                    $ordered[] = $row;
                    $used[$id] = true;
                }
            }
        }

        return $ordered;
    }

    /**
     * @param array<int, string|int> $values
     * @return object[]
     */
    private static function query_waybills(array $values, string $column): array
    {
        global $wpdb;

        if ($values === [] || !in_array($column, ['w.waybill_no', 'w.id'], true)) {
            return [];
        }

        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $cities_table = $wpdb->prefix . 'kit_operating_cities';
        $format = $column === 'w.id' ? '%d' : '%s';
        $placeholders = implode(',', array_fill(0, count($values), $format));

        $sql = "
            SELECT
                w.id,
                w.waybill_no,
                w.description,
                w.customer_id,
                w.product_invoice_amount,
                w.item_length,
                w.item_width,
                w.item_height,
                w.total_mass_kg,
                w.total_volume,
                w.status,
                w.approval,
                w.warehouse,
                w.created_by,
                COALESCE(NULLIF(c.name, ''), c2.name) AS customer_name,
                COALESCE(NULLIF(c.surname, ''), c2.surname) AS customer_surname,
                COALESCE(NULLIF(co.company_name, ''), NULLIF(cust_co.company_name, ''), NULLIF(cust_co2.company_name, '')) AS company_name,
                dest_city.city_name AS customer_city,
                u.display_name AS created_by_name
            FROM {$waybills_table} w
            LEFT JOIN {$customers_table} c ON w.customer_id = c.cust_id
            LEFT JOIN {$customers_table} c2 ON w.customer_id = c2.id
            LEFT JOIN {$wpdb->prefix}kit_company_customers co ON w.company_id = co.company_id
            LEFT JOIN {$wpdb->prefix}kit_company_customers cust_co ON c.company_id = cust_co.company_id
            LEFT JOIN {$wpdb->prefix}kit_company_customers cust_co2 ON c2.company_id = cust_co2.company_id
            LEFT JOIN {$cities_table} dest_city ON w.city_id = dest_city.id
            LEFT JOIN {$wpdb->users} u ON w.created_by = u.ID
            WHERE {$column} IN ({$placeholders})
        ";

        $prepared = $wpdb->prepare($sql, ...$values);
        $rows = $wpdb->get_results($prepared);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array{label:string,width:float,type:string}>
     */
    private static function columns(bool $show_total): array
    {
        $columns = [
            ['label' => 'Waybill No', 'width' => 14, 'type' => 'text'],
            ['label' => 'Customer', 'width' => 28, 'type' => 'text'],
            ['label' => 'Created by', 'width' => 22, 'type' => 'text'],
            ['label' => 'Description', 'width' => 48, 'type' => 'text'],
            ['label' => 'City', 'width' => 18, 'type' => 'text'],
            ['label' => 'Status', 'width' => 14, 'type' => 'text'],
            ['label' => 'Mass (kg)', 'width' => 12, 'type' => 'mass'],
            ['label' => 'Length', 'width' => 10, 'type' => 'int'],
            ['label' => 'Width', 'width' => 10, 'type' => 'int'],
            ['label' => 'Height', 'width' => 10, 'type' => 'int'],
            ['label' => 'Volume (m3)', 'width' => 14, 'type' => 'volume'],
        ];
        if ($show_total) {
            $columns[] = ['label' => 'Total', 'width' => 14, 'type' => 'money'];
        }

        return $columns;
    }

    /**
     * @return array<int, array{value:string|float|int,type:string}>
     */
    private static function sheet_row(object $row, bool $show_total): array
    {
        $cells = [
            ['value' => (string) ($row->waybill_no ?? ''), 'type' => 'text'],
            ['value' => self::customer_label($row), 'type' => 'text'],
            ['value' => self::created_by_label($row), 'type' => 'text'],
            ['value' => trim((string) ($row->description ?? '')), 'type' => 'text'],
            ['value' => self::city_label($row), 'type' => 'text'],
            ['value' => self::status_label($row), 'type' => 'text'],
            ['value' => round((float) ($row->total_mass_kg ?? 0), 1), 'type' => 'mass'],
            ['value' => (int) round((float) ($row->item_length ?? 0)), 'type' => 'int'],
            ['value' => (int) round((float) ($row->item_width ?? 0)), 'type' => 'int'],
            ['value' => (int) round((float) ($row->item_height ?? 0)), 'type' => 'int'],
            ['value' => round((float) ($row->total_volume ?? 0), 3), 'type' => 'volume'],
        ];
        if ($show_total) {
            $cells[] = ['value' => round((float) ($row->product_invoice_amount ?? 0), 2), 'type' => 'money'];
        }

        return $cells;
    }

    private static function customer_label(object $row): string
    {
        $is_placeholder = static function ($value): bool {
            $v = strtolower(trim((string) $value));
            return $v === '' || in_array($v, ['0', 'null', 'n/a', 'na', 'none', '-', '--'], true);
        };

        $customer_name = trim((string) ($row->customer_name ?? ''));
        $customer_surname = trim((string) ($row->customer_surname ?? ''));
        $company = trim((string) ($row->company_name ?? ''));
        if ($is_placeholder($customer_name)) {
            $customer_name = '';
        }
        if ($is_placeholder($customer_surname)) {
            $customer_surname = '';
        }
        if ($is_placeholder($company)) {
            $company = '';
        }

        $person_name = class_exists('KIT_Customers')
            ? KIT_Customers::format_person_display_name($customer_name, $customer_surname)
            : trim($customer_name . ' ' . $customer_surname);
        $company_is_displayable = $company !== '' && !in_array(strtolower($company), ['individual', '1ndividual', 'private'], true);
        $company_is_redundant = $company_is_displayable && $person_name !== '' && (
            strcasecmp($company, $person_name) === 0
            || strcasecmp($company, $customer_surname) === 0
        );
        if ($person_name !== '' && $company_is_displayable && !$company_is_redundant) {
            return $person_name . ' · ' . $company;
        }
        if ($person_name !== '') {
            return $person_name;
        }

        return $company_is_displayable ? $company : '';
    }

    private static function created_by_label(object $row): string
    {
        $name = trim((string) ($row->created_by_name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $raw = $row->created_by ?? '';
        if (is_numeric($raw) && function_exists('get_userdata')) {
            $user = get_userdata((int) $raw);
            if ($user && !empty($user->display_name)) {
                return (string) $user->display_name;
            }
        }

        return trim((string) $raw);
    }

    private static function city_label(object $row): string
    {
        if ((int) ($row->warehouse ?? 0) === 1) {
            return 'Warehoused';
        }
        $city = trim((string) ($row->customer_city ?? ''));

        return $city !== '' ? $city : 'Unassigned City';
    }

    private static function status_label(object $row): string
    {
        $raw = $row->status ?? $row->approval ?? 'pending';
        $status = strtolower(trim((string) $raw));
        if ($status === '1' || $status === 'true') {
            $status = 'approved';
        } elseif ($status === '0' || $status === 'false' || $status === '') {
            $status = 'pending';
        }

        $labels = [
            'approved' => 'Approved',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'quoted' => 'Quoted',
            'paid' => 'Paid',
            'assigned' => 'Assigned',
            'scheduled' => 'Scheduled',
            'unconfirmed' => 'Unconfirmed',
            'in_transit' => 'In Transit',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'invoiced' => 'Invoiced',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * @param array<int, array{label:string,width:float,type:string}> $columns
     * @param array<int, array<int, array{value:string|float|int,type:string}>> $rows
     */
    public static function build_xlsx(array $columns, array $rows): ?string
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kitxlsx');
        if ($tmp === false) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            return null;
        }

        $zip->addFromString('[Content_Types].xml', self::content_types_xml());
        $zip->addFromString('_rels/.rels', self::root_rels_xml());
        $zip->addFromString('xl/workbook.xml', self::workbook_xml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbook_rels_xml());
        $zip->addFromString('xl/styles.xml', self::styles_xml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet_xml($columns, $rows));
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    /**
     * @param array<int, array{label:string,width:float,type:string}> $columns
     * @param array<int, array<int, array{value:string|float|int,type:string}>> $rows
     */
    public static function build_spreadsheetml(array $columns, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Worksheet ss:Name="Waybills"><Table>';
        $xml .= '<Row>';
        foreach ($columns as $column) {
            $xml .= '<Cell><Data ss:Type="String">' . self::xml_text($column['label']) . '</Data></Cell>';
        }
        $xml .= '</Row>';
        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                if ($cell['type'] === 'text') {
                    $xml .= '<Cell><Data ss:Type="String">' . self::xml_text((string) $cell['value']) . '</Data></Cell>';
                } else {
                    $xml .= '<Cell><Data ss:Type="Number">' . self::xml_number($cell['value']) . '</Data></Cell>';
                }
            }
            $xml .= '</Row>';
        }
        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }

    /**
     * @param array<int, array{label:string,width:float,type:string}> $columns
     * @param array<int, array<int, array{value:string|float|int,type:string}>> $rows
     */
    private static function sheet_xml(array $columns, array $rows): string
    {
        $styles = [
            'text' => 0,
            'header' => 1,
            'money' => 2,
            'int' => 3,
            'mass' => 4,
            'volume' => 5,
        ];

        $cols = '';
        foreach ($columns as $index => $column) {
            $n = $index + 1;
            $width = number_format((float) $column['width'], 2, '.', '');
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $width . '" customWidth="1"/>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheet .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $sheet .= '<cols>' . $cols . '</cols><sheetData>';

        $sheet .= '<row r="1">';
        foreach ($columns as $index => $column) {
            $ref = self::cell_ref($index, 1);
            $sheet .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . self::xml_text($column['label']) . '</t></is></c>';
        }
        $sheet .= '</row>';

        foreach ($rows as $row_index => $row) {
            $excel_row = $row_index + 2;
            $sheet .= '<row r="' . $excel_row . '">';
            foreach ($row as $index => $cell) {
                $ref = self::cell_ref($index, $excel_row);
                $style = $styles[$cell['type']] ?? 0;
                if ($cell['type'] === 'text') {
                    $sheet .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . self::xml_text((string) $cell['value']) . '</t></is></c>';
                } else {
                    $sheet .= '<c r="' . $ref . '" s="' . $style . '"><v>' . self::xml_number($cell['value']) . '</v></c>';
                }
            }
            $sheet .= '</row>';
        }

        $sheet .= '</sheetData></worksheet>';

        return $sheet;
    }

    private static function cell_ref(int $column_index, int $row): string
    {
        $column_index++;
        $letters = '';
        while ($column_index > 0) {
            $mod = ($column_index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $column_index = intdiv($column_index - 1, 26);
        }

        return $letters . $row;
    }

    private static function xml_text(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @param mixed $value
     */
    private static function xml_number($value): string
    {
        if (!is_numeric($value)) {
            return '0';
        }
        $number = (float) $value;
        if (!is_finite($number)) {
            return '0';
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.') ?: '0';
    }

    private static function content_types_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function root_rels_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Waybills" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbook_rels_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function styles_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2">'
            . '<numFmt numFmtId="164" formatCode="0.0"/>'
            . '<numFmt numFmtId="165" formatCode="0.000"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F2937"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}

if (function_exists('add_action')) {
    add_action('admin_post_kit_export_waybills_excel', ['KIT_Waybill_Excel_Export', 'handle']);
}
