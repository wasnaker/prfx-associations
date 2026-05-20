<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "association_equipment` (
  `id`               int(11)       NOT NULL AUTO_INCREMENT,
  `client_id`        int(11)       NOT NULL,
  `item_id`          int(11)       NOT NULL,
  `unit_code`        varchar(100)  NOT NULL DEFAULT '',
  `serial_number`    varchar(100)  DEFAULT NULL,
  `location`         varchar(191)  NOT NULL DEFAULT '',
  `capacity`         varchar(100)  DEFAULT NULL,
  `brand`            varchar(100)  DEFAULT NULL,
  `model_type`       varchar(100)  DEFAULT NULL,
  `procurement_year` year          DEFAULT NULL,
  `cert_expired_date` date         DEFAULT NULL,
  `notes`            text          DEFAULT NULL,
  `addedfrom`        int(11)       NOT NULL DEFAULT 0,
  `datecreated`      datetime      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unit_code` (`client_id`, `unit_code`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
