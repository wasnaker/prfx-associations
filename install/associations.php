<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Main associations table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "associations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `datesend` datetime DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `clientid` int(11) NOT NULL,
  `deleted_association_name` varchar(200) DEFAULT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `number` int(11) NOT NULL,
  `prefix` varchar(50) NOT NULL,
  `number_format` tinyint(1) NOT NULL DEFAULT 1,
  `formatted_number` varchar(100) DEFAULT NULL,
  `hash` varchar(40) NOT NULL,
  `date` date NOT NULL,
  `expirydate` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `adjustment` decimal(15,2) DEFAULT NULL,
  `addedfrom` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `clientnote` mediumtext DEFAULT NULL,
  `adminnote` mediumtext DEFAULT NULL,
  `currency` int(11) NOT NULL DEFAULT 1,
  `discount_percent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(30) NOT NULL DEFAULT '',
  `quotationid` int(11) DEFAULT NULL,
  `quoted_date` date DEFAULT NULL,
  `terms` mediumtext DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `sale_agent` int(11) NOT NULL DEFAULT 0,
  `billing_street` varchar(200) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_zip` varchar(100) DEFAULT NULL,
  `billing_country` int(11) DEFAULT NULL,
  `shipping_street` varchar(200) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(100) DEFAULT NULL,
  `shipping_country` int(11) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `show_shipping_on_association` tinyint(1) NOT NULL DEFAULT 1,
  `show_quantity_as` int(11) NOT NULL DEFAULT 1,
  `pipeline_order` int(11) NOT NULL DEFAULT 0,
  `is_expiry_notified` tinyint(1) NOT NULL DEFAULT 0,
  `acceptance_firstname` varchar(50) DEFAULT NULL,
  `acceptance_lastname` varchar(50) DEFAULT NULL,
  `acceptance_email` varchar(200) DEFAULT NULL,
  `acceptance_date` datetime DEFAULT NULL,
  `acceptance_ip` varchar(40) DEFAULT NULL,
  `signature` varchar(40) DEFAULT NULL,
  `short_link` varchar(100) DEFAULT NULL,
  `cancel_overdue_reminders` tinyint(1) NOT NULL DEFAULT 0,
  `surveyor_id` int(11) NOT NULL DEFAULT 0,
  `requestor_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `clientid` (`clientid`),
  KEY `project_id` (`project_id`),
  KEY `sale_agent` (`sale_agent`),
  KEY `status` (`status`),
  KEY `formatted_number` (`formatted_number`),
  KEY `surveyor_id` (`surveyor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("INSERT IGNORE INTO `" . db_prefix() . "emailtemplates`
    (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`)
VALUES

('associations', 'surveyor-registered-to-association', 'english',
    'Surveyor Registered to Association',
    'New Membership Registration: {surveyor_company}',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>A new membership registration has been submitted by <strong>{surveyor_company}</strong>.</p><p>Please review and approve or reject the registration.</p><p>Kind Regards,<br>{email_signature}</p>',
    '{companyname} | CRM', '', 0, 1, 1),

('associations', 'association-approved-surveyor-registration', 'english',
    'Association Approved Surveyor Registration',
    'Your Membership Has Been Approved',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Your membership registration with <strong>{association_company}</strong> has been approved. You are now an active member.</p><p>Kind Regards,<br>{email_signature}</p>',
    '{companyname} | CRM', '', 0, 1, 2),

('associations', 'association-rejected-surveyor-registration', 'english',
    'Association Rejected Surveyor Registration',
    'Your Membership Registration Has Been Rejected',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Unfortunately, your membership registration with <strong>{association_company}</strong> has been rejected.</p><p><strong>Reason:</strong><br>{rejection_reason}</p><p>Kind Regards,<br>{email_signature}</p>',
    '{companyname} | CRM', '', 0, 1, 3),

('associations', 'association-surveyor-set-pending', 'english',
    'Association Set Surveyor Membership to Pending',
    'Your Membership Has Been Set to Pending Review',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Your membership with <strong>{association_company}</strong> has been set back to pending review. You will be notified once a decision has been made.</p><p>Kind Regards,<br>{email_signature}</p>',
    '{companyname} | CRM', '', 0, 1, 4)
");

