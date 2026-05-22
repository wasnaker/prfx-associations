<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Email template definitions for the Associations module.
 *
 * CONVENTION: All module email templates MUST be defined here,
 * not in install.php or associations.php.
 *
 * install.php calls associations_register_email_templates() on activation.
 */

if (!function_exists('associations_email_templates')) {

function associations_email_templates(): array
{
    return [
        [
            'subject' => 'New Association Registration Pending: {client_company}',
            'message' => '<p>Hi,</p><p>A new association registration is awaiting your approval.</p><p><strong>Company:</strong> {client_company}</p><p>Please log in to the admin panel to review and approve this registration.</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'New Association Registration (Sent to Admin)',
            'slug'    => 'new-association-registered-to-admin',
            'active'  => 1,
        ],
        [
            'subject' => 'Your Registration Has Been Approved',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>Congratulations! Your registration with <strong>{companyname}</strong> has been approved. You can now log in to your account.</p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Association Registration Approved',
            'slug'    => 'association-registration-confirmed',
            'active'  => 1,
        ],
        [
            'subject' => 'Your registration has been received - {companyname}',
            'message' => 'Dear {contact_firstname} {contact_lastname},<br /><br />Thank you for registering on the <strong>{companyname}</strong> portal.<br /><br />Your registration is currently <strong>pending approval</strong>. We will notify you once your account has been reviewed.<br /><br />Kind Regards,<br />{email_signature}<br /><br />(This is an automated email, so please don\'t reply to this email address)',
            'type'    => 'associations',
            'name'    => 'Association Registration Received (Welcome Email)',
            'slug'    => 'new-association-created',
            'active'  => 1,
        ],
        [
            'subject' => 'Set Your Password - {companyname}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>Please click the link below to set your password:</p><p><a href="{set_password_url}">{set_password_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Set Password',
            'slug'    => 'association-set-password',
            'active'  => 1,
        ],
        [
            'subject' => 'Your Registration Was Not Approved',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>Unfortunately your registration for <strong>{client_company}</strong> was not approved.</p><p>Please contact us if you have any questions.</p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Association Registration Rejected',
            'slug'    => 'association-registration-rejected',
            'active'  => 1,
        ],
        [
            'subject' => 'Reset Your Password - {companyname}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>We received a request to reset your password. Click the link below to reset it:</p><p><a href="{reset_password_url}">{reset_password_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Forgot Password',
            'slug'    => 'association-forgot-password',
            'active'  => 1,
        ],
        [
            'subject' => 'Your Password Has Been Reset',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>Your password has been successfully reset. You can now log in with your new password.</p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Password Reset Confirmation',
            'slug'    => 'association-password-reseted',
            'active'  => 1,
        ],
        [
            'subject' => 'Verify Your Email Address - {companyname}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p><p>Please verify your email address by clicking the link below:</p><p><a href="{association_verification_url}">{association_verification_url}</a></p><p>Kind Regards,</p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Email Verification',
            'slug'    => 'association-verification-email',
            'active'  => 1,
        ],
        [
            'subject' => 'Association Profile File Uploaded: {client_company}',
            'message' => '<p>Hi,</p><p>A new file has been uploaded to the association profile of <strong>{client_company}</strong>.</p><p>View files: <a href="{association_profile_files_admin_link}">{association_profile_files_admin_link}</a></p><p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Association Profile File Uploaded (Sent to Staff)',
            'slug'    => 'new-association-profile-file-uploaded-to-staff',
            'active'  => 1,
        ],
        [
            'subject' => 'New Membership Registration — {surveyor_company}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>A new membership registration request has been submitted to <strong>{client_company}</strong>.</p>
<p><strong>Company:</strong> {surveyor_company}</p>
<p>Please log in to review and take action (Approve or Reject):</p>
<p><a href="{surveyors_list_url}">{surveyors_list_url}</a></p>
<p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Surveyor Registered to Association (Sent to Owner)',
            'slug'    => 'surveyor-registered-to-association',
            'active'  => 1,
        ],
        [
            'subject' => 'Membership Registration Approved — {client_company}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>We are pleased to inform you that your company\'s membership registration with <strong>{client_company}</strong> has been <strong>approved</strong>.</p>
<p>You are now an active member of <strong>{client_company}</strong>.</p>
<p>Association Contact:<br>
{client_address}<br>
{client_city}, {client_state}<br>
{client_phonenumber}</p>
<p>Welcome aboard!</p>
<p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Association Approved Surveyor Registration',
            'slug'    => 'association-approved-surveyor-registration',
            'active'  => 1,
        ],
        [
            'subject' => 'Membership Registration Rejected — {client_company}',
            'message' => '<p>Dear {contact_firstname} {contact_lastname},</p>
<p>We regret to inform you that your company\'s membership registration with <strong>{client_company}</strong> has been <strong>rejected</strong>.</p>
<p><strong>Reason:</strong><br>{rejection_reason}</p>
<p>You may re-apply after addressing the above. Please contact us if you have any questions.</p>
<p>{email_signature}</p>',
            'type'    => 'associations',
            'name'    => 'Association Rejected Surveyor Registration',
            'slug'    => 'association-rejected-surveyor-registration',
            'active'  => 1,
        ],
    ];
}

function associations_register_email_templates(): void
{
    foreach (associations_email_templates() as $tpl) {
        create_email_template(
            $tpl['subject'],
            $tpl['message'],
            $tpl['type'],
            $tpl['name'],
            $tpl['slug'],
            $tpl['active'] ?? 1
        );
    }
}

} // end function_exists guard
