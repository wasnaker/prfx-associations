<?php

defined('BASEPATH') or exit('No direct script access allowed');


require_once __DIR__ . '/helpers/email_templates_helper.php';
associations_register_email_templates();

// Add module options
add_option('association_registration_min_seconds', 8);
add_option('delete_only_on_last_association', 1);
add_option('association_due_after', 7);
add_option('allow_staff_view_associations_assigned', 1);
add_option('show_assigned_on_association', 1);
add_option('require_client_logged_in_to_view_association', 0);
add_option('show_project_on_association', 1);
add_option('associations_pipeline_limit', 1);
add_option('default_associations_pipeline_sort', 1);
add_option('default_associations_pipeline_sort_type', 'asc');
add_option('association_accept_identity_confirmation', 1);
add_option('association_qrcode_size', '160');
add_option('association_send_telegram_message', 0);
add_option('association_auto_convert_to_quotation_on_client_accept', 0);
add_option('show_pdf_signature_association', 0);

// Role-based capabilities — checked by associations_staff_can_filter via get_option().
// Key format: association_{capability}_role_{role_id}  (lookup by role name, not hardcoded ID)
$_association_role_caps = [
    'Association' => ['view', 'view_own', 'create', 'edit', 'edit_own', 'mark_as'],
    'Surveyor' => ['view', 'view_own', 'mark_as', 'convert_to_quotation'],
];
foreach ($_association_role_caps as $_association_role_name => $_association_caps) {
    $_association_role = $CI->db->get_where(db_prefix() . 'roles', ['name' => $_association_role_name])->row();
    if (!$_association_role) { continue; }
    $_association_rid = (int) $_association_role->roleid;
    foreach ($_association_caps as $_association_cap) {
        add_option('association_' . $_association_cap . '_role_' . $_association_rid, '1');
    }
}
unset($_association_role_caps, $_association_role_name, $_association_caps, $_association_role, $_association_rid, $_association_cap);

// Add association_emails column to contacts table if not exists
if (!$CI->db->field_exists('association_emails', db_prefix() . 'contacts')) {
    $CI->db->query('ALTER TABLE ' . db_prefix() . 'contacts ADD COLUMN `association_emails` tinyint(1) NOT NULL DEFAULT 1 AFTER `estimate_emails`');
}


// ---------------------------------------------------------------------------
// start from previouse installation:
// ---------------------------------------------------------------------------

$CI = &get_instance();

// ---------------------------------------------------------------------------
// Add client_type column to tblclients
// Distinguishes record ownership: 'association', 'association', etc.
// Default 'association' so all existing records remain as associations.
// ---------------------------------------------------------------------------
if (!$CI->db->field_exists('client_type', db_prefix() . 'clients')) {
    $CI->db->query(
        'ALTER TABLE `' . db_prefix() . 'clients`
         ADD COLUMN `client_type` VARCHAR(30) NOT NULL DEFAULT \'association\'
         AFTER `active`'
    );
}

// ---------------------------------------------------------------------------
// Association self-registration support
// ---------------------------------------------------------------------------

// Add registration_status to tblstaff
if (!$CI->db->field_exists('registration_status', db_prefix() . 'staff')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "staff` ADD COLUMN `registration_status` ENUM('pending','user_activated','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_entity_owner`");
} else {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "staff` MODIFY `registration_status` ENUM('pending','user_activated','approved','rejected') NOT NULL DEFAULT 'approved'");
}

// Create Association role
$existing_association_role = $CI->db->get_where(db_prefix() . 'roles', ['name' => 'Association'])->row();
if (!$existing_association_role) {
    $CI->db->insert(db_prefix() . 'roles', ['name' => 'Association', 'permissions' => '']);
}

// Assign edit_own permission to Association role
$association_role = $CI->db->get_where(db_prefix() . 'roles', ['name' => 'Association'])->row();
if ($association_role) {
    $perms = ($association_role->permissions && $association_role->permissions !== '')
        ? unserialize($association_role->permissions) ?: []
        : [];
    $changed = false;
    if (!isset($perms['associations']['view_own'])) { $perms['associations']['view_own'] = '1'; $changed = true; }
    if (!isset($perms['associations']['edit_own'])) { $perms['associations']['edit_own'] = '1'; $changed = true; }
    if ($changed) {
        $CI->db->where('roleid', $association_role->roleid)
               ->update(db_prefix() . 'roles', ['permissions' => serialize($perms)]);
    }
}

// Create Association Admin role
$existing = $CI->db->get_where(db_prefix() . 'roles', ['name' => 'Association Admin'])->row();
if (!$existing) {
    $CI->db->insert(db_prefix() . 'roles', ['name' => 'Association Admin', 'permissions' => '']);
}
$r = $CI->db->get_where(db_prefix() . 'roles', ['name' => 'Association Admin'])->row();
if ($r) {
    $perms   = ($r->permissions && $r->permissions !== '') ? unserialize($r->permissions) ?: [] : [];
    $changed = false;
    foreach (['view_own', 'edit_own'] as $cap) {
        if (!isset($perms['associations'][$cap])) { $perms['associations'][$cap] = '1'; $changed = true; }
    }
    if (!isset($perms['equipments']['view']))     { $perms['equipments']['view']     = '1'; $changed = true; }
    if (!isset($perms['equipments']['edit_own'])) { $perms['equipments']['edit_own'] = '1'; $changed = true; }
    if ($changed) {
        $CI->db->where('roleid', $r->roleid)
               ->update(db_prefix() . 'roles', ['permissions' => serialize($perms)]);
    }
}


// Default settings
if (!$CI->db->field_exists('logo_light', db_prefix() . 'clients')) {
    $CI->db->query('ALTER TABLE ' . db_prefix() . 'clients ADD COLUMN `logo_light` varchar(100) NULL DEFAULT NULL');
}
if (!$CI->db->field_exists('logo_dark', db_prefix() . 'clients')) {
    $CI->db->query('ALTER TABLE ' . db_prefix() . 'clients ADD COLUMN `logo_dark` varchar(100) NULL DEFAULT NULL');
}

// ---------------------------------------------------------------------------
// end previouse installation:
// ---------------------------------------------------------------------------
