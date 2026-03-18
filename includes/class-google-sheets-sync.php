<?php
/**
 * Sync plugin entities (drivers, waybills, waybill items, customers, deliveries) to Google Sheets.
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Courier_Google_Sheets_Sync {
    const AUTO_SYNC_OPTION = 'courier_google_auto_sync_enabled';

    /**
     * Auto-sync state (false by default for safer testing).
     */
    public static function is_auto_sync_enabled() {
        return (bool) get_option(self::AUTO_SYNC_OPTION, 0);
    }

    /**
     * True when Google is configured and auto-sync is enabled.
     */
    public static function can_auto_sync() {
        return self::can_sync() && self::is_auto_sync_enabled();
    }

    /** Sync driver add (append to sheet) */
    public static function sync_driver_add($data) {
        if (!self::can_auto_sync()) {
            return false;
        }
        try {
            $sheet = self::get_sheet_name('drivers');
            $row = [
                '', $data['name'] ?? '', $data['phone'] ?? '', $data['email'] ?? '',
                $data['license_number'] ?? '', $data['is_active'] ?? 1,
                current_time('mysql'), current_time('mysql'),
            ];
            Courier_Google_Sheets::append_values([$row], '', 'A:H', $sheet);
            return true;
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Driver sync failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /** Sync driver delete (remove from sheet) */
    public static function sync_driver_delete($driver_name) {
        if (!self::can_auto_sync()) {
            return false;
        }
        try {
            Courier_Google_Sheets::delete_row_by_value(trim((string) $driver_name), self::get_sheet_name('drivers'), 1);
            return true;
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Driver delete sync failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    public static function get_sheet_name($entity) {
        $map = [
            'drivers'       => defined('COURIER_GOOGLE_DRIVERS_SHEET') ? COURIER_GOOGLE_DRIVERS_SHEET : 'kit_drivers',
            'waybills'      => defined('COURIER_GOOGLE_WAYBILLS_SHEET') ? COURIER_GOOGLE_WAYBILLS_SHEET : 'kit_waybills',
            'waybill_items' => defined('COURIER_GOOGLE_WAYBILL_ITEMS_SHEET') ? COURIER_GOOGLE_WAYBILL_ITEMS_SHEET : 'kit_waybill_items',
            'customers'     => defined('COURIER_GOOGLE_CUSTOMERS_SHEET') ? COURIER_GOOGLE_CUSTOMERS_SHEET : 'kit_customers',
            'deliveries'    => defined('COURIER_GOOGLE_DELIVERIES_SHEET') ? COURIER_GOOGLE_DELIVERIES_SHEET : 'kit_deliveries',
        ];
        return $map[$entity] ?? $entity;
    }

    public static function can_sync() {
        return class_exists('Courier_Google_Sheets') && Courier_Google_Sheets::is_configured();
    }

    /** Normalize value for sheet: never write literal "NULL"; use empty string for null/empty. */
    private static function sheet_val($v) {
        if ($v === null || $v === '') {
            return '';
        }
        $s = is_string($v) ? trim($v) : (string) $v;
        return (strtoupper($s) === 'NULL') ? '' : $v;
    }

    /** Strip special characters from phone/customer number: keep only digits and optional leading +. */
    private static function strip_phone_special_chars($v) {
        $s = self::sheet_val($v);
        if ($s === '') {
            return '';
        }
        $s = (string) $s;
        $leading = (strpos($s, '+') === 0) ? '+' : '';
        $digits = preg_replace('/[^0-9]/', '', $s);
        return $leading . $digits;
    }

    /** Integer ID for sheet (country_id, city_id): 0 for null/empty, otherwise cast to int. */
    private static function sheet_id($v) {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_string($v) && strtoupper(trim($v)) === 'NULL') {
            return 0;
        }
        return (int) $v;
    }

    /**
     * Build waybill row for sheet. Order must match sheet headers (42 cols): id (auto increment from DB), parcel_id (always empty), description ... last_updated_at. All values scalar.
     */
    private static function build_waybill_row($w) {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $city_name = '';
        $cust_name = '';
        if (!empty($w->city_id)) {
            $city_name = $wpdb->get_var($wpdb->prepare(
                "SELECT city_name FROM {$prefix}kit_operating_cities WHERE id = %d",
                (int) $w->city_id
            ));
        }
        $cust_id = isset($w->customer_id) ? (int) $w->customer_id : 0;
        if ($cust_id > 0) {
            $c = $wpdb->get_row($wpdb->prepare(
                "SELECT name, surname, company_name FROM {$prefix}kit_customers WHERE cust_id = %d",
                $cust_id
            ), ARRAY_A);
            if ($c) {
                $cust_name = !empty($c['company_name']) ? trim((string) $c['company_name']) : trim(($c['name'] ?? '') . ' ' . ($c['surname'] ?? ''));
            }
        }
        $vat = $w->vat_include ?? '';
        $vat_bool = (is_numeric($vat) && (int) $vat !== 0) || strtolower((string) $vat) === 'yes' || strtolower((string) $vat) === 'true' || (string) $vat === '1';
        $warehouse_val = isset($w->warehouse) ? ((int) $w->warehouse ? 1 : 0) : 0;
        $charge_basis = $w->charge_basis ?? '';
        if (is_object($charge_basis) || is_array($charge_basis)) {
            $charge_basis = '';
        }
        $created_at = isset($w->created_at) && (string) $w->created_at !== '' ? (string) $w->created_at : current_time('mysql');
        $last_updated_at = isset($w->last_updated_at) && (string) $w->last_updated_at !== '' ? (string) $w->last_updated_at : $created_at;
        return [
            $w->id ?? '',                                                                 // A: id (auto increment from DB)
            '',                                                                           // B: parcel_id (always empty)
            (string) ($w->description ?? ''),                                             // C: description
            $w->direction_id ?? '',                                                       // D: direction_id
            (string) ($city_name ?: ''),                                                  // E: city_name_ignore
            $w->city_id ?? '',                                                             // F: city_id (skipped on write)
            $w->delivery_id ?? '',                                                         // G: delivery_id
            (string) ($cust_name ?: ''),                                                  // H: cust_name_ignore
            $w->customer_id ?? '',                                                         // I: customer_id
            (string) ($w->approval ?? ''),                                                 // J: approval
            $w->approval_userid ?? '',                                                     // K: approval_userid
            (string) ($w->waybill_no ?? ''),                                               // L: waybill_no (match for upsert)
            (string) ($w->product_invoice_number ?? ''),                                   // M: product_invoice_number
            $w->product_invoice_amount ?? '',                                              // N: product_invoice_amount
            $w->waybill_items_total ?? '',                                                 // O: waybill_items_total
            $w->misc_total ?? '',                                                          // P: misc_total
            $w->border_clearing_total ?? '',                                                // Q: border_clearing_total
            $w->sad500_amount ?? '',                                                       // R: sad500_amount
            $w->sadc_amount ?? '',                                                         // S: sadc_amount
            $w->international_price_rands ?? '',                                           // T: international_price_rands
            $w->item_length ?? '',                                                          // U: item_length
            $w->item_width ?? '',                                                          // V: item_width
            $w->item_height ?? '',                                                         // W: item_height
            $w->total_mass_kg ?? '',                                                       // X: total_mass_kg
            $w->total_volume ?? '',                                                        // Y: total_volume
            $w->mass_charge ?? '',                                                         // Z: mass_charge
            $w->volume_charge ?? '',                                                       // AA: volume_charge
            (string) $charge_basis,                                                        // AB: charge_basis (scalar only)
            $vat_bool ? 'TRUE' : 'FALSE',                                                  // AC: vat_include
            $warehouse_val,                                                                // AD: warehouse (1 or 0)
            (string) ($w->miscellaneous ?? ''),                                            // AE: miscellaneous
            $w->include_sad500 ?? '',                                                      // AF: include_sad500
            $w->include_sadc ?? '',                                                        // AG: include_sadc
            $w->return_load ?? '',                                                         // AH: return_load
            (string) ($w->tracking_number ?? ''),                                          // AI: tracking_number
            (string) ($w->qr_code_data ?? ''),                                             // AJ: qr_code_data
            $w->created_by ?? '',                                                          // AK: created_by
            $w->last_updated_by ?? '',                                                     // AL: last_updated_by
            (string) ($w->status ?? ''),                                                   // AM: status
            $w->status_userid ?? '',                                                       // AN: status_userid
            $created_at,                                                                   // AO: created_at (never blank)
            $last_updated_at,                                                              // AP: last_updated_at
        ];
    }

    /** Sync waybill row to sheet (upsert: update if waybill_no exists, else append). Match on column L (waybill_no). Skip column F (city_id) on update so sheet formulas work. */
    public static function sync_waybill($waybill, $is_new = true) {
        if (!self::can_auto_sync()) {
            if (function_exists('error_log')) {
                error_log('[Courier Google Sheets] Sync skipped: auto sync is disabled or Google Sheets is not configured.');
            }
            return;
        }
        $waybill_no = (string) ($waybill->waybill_no ?? '');
        $waybill_no = trim($waybill_no);
        if ($waybill_no === '' || $waybill_no === '0') {
            if (function_exists('error_log')) {
                error_log('[Courier Google Sheets] Sync skipped: waybill_no is empty or 0 (id=' . ($waybill->id ?? '') . ').');
            }
            return;
        }
        $sheet = self::get_sheet_name('waybills');
        $row = self::build_waybill_row($waybill);
        $row[1] = '';        // B: parcel_id always empty (mapping must not fill it)
        $row[11] = $waybill_no;  // L: waybill_no (match value for upsert)
        try {
            $updated = Courier_Google_Sheets::update_row_by_value_skip_columns(
                $waybill_no,
                $sheet,
                $row,
                11,
                [5],
                ''
            );
            if (!$updated) {
                Courier_Google_Sheets::append_row_skip_columns($row, $sheet, [5], '');
            }
            delete_option('courier_last_sync_error');
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier Google Sheets] Waybill sync failed for waybill_no=' . $waybill_no . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
            update_option('courier_last_sync_error', [
                'waybill_no' => $waybill_no,
                'message'    => $e->getMessage(),
                'time'       => time(),
            ], false);
        }
    }

    /** Remove waybill from sheet */
    public static function sync_waybill_delete($waybill_no) {
        if (!self::can_auto_sync()) {
            return;
        }
        try {
            Courier_Google_Sheets::delete_row_by_value((string) $waybill_no, self::get_sheet_name('waybills'), 11);
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Waybill delete sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Replace waybill items in sheet for given waybill_no */
    public static function sync_waybill_items($waybill_no, $items) {
        if (!self::can_auto_sync()) {
            return;
        }
        $sheet = self::get_sheet_name('waybill_items');
        try {
            Courier_Google_Sheets::delete_rows_by_value((string) $waybill_no, $sheet, 0);
            if (!empty($items) && is_array($items)) {
                $rows = [];
                foreach ($items as $item) {
                    $rows[] = [
                        $item['waybillno'] ?? $waybill_no,
                        $item['item_name'] ?? '',
                        (int) ($item['quantity'] ?? 1),
                        (float) ($item['unit_price'] ?? 0),
                        (float) ($item['unit_mass'] ?? 0),
                        (float) ($item['unit_volume'] ?? 0),
                        (float) ($item['total_price'] ?? 0),
                        $item['client_invoice'] ?? '',
                    ];
                }
                if (!empty($rows)) {
                    Courier_Google_Sheets::append_values($rows, '', 'A:H', $sheet);
                }
            }
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Waybill items sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Remove waybill items from sheet */
    public static function sync_waybill_items_delete($waybill_no) {
        if (!self::can_auto_sync()) {
            return;
        }
        try {
            Courier_Google_Sheets::delete_rows_by_value((string) $waybill_no, self::get_sheet_name('waybill_items'), 0);
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Waybill items delete sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Sync customer add (append to sheet) */
    public static function sync_customer_add($customer) {
        if (!self::can_auto_sync()) {
            return false;
        }
        try {
            $sheet = self::get_sheet_name('customers');
            $cust_id = $customer->cust_id ?? '';
            if (!is_numeric($cust_id)) {
                $cust_id = rand(1000, 9999);
            }
            // Column order must match sheet: A=id, B=cust_id, C=name, D=surname, E=cell, F=telephone, G=email_address, H=country_id, I=city_id, J=vat_number, K=address, L=company_name.
            $row = [
                self::sheet_val($customer->id ?? ''),
                $cust_id,
                self::sheet_val($customer->name ?? ''),
                self::sheet_val($customer->surname ?? ''),
                self::strip_phone_special_chars($customer->cell ?? ''),
                self::strip_phone_special_chars($customer->telephone ?? ''),
                self::sheet_val($customer->email_address ?? ''),
                self::sheet_id($customer->country_id ?? 0),
                self::sheet_id($customer->city_id ?? 0),
                self::sheet_val($customer->vat_number ?? ''),
                self::sheet_val($customer->address ?? ''),
                self::sheet_val($customer->company_name ?? ''),
            ];
            Courier_Google_Sheets::append_values([$row], '', 'A:L', $sheet, 'RAW');
            return true;
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Customer sync failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /** Sync customer update */
    public static function sync_customer_update($customer) {
        if (!self::can_auto_sync()) {
            return;
        }
        $sheet = self::get_sheet_name('customers');
        $cust_id = $customer->cust_id ?? '';
        if (!is_numeric($cust_id)) {
            $cust_id = rand(1000, 9999);
        }
        // Column order must match sheet; country_id and city_id as integers
        $row = [
            self::sheet_val($customer->id ?? ''),
            $cust_id,
            self::sheet_val($customer->name ?? ''),
            self::sheet_val($customer->surname ?? ''),
            self::strip_phone_special_chars($customer->cell ?? ''),
            self::strip_phone_special_chars($customer->telephone ?? ''),
            self::sheet_val($customer->email_address ?? ''),
            self::sheet_id($customer->country_id ?? 0),
            self::sheet_id($customer->city_id ?? 0),
            self::sheet_val($customer->vat_number ?? ''),
            self::sheet_val($customer->address ?? ''),
            self::sheet_val($customer->company_name ?? ''),
        ];
        try {
            Courier_Google_Sheets::update_row_by_value((string) $cust_id, $sheet, $row, 1, '', 'RAW');
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Customer update sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Sync customer delete - removes row from sheet. Tries cust_id (col B) first, then id (col A) as fallback. */
    public static function sync_customer_delete($cust_id, $db_id = null) {
        if (!self::can_auto_sync()) {
            return;
        }
        $sheet = self::get_sheet_name('customers');
        try {
            $deleted = Courier_Google_Sheets::delete_row_by_value((string) $cust_id, $sheet, 1);
            if (!$deleted && $db_id !== null && $db_id !== '') {
                Courier_Google_Sheets::delete_row_by_value((string) $db_id, $sheet, 0);
            }
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Customer delete sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Sync delivery add (append to sheet) */
    public static function sync_delivery_add($delivery) {
        if (!self::can_auto_sync()) {
            return false;
        }
        try {
            $sheet = self::get_sheet_name('deliveries');
            $row = [
                $delivery->id ?? '',
                $delivery->delivery_reference ?? '',
                $delivery->direction_id ?? '',
                $delivery->destination_city_id ?? '',
                $delivery->dispatch_date ?? '',
                $delivery->driver_id ?? '',
                $delivery->status ?? '',
                $delivery->created_by ?? '',
                $delivery->created_at ?? current_time('mysql'),
            ];
            Courier_Google_Sheets::append_values([$row], '', 'A:I', $sheet);
            return true;
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Delivery sync failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /** Sync delivery update */
    public static function sync_delivery_update($delivery) {
        if (!self::can_auto_sync()) {
            return;
        }
        $sheet = self::get_sheet_name('deliveries');
        $row = [
            $delivery->id ?? '',
            $delivery->delivery_reference ?? '',
            $delivery->direction_id ?? '',
            $delivery->destination_city_id ?? '',
            $delivery->dispatch_date ?? '',
            $delivery->driver_id ?? '',
            $delivery->status ?? '',
            $delivery->created_by ?? '',
            $delivery->created_at ?? '',
        ];
        try {
            Courier_Google_Sheets::update_row_by_value((string) ($delivery->delivery_reference ?? ''), $sheet, $row, 1);
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Delivery update sync failed: ' . $e->getMessage());
            }
        }
    }

    /** Sync delivery delete */
    public static function sync_delivery_delete($delivery_reference) {
        if (!self::can_auto_sync()) {
            return;
        }
        try {
            Courier_Google_Sheets::delete_row_by_value(trim((string) $delivery_reference), self::get_sheet_name('deliveries'), 1);
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Delivery delete sync failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Push all rows from DB to Google Sheet (overwrites sheet data).
     * @param string $entity One of: drivers, customers, deliveries, waybills
     * @return array ['success' => bool, 'message' => string, 'count' => int]
     */
    public static function push_all($entity) {
        if (!self::can_sync()) {
            return ['success' => false, 'message' => 'Google Sheets not configured.', 'count' => 0];
        }
        global $wpdb;
        $prefix = $wpdb->prefix;

        try {
            switch ($entity) {
                case 'drivers':
                    $sheet = self::get_sheet_name('drivers');
                    $rows = $wpdb->get_results("SELECT * FROM {$prefix}kit_drivers ORDER BY id ASC");
                    Courier_Google_Sheets::clear_range($sheet, 'A2:H10000');
                    $data = [];
                    foreach ($rows as $r) {
                        $data[] = ['', $r->name ?? '', $r->phone ?? '', $r->email ?? '', $r->license_number ?? '', $r->is_active ?? 1, $r->created_at ?? '', $r->updated_at ?? ''];
                    }
                    if (!empty($data)) {
                        Courier_Google_Sheets::append_values($data, '', 'A:H', $sheet);
                    }
                    return ['success' => true, 'message' => sprintf('Pushed %d drivers to sheet.', count($data)), 'count' => count($data)];

                case 'customers':
                    $sheet = self::get_sheet_name('customers');
                    $rows = $wpdb->get_results("SELECT * FROM {$prefix}kit_customers ORDER BY id ASC");
                    Courier_Google_Sheets::clear_range($sheet, 'A2:L10000');
                    $data = [];
                    foreach ($rows as $r) {
                        $cust_id = isset($r->cust_id) && is_numeric($r->cust_id) ? $r->cust_id : rand(1000, 9999);
                        $data[] = [
                            self::sheet_val($r->id ?? ''),
                            $cust_id,
                            self::sheet_val($r->name ?? ''),
                            self::sheet_val($r->surname ?? ''),
                            self::strip_phone_special_chars($r->cell ?? ''),
                            self::strip_phone_special_chars($r->telephone ?? ''),
                            self::sheet_val($r->email_address ?? ''),
                            self::sheet_id($r->country_id ?? 0),
                            self::sheet_id($r->city_id ?? 0),
                            self::sheet_val($r->vat_number ?? ''),
                            self::sheet_val($r->address ?? ''),
                            self::sheet_val($r->company_name ?? ''),
                        ];
                    }
                    if (!empty($data)) {
                        Courier_Google_Sheets::append_values($data, '', 'A:L', $sheet, 'RAW');
                    }
                    return ['success' => true, 'message' => sprintf('Pushed %d customers to sheet.', count($data)), 'count' => count($data)];

                case 'deliveries':
                    $sheet = self::get_sheet_name('deliveries');
                    $rows = $wpdb->get_results("SELECT * FROM {$prefix}kit_deliveries ORDER BY id ASC");
                    Courier_Google_Sheets::clear_range($sheet, 'A2:I10000');
                    $data = [];
                    foreach ($rows as $r) {
                        $data[] = [$r->id ?? '', $r->delivery_reference ?? '', $r->direction_id ?? '', $r->destination_city_id ?? '', $r->dispatch_date ?? '', $r->driver_id ?? '', $r->status ?? '', $r->created_by ?? '', $r->created_at ?? ''];
                    }
                    if (!empty($data)) {
                        Courier_Google_Sheets::append_values($data, '', 'A:I', $sheet);
                    }
                    return ['success' => true, 'message' => sprintf('Pushed %d deliveries to sheet.', count($data)), 'count' => count($data)];

                case 'waybills':
                    $sheet = self::get_sheet_name('waybills');
                    $rows = $wpdb->get_results("SELECT * FROM {$prefix}kit_waybills ORDER BY id ASC");
                    $count = 0;
                    foreach ($rows as $r) {
                        $row = self::build_waybill_row($r);
                        $wb_no = (string) ($r->waybill_no ?? '');
                        $updated = Courier_Google_Sheets::update_row_by_value_skip_columns($wb_no, $sheet, $row, 11, [5], '');
                        if (!$updated) {
                            Courier_Google_Sheets::append_row_skip_columns($row, $sheet, [5], '');
                        }
                        $count++;
                    }
                    $items_sheet = self::get_sheet_name('waybill_items');
                    $items = $wpdb->get_results("SELECT * FROM {$prefix}kit_waybill_items ORDER BY id ASC");
                    Courier_Google_Sheets::clear_range($items_sheet, 'A2:H10000');
                    $items_data = [];
                    foreach ($items as $i) {
                        $items_data[] = [$i->waybillno ?? '', $i->item_name ?? '', (int) $i->quantity, (float) $i->unit_price, (float) $i->unit_mass, (float) $i->unit_volume, (float) $i->total_price, $i->client_invoice ?? ''];
                    }
                    if (!empty($items_data)) {
                        Courier_Google_Sheets::append_values($items_data, '', 'A:H', $items_sheet);
                    }
                    return ['success' => true, 'message' => sprintf('Pushed %d waybills and %d items to sheet.', $count, count($items_data)), 'count' => $count];

                default:
                    return ['success' => false, 'message' => 'Unknown entity: ' . $entity, 'count' => 0];
            }
        } catch (Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[Courier] Push sync failed: ' . $e->getMessage());
            }
            return ['success' => false, 'message' => 'Push failed: ' . $e->getMessage(), 'count' => 0];
        }
    }

    /**
     * Pull all rows from Google Sheet into DB (insert new, skip existing by key).
     * @param string $entity One of: drivers, customers, deliveries, waybills
     * @return array ['success' => bool, 'message' => string, 'count' => int]
     */
    public static function pull_all($entity) {
        if (!self::can_sync()) {
            return ['success' => false, 'message' => 'Google Sheets not configured.', 'count' => 0];
        }
        global $wpdb;
        $prefix = $wpdb->prefix;

        try {
            switch ($entity) {
                case 'drivers':
                    $sheet = self::get_sheet_name('drivers');
                    $rows = Courier_Google_Sheets::get_values('', 'A2:H5000', $sheet);
                    $count = 0;
                    foreach ($rows as $row) {
                        $name = trim((string) ($row[1] ?? ''));
                        if ($name === '') continue;
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$prefix}kit_drivers WHERE name = %s", $name));
                        if ($exists) continue;
                        $wpdb->insert($prefix . 'kit_drivers', [
                            'name' => $name,
                            'phone' => $row[2] ?? '',
                            'email' => $row[3] ?? '',
                            'license_number' => $row[4] ?? '',
                            'is_active' => !empty($row[5]) ? 1 : 0,
                        ]);
                        if ($wpdb->insert_id) $count++;
                    }
                    return ['success' => true, 'message' => sprintf('Pulled %d new drivers from sheet.', $count), 'count' => $count];

                case 'customers':
                    $sheet = self::get_sheet_name('customers');
                    $rows = Courier_Google_Sheets::get_values('', 'A2:L5000', $sheet);
                    $count = 0;
                    foreach ($rows as $row) {
                        $cust_id_raw = trim((string) ($row[1] ?? ''));
                        $cust_id = is_numeric($cust_id_raw) ? (int) $cust_id_raw : 0;
                        $name = trim((string) ($row[2] ?? ''));
                        $surname = trim((string) ($row[3] ?? ''));
                        if ($name === '' && $surname === '') continue;
                        if ($cust_id > 0) {
                            $exists = $wpdb->get_var($wpdb->prepare("SELECT cust_id FROM {$prefix}kit_customers WHERE cust_id = %d", $cust_id));
                        } else {
                            $cust_id = rand(1000, 9999);
                            $exists = $wpdb->get_var($wpdb->prepare("SELECT cust_id FROM {$prefix}kit_customers WHERE name = %s AND surname = %s", $name, $surname));
                        }
                        if ($exists) continue;
                        // Sheet columns: A=id, B=cust_id, C=name, D=surname, E=cell, F=telephone, G=email_address, H=country_id, I=city_id, J=vat_number, K=address, L=company_name
                        $wpdb->insert($prefix . 'kit_customers', [
                            'cust_id' => $cust_id,
                            'name' => $name,
                            'surname' => $surname,
                            'cell' => $row[4] ?? '',
                            'email_address' => $row[6] ?? null,
                            'address' => $row[10] ?? '',
                            'country_id' => (int) ($row[7] ?? 0),
                            'city_id' => !empty($row[8]) ? (int) $row[8] : null,
                            'company_name' => $row[11] ?? 'Individual',
                            'vat_number' => $row[9] ?? '',
                        ]);
                        if ($wpdb->insert_id) $count++;
                    }
                    return ['success' => true, 'message' => sprintf('Pulled %d new customers from sheet.', $count), 'count' => $count];

                case 'deliveries':
                    $sheet = self::get_sheet_name('deliveries');
                    $rows = Courier_Google_Sheets::get_values('', 'A2:I5000', $sheet);
                    $count = 0;
                    foreach ($rows as $row) {
                        $ref = trim((string) ($row[1] ?? ''));
                        if ($ref === '') continue;
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$prefix}kit_deliveries WHERE delivery_reference = %s", $ref));
                        if ($exists) continue;
                        $direction_id = (int) ($row[2] ?? 1);
                        $dest_city = (int) ($row[3] ?? 1);
                        if ($dest_city <= 0) $dest_city = 1;
                        $wpdb->insert($prefix . 'kit_deliveries', [
                            'delivery_reference' => $ref,
                            'direction_id' => $direction_id,
                            'destination_city_id' => $dest_city,
                            'dispatch_date' => $row[4] ?? current_time('Y-m-d'),
                            'driver_id' => !empty($row[5]) ? (int) $row[5] : null,
                            'status' => in_array($row[6] ?? '', ['scheduled', 'in_transit', 'delivered']) ? $row[6] : 'scheduled',
                            'created_by' => (int) ($row[7] ?? get_current_user_id()),
                        ]);
                        if ($wpdb->insert_id) $count++;
                    }
                    return ['success' => true, 'message' => sprintf('Pulled %d new deliveries from sheet.', $count), 'count' => $count];

                case 'waybills':
                    set_time_limit(120);
                    $sheet_name = defined('COURIER_GOOGLE_SEED_SHEET_NAME') ? COURIER_GOOGLE_SEED_SHEET_NAME : 'kit_waybills';
                    $range = defined('COURIER_GOOGLE_SEED_RANGE') ? COURIER_GOOGLE_SEED_RANGE : 'A1:AR5000';
                    // Normalize range: API requires start cell with row (e.g. A1). "AT:AR5000" is invalid; use A1:AR5000.
                    if (!preg_match('/^[A-Z]+\d+:[A-Z]+\d+$/i', $range) && preg_match('/^[A-Z]+:([A-Z]+\d+)$/i', $range, $m)) {
                        $range = 'A1:' . $m[1];
                    }
                    $rows = null;
                    try {
                        $rows = Courier_Google_Sheets::get_values('', $range, $sheet_name);
                    } catch (Throwable $e) {
                        $msg = $e->getMessage();
                        $is_range_or_sheet = (
                            stripos($msg, 'Unable to parse range') !== false
                            || stripos($msg, 'parse range') !== false
                            || (stripos($msg, 'INVALID_ARGUMENT') !== false && stripos($msg, 'range') !== false)
                            || stripos($msg, 'sheet') !== false
                        );
                        if ($is_range_or_sheet) {
                            try {
                                $available = Courier_Google_Sheets::get_sheet_names('');
                                if (!empty($available)) {
                                    $configured_ok = in_array($sheet_name, $available, true);
                                    if (!$configured_ok) {
                                        $try_sheet = $available[0];
                                        $rows = Courier_Google_Sheets::get_values('', $range, $try_sheet);
                                        if (!empty($rows) && count($rows) >= 2 && function_exists('run_google_sheet_seed')) {
                                            $result = run_google_sheet_seed($rows, false);
                                            $cnt = isset($result['stats']['waybills']) ? (int) $result['stats']['waybills'] : 0;
                                            return [
                                                'success' => !empty($result['success']),
                                                'message' => ($result['message'] ?? 'Waybills pull completed.') . ' (Used sheet "' . $try_sheet . '". Add define(\'COURIER_GOOGLE_SEED_SHEET_NAME\', \'' . $try_sheet . '\'); to wp-config.php to fix the warning.)',
                                                'count' => $cnt,
                                            ];
                                        }
                                    }
                                    $hint = 'Available sheet tabs: ' . implode(', ', array_map(function ($n) { return '"' . $n . '"'; }, $available)) . '. Set COURIER_GOOGLE_SEED_SHEET_NAME in wp-config.php to one of these.';
                                } else {
                                    $hint = 'No sheets found in the spreadsheet. Check COURIER_GOOGLE_SPREADSHEET_ID and that the sheet is shared with the service account.';
                                }
                            } catch (Throwable $inner) {
                                $hint = 'Could not list sheet names: ' . $inner->getMessage();
                            }
                            return [
                                'success' => false,
                                'message' => 'Invalid sheet range. ' . (isset($hint) ? $hint : 'Check that the sheet tab name matches COURIER_GOOGLE_SEED_SHEET_NAME (e.g. "kit_waybills") and COURIER_GOOGLE_SEED_RANGE is valid A1 notation (e.g. A1:AR5000).'),
                                'count' => 0,
                            ];
                        }
                        throw $e;
                    }
                    if (empty($rows) || count($rows) < 2) {
                        return ['success' => false, 'message' => 'Waybills pull uses seed sheet (e.g. kit_waybills). No data or invalid format.', 'count' => 0];
                    }
                    if (!function_exists('run_google_sheet_seed')) {
                        return ['success' => false, 'message' => 'Seed function not available.', 'count' => 0];
                    }
                    $result = run_google_sheet_seed($rows, false);
                    $cnt = isset($result['stats']['waybills']) ? (int) $result['stats']['waybills'] : 0;
                    return [
                        'success' => !empty($result['success']),
                        'message' => $result['message'] ?? 'Waybills pull completed.',
                        'count' => $cnt,
                    ];

                default:
                    return ['success' => false, 'message' => 'Unknown entity: ' . $entity, 'count' => 0];
            }
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $is_range_error = stripos($msg, 'Unable to parse range') !== false || stripos($msg, 'parse range') !== false || stripos($msg, 'INVALID_ARGUMENT') !== false || stripos($msg, 'sheet') !== false;
            $user_message = 'Pull failed: ' . $msg;
            if ($is_range_error) {
                try {
                    $available = Courier_Google_Sheets::get_sheet_names('');
                    $user_message = 'Invalid sheet range. Available sheet tabs: ' . (empty($available) ? 'none' : implode(', ', array_map(function ($n) { return '"' . $n . '"'; }, $available))) . '. Set COURIER_GOOGLE_SEED_SHEET_NAME in wp-config.php to match your tab, and COURIER_GOOGLE_SEED_RANGE to A1 notation (e.g. A1:AR5000).';
                } catch (Throwable $ignored) {
                    $user_message = 'Invalid sheet range. Check that the sheet tab name matches COURIER_GOOGLE_SEED_SHEET_NAME (e.g. "kit_waybills") and COURIER_GOOGLE_SEED_RANGE is valid A1 notation (e.g. A1:AR5000).';
                }
            }
            $log_line = '[' . gmdate('d-M-Y H:i:s') . ' UTC] [Courier] Pull sync failed: ' . $msg . "\n";
            if (function_exists('error_log')) {
                error_log(trim($log_line));
            }
            if (defined('WP_CONTENT_DIR')) {
                $debug_log = WP_CONTENT_DIR . '/debug.log';
                if (is_writable(WP_CONTENT_DIR) || (file_exists($debug_log) && is_writable($debug_log))) {
                    @file_put_contents($debug_log, $log_line, FILE_APPEND | LOCK_EX);
                }
            }
            return ['success' => false, 'message' => $user_message, 'count' => 0];
        }
    }
}
