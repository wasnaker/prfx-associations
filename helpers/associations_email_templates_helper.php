<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ────────────────────────────────────────────────────────

hooks()->add_action('after_email_templates', 'associations_email_templates_section');

// ─── Email Templates Section ──────────────────────────────────────────────────

function associations_email_templates_section()
{
    $CI = &get_instance();

    $module = $CI->app_modules->get(ASSOCIATIONS_MODULE_NAME);
    if (!$module || (int) $module['activated'] !== 1) {
        return;
    }

    $CI->load->model('emails_model');
    $data['association_email_templates'] = $CI->emails_model->get([
        'type'     => 'associations',
        'language' => 'english',
    ]);
    $data['hasPermissionEdit'] = staff_can('edit', 'email_templates');
    $CI->load->view('associations/admin/emails/association_email_templates', $data);
}
