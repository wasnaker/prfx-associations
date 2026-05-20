<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(__DIR__ . '/install/associations.php');
require_once(__DIR__ . '/install/association_equipment.php');
require_once(__DIR__ . '/install/association_doc_equipment.php');
require_once(__DIR__ . '/install/association_items.php');
require_once(__DIR__ . '/install/association_activity.php');
require_once(__DIR__ . '/install/association_permits.php');
require_once(__DIR__ . '/install/surveyors_associations.php');

// All slugs use unique association- prefix — safe to use create_email_template()
// since total_rows() won't find them in core type='client' templates.
create_email_template(
    'New Association Registration Pending: {client_company}',
    '<p>Hi,</p><p>A new association registration is awaiting your approval.</p><p><strong>Company:</strong> {client_company}</p><p>Please log in to the admin panel to review and approve this registration.</p><p>{email_signature}</p>',
    'associations', 'New Association Registration (Sent to Admin)', 'new-association-registered-to-admin'
);
create_email_template(
    'Your Registration Has Been Approved',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Congratulations! Your registration with <strong>{companyname}</strong> has been approved. You can now log in to your account.</p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Association Registration Approved', 'association-registration-confirmed'
);
create_email_template(
    'Your registration has been received - {companyname}',
    'Dear {contact_firstname} {contact_lastname},<br /><br />Thank you for registering on the <strong>{companyname}</strong> portal.<br /><br />Your registration is currently <strong>pending approval</strong>. We will notify you once your account has been reviewed.<br /><br />Kind Regards,<br />{email_signature}<br /><br />(This is an automated email, so please don\'t reply to this email address)',
    'associations', 'Association Registration Received (Welcome Email)', 'new-association-created'
);
create_email_template(
    'Set Your Password - {companyname}',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Please click the link below to set your password:</p><p><a href="{set_password_url}">{set_password_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Set Password', 'association-set-password'
);
create_email_template(
    'Your Registration Was Not Approved',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Unfortunately your registration for <strong>{client_company}</strong> was not approved.</p><p>Please contact us if you have any questions.</p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Association Registration Rejected', 'association-registration-rejected'
);
create_email_template(
    'Reset Your Password - {companyname}',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>We received a request to reset your password. Click the link below to reset it:</p><p><a href="{reset_password_url}">{reset_password_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Forgot Password', 'association-forgot-password'
);
create_email_template(
    'Your Password Has Been Reset',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Your password has been successfully reset. You can now log in with your new password.</p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Password Reset Confirmation', 'association-password-reseted'
);
create_email_template(
    'Verify Your Email Address - {companyname}',
    '<p>Dear {contact_firstname} {contact_lastname},</p><p>Please verify your email address by clicking the link below:</p><p><a href="{association_verification_url}">{association_verification_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
    'associations', 'Email Verification', 'association-verification-email'
);
create_email_template(
    'Association Profile File Uploaded: {client_company}',
    '<p>Hi,</p><p>A new file has been uploaded to the association profile of <strong>{client_company}</strong>.</p><p>View files: <a href="{association_profile_files_admin_link}">{association_profile_files_admin_link}</a></p><p>{email_signature}</p>',
    'associations', 'Association Profile File Uploaded (Sent to Staff)', 'new-association-profile-file-uploaded-to-staff'
);

create_email_template(
    'New Membership Registration — {surveyor_company}',
    '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>A new membership registration request has been submitted to <strong>{client_company}</strong>.</p>
<p><strong>Company:</strong> {surveyor_company}</p>
<p>Please log in to review and take action (Approve or Reject):</p>
<p><a href="{surveyors_list_url}">{surveyors_list_url}</a></p>
<p>{email_signature}</p>',
    'associations', 'Surveyor Registered to Association (Sent to Owner)', 'surveyor-registered-to-association'
);

create_email_template(
    'Membership Registration Approved — {client_company}',
    '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>We are pleased to inform you that your company\'s membership registration with <strong>{client_company}</strong> has been <strong>approved</strong>.</p>
<p>You are now an active member of <strong>{client_company}</strong>.</p>
<p>Association Contact:<br>
{client_address}<br>
{client_city}, {client_state}<br>
{client_phonenumber}</p>
<p>Welcome aboard!</p>
<p>{email_signature}</p>',
    'associations', 'Association Approved Surveyor Registration', 'association-approved-surveyor-registration'
);

create_email_template(
    'Membership Registration Rejected — {client_company}',
    '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>We regret to inform you that your company\'s membership registration with <strong>{client_company}</strong> has been <strong>rejected</strong>.</p>
<p><strong>Reason:</strong><br>{rejection_reason}</p>
<p>You may re-apply after addressing the above. Please contact us if you have any questions.</p>
<p>{email_signature}</p>',
    'associations', 'Association Rejected Surveyor Registration', 'association-rejected-surveyor-registration'
);

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
