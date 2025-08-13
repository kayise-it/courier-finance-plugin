<?php

(!defined('ABSPATH')) ?? exit;

class Database
{
    public static function create_customers_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            cust_id MEDIUMINT(10) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            surname VARCHAR(255) NOT NULL,
            cell VARCHAR(20) NOT NULL,
            email_address VARCHAR(255) NOT NULL,
            country_id INT UNSIGNED NULL,
            city_id INT UNSIGNED NULL,
            vat_number VARCHAR(50),
            address TEXT NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cust_id (cust_id) -- ✅ This is the missing part!
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    public static function create_services_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_services';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255),
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function create_deliveries_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_deliveries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            delivery_reference VARCHAR(100) NOT NULL,
            direction_id INT UNSIGNED NOT NULL,
            destination_city_id INT UNSIGNED NOT NULL,
            dispatch_date DATE,
            truck_number VARCHAR(50),
            status ENUM('scheduled', 'in_transit', 'delivered') DEFAULT 'scheduled',
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (direction_id) REFERENCES {$wpdb->prefix}kit_shipping_directions(id),
            FOREIGN KEY (destination_city_id) REFERENCES {$wpdb->prefix}kit_operating_cities(id)
            ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create a system delivery row for warehoused items
        $wpdb->insert($table_name, [
            'delivery_reference' => 'warehoused',
            'direction_id' => 1,
            'destination_city_id' => 1,
            'created_by' => 0,
            'created_at' => current_time('mysql')
        ]);
    }
    
    public static function create_waybills_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybills';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        direction_id INT UNSIGNED NOT NULL,
        city_id INT UNSIGNED NULL,
        delivery_id INT UNSIGNED NOT NULL,
        customer_id MEDIUMINT(10) UNSIGNED NOT NULL,
        approval ENUM('approved','pending','cancelled', 'rejected', 'completed') DEFAULT 'pending',
        approval_userid INT UNSIGNED NULL,
        waybill_no INT UNSIGNED NOT NULL,
        product_invoice_number VARCHAR(50),
        product_invoice_amount DECIMAL(10,2),
        waybill_items_total DECIMAL(10,2),
        item_length DECIMAL(10,2),
        item_width DECIMAL(10,2),
        item_height DECIMAL(10,2),
        total_mass_kg DECIMAL(10,2),
        total_volume DECIMAL(10,2),
        mass_charge DECIMAL(10,2),
        volume_charge DECIMAL(10,2),
        charge_basis VARCHAR(20),
        vat_include VARCHAR(50),
        warehouse VARCHAR(50),
        miscellaneous LONGTEXT,
        include_sad500 TINYINT(1) DEFAULT 0,
        include_sadc TINYINT(1) DEFAULT 0,
        return_load TINYINT(1) DEFAULT 0,
        tracking_number VARCHAR(50),
        created_by INT UNSIGNED NOT NULL,
        last_updated_by INT UNSIGNED NOT NULL,
        status ENUM('warehoused', 'pending', 'quoted', 'paid', 'completed', 'invoiced', 'rejected') DEFAULT 'pending',
        status_userid INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (direction_id) REFERENCES {$wpdb->prefix}kit_shipping_directions(id),
        FOREIGN KEY (delivery_id) REFERENCES {$wpdb->prefix}kit_deliveries(id),
        FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}kit_customers(cust_id),
        FOREIGN KEY (city_id) REFERENCES {$wpdb->prefix}kit_operating_cities(id),
        INDEX (status),
        INDEX (customer_id),
        UNIQUE KEY waybill_no (waybill_no)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    public static function create_waybill_items_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybill_items';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            waybillno INT UNSIGNED NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            unit_mass DECIMAL(10,2) DEFAULT 0,
            unit_volume DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (waybillno) REFERENCES {$wpdb->prefix}kit_waybills(waybill_no) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    public static function create_invoices_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_invoices';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            waybill_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            invoice_date DATE NOT NULL,
            due_date DATE NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            vat_amount DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_updated_by INT NULL,
            last_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (waybill_id) REFERENCES {$wpdb->prefix}kit_waybills(id),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}kit_customers(id),
            INDEX (status)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    // Quotations table creation function removed
    public static function create_operating_countries_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kit_operating_countries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            country_name VARCHAR(100) NOT NULL,
            country_code VARCHAR(10) NULL,
            charge_group TINYINT(1),
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY country_code (country_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Prepopulate common operating countries
        $countries = [
            ['South Africa', 'ZA', 1, 1],
            ['Tanzania', 'TZ', 1, 2],
            ['Botswana', 'BW', 0, 2],
            ['Zimbabwe', 'ZW', 0, 2],
            ['Mozambique', 'MZ', 0, 2],
            ['Zambia', 'ZM', 0, 2],
            ['Namibia', 'NA', 0, 2],
            ['Malawi', 'MW', 0, 2],
            ['Eswatini', 'SZ', 0, 2],
            ['Lesotho', 'LS', 0, 2],
        ];

        foreach ($countries as $country) {
            list($name, $code, $activecode, $charge_group) = $country;

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE country_code = %s",
                $code
            ));

            if (!$exists) {
                $wpdb->insert($table_name, [
                    'country_name' => $name,
                    'country_code' => $code,
                    'charge_group' => $charge_group,
                    'is_active' => $activecode,
                    'created_at' => current_time('mysql'),
                ], [
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s'
                ]);
            }
        }
    }
    public static function create_operating_cities_table()
    {
        global $wpdb;

        $city_table = $wpdb->prefix . 'kit_operating_cities';
        $country_table = $wpdb->prefix . 'kit_operating_countries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $city_table (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            country_id INT UNSIGNED NOT NULL,
            city_name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (country_id) REFERENCES $country_table(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $cities = [
            'ZA' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria', 'Port Elizabeth'],
            'TZ' => ['Dar es Salaam', 'Dodoma', 'Arusha', 'Mbeya', 'Mwanza'],
            'BW' => ['Gaborone', 'Francistown', 'Maun', 'Molepolole', 'Serowe'],
            'ZW' => ['Harare', 'Bulawayo', 'Mutare', 'Gweru', 'Kwekwe'],
            'MZ' => ['Maputo', 'Matola', 'Beira', 'Nampula', 'Chimoio'],
            'ZM' => ['Lusaka', 'Kitwe', 'Ndola', 'Livingstone', 'Chingola'],
            'NA' => ['Windhoek', 'Swakopmund', 'Walvis Bay', 'Otjiwarongo', 'Rundu'],
            'MW' => ['Lilongwe', 'Blantyre', 'Mzuzu', 'Zomba', 'Kasungu'],
            'SZ' => ['Mbabane', 'Manzini', 'Lobamba', 'Siteki', 'Piggs Peak'],
            'LS' => ['Maseru', 'Teyateyaneng', 'Leribe', 'Maputsoe', 'Mohales Hoek']
        ];

        foreach ($cities as $country_code => $city_list) {
            $country_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $country_table WHERE country_code = %s",
                $country_code
            ));

            if ($country_id) {
                foreach ($city_list as $city) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $city_table WHERE city_name = %s AND country_id = %d",
                        $city,
                        $country_id
                    ));

                    if (!$exists) {
                        $wpdb->insert($city_table, [
                            'country_id' => $country_id,
                            'city_name' => $city,
                            'is_active' => 1,
                            'created_at' => current_time('mysql')
                        ], [
                            '%d',
                            '%s',
                            '%d',
                            '%s'
                        ]);
                    }
                }
            }
        }
    }
    public static function create_shipping_directions_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_shipping_directions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            origin_country_id INT UNSIGNED NOT NULL,
            destination_country_id INT UNSIGNED NOT NULL,
            description VARCHAR(255) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (origin_country_id) REFERENCES {$wpdb->prefix}kit_operating_countries(id),
            FOREIGN KEY (destination_country_id) REFERENCES {$wpdb->prefix}kit_operating_countries(id),
            UNIQUE KEY direction_pair (origin_country_id, destination_country_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default active routes and a system delivery row for warehoused items
        $wpdb->insert($table_name, [
            'origin_country_id' => 1,
            'destination_country_id' => 1,
            'description' => 'Warehoused items',
            'is_active' => 1,
            'created_at' => current_time('mysql')
        ]);
        $sa_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}kit_operating_countries WHERE country_name = 'South Africa'");
        $tanzania_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}kit_operating_countries WHERE country_name = 'Tanzania'");

        if ($sa_id && $tanzania_id) {
            $existing_route_1 = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kit_shipping_directions WHERE origin_country_id = %d AND destination_country_id = %d",
                $sa_id,
                $tanzania_id
            ));

            $existing_route_2 = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}kit_shipping_directions WHERE origin_country_id = %d AND destination_country_id = %d",
                $tanzania_id,
                $sa_id
            ));

            if (!$existing_route_1) {
                $wpdb->insert(
                    "{$wpdb->prefix}kit_shipping_directions",
                    [
                        'origin_country_id' => $sa_id,
                        'destination_country_id' => $tanzania_id,
                        'description' => 'South Africa to Tanzania',
                        'is_active' => 1
                    ]
                );
            }

            if (!$existing_route_2) {
                $wpdb->insert(
                    "{$wpdb->prefix}kit_shipping_directions",
                    [
                        'origin_country_id' => $tanzania_id,
                        'destination_country_id' => $sa_id,
                        'description' => 'Tanzania to South Africa',
                        'is_active' => 1
                    ]
                );
            }
        }
    }
    public static function create_shipping_rate_types_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_shipping_rate_types';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            type_name VARCHAR(50) NOT NULL,
            description TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY type_name (type_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default rate types
        $rate_types = ['Consolidated', 'Dedicated'];
        foreach ($rate_types as $type) {
            $wpdb->insert($table_name, [
                'type_name' => $type,
                'description' => $type . ' shipping rate type',
                'created_at' => current_time('mysql')
            ]);
        }
    }
    public static function create_shipping_rates_mass_table()
    {
        global $wpdb;
        $table_name =  $wpdb->prefix . 'kit_shipping_rates_mass';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            direction_id INT UNSIGNED NOT NULL,
            rate_type_id INT UNSIGNED NOT NULL,
            min_weight DECIMAL(10,2) NOT NULL,
            max_weight DECIMAL(10,2) NOT NULL,
            rate_per_kg DECIMAL(10,2) NOT NULL,
            effective_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (direction_id) REFERENCES {$wpdb->prefix}kit_shipping_directions(id),
            FOREIGN KEY (rate_type_id) REFERENCES {$wpdb->prefix}kit_shipping_rate_types(id),
            INDEX weight_range (min_weight, max_weight)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->query("
        INSERT INTO $table_name (direction_id, rate_type_id, min_weight, max_weight, rate_per_kg)
        VALUES 
        (1, 1, 10, 499, 40),
        (1, 1, 500, 999, 35),
        (1, 1, 1000, 2499, 30),
        (1, 1, 2500, 4999, 25),
        (1, 1, 5000, 7499, 20),
        (1, 1, 7500, 9999, 17.50),
        (1, 1, 10000, 999999.99, 15),
        (2, 1, 10, 499, 30),
        (2, 1, 500, 999, 25),
        (2, 1, 1000, 2499, 20),
        (2, 1, 2500, 4999, 15),
        (2, 1, 5000, 7499, 12.50),
        (2, 1, 7500, 9999, 10),
        (2, 1, 10000, 999999.99, 7.50)");
    }
    public static function create_shipping_rates_volume_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_shipping_rates_volume';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            direction_id INT UNSIGNED NOT NULL,
            rate_type_id INT UNSIGNED NOT NULL,
            min_volume DECIMAL(10,2) NOT NULL,
            max_volume DECIMAL(10,2) NOT NULL,
            rate_per_m3 DECIMAL(10,2) NOT NULL,
            effective_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (direction_id) REFERENCES {$wpdb->prefix}kit_shipping_directions(id),
            FOREIGN KEY (rate_type_id) REFERENCES {$wpdb->prefix}kit_shipping_rate_types(id),
            INDEX volume_range (min_volume, max_volume)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->query("
        INSERT INTO $table_name
        (`direction_id`, `rate_type_id`, `min_volume`, `max_volume`, `rate_per_m3`, `is_active`) 
        VALUES
        (1, 1, 0, 1, 7500, 1),
        (1, 1, 1, 2, 7000, 1),
        (1, 1, 2, 5, 6500, 1),
        (1, 1, 5, 10, 5500, 1),
        (1, 1, 10, 15, 5000, 1),
        (1, 1, 15, 20, 4500, 1),
        (1, 1, 20, 30, 4000, 1),
        (1, 1, 30, 9999.99, 3500, 1),
        (2, 1, 0, 1, 4000, 1),
        (2, 1, 1, 2, 3500, 1),
        (2, 1, 2, 5, 3000, 1),
        (2, 1, 5, 10, 2500, 1),
        (2, 1, 10, 15, 2000, 1),
        (2, 1, 15, 20, 2000, 1),
        (2, 1, 20, 30, 2000, 1),
        (2, 1, 30, 9999.99, 2000, 1)
        ");
    }
    public static function create_shipping_dedicated_truck_rates_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_shipping_dedicated_truck_rates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            direction_id INT UNSIGNED NOT NULL,
            truck_type VARCHAR(50) NOT NULL,
            capacity_kg DECIMAL(10,2) NOT NULL,
            flat_rate DECIMAL(10,2) NOT NULL,
            effective_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (direction_id) REFERENCES {$wpdb->prefix}kit_shipping_directions(id),
            INDEX truck_type (truck_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->query("
        INSERT INTO $table_name 
        (direction_id, truck_type, capacity_kg, flat_rate) VALUES
        (1, '8 Ton Truck', 7500, 130000),
        (1, '15 Ton Truck', 14500, 155000),
        (1, '30 Ton Truck', 28000, 220000),
        (2, '8 Ton Truck', 7500, 78000),
        (2, '15 Ton Truck', 14500, 93000),
        (2, '30 Ton Truck', 28000, 132000);");
    }
    public static function create_discounts_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_discounts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            discount_name VARCHAR(100),
            discount_type ENUM('percentage', 'fixed') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            condition_json TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function create_warehouse_tracking_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_warehouse_tracking';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            waybill_no INT UNSIGNED NOT NULL,
            waybill_id INT UNSIGNED NOT NULL,
            customer_id MEDIUMINT(10) UNSIGNED NOT NULL,
            action ENUM('warehoused', 'assigned', 'removed') DEFAULT 'warehoused',
            previous_status VARCHAR(50),
            new_status VARCHAR(50),
            assigned_delivery_id INT UNSIGNED NULL,
            notes TEXT,
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (waybill_no) REFERENCES {$wpdb->prefix}kit_waybills(waybill_no) ON DELETE CASCADE,
            FOREIGN KEY (waybill_id) REFERENCES {$wpdb->prefix}kit_waybills(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}kit_customers(cust_id),
            INDEX (waybill_no),
            INDEX (action),
            INDEX (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function delete_table($name)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $name;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    public static function activate()
    {
        self::create_customers_table();
        self::create_services_table();
        self::create_operating_countries_table();
        self::create_operating_cities_table();
        self::create_shipping_directions_table();
        self::create_shipping_rate_types_table();
        self::create_shipping_rates_mass_table();
        self::create_shipping_rates_volume_table();
        self::create_shipping_dedicated_truck_rates_table();
        self::create_deliveries_table();
        self::create_waybills_table();
        self::create_waybill_items_table();
        // Quotations table creation removed
        self::create_invoices_table();
        self::create_discounts_table();
        self::create_warehouse_tracking_table();
    }
    public static function deactivate()
    {

        // 1. Drop tables that depend on everything else (deepest children)
        self::delete_table('kit_waybill_items');
        // Quotations table deletion removed
        self::delete_table('kit_invoices');
        self::delete_table('kit_waybills');
        self::delete_table('kit_warehouse_tracking');

        // 2. Drop delivery table (depends on directions & cities)
        self::delete_table('kit_deliveries');

        // 3. Drop rate tables (depend on directions & rate types)
        self::delete_table('kit_shipping_rates_mass');
        self::delete_table('kit_shipping_rates_volume');
        self::delete_table('kit_shipping_dedicated_truck_rates');

        // 4. Drop direction and rate type tables (parents of above)
        self::delete_table('kit_shipping_rate_types');
        self::delete_table('kit_shipping_directions');

        // 5. Drop cities (they depend on countries)
        self::delete_table('kit_operating_cities');

        // 6. Now safe to drop countries
        self::delete_table('kit_operating_countries');

        // 7. Drop remaining unrelated base tables
        self::delete_table('kit_customers');
        //self::delete_table('kit_services');
        self::delete_table('kit_discounts');
        
    }
}
