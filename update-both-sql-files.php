<?php
/**
 * Update latestSQL.sql and newSQL.sql with city_id from CSV
 */
require_once('../../../wp-load.php');
global $wpdb;

// Read CSV
$csv = fopen(__DIR__ . '/waybills_csv/08600 Waybills - Waybills.csv', 'r');
$header = fgetcsv($csv);
$wb_idx = array_search('WAYBILL #', $header);
$city_idx = array_search('CITY', $header);

$wb_to_city = [];
while ($row = fgetcsv($csv)) {
    if (isset($row[$wb_idx]) && isset($row[$city_idx])) {
        $wb_to_city[trim($row[$wb_idx])] = trim($row[$city_idx]);
    }
}
fclose($csv);

// Get city mappings (case-insensitive)
$cities = $wpdb->get_results("SELECT id, city_name FROM {$wpdb->prefix}kit_operating_cities", ARRAY_A);
$city_map = [];
foreach ($cities as $c) {
    $city_map[strtolower($c['city_name'])] = (int)$c['id'];
}

// Function to update SQL file
function update_sql_file($file_path, $wb_to_city, $city_map) {
    if (!file_exists($file_path)) {
        echo "File not found: $file_path\n";
        return 0;
    }
    
    $sql = file_get_contents($file_path);
    
    // Add city_id to column list if missing
    if (strpos($sql, '(description,direction_id,city_id') === false) {
        $sql = str_replace(
            '(description,direction_id,delivery_id',
            '(description,direction_id,city_id,delivery_id',
            $sql
        );
    }
    
    // Update each INSERT statement
    $lines = explode("\n", $sql);
    $updated = [];
    $count = 0;
    
    foreach ($lines as $line) {
        if (strpos($line, 'INSERT INTO wp_kit_waybills') !== false && strpos($line, 'SELECT') !== false) {
            // Extract waybill_no from SELECT values
            if (preg_match('/SELECT.*?(\d+),0,\'INV/', $line, $m) || preg_match('/SELECT.*?(\d+),\d+,\'INV/', $line, $m)) {
                $wb_no = $m[1];
                $city_name = $wb_to_city[$wb_no] ?? '';
                $city_id = 9; // default
                
                if ($city_name) {
                    $key = strtolower($city_name);
                    if (isset($city_map[$key])) {
                        $city_id = $city_map[$key];
                    }
                }
                
                // Insert city_id value after direction_id
                if (preg_match("/(SELECT '[^']+',)(\d+),(\d+,)/", $line, $select_match)) {
                    $line = preg_replace(
                        "/(SELECT '[^']+',)(\d+),(\d+,)/",
                        '$1$2,' . $city_id . ',$3',
                        $line,
                        1
                    );
                    $count++;
                }
            }
        }
        $updated[] = $line;
    }
    
    file_put_contents($file_path, implode("\n", $updated));
    return $count;
}

// Update both files
$count1 = update_sql_file(__DIR__ . '/latestSQL.sql', $wb_to_city, $city_map);
$count2 = update_sql_file(__DIR__ . '/newSQL.sql', $wb_to_city, $city_map);

echo "Updated latestSQL.sql: $count1 waybills\n";
echo "Updated newSQL.sql: $count2 waybills\n";

