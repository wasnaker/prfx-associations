<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "association_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `association_id` int(11) NOT NULL,
  `association_equipment_id` int(11) DEFAULT NULL,
  `association_equipment_name` varchar(255) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimum_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) DEFAULT NULL,
  `long_description` longtext DEFAULT NULL,
  `item_order` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `association_id` (`association_id`),
  KEY `association_equipment_id` (`association_equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
