-- SQL script to create 10 warehouse waybills
-- Run this in your WordPress database

-- First, let's check if we have customers and deliveries
SELECT 'Customers count:' as info, COUNT(*) as count FROM wp_kit_customers;
SELECT 'Deliveries count:' as info, COUNT(*) as count FROM wp_kit_deliveries;

-- Get sample customer and delivery IDs
SET @customer_id = (SELECT cust_id FROM wp_kit_customers LIMIT 1);
SET @delivery_id = (SELECT id FROM wp_kit_deliveries LIMIT 1);

-- If no customers or deliveries exist, we'll use default values
SET @customer_id = IFNULL(@customer_id, 1);
SET @delivery_id = IFNULL(@delivery_id, 1);

-- Create 10 warehouse waybills
INSERT INTO wp_kit_waybills (
    direction_id, delivery_id, customer_id, city_id, waybill_no, 
    product_invoice_number, product_invoice_amount, waybill_items_total,
    item_length, item_width, item_height, total_mass_kg, total_volume,
    mass_charge, volume_charge, charge_basis, miscellaneous,
    include_sad500, include_sadc, vat_include, tracking_number,
    created_by, last_updated_by, status, warehouse, approval,
    created_at, last_updated_at
) VALUES
(1, @delivery_id, @customer_id, 1, 1001, 'INV-2024-001', 1250.00, 1000.00, 50.0, 30.0, 20.0, 15.5, 0.030, 155.00, 120.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-ABC12345', 1, 1, 'warehoused', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1002, 'INV-2024-002', 890.50, 712.40, 40.0, 25.0, 15.0, 8.2, 0.015, 82.00, 75.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-DEF67890', 1, 1, 'pending', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1003, 'INV-2024-003', 2100.75, 1680.60, 60.0, 40.0, 30.0, 25.8, 0.072, 258.00, 180.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-GHI11111', 1, 1, 'warehoused', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1004, 'INV-2024-004', 675.25, 540.20, 35.0, 20.0, 12.0, 6.5, 0.008, 65.00, 42.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-JKL22222', 1, 1, 'created', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1005, 'INV-2024-005', 1850.00, 1480.00, 55.0, 35.0, 25.0, 18.3, 0.048, 183.00, 120.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-MNO33333', 1, 1, 'warehoused', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1006, 'INV-2024-006', 950.80, 760.64, 45.0, 28.0, 18.0, 10.1, 0.023, 101.00, 58.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-PQR44444', 1, 1, 'pending', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1007, 'INV-2024-007', 3200.50, 2560.40, 70.0, 45.0, 35.0, 32.7, 0.110, 327.00, 275.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-STU55555', 1, 1, 'warehoused', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1008, 'INV-2024-008', 750.00, 600.00, 38.0, 22.0, 14.0, 7.8, 0.012, 78.00, 30.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-VWX66666', 1, 1, 'created', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1009, 'INV-2024-009', 1450.25, 1160.20, 52.0, 32.0, 22.0, 14.2, 0.037, 142.00, 93.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-YZA77777', 1, 1, 'warehoused', 1, 'approved', NOW(), NOW()),
(1, @delivery_id, @customer_id, 1, 1010, 'INV-2024-010', 1100.75, 880.60, 48.0, 30.0, 19.0, 11.5, 0.027, 115.00, 68.00, 'mass', 'Sample warehouse waybill', 0, 0, 'VAT', 'TRK-BCD88888', 1, 1, 'pending', 1, 'approved', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    product_invoice_amount = VALUES(product_invoice_amount),
    status = VALUES(status),
    warehouse = VALUES(warehouse),
    last_updated_at = NOW();

-- Show the results
SELECT 'Created warehouse waybills:' as info, COUNT(*) as count FROM wp_kit_waybills WHERE warehouse = 1;

-- Show the new waybills
SELECT 
    waybill_no,
    product_invoice_number,
    product_invoice_amount,
    status,
    warehouse,
    created_at
FROM wp_kit_waybills 
WHERE warehouse = 1 
ORDER BY waybill_no;
