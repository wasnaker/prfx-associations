<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "surveyors_associations` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `surveyor_id`     int(11)      NOT NULL,
  `association_id`  int(11)      NOT NULL,
  `status`          varchar(30)  NOT NULL DEFAULT 'pending',
  `date_registered` datetime     NOT NULL,
  `date_approved`   datetime     DEFAULT NULL,
  `reject_reason`   text         DEFAULT NULL,
  `registered_by`   int(11)      NOT NULL DEFAULT 0,
  `approved_by`     int(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_surveyor_association` (`surveyor_id`, `association_id`),
  KEY `surveyor_id` (`surveyor_id`),
  KEY `association_id` (`association_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
