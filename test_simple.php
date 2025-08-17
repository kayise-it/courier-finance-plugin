<?php
// Simple test to check database and data
echo "Testing database connection...\n";

// Try to connect to database
try {
    $host = '127.0.0.1';
    $port = '8889'; // MAMP default port
    $dbname = '08600';
    $username = 'root';
    $password = 'root';
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Check countries table
    $stmt = $pdo->query("SELECT COUNT(*) FROM wp_kit_operating_countries");
    $countries_count = $stmt->fetchColumn();
    echo "Countries found: $countries_count\n";
    
    // Check cities table
    $stmt = $pdo->query("SELECT COUNT(*) FROM wp_kit_operating_cities");
    $cities_count = $stmt->fetchColumn();
    echo "Cities found: $cities_count\n";
    
    // Show sample countries
    $stmt = $pdo->query("SELECT * FROM wp_kit_operating_countries LIMIT 3");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample countries:\n";
    foreach ($countries as $country) {
        echo "- ID: {$country['id']}, Name: {$country['country_name']}\n";
    }
    
    // Show sample cities
    $stmt = $pdo->query("SELECT * FROM wp_kit_operating_cities LIMIT 5");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample cities:\n";
    foreach ($cities as $city) {
        echo "- ID: {$city['id']}, Name: {$city['city_name']}, Country ID: {$city['country_id']}\n";
    }
    
    // Test cities for country ID 1
    $stmt = $pdo->prepare("SELECT * FROM wp_kit_operating_cities WHERE country_id = ?");
    $stmt->execute([1]);
    $cities_for_country_1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Cities for country ID 1: " . count($cities_for_country_1) . "\n";
    foreach ($cities_for_country_1 as $city) {
        echo "- {$city['city_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
