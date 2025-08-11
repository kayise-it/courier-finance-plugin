-- Create sample waybills for testing assignment feature
-- Run this in your WordPress database

-- First, let's create some sample waybills in the warehouse
INSERT INTO wp_kit_waybills (
    waybill_no, 
    customer_id, 
    product_invoice_amount, 
    total_mass_kg, 
    warehouse, 
    status, 
    delivery_id,
    created_at,
    created_by
) VALUES 
('WB-2024-001', 1, 1250.00, 15.5, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-002', 1, 890.50, 8.2, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-003', 2, 2100.75, 22.1, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-004', 2, 675.25, 5.8, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-005', 3, 1850.00, 18.9, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-006', 3, 950.00, 12.3, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-007', 4, 3200.50, 28.7, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-008', 4, 1450.75, 16.4, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-009', 5, 2750.00, 25.6, 1, 'warehoused', 0, NOW(), 1),
('WB-2024-010', 5, 1100.25, 11.2, 1, 'warehoused', 0, NOW(), 1)
ON DUPLICATE KEY UPDATE waybill_no = waybill_no;

-- Create sample deliveries if they don't exist
INSERT INTO wp_kit_deliveries (
    delivery_reference,
    direction_id,
    destination_city_id,
    dispatch_date,
    truck_number,
    status,
    created_by,
    created_at
) VALUES 
('TRK-2024-001', 1, 1, DATE_ADD(NOW(), INTERVAL 2 DAY), 'GP123456', 'scheduled', 1, NOW()),
('TRK-2024-002', 1, 1, DATE_ADD(NOW(), INTERVAL 3 DAY), 'GP789012', 'scheduled', 1, NOW()),
('TRK-2024-003', 1, 1, DATE_ADD(NOW(), INTERVAL 5 DAY), 'GP345678', 'scheduled', 1, NOW())
ON DUPLICATE KEY UPDATE delivery_reference = delivery_reference;

-- Show what we created
SELECT 'Sample waybills created:' as info;
SELECT waybill_no, product_invoice_amount, total_mass_kg, status FROM wp_kit_waybills WHERE warehouse = 1 AND (delivery_id = 0 OR delivery_id IS NULL) ORDER BY created_at DESC LIMIT 10;

SELECT 'Sample deliveries created:' as info;
SELECT delivery_reference, dispatch_date, truck_number, status FROM wp_kit_deliveries WHERE status = 'scheduled' ORDER BY dispatch_date ASC LIMIT 5;
