<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ────────────────────────────────────────────────────────

hooks()->add_action('after_email_templates', 'associations_email_templates_section');

// ─── Register Email Templates on Install ──────────────────────────────────────

function associations_register_email_templates()
{
    $CI = &get_instance();
    $CI->load->model('emails_model');

    $templates = [
        [
            'slug'     => 'association-registered',
            'name'     => 'Association Registered',
            'subject'  => 'New Association Registration',
            'message'  => '<p>Hi {staff_firstname},</p><p>A new association has registered.</p>',
            'type'     => 'associations',
            'language' => 'english',
        ],
    ];

    foreach ($templates as $template) {
        $exists = $CI->db->where('slug', $template['slug'])
                         ->where('language', $template['language'])
                         ->get(db_prefix() . 'emailtemplates')
                         ->num_rows();
        if ($exists === 0) {
            $CI->db->insert(db_prefix() . 'emailtemplates', $template);
        }
    }
}

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
