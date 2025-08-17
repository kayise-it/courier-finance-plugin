<?php
// Test script to simulate country/city selection functionality
require_once 'includes/deliveries/deliveries-functions.php';

echo "<h2>Testing Country/City Selection Functionality</h2>";

// Test 1: Get all countries
echo "<h3>Test 1: Getting All Countries</h3>";
$countries = KIT_Deliveries::getAllCountries();
echo "Found " . count($countries) . " countries:<br>";
foreach ($countries as $country) {
    echo "- ID: {$country->id}, Name: {$country->country_name}, Active: {$country->is_active}<br>";
}

// Test 2: Get cities for a specific country (assuming country ID 1 exists)
echo "<h3>Test 2: Getting Cities for Country ID 1</h3>";
$cities = KIT_Deliveries::get_Cities_forCountry(1);
if ($cities && is_array($cities)) {
    echo "Found " . count($cities) . " cities for country ID 1:<br>";
    foreach ($cities as $city) {
        echo "- ID: {$city->id}, Name: {$city->city_name}, Country ID: {$city->country_id}<br>";
    }
} else {
    echo "No cities found for country ID 1<br>";
}

// Test 3: Test with a different country ID
echo "<h3>Test 3: Getting Cities for Country ID 2</h3>";
$cities2 = KIT_Deliveries::get_Cities_forCountry(2);
if ($cities2 && is_array($cities2)) {
    echo "Found " . count($cities2) . " cities for country ID 2:<br>";
    foreach ($cities2 as $city) {
        echo "- ID: {$city->id}, Name: {$city->city_name}, Country ID: {$city->country_id}<br>";
    }
} else {
    echo "No cities found for country ID 2<br>";
}

// Test 4: Check database tables
echo "<h3>Test 4: Database Table Check</h3>";
global $wpdb;

// Check countries table
$countries_table = $wpdb->prefix . 'kit_operating_countries';
$countries_count = $wpdb->get_var("SELECT COUNT(*) FROM $countries_table");
echo "Countries table ($countries_table): $countries_count records<br>";

// Check cities table
$cities_table = $wpdb->prefix . 'kit_operating_cities';
$cities_count = $wpdb->get_var("SELECT COUNT(*) FROM $cities_table");
echo "Cities table ($cities_table): $cities_count records<br>";

// Show sample data
echo "<h3>Sample Data:</h3>";
$sample_countries = $wpdb->get_results("SELECT * FROM $countries_table LIMIT 3");
echo "Sample countries:<br>";
foreach ($sample_countries as $country) {
    echo "- ID: {$country->id}, Name: {$country->country_name}<br>";
}

$sample_cities = $wpdb->get_results("SELECT * FROM $cities_table LIMIT 5");
echo "Sample cities:<br>";
foreach ($sample_cities as $city) {
    echo "- ID: {$city->id}, Name: {$city->city_name}, Country ID: {$city->country_id}<br>";
}

echo "<h3>Test Complete</h3>";
?>
