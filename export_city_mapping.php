<?php
/**
 * Export city mapping from database for use in Python seed generation script
 * Run this via WordPress admin or CLI to generate city_mapping.json
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../wp-load.php');
}

global $wpdb;

$cities_table = $wpdb->prefix . 'kit_operating_cities';
$countries_table = $wpdb->prefix . 'kit_operating_countries';

// Query all cities with their country information
$cities = $wpdb->get_results("
    SELECT 
        c.id as city_id,
        c.city_name,
        c.country_id,
        co.country_name,
        co.country_code
    FROM {$cities_table} c
    LEFT JOIN {$countries_table} co ON c.country_id = co.id
    ORDER BY co.id, c.id
");

$mapping = [];
foreach ($cities as $city) {
    // Create multiple keys for flexible matching
    $city_name = trim($city->city_name);
    
    // Exact match
    $mapping[$city_name] = [
        'city_id' => (int)$city->city_id,
        'country_id' => (int)$city->country_id,
        'country_name' => $city->country_name,
        'country_code' => $city->country_code
    ];
    
    // Case-insensitive match
    $mapping[strtolower($city_name)] = [
        'city_id' => (int)$city->city_id,
        'country_id' => (int)$city->country_id,
        'country_name' => $city->country_name,
        'country_code' => $city->country_code
    ];
    
    // Trimmed match
    $mapping[trim($city_name)] = [
        'city_id' => (int)$city->city_id,
        'country_id' => (int)$city->country_id,
        'country_name' => $city->country_name,
        'country_code' => $city->country_code
    ];
}

// Output as JSON
$output_file = __DIR__ . '/assets/city_mapping.json';
file_put_contents($output_file, json_encode($mapping, JSON_PRETTY_PRINT));

echo "City mapping exported to: " . $output_file . "\n";
echo "Found " . count($cities) . " cities in database.\n";
echo "\nFirst 10 cities:\n";
foreach (array_slice($cities, 0, 10) as $city) {
    echo sprintf("  %s (ID: %d) - %s (%s)\n", 
        $city->city_name, 
        $city->city_id, 
        $city->country_name,
        $city->country_code
    );
}


