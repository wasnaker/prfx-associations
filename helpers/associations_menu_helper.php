<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ────────────────────────────────────────────────────────

hooks()->add_action('admin_init', 'associations_init_menu_items');

// ─── Menu Item Registration ───────────────────────────────────────────────────

function associations_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
        'name'       => _l('associations'),
        'url'        => 'associations',
        'permission' => 'associations',
        'icon'       => 'fa-solid fa-file-invoice',
        'position'   => 11,
    ]);

    if (staff_can('view', 'associations') || staff_can('view_own', 'associations')) {
        $CI->app_menu->add_sidebar_children_item('wasnaker-member', [
            'slug'     => 'associations-tracking',
            'name'     => _l('associations'),
            'href'     => admin_url('associations'),
            'position' => 5,
        ]);
    }

    $_me_sp = get_staff(get_staff_user_id());
    $_ct_sp = $_me_sp->client_type ?? '';
    if (is_admin() || $_ct_sp === 'association') {
        $pending_permits = 0;
        if ($CI->db->table_exists(db_prefix() . 'surveyor_permits')) {
            $pending_permits = $CI->db
                ->where('status', 'pending')
                ->count_all_results(db_prefix() . 'surveyor_permits');
        }

        $CI->app_menu->add_sidebar_children_item('wasnaker-transaction', [
            'slug'     => 'surveyor-permits-approval',
            'name'     => _l('assoc_surveyor_permits'),
            'href'     => admin_url('associations/list_surveyor_permits'),
            'position' => 12,
            'badge'    => $pending_permits > 0 ? ['count' => $pending_permits, 'bg' => 'warning'] : [],
        ]);
    }

    $_me_menu = get_staff(get_staff_user_id());
    if ($_me_menu && $_me_menu->client_type === 'surveyor' && !empty($_me_menu->client_id)) {
        $CI->app_menu->add_sidebar_children_item('wasnaker-member', [
            'slug'     => 'my-associations',
            'name'     => _l('my_associations'),
            'href'     => admin_url('associations/my_associations'),
            'position' => 6,
        ]);
    }

    if (has_permission('associations', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('reports', [
            'slug'     => 'associations-report',
            'name'     => _l('associations_report'),
            'href'     => admin_url('associations/associations_report'),
            'position' => 35,
        ]);
    }
}
