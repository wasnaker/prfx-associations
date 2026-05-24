<?php

defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ───────────────────────────────────────────────────────

hooks()->add_action('admin_init',                    'associations_permissions');
hooks()->add_action('admin_init',                    'associations_ensure_role_permissions');
hooks()->add_filter('staff_can',                     'associations_staff_can_filter', 10, 4);
hooks()->add_filter('staff_permissions',             'associations_add_staff_permissions', 10, 2);
hooks()->add_filter('get_contact_permissions',       'associations_add_contact_permission');
hooks()->add_filter('role_capabilities_features',    'associations_role_capabilities_features', 10);

// ─── Layer 1: Baseline Capabilities ──────────────────────────────────────────

function associations_permissions()
{
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'                          => _l('permission_view') . ' (' . _l('permission_global') . ')',
        'view_own'                      => _l('permission_view_own'),
        'create'                        => _l('permission_create'),
        'edit'                          => _l('permission_edit'),
        'delete'                        => _l('permission_delete'),
        'mark_as'                       => _l('permission_mark_as'),
        'register'                      => _l('permission_register'),
        'approve_surveyor_registration' => _l('associations_approve_surveyor_registration'),
        'approve_surveyor_permit'       => _l('associations_approve_surveyor_permit'),
        'approve_personnel_permit'      => _l('associations_approve_personnel_permit'),
    ];
    register_staff_capabilities('associations', $capabilities, _l('associations'));
}

// ─── Layer 2: Staff Permissions Form ─────────────────────────────────────────

function associations_add_staff_permissions($permissions, $data)
{
    $permissions['associations'] = [
        'name'         => _l('associations'),
        'capabilities' => [
            'view'                          => _l('permission_view') . ' (' . _l('permission_global') . ')',
            'view_own'                      => _l('permission_view_own'),
            'create'                        => _l('permission_create'),
            'edit'                          => _l('permission_edit'),
            'mark_as'                       => _l('permission_mark_as'),
            'approve_surveyor_registration' => _l('permission_approve_surveyor_registration'),
            'approve_surveyor_permit'       => _l('permission_approve_surveyor_permit'),
            'approve_personnel_permit'      => _l('permission_approve_personnel_permit'),
        ],
    ];
    return $permissions;
}

// ─── Layer 3: Role Default Seeds ─────────────────────────────────────────────

function associations_ensure_role_permissions()
{
    $CI = &get_instance();

    $allowed = [
        'Association'       => ['view_own'],
        'Association Admin' => ['view', 'edit', 'create', 'delete', 'mark_as',
                                'approve_surveyor_registration',
                                'approve_surveyor_permit',
                                'approve_personnel_permit'],
        'Surveyor'          => ['view'],
        'Surveyor Admin'        => ['view', 'register'],
        'Surveyor Branch Admin' => ['view', 'register'],
        'Customer Service'  => ['view'],
        'IT Support'        => ['view'],
    ];

    $denied = [
        'Association'       => ['view', 'edit', 'edit_own',
                                'approve_surveyor_registration',
                                'approve_surveyor_permit',
                                'approve_personnel_permit'],
        'Association Admin' => ['view_own', 'edit_own'],
    ];

    foreach ($allowed as $role_name => $caps) {
        $role = $CI->db->get_where(db_prefix() . 'roles', ['name' => $role_name])->row();
        if (!$role) { continue; }
        $rid = (int) $role->roleid;
        foreach ($caps as $cap) {
            $key = 'association_' . $cap . '_role_' . $rid;
            if (get_option($key) === '') { add_option($key, '1'); }
        }
    }

    foreach ($denied as $role_name => $caps) {
        $role = $CI->db->get_where(db_prefix() . 'roles', ['name' => $role_name])->row();
        if (!$role) { continue; }
        $rid = (int) $role->roleid;
        foreach ($caps as $cap) {
            $key = 'association_' . $cap . '_role_' . $rid;
            if (get_option($key) === '') { add_option($key, '0'); }
        }
    }
}

// ─── staff_can Hook Filter ────────────────────────────────────────────────────

function associations_staff_can_filter($result, $capability, $feature, $staff_id)
{
    if ($feature !== 'associations') { return $result; }
    if ($result === true) { return true; }

    $CI   = &get_instance();
    $role = $CI->db->select('role')
        ->get_where(db_prefix() . 'staff', ['staffid' => $staff_id])
        ->row();

    if (!$role || empty($role->role)) { return $result; }

    $opt = get_option('association_' . $capability . '_role_' . (int) $role->role);
    if ($opt !== '') { return $opt == '1'; }

    return $result;
}

// ─── Layer 4: Role Capabilities Matrix ───────────────────────────────────────

function associations_role_capabilities_features($features)
{
    $features['associations'] = [
        'module'        => 'associations',
        'label'         => _l('associations'),
        'prefix'        => 'association',
        'capabilities'  => ['view', 'view_own', 'create', 'edit', 'delete', 'mark_as', 'register', 'approve_surveyor_registration', 'approve_surveyor_permit', 'approve_personnel_permit'],
        'resource_type' => 'association',
    ];

    return $features;
}

// ─── Contact Permissions ──────────────────────────────────────────────────────

function associations_add_contact_permission($permissions)
{
    $permissions[] = [
        'id'         => 7,
        'name'       => _l('association_permission_association'),
        'short_name' => 'associations',
    ];
    return $permissions;
}
