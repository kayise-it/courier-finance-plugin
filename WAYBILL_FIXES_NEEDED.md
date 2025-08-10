# WAYBILL CREATION - CRITICAL FIXES NEEDED

## 🚨 IMMEDIATE FIXES REQUIRED

### 1. Fix Variable Name Conflict (Line 887-893)
```php
// CURRENT BROKEN CODE:
$customer_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 0;
if (empty($cust_id) || $customer_select === 'new') { // $cust_id undefined!

// FIX:
$cust_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 0;
if (empty($cust_id) || $customer_select === 'new') {
```

### 2. Add Proper Field Validation
```php
// ADD to process_form() before save_waybill():
$validation_errors = [];

// Required fields
if (empty($_POST['waybill_no'])) {
    $validation_errors[] = 'Waybill number is required';
}

if (empty($_POST['cust_id']) && empty($_POST['customer_name'])) {
    $validation_errors[] = 'Customer selection or customer details required';
}

if (empty($_POST['delivery_id'])) {
    $validation_errors[] = 'Delivery selection is required';
}

if (!empty($validation_errors)) {
    if ($is_ajax) {
        wp_send_json_error(['message' => implode(', ', $validation_errors)]);
    } else {
        wp_die('Validation failed: ' . implode(', ', $validation_errors));
    }
}
```

### 3. Fix Charge Calculation Logic (Line 871)
```php
// CURRENT BROKEN:
if (isset($_POST['enable_price_manipulator']) && isset($_POST['new_mass_rate']) && isset($_POST['new_mass_rate']) < 0) {

// FIX:
if (isset($_POST['enable_price_manipulator']) && isset($_POST['new_mass_rate']) && floatval($_POST['new_mass_rate']) > 0) {
    $mass_charge = $_POST['mass_charge'] = floatval($_POST['new_mass_rate']);
}
```

### 4. Add Database Transaction Support
```php
// WRAP save_waybill() in transaction:
$wpdb->query('START TRANSACTION');

try {
    // Insert waybill
    $inserted = $wpdb->insert($waybills_table, $waybill_data);
    if (!$inserted) {
        throw new Exception('Failed to insert waybill');
    }
    
    $waybill_id = $wpdb->insert_id;
    
    // Save items if provided
    if (!empty($data['custom_items'])) {
        $items_saved = self::save_waybill_items($data['custom_items'], $waybill_no, $waybill_id, $vat);
        if (!$items_saved) {
            throw new Exception('Failed to save waybill items');
        }
    }
    
    $wpdb->query('COMMIT');
    return ['id' => $waybill_id, 'waybill_no' => $waybill_no, 'amount' => $waybillTotal];
    
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    return new WP_Error('save_failed', $e->getMessage());
}
```

### 5. Add Proper Error Logging
```php
// ADD error logging throughout:
if (!$inserted) {
    error_log('Waybill save failed: ' . $wpdb->last_error);
    error_log('Query: ' . $wpdb->last_query);
    return new WP_Error('db_error', 'Could not save waybill: ' . $wpdb->last_error);
}
```

### 6. Fix Session/AJAX Inconsistency
```php
// ADD to process_form() for AJAX requests:
if ($is_ajax && isset($_POST['session_data'])) {
    // Restore session data for AJAX requests
    session_start();
    $_SESSION['waybill_form_data'] = json_decode(stripslashes($_POST['session_data']), true);
}
```

## 🔍 TESTING GAPS TO ADDRESS

1. **Customer Creation Flow**: Test when cust_id = 0 and new customer creation
2. **Warehoused Delivery**: Test warehoused checkbox logic
3. **Charge Calculations**: Test mass vs volume selection logic
4. **Error Scenarios**: Test database failures, missing data
5. **AJAX vs Regular Form**: Test both submission methods
6. **Nonce Validation**: Test security in different scenarios

## ⚠️ SECURITY CONCERNS

1. **SQL Injection**: Some dynamic queries need preparation
2. **Permission Bypass**: Check user capabilities more thoroughly
3. **CSRF**: Ensure consistent nonce validation
4. **Data Sanitization**: Sanitize all input before database operations

## 📊 PERFORMANCE ISSUES

1. **Multiple DB Queries**: Consolidate related queries
2. **Large POST Data**: Optimize form data handling
3. **Session Storage**: Consider database sessions for large forms
4. **Query Optimization**: Add proper indexes for frequently queried fields
