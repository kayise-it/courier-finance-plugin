<?php
/**
 * Push MySQL waybills onto the live 08600Africa "Waybills" tab (operational columns).
 *
 * kit_waybills stays Apps Script–owned. This writer targets the human-facing
 * Driver / WAYBILL # / CUSTOMER layout on spreadsheet 1w-9PfeN… gid 316636864.
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Waybills_Sheet_Push
{
    public const SHEET_NAME = 'Waybills';
    public const CRON_HOOK = 'kit_push_db_to_africa_sheet';
    public const RANGE = 'A1:AK5000';

    /** Column B: sequential trip # on the operational tab (header is blank). */
    private const TRIP_SEQ_INDEX = 1;

    /**
     * @var array<string, int>|null
     */
    private static $header_map = null;

    /**
     * @var array<int, string>|null
     */
    private static $header_row = null;

    public static function init(): void
    {
        add_action(self::CRON_HOOK, [self::class, 'cron_push_db']);
    }

    public static function spreadsheet_id(): string
    {
        return class_exists('Courier_Google_Sheets')
            ? Courier_Google_Sheets::get_source_spreadsheet_id()
            : '1w-9PfeN198UoLp-LO-ZFUYYjWiewuIfsp9r-2lY_Xec';
    }

    /**
     * Full DB → 08600Africa: operational Waybills tab, then kit_* table dumps.
     *
     * @return array{success:bool,message:string,waybills:int,tables:?array}
     */
    public static function cron_push_db(): array
    {
        if (function_exists('kit_seed_extend_runtime_limits')) {
            kit_seed_extend_runtime_limits();
        } else {
            @set_time_limit(300);
        }

        $waybills = self::push_all_from_db();
        $tables = ['success' => false, 'message' => 'Google Sheets export unavailable.', 'results' => []];
        if (class_exists('Courier_Google_Sheets') && Courier_Google_Sheets::is_configured()) {
            $tables = Courier_Google_Sheets::export_all_kit_tables(self::spreadsheet_id());
        }

        $ok = !empty($waybills['success']) && !empty($tables['success']);
        $message = trim(
            (string) ($waybills['message'] ?? '')
            . ' '
            . (string) ($tables['message'] ?? '')
        );

        return [
            'success' => $ok,
            'message' => $message,
            'waybills' => (int) ($waybills['count'] ?? 0),
            'tables' => $tables,
        ];
    }

    /**
     * Overlay every DB waybill onto the operational Waybills tab.
     * Existing sheet rows that are not in the DB are kept. Trip # / packing-list
     * cells already on the sheet are not overwritten.
     *
     * @return array{success:bool,message:string,count:int}
     */
    public static function push_all_from_db(): array
    {
        if (!class_exists('Courier_Google_Sheets') || !Courier_Google_Sheets::is_configured()) {
            return ['success' => false, 'message' => 'Google Sheets not configured.', 'count' => 0];
        }

        global $wpdb;
        $prefix = $wpdb->prefix;
        $sid = self::spreadsheet_id();

        try {
            $sheet_rows = Courier_Google_Sheets::get_values($sid, self::RANGE, self::SHEET_NAME);
            if (empty($sheet_rows) || !isset($sheet_rows[0])) {
                return ['success' => false, 'message' => 'Waybills tab is missing a header row.', 'count' => 0];
            }

            $header = array_map('strval', $sheet_rows[0]);
            $map = self::header_map_from_row($header);
            $wb_idx = self::waybill_column_index($map);
            if ($wb_idx === null) {
                return ['success' => false, 'message' => 'Waybills tab has no WAYBILL # column.', 'count' => 0];
            }

            $width = max(count($header), 37);
            $data = [];
            $index = [];
            foreach (array_slice($sheet_rows, 1) as $i => $row) {
                $line = self::pad_row(is_array($row) ? $row : [], $width);
                $data[] = $line;
                $key = self::waybill_key($line[$wb_idx] ?? '');
                if ($key !== '' && !isset($index[$key])) {
                    $index[$key] = $i;
                }
            }

            $lookups = self::load_lookups();
            $db_rows = $wpdb->get_results("SELECT * FROM {$prefix}kit_waybills ORDER BY id ASC") ?: [];
            $updated = 0;
            $appended = 0;

            foreach ($db_rows as $w) {
                $key = self::waybill_key($w->waybill_no ?? '');
                if ($key === '') {
                    continue;
                }
                $built = self::build_row($w, $lookups, $header, $map, $width);
                if (isset($index[$key])) {
                    $pos = $index[$key];
                    $data[$pos] = self::merge_row($data[$pos], $built, $map, $width);
                    $updated++;
                } else {
                    $index[$key] = count($data);
                    $data[] = $built;
                    $appended++;
                }
            }

            $end_row = count($data) + 1;
            [$sheet_id, $cur_rows, $cur_cols] = Courier_Google_Sheets::ensure_sheet(
                self::SHEET_NAME,
                $sid,
                max($end_row + 20, 100),
                $width
            );
            Courier_Google_Sheets::resize_sheet_grid(
                $sheet_id,
                max($end_row + 20, $cur_rows),
                max($width, $cur_cols),
                $sid,
                $cur_rows,
                $cur_cols
            );

            $end_col = Courier_Google_Sheets::column_letter($width - 1);
            $chunk = 400;
            for ($i = 0; $i < count($data); $i += $chunk) {
                $slice = array_slice($data, $i, $chunk);
                $start = $i + 2;
                $end = $i + count($slice) + 1;
                Courier_Google_Sheets::update_range(
                    self::SHEET_NAME,
                    'A' . $start . ':' . $end_col . $end,
                    $slice,
                    $sid
                );
            }

            return [
                'success' => true,
                'message' => sprintf(
                    'Wrote %d DB waybills to the 08600Africa Waybills tab (%d updated, %d appended).',
                    count($db_rows),
                    $updated,
                    $appended
                ),
                'count' => count($db_rows),
            ];
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[KIT_Waybills_Sheet_Push] push_all_from_db failed: ' . $e->getMessage());
            }
            return ['success' => false, 'message' => 'Waybills tab push failed: ' . $e->getMessage(), 'count' => 0];
        }
    }

    /**
     * Upsert one waybill on the operational tab (auto-sync path).
     *
     * @param object|array $waybill
     */
    public static function upsert_waybill($waybill): bool
    {
        if (!class_exists('Courier_Google_Sheets') || !Courier_Google_Sheets::is_configured()) {
            return false;
        }
        $w = is_object($waybill) ? $waybill : (object) $waybill;
        $waybill_no = trim((string) ($w->waybill_no ?? ''));
        if ($waybill_no === '' || $waybill_no === '0') {
            return false;
        }

        $sid = self::spreadsheet_id();
        try {
            $ctx = self::load_header_context($sid);
            $map = $ctx['map'];
            $header = $ctx['header'];
            $wb_idx = $ctx['wb_idx'];
            $width = $ctx['width'];
            $lookups = self::load_lookups();
            $built = self::build_row($w, $lookups, $header, $map, $width);

            $existing_rows = Courier_Google_Sheets::get_values(
                $sid,
                'A2:' . Courier_Google_Sheets::column_letter($width - 1) . '5000',
                self::SHEET_NAME
            );
            $match = null;
            $match_row_num = null;
            foreach ($existing_rows as $i => $row) {
                if (self::waybill_key($row[$wb_idx] ?? '') === self::waybill_key($waybill_no)) {
                    $match = self::pad_row($row, $width);
                    $match_row_num = $i + 2;
                    break;
                }
            }

            if ($match_row_num !== null) {
                $merged = self::merge_row($match, $built, $map, $width);
                $end_col = Courier_Google_Sheets::column_letter($width - 1);
                Courier_Google_Sheets::update_range(
                    self::SHEET_NAME,
                    'A' . $match_row_num . ':' . $end_col . $match_row_num,
                    [$merged],
                    $sid
                );
                return true;
            }

            Courier_Google_Sheets::append_values([$built], $sid, 'A:' . Courier_Google_Sheets::column_letter($width - 1), self::SHEET_NAME);
            return true;
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[KIT_Waybills_Sheet_Push] upsert failed for ' . $waybill_no . ': ' . $e->getMessage());
            }
            return false;
        }
    }

    public static function delete_waybill(string $waybill_no): bool
    {
        if (!class_exists('Courier_Google_Sheets') || !Courier_Google_Sheets::is_configured()) {
            return false;
        }
        $waybill_no = trim($waybill_no);
        if ($waybill_no === '') {
            return false;
        }
        try {
            $ctx = self::load_header_context(self::spreadsheet_id());
            return (bool) Courier_Google_Sheets::delete_row_by_value(
                $waybill_no,
                self::SHEET_NAME,
                $ctx['wb_idx'],
                self::spreadsheet_id()
            );
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[KIT_Waybills_Sheet_Push] delete failed for ' . $waybill_no . ': ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * @return array{header:array<int,string>,map:array<string,int>,wb_idx:int,width:int}
     */
    private static function load_header_context(string $sid): array
    {
        if (self::$header_row !== null && self::$header_map !== null) {
            $map = self::$header_map;
            $wb = self::waybill_column_index($map);
            if ($wb === null) {
                throw new RuntimeException('Waybills tab has no WAYBILL # column.');
            }
            return [
                'header' => self::$header_row,
                'map' => $map,
                'wb_idx' => $wb,
                'width' => max(count(self::$header_row), 37),
            ];
        }
        $rows = Courier_Google_Sheets::get_values($sid, 'A1:AK1', self::SHEET_NAME);
        $header = array_map('strval', $rows[0] ?? []);
        if ($header === []) {
            throw new RuntimeException('Waybills tab is missing a header row.');
        }
        self::$header_row = $header;
        self::$header_map = self::header_map_from_row($header);
        $wb = self::waybill_column_index(self::$header_map);
        if ($wb === null) {
            throw new RuntimeException('Waybills tab has no WAYBILL # column.');
        }
        return [
            'header' => $header,
            'map' => self::$header_map,
            'wb_idx' => $wb,
            'width' => max(count($header), 37),
        ];
    }

    /**
     * @param array<int, string> $header
     * @return array<string, int>
     */
    public static function header_map_from_row(array $header): array
    {
        $col = [];
        foreach ($header as $i => $c) {
            $h = strtolower(trim((string) $c));
            $h = str_replace(' ', '_', $h);
            if ($h !== '' && !isset($col[$h])) {
                $col[$h] = (int) $i;
            }
        }
        return $col;
    }

    /**
     * @param array<string, int> $map
     */
    private static function waybill_column_index(array $map): ?int
    {
        foreach (['waybill_#', 'waybill_no', 'waybillno', 'waybill_number', 'wb_#', 'wb_no', 'waybill'] as $key) {
            if (isset($map[$key])) {
                return (int) $map[$key];
            }
        }
        return $map['waybill'] ?? null;
    }

    private static function waybill_key($raw): string
    {
        $s = trim((string) $raw);
        if ($s === '' || strtoupper($s) === 'NULL') {
            return '';
        }
        if (function_exists('kit_seed_normalize_waybill_no_key')) {
            return kit_seed_normalize_waybill_no_key($s);
        }
        return ltrim($s, '0') === '' ? $s : ltrim($s, '0');
    }

    /**
     * @param array<int, mixed> $row
     * @return array<int, mixed>
     */
    private static function pad_row(array $row, int $width): array
    {
        for ($i = 0; $i < $width; $i++) {
            if (!array_key_exists($i, $row)) {
                $row[$i] = '';
            }
        }
        ksort($row);
        return array_slice($row, 0, $width);
    }

    /**
     * @param array<string, mixed> $lookups
     * @param array<int, string>   $header
     * @param array<string, int>   $map
     * @return array<int, mixed>
     */
    private static function build_row($w, array $lookups, array $header, array $map, int $width): array
    {
        $w = is_object($w) ? $w : (object) $w;
        $waybill_no = trim((string) ($w->waybill_no ?? ''));
        $fields = self::field_values($w, $lookups);

        $row = array_fill(0, $width, '');
        $row[self::TRIP_SEQ_INDEX] = $fields['trip_seq'];

        $assignments = [
            'driver' => $fields['driver'],
            'dispatch_date' => $fields['dispatch_date'],
            'date_received' => $fields['date_received'],
            'waybill_#' => $waybill_no,
            'customer' => $fields['customer'],
            'cell' => $fields['cell'],
            'email' => $fields['email'],
            'company' => $fields['company'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'waybill_description' => $fields['waybill_description'],
            'item_description' => $fields['item_description'],
            'qty' => $fields['qty'],
            'basis' => $fields['basis'],
            't_mass' => $fields['t_mass'],
            'length' => $fields['length'],
            'width' => $fields['width'],
            'height' => $fields['height'],
            't_volume' => $fields['t_volume'],
            'mass_cost' => $fields['mass_cost'],
            'vol_cost' => $fields['vol_cost'],
            'sad500' => $fields['sad500'],
            'sadc' => $fields['sadc'],
            'vat' => $fields['vat'],
            'cl_inv_#' => $fields['cl_inv'],
            'supplier' => $fields['supplier'],
            'customer_inv(_r)' => $fields['customer_inv'],
            'status' => $fields['status'],
            'created_by' => $fields['created_by'],
            'approved_by' => $fields['approved_by'],
            'created_id' => $fields['created_id'],
            'approved_id' => $fields['approved_id'],
        ];

        foreach ($assignments as $key => $value) {
            if (!isset($map[$key])) {
                continue;
            }
            $row[(int) $map[$key]] = $value;
        }

        return self::pad_row($row, $width);
    }

    /**
     * @param array<int, mixed>  $existing
     * @param array<int, mixed>  $built
     * @param array<string, int> $map
     * @return array<int, mixed>
     */
    private static function merge_row(array $existing, array $built, array $map, int $width): array
    {
        $existing = self::pad_row($existing, $width);
        $built = self::pad_row($built, $width);
        $preserve = [self::TRIP_SEQ_INDEX];
        foreach (['trip_#', 'plist_#', 'qty', 'supplier', 'volumetric_weight'] as $key) {
            if (isset($map[$key])) {
                $preserve[] = (int) $map[$key];
            }
        }
        $out = $existing;
        for ($i = 0; $i < $width; $i++) {
            $new = $built[$i];
            $old = $existing[$i];
            $old_s = is_scalar($old) ? trim((string) $old) : '';
            if (in_array($i, $preserve, true) && $old_s !== '') {
                continue;
            }
            if ($new === '' || $new === null) {
                continue;
            }
            $out[$i] = $new;
        }
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function field_values($w, array $lookups): array
    {
        $cust_id = (int) ($w->customer_id ?? 0);
        $company_id = (int) ($w->company_id ?? 0);
        $delivery_id = (int) ($w->delivery_id ?? 0);
        $city_id = (int) ($w->city_id ?? 0);
        $waybill_no = trim((string) ($w->waybill_no ?? ''));

        $customer = $lookups['customers'][$cust_id] ?? null;
        $delivery = $lookups['deliveries'][$delivery_id] ?? null;
        $items = $lookups['items'][$waybill_no] ?? [];

        $person = '';
        $cell = '';
        $email = '';
        $address = '';
        if (is_array($customer)) {
            $person = class_exists('KIT_Customers')
                ? KIT_Customers::format_person_display_name((string) ($customer['name'] ?? ''), (string) ($customer['surname'] ?? ''))
                : trim(($customer['name'] ?? '') . ' ' . ($customer['surname'] ?? ''));
            $cell = (string) ($customer['cell'] ?? '');
            $email = (string) ($customer['email_address'] ?? '');
            $address = (string) ($customer['address'] ?? '');
            if ($company_id <= 0) {
                $company_id = (int) ($customer['company_id'] ?? 0);
            }
        }

        $company = '';
        if ($company_id > 0 && isset($lookups['companies'][$company_id])) {
            $company = (string) $lookups['companies'][$company_id];
        }
        if ($company === '') {
            $company = 'Private';
        }

        $driver = '';
        $dispatch = '';
        $trip_seq = '';
        $warehouse = self::flag($w->warehouse ?? 0);
        if ($warehouse === 'TRUE' || (int) ($w->warehouse ?? 0) === 1) {
            $driver = 'Warehouse';
        }
        if (is_array($delivery)) {
            $dispatch = self::sheet_date($delivery['dispatch_date'] ?? '');
            $trip_seq = self::trip_seq_from_reference((string) ($delivery['delivery_reference'] ?? ''));
            $driver_id = (int) ($delivery['driver_id'] ?? 0);
            if ($driver === '' && $driver_id > 0 && isset($lookups['drivers'][$driver_id])) {
                $driver = (string) $lookups['drivers'][$driver_id];
            }
        }

        $item_names = [];
        $qty = 0;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            if ($name !== '') {
                $item_names[] = $name;
            }
            $qty += (int) ($item['quantity'] ?? 0);
        }
        if ($qty < 1) {
            $qty = 1;
        }

        $basis_raw = strtolower(trim((string) ($w->charge_basis ?? '')));
        $basis = '';
        if ($basis_raw === 'mass' || $basis_raw === 'weight') {
            $basis = 'Mass';
        } elseif ($basis_raw === 'volume') {
            $basis = 'Volume';
        }

        $cl_inv = self::client_invoice_from_waybill($w);
        $items_total = (float) ($w->waybill_items_total ?? 0);
        $customer_inv = $items_total != 0.0 ? ('R' . number_format($items_total, 2)) : '';

        $created_id = (int) ($w->created_by ?? 0);
        $approved_id = (int) ($w->approval_userid ?? 0);

        return [
            'driver' => $driver,
            'trip_seq' => $trip_seq,
            'dispatch_date' => $dispatch,
            'date_received' => self::sheet_date($w->created_at ?? ''),
            'customer' => $person,
            'cell' => $cell,
            'email' => $email,
            'company' => $company,
            'address' => $address,
            'city' => (string) ($lookups['cities'][$city_id] ?? ''),
            'waybill_description' => (string) ($w->description ?? ''),
            'item_description' => implode(' + ', $item_names),
            'qty' => (string) $qty,
            'basis' => $basis,
            't_mass' => self::sheet_num($w->total_mass_kg ?? '', 2),
            'length' => self::sheet_num($w->item_length ?? '', 2),
            'width' => self::sheet_num($w->item_width ?? '', 2),
            'height' => self::sheet_num($w->item_height ?? '', 2),
            't_volume' => self::sheet_num($w->total_volume ?? '', 4),
            'mass_cost' => self::sheet_num($w->mass_charge ?? '', 2),
            'vol_cost' => self::sheet_num($w->volume_charge ?? '', 2),
            'sad500' => self::flag($w->include_sad500 ?? 0),
            'sadc' => self::flag($w->include_sadc ?? 0),
            'vat' => self::flag($w->vat_include ?? 0),
            'cl_inv' => $cl_inv,
            'supplier' => '',
            'customer_inv' => $customer_inv,
            'status' => (string) ($w->status ?? ''),
            'created_by' => self::first_name($lookups['users'][$created_id] ?? ''),
            'approved_by' => self::first_name($lookups['users'][$approved_id] ?? ''),
            'created_id' => $created_id > 0 ? (string) $created_id : '',
            'approved_id' => $approved_id > 0 ? (string) $approved_id : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function load_lookups(): array
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $cities = [];
        foreach ($wpdb->get_results("SELECT id, city_name FROM {$prefix}kit_operating_cities", ARRAY_A) ?: [] as $row) {
            $cities[(int) $row['id']] = (string) $row['city_name'];
        }

        $customers = [];
        foreach ($wpdb->get_results("SELECT * FROM {$prefix}kit_customers", ARRAY_A) ?: [] as $row) {
            $customers[(int) $row['cust_id']] = $row;
        }

        $companies = [];
        foreach ($wpdb->get_results("SELECT company_id, company_name FROM {$prefix}kit_company_customers", ARRAY_A) ?: [] as $row) {
            $companies[(int) $row['company_id']] = trim((string) $row['company_name']);
        }

        $drivers = [];
        foreach ($wpdb->get_results("SELECT id, name FROM {$prefix}kit_drivers", ARRAY_A) ?: [] as $row) {
            $drivers[(int) $row['id']] = trim((string) $row['name']);
        }

        $deliveries = [];
        foreach ($wpdb->get_results("SELECT * FROM {$prefix}kit_deliveries", ARRAY_A) ?: [] as $row) {
            $deliveries[(int) $row['id']] = $row;
        }

        $items = [];
        foreach ($wpdb->get_results("SELECT waybillno, item_name, quantity FROM {$prefix}kit_waybill_items ORDER BY id ASC", ARRAY_A) ?: [] as $row) {
            $wb = trim((string) ($row['waybillno'] ?? ''));
            if ($wb === '') {
                continue;
            }
            $items[$wb][] = $row;
        }

        $users = [];
        foreach ($wpdb->get_results("SELECT ID, display_name FROM {$wpdb->users}", ARRAY_A) ?: [] as $row) {
            $users[(int) $row['ID']] = (string) $row['display_name'];
        }

        return [
            'cities' => $cities,
            'customers' => $customers,
            'companies' => $companies,
            'drivers' => $drivers,
            'deliveries' => $deliveries,
            'items' => $items,
            'users' => $users,
        ];
    }

    private static function client_invoice_from_waybill($w): string
    {
        $misc = $w->miscellaneous ?? '';
        if (is_string($misc) && $misc !== '') {
            $parsed = @unserialize($misc);
            if (is_array($parsed)) {
                $from_others = trim((string) ($parsed['others']['client_invoice'] ?? ''));
                if ($from_others !== '') {
                    return $from_others;
                }
            }
        }
        return trim((string) ($w->product_invoice_number ?? ''));
    }

    private static function trip_seq_from_reference(string $ref): string
    {
        if (preg_match('/-(\d{3})$/', trim($ref), $m)) {
            return (string) ((int) $m[1]);
        }
        return '';
    }

    private static function sheet_date($raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || preg_match('/^(0000-00-00|null|n\/a)$/i', $raw)) {
            return '';
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return $raw;
        }
        return date('n/j/Y', $ts);
    }

    private static function sheet_num($v, int $decimals): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (!is_numeric($v)) {
            return (string) $v;
        }
        $n = round((float) $v, $decimals);
        if (abs($n) < 0.0000001) {
            return '0';
        }
        $formatted = number_format($n, $decimals, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted;
    }

    private static function flag($v): string
    {
        if ($v === true || $v === 1 || $v === '1' || (is_string($v) && strtoupper(trim($v)) === 'TRUE')) {
            return 'TRUE';
        }
        return '';
    }

    private static function first_name(string $display): string
    {
        $display = trim($display);
        if ($display === '') {
            return '';
        }
        $parts = preg_split('/\s+/', $display) ?: [];
        return (string) ($parts[0] ?? $display);
    }
}

KIT_Waybills_Sheet_Push::init();
