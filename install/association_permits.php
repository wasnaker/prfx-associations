<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "association_permits` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `association_id`  int(11)      NOT NULL,
  `number`       varchar(100) NOT NULL,
  `groupid`      int(11)      NOT NULL DEFAULT 0,
  `publish_date` date         DEFAULT NULL,
  `expired_date` date         DEFAULT NULL,
  `status`       varchar(20)  NOT NULL DEFAULT 'active',
  `file`         varchar(191) DEFAULT NULL,
  `addedfrom`    int(11)      NOT NULL DEFAULT 0,
  `datecreated`  datetime     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_association_id` (`association_id`),
  KEY `idx_groupid` (`groupid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
