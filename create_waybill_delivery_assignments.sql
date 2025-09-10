-- Create waybill delivery assignments table
CREATE TABLE IF NOT EXISTS `wp_kit_waybill_delivery_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `waybill_id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) DEFAULT 'assigned',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_waybill_delivery` (`waybill_id`, `delivery_id`),
  KEY `waybill_id` (`waybill_id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `assigned_by` (`assigned_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

