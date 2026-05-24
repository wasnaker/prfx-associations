<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Associations
Description: Full-featured Associations module for Perfex CRM — independent of core Estimates
Version: 1.0.0
Requires at least: 2.3.*
*/

define('ASSOCIATIONS_MODULE_NAME', 'associations');
define('ASSOCIATIONS_ATTACHMENTS_FOLDER', FCPATH . 'uploads/associations/');

// Pre-load association mail template classes from module path.
// App_mail_template::createReflectionMailClass() resolves paths without a module hint,
// falling back to APPPATH/libraries/mails/ and emitting a warning for missing files.
// Pre-loading ensures the class is already defined before that include_once runs,
// so ReflectionClass succeeds and no exception is thrown.
// The spl_autoload_register is a secondary safety net for any late-bound calls.
$_association_mail_classes = [
    'Association_send_to_surveyor',
    'Association_send_to_surveyor_already_sent',
    'Association_accepted_to_association',
    'Association_accepted_to_staff',
    'Association_declined_to_staff',
    'Association_expiration_reminder',
    'Association_approved_surveyor_registration',
    'Association_rejected_surveyor_registration',
    'Association_surveyor_registered',
    'Association_surveyor_set_pending',
];
foreach ($_association_mail_classes as $_qmc) {
    $_qmc_path = FCPATH . 'modules/' . ASSOCIATIONS_MODULE_NAME . '/libraries/mails/' . $_qmc . '.php';
    if (file_exists($_qmc_path) && !class_exists(strtolower($_qmc), false)) {
        include_once($_qmc_path);
    }
}
unset($_association_mail_classes, $_qmc, $_qmc_path);

spl_autoload_register(function ($class) {
    $mail_path = FCPATH . 'modules/' . ASSOCIATIONS_MODULE_NAME . '/libraries/mails/' . ucfirst($class) . '.php';
    if (file_exists($mail_path)) {
        include_once($mail_path);
    }
});

// SMS trigger constants
define('SMS_TRIGGER_ASSOCIATION_EXP_REMINDER', 'association_expiration_reminder');

// Status constants
define('ASSOCIATION_STATUS_DRAFT',    1);
define('ASSOCIATION_STATUS_SENT',     2);
define('ASSOCIATION_STATUS_DECLINED', 3);
define('ASSOCIATION_STATUS_ACCEPTED', 4);
define('ASSOCIATION_STATUS_EXPIRED',  5);

// ─── Hooks ───────────────────────────────────────────────────────────────────

hooks()->add_action('admin_init',                    'associations_module_init_menu_items');
hooks()->add_action('after_email_templates',         'associations_email_templates_section');
hooks()->add_action('admin_init',                    'associations_permissions');
hooks()->add_action('admin_init',                    'associations_ensure_role_permissions');
hooks()->add_action('admin_init',                    'associations_settings_tab');
hooks()->add_action('admin_init',                    'associations_register_app_table');
hooks()->add_action('after_cron_run',                'associations_notification');
hooks()->add_action('staff_member_deleted',          'associations_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links',    'associations_migration_tables_to_replace_old_links');
hooks()->add_filter('other_merge_fields_available_for',          'associations_other_merge_fields_available_for');
hooks()->add_filter('global_search_result_query',  'associations_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'associations_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets',        'associations_add_dashboard_widget');
hooks()->add_filter('module_associations_action_links', 'module_associations_action_links');
hooks()->add_filter('get_contact_permissions',        'associations_add_contact_permission');
hooks()->add_filter('staff_can', 'associations_staff_can_filter', 10, 4);
hooks()->add_filter('staff_permissions', 'associations_add_staff_permissions', 10, 2);
hooks()->add_action('app_admin_footer', 'associations_inactive_company_modal');
hooks()->add_filter('surveyors_table_sql_where',          'associations_filter_surveyors_datatable_where');
hooks()->add_filter('can_view_surveyor_profile',          'associations_can_view_surveyor_profile', 10, 2);
hooks()->add_filter('personnels_permits_datatable_where', 'associations_filter_permits_by_connection', 10, 2);
hooks()->add_filter('can_view_personnel_permit',          'associations_can_view_personnel_permit', 10, 2);
hooks()->add_filter('surveyors_permits_datatable_where',  'associations_filter_surveyor_permits_by_connection', 10, 2);
hooks()->add_filter('can_view_surveyor_permit',           'associations_can_view_surveyor_permit', 10, 2);
hooks()->add_filter('surveyors_table_sql_join',       'associations_inject_membership_join');
hooks()->add_filter('surveyors_table_row_data',       'associations_filter_surveyors_table_row_data', 10, 2);
hooks()->add_filter('surveyors_table_row_options',    'associations_inject_surveyor_row_option', 10, 2);
hooks()->add_action('after_surveyor_view_as_client_link',                          'associations_surveyor_more_menu_items');
hooks()->add_action('after_admin_surveyor_preview_template_tab_content_last_item', 'associations_surveyor_preview_modals');

// ─── Activation / Deactivation ───────────────────────────────────────────────

register_activation_hook(ASSOCIATIONS_MODULE_NAME, 'associations_module_activation_hook');

function associations_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
    // Create uploads directory
    if (!file_exists(ASSOCIATIONS_ATTACHMENTS_FOLDER)) {
        mkdir(ASSOCIATIONS_ATTACHMENTS_FOLDER, 0755, true);
    }
    log_activity('Associations module activated');
}

register_deactivation_hook(ASSOCIATIONS_MODULE_NAME, 'associations_module_deactivation_hook');

function associations_module_deactivation_hook()
{
    $CI = &get_instance();
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'association_activity');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'association_items');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'association_doc_equipment');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'association_equipment');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'association_permits');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'associations');

    // Remove options
    $CI->db->where('name LIKE', 'association%')->delete(db_prefix() . 'options');
    $CI->db->where('type', 'associations')->delete(db_prefix() . 'emailtemplates');

    log_activity('Associations module deactivated - tables dropped');
}

// ─── Language ─────────────────────────────────────────────────────────────────

register_language_files(ASSOCIATIONS_MODULE_NAME, [ASSOCIATIONS_MODULE_NAME]);

// ─── Relation Helpers ──────────────────────────────────────────────────────────

require_once(__DIR__ . '/helpers/association_relation_helpers.php');

// ─── Menu ─────────────────────────────────────────────────────────────────────

function associations_module_init_menu_items()
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

    // Surveyor Permits approval — admin atau association staff
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

    if (is_admin()) {
        $pending_count = $CI->db
            ->where('client_type', 'association')
            ->where_in('registration_status', ['pending', 'user_activated'])
            ->count_all_results(db_prefix() . 'staff');

        $CI->app_menu->add_sidebar_children_item('wasnaker-registration', [
            'slug'     => 'associations-pending-approvals',
            'name'     => _l('associations'),
            'href'     => admin_url('associations/pending_approvals'),
            'position' => 1,
            'badge'    => $pending_count > 0 ? ['count' => $pending_count, 'bg' => 'danger'] : [],
        ]);
    }

    // Surveyor registration approval — admin, platform, atau association staff
    $_me_reg = get_staff(get_staff_user_id());
    $_ct_reg = $_me_reg->client_type ?? '';
    if (is_admin() || is_platform() || $_ct_reg === 'association') {
        $pending_reg = 0;
        if ($CI->db->table_exists(db_prefix() . 'surveyors_associations')) {
            $pending_reg = $CI->db
                ->where('status', 'pending')
                ->count_all_results(db_prefix() . 'surveyors_associations');
        }

        $CI->app_menu->add_sidebar_children_item('wasnaker-registration', [
            'slug'     => 'surveyor-registrations',
            'name'     => _l('association_surveyor_registrations_tab'),
            'href'     => admin_url('associations/list_surveyor_registrations'),
            'position' => 5,
            'badge'    => $pending_reg > 0 ? ['count' => $pending_reg, 'bg' => 'warning'] : [],
        ]);
    }
}

// ─── Relation Data Hooks ─────────────────────────────────────────────────────


// ─── Permissions ──────────────────────────────────────────────────────────────

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

function associations_add_contact_permission($permissions)
{
    $permissions[] = [
        'id'         => 7,
        'name'       => _l('association_permission_association'),
        'short_name' => 'associations',
    ];
    return $permissions;
}

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

// ─── Settings Tab ─────────────────────────────────────────────────────────────

function associations_settings_tab()
{
    $CI = &get_instance();
    $CI->app->add_settings_section_child('finance', 'associations', [
        'name'     => _l('associations'),
        'view'     => 'associations/admin/settings/includes/associations',
        'position' => 52,
        'icon'     => 'fa-solid fa-file-invoice',
    ]);
}

// ─── App_table Registration ───────────────────────────────────────────────────

function associations_register_app_table()
{
    $base = FCPATH . 'modules/' . ASSOCIATIONS_MODULE_NAME . '/views/admin/tables/';

    $associationsTable = App_table::new('associations', $base . 'associations')->customfieldable('association');
    App_table::register($associationsTable);

    App_table::register(
        App_table::new('project_associations', $base . 'associations')
            ->relatedTo($associationsTable->id())
            ->setRules($associationsTable->rules())
    );

    App_table::register(App_table::new('association_surveyor_registrations', $base . 'surveyor_registrations'));
    App_table::register(App_table::new('association_surveyor_permits',       $base . 'surveyor_permits'));
}

// ─── Dashboard Widget ─────────────────────────────────────────────────────────

function _associations_is_association_entity_user()
{
    $me = get_staff(get_staff_user_id());
    return $me && $me->client_type === 'association' && !empty($me->client_id);
}

function _associations_get_connected_surveyor_ids(int $association_id): array
{
    $CI  = &get_instance();
    $rows = $CI->db->query("
        SELECT surveyor_id
        FROM " . db_prefix() . "surveyors_associations
        WHERE association_id = {$association_id}
          AND status = 'active'
    ")->result_array();
    return array_column($rows, 'surveyor_id');
}

// Filter surveyors datatable — association sees only member surveyors
function associations_filter_surveyors_datatable_where($where)
{
    if (!_associations_is_association_entity_user()) { return $where; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $pfx            = db_prefix();

    $where[] = 'AND ' . $pfx . 'clients.userid IN ('
        . 'SELECT sa.surveyor_id FROM ' . $pfx . 'surveyors_associations sa'
        . ' WHERE sa.association_id = ' . $association_id
        . ')';

    return $where;
}

// ─── Surveyor Membership Datatable Hooks ─────────────────────────────────────

function associations_inject_membership_join($join)
{
    return $join;
}

function associations_inject_membership_column($columns)
{
    if (!_associations_is_association_entity_user()) { return $columns; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $pfx            = db_prefix();

    $columns[] = '(SELECT sa.status FROM ' . $pfx . 'surveyors_associations sa'
        . ' WHERE sa.surveyor_id = ' . $pfx . 'clients.userid'
        . ' AND sa.association_id = ' . $association_id
        . ' LIMIT 1) as sa_membership_status';

    return $columns;
}

function associations_inject_membership_header($columns)
{
    if (!_associations_is_association_entity_user()) { return $columns; }
    $columns[] = _l('membership_status');
    return $columns;
}

function associations_filter_surveyors_table_row_data($row, $aRow)
{
    if (!_associations_is_association_entity_user()) { return $row; }

    $row[] = '<span class="label label-success">' . _l('membership_status_active') . '</span>';
    return $row;
}

function associations_surveyor_more_menu_items($surveyor)
{
    if (!_associations_is_association_entity_user()) { return; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $surveyor_id    = (int) $surveyor->userid;

    $CI  = &get_instance();
    $row = $CI->db
        ->where('surveyor_id',    $surveyor_id)
        ->where('association_id', $association_id)
        ->get(db_prefix() . 'surveyors_associations')->row();

    if (!$row) { return; }

    if ($row->status === 'pending' && staff_can('approve_surveyor_registration', 'associations')) {
        echo '<li><a href="#" onclick="assoc_approve_member(' . $surveyor_id . ',' . $association_id . '); return false;">'
            . _l('approve') . '</a></li>';
        echo '<li><a href="#" class="text-danger" onclick="assoc_reject_member(' . $surveyor_id . ',' . $association_id . ',\'' . e($surveyor->company) . '\'); return false;">'
            . _l('reject') . '</a></li>';
    } elseif ($row->status === 'active' && staff_can('mark_as', 'associations')) {
        echo '<li><a href="#" data-toggle="modal" data-target="#assoc-mark-pending-modal">'
            . _l('mark_as_pending') . '</a></li>';
    }
}

function associations_inject_surveyor_row_option($options, $aRow)
{
    if (!_associations_is_association_entity_user()) { return $options; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $surveyor_id    = (int) ($aRow['userid'] ?? 0);
    if (!$surveyor_id) { return $options; }

    $CI  = &get_instance();
    $row = $CI->db
        ->where('surveyor_id',    $surveyor_id)
        ->where('association_id', $association_id)
        ->get(db_prefix() . 'surveyors_associations')->row();

    if (!$row) { return $options; }

    if ($row->status === 'pending' && staff_can('approve_surveyor_registration', 'associations')) {
        $options .= ' | <a href="#" onclick="assoc_approve_member(' . $surveyor_id . ',' . $association_id . '); return false;">' . _l('approve') . '</a>';
        $options .= ' | <a href="#" class="text-danger" onclick="assoc_reject_member(' . $surveyor_id . ',' . $association_id . ',\'' . e($aRow['company'] ?? '') . '\'); return false;">' . _l('reject') . '</a>';
    } elseif ($row->status === 'active' && staff_can('mark_as', 'associations')) {
        $options .= ' | <a href="#" onclick="assoc_mark_pending_confirm(' . $surveyor_id . ',' . $association_id . '); return false;">' . _l('mark_as_pending') . '</a>';
    }

    return $options;
}

function associations_surveyor_preview_modals($surveyor)
{
    if (!_associations_is_association_entity_user()) { return; }
    if (!staff_can('mark_as', 'associations')) { return; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $surveyor_id    = (int) $surveyor->userid;

    $CI  = &get_instance();
    $row = $CI->db
        ->where('surveyor_id',    $surveyor_id)
        ->where('association_id', $association_id)
        ->where('status',         'active')
        ->get(db_prefix() . 'surveyors_associations')->row();

    if (!$row) { return; }
    ?>
    <div class="modal fade" id="assoc-mark-pending-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><?= _l('mark_as_pending'); ?></h4>
                </div>
                <div class="modal-body">
                    <p><?= _l('confirm_mark_as_pending_desc'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('cancel'); ?></button>
                    <button type="button" class="btn btn-warning"
                        onclick="assoc_mark_pending(<?= $surveyor_id; ?>, <?= $association_id; ?>); return false;">
                        <?= _l('mark_as_pending'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Protect surveyor right panel — allow any registered surveyor (any status) for review
function associations_can_view_surveyor_profile($can_view, $surveyor_id)
{
    if (!_associations_is_association_entity_user()) { return $can_view; }

    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;
    $CI             = &get_instance();

    $exists = $CI->db->where('association_id', $association_id)
        ->where('surveyor_id', (int) $surveyor_id)
        ->count_all_results(db_prefix() . 'surveyors_associations');

    return $exists > 0;
}

// Personnel permits datatable — association sees only active permits from connected surveyors
function associations_filter_permits_by_connection($where, $me)
{
    if (!_associations_is_association_entity_user()) { return $where; }

    $association_id  = (int) $me->client_id;
    $pfx             = db_prefix();
    $can_approve     = staff_can('approve_personnel_permit', 'associations');

    $where[] = 'AND s.client_type = "surveyor"';
    $where[] = 'AND s.client_id IN (
        SELECT surveyor_id FROM ' . $pfx . 'surveyors_associations
        WHERE association_id = ' . $association_id . '
          AND status = \'active\'
    )';

    if ($can_approve) {
        $where[] = 'AND p.status IN ("pending", "active", "expired")';
    } else {
        $where[] = 'AND p.status = "active"';
    }

    return $where;
}

// Protect permit right panel — association sees active always; pending/expired only if can approve
function associations_can_view_personnel_permit($can_view, $permit)
{
    if (!_associations_is_association_entity_user()) { return $can_view; }

    $CI             = &get_instance();
    $me             = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;

    $staff = $CI->db->get_where(db_prefix() . 'staff', ['staffid' => (int) $permit->staff_id])->row();
    if (!$staff || $staff->client_type !== 'surveyor') { return 'not_connected'; }

    $connected = _associations_get_connected_surveyor_ids($association_id);
    if (!in_array((int) $staff->client_id, $connected)) { return 'not_connected'; }

    // Entity + view_own cukup untuk melihat panel — approval butuh capability terpisah
    if (staff_can('view_own', 'personnels') || staff_can('view', 'personnels')) {
        return $can_view;
    }

    return 'not_connected';
}

// Surveyor permits datatable — association sees only active permits from connected surveyors
function associations_filter_surveyor_permits_by_connection($where, $me)
{
    if (!_associations_is_association_entity_user()) { return $where; }

    $association_id = (int) $me->client_id;
    $pfx         = db_prefix();

    $where[] = 'AND p.surveyor_id IN (
        SELECT surveyor_id FROM ' . $pfx . 'surveyors_associations
        WHERE association_id = ' . $association_id . '
          AND status = \'active\'
    )';
    $where[] = 'AND p.status = "active"';

    return $where;
}

// Protect surveyor permit right panel — returns true, 'not_connected', or 'permit_not_active'
function associations_can_view_surveyor_permit($can_view, $permit)
{
    if (!_associations_is_association_entity_user()) { return $can_view; }

    $me          = get_staff(get_staff_user_id());
    $association_id = (int) $me->client_id;

    $connected = _associations_get_connected_surveyor_ids($association_id);
    if (!in_array((int) $permit->surveyor_id, $connected)) { return 'not_connected'; }

    if ($permit->status !== 'active') { return 'permit_not_active'; }

    return $can_view;
}

function associations_add_dashboard_widget($widgets)
{
    return $widgets;
}

// ─── Staff Member Deleted ─────────────────────────────────────────────────────

function associations_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('sale_agent', $data['id']);
    $CI->db->update(db_prefix() . 'associations', ['sale_agent' => $data['transfer_data_to']]);

}

// ─── Global Search ────────────────────────────────────────────────────────────

function associations_global_search_result_output($output, $data)
{
    if ($data['type'] == 'associations') {
        $output = '<a href="' . admin_url('associations/list_associations/' . $data['result']['id']) . '">'
            . format_association_number($data['result']['id']) . '</a>';
    }
    return $output;
}

function associations_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('associations', '', 'view')) {
        $CI->db->select()
            ->from(db_prefix() . 'associations')
            ->like('formatted_number', $q)
            ->limit($limit);

        $result[] = [
            'result'         => $CI->db->get()->result_array(),
            'type'           => 'associations',
            'search_heading' => _l('associations'),
        ];

        if (isset($result[0]['result'][0]['id'])) {
            return $result;
        }

        $CI->db->select()
            ->from(db_prefix() . 'associations')
            ->join(db_prefix() . 'clients', db_prefix() . 'associations.client_id=' . db_prefix() . 'clients.userid', 'left')
            ->like(db_prefix() . 'clients.company', $q)
            ->or_like(db_prefix() . 'associations.formatted_number', $q)
            ->order_by(db_prefix() . 'clients.company', 'ASC')
            ->limit($limit);

        $result[] = [
            'result'         => $CI->db->get()->result_array(),
            'type'           => 'associations',
            'search_heading' => _l('associations'),
        ];
    }
    return $result;
}

// ─── Migration ────────────────────────────────────────────────────────────────

function associations_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
        'table' => db_prefix() . 'associations',
        'field' => 'clientnote',
    ];
    $tables[] = [
        'table' => db_prefix() . 'associations',
        'field' => 'adminnote',
    ];
    return $tables;
}

// ─── Action Links ─────────────────────────────────────────────────────────────

function module_associations_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=associations') . '">' . _l('settings') . '</a>';
    return $actions;
}

// ─── Merge Fields ─────────────────────────────────────────────────────────────

function associations_other_merge_fields_available_for($available_for)
{
    $available_for[] = 'associations';
    return $available_for;
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

// ─── Cron Notification ────────────────────────────────────────────────────────

function associations_notification()
{
    $CI = &get_instance();
    $CI->load->model('associations/Associations_model', 'associations_model');

    // Send expiry reminders
    if (method_exists($CI->associations_model, 'send_expiry_reminder')) {
        $CI->associations_model->send_expiry_reminder();
    }

    // Auto-expire overdue associations
    $CI->db->where('expirydate <', date('Y-m-d'));
    $CI->db->where('status', ASSOCIATION_STATUS_SENT);
    $CI->db->update(db_prefix() . 'associations', ['status' => ASSOCIATION_STATUS_EXPIRED]);
}

// ─── Load Helper & Assets ─────────────────────────────────────────────────────

$CI = &get_instance();
$CI->load->helper(ASSOCIATIONS_MODULE_NAME . '/associations');

register_merge_fields(['associations/merge_fields/association_merge_fields']);

$current_url = $CI->uri->segment(1) . '/' . $CI->uri->segment(2);
if (
    ($CI->uri->segment(1) == 'admin' && $CI->uri->segment(2) == 'associations') ||
    ($CI->uri->segment(1) == 'admin' && $CI->uri->segment(2) == 'surveyors' && _associations_is_association_entity_user()) ||
    $CI->uri->segment(1) == 'associations'
) {
    $CI->app_css->add(
        ASSOCIATIONS_MODULE_NAME . '-css',
        base_url('modules/' . ASSOCIATIONS_MODULE_NAME . '/assets/css/associations.css')
    );
    $CI->app_scripts->add(
        ASSOCIATIONS_MODULE_NAME . '-js',
        base_url('modules/' . ASSOCIATIONS_MODULE_NAME . '/assets/js/associations.js') . '?v=' . filemtime(FCPATH . 'modules/' . ASSOCIATIONS_MODULE_NAME . '/assets/js/associations.js')
    );
}

function associations_inactive_company_modal()
{
    $CI  = &get_instance();
    $me  = get_staff(get_staff_user_id());

    // Only for association entity staff
    if (!$me || $me->client_type !== 'association' || !$me->client_id) { return; }

    // Load company record
    $company = $CI->db->get_where(db_prefix() . 'clients', [
        'userid'      => (int) $me->client_id,
        'client_type' => 'association',
    ])->row();

    if (!$company || $company->active == 1) { return; }

    // Build completeness checks
    $checks = [
        ['label' => _l('association_vat'),        'ok' => !empty($company->vat)],
        ['label' => _l('client_phonenumber'),  'ok' => !empty($company->phonenumber)],
        ['label' => _l('client_address'),      'ok' => !empty($company->address)],
        ['label' => _l('client_state'),        'ok' => !empty($company->state)],
        ['label' => _l('client_city'),         'ok' => !empty($company->city)],
        ['label' => _l('billing_address'),     'ok' => !empty($company->billing_street) && !empty($company->billing_city) && !empty($company->billing_state)],
        ['label' => _l('association_logo_light'), 'ok' => !empty($company->logo_light) || !empty($company->logo_dark)],
    ];

    $total   = count($checks);
    $filled  = count(array_filter(array_column($checks, 'ok')));
    $percent = (int) round(($filled / $total) * 100);

    $restricted = ['rfqs', 'quotations', 'orders', 'programs', 'jobs',
                   'associations/equipment', 'schedules', 'billings'];

    $edit_url  = admin_url('associations/association/' . (int) $me->client_id);
    $back_url  = admin_url('associations');
    $comp_name = e($company->company);

    $checks_js = json_encode(array_map(fn($c) => [
        'label' => $c['label'],
        'ok'    => (bool) $c['ok'],
    ], $checks));

    $restricted_js = json_encode($restricted);
    ?>
<!-- Inactive Company Modal -->
<div class="modal fade" id="inactive-company-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;">

            <!-- Header gradient -->
            <div style="background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);padding:28px 28px 20px;">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa fa-building" style="font-size:22px;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 style="color:#fff;margin:0;font-weight:700;font-size:18px;">
                            <?= _l('inactive_company_modal_title'); ?>
                        </h4>
                        <p style="color:rgba(255,255,255,0.85);margin:0;font-size:13px;">
                            <?= e($comp_name); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-body" style="padding:24px 28px;">

                <!-- Progress bar -->
                <div class="tw-mb-4">
                    <div class="tw-flex tw-justify-between tw-mb-2">
                        <span class="tw-text-sm tw-font-medium tw-text-neutral-600"><?= _l('profile_completeness'); ?></span>
                        <span class="tw-text-sm tw-font-bold" id="icm-percent-label"
                            style="color:<?= $percent === 100 ? '#16a34a' : ($percent >= 60 ? '#d97706' : '#dc2626'); ?>">
                            <?= $filled; ?>/<?= $total; ?> &mdash; <?= $percent; ?>%
                        </span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:999px;height:10px;overflow:hidden;">
                        <div style="height:100%;border-radius:999px;width:<?= $percent; ?>%;
                            background:<?= $percent === 100 ? '#16a34a' : ($percent >= 60 ? '#f59e0b' : '#ef4444'); ?>;
                            transition:width .4s ease;"></div>
                    </div>
                </div>

                <!-- Missing fields only -->
                <?php $missing = array_filter($checks, fn($c) => !$c['ok']); ?>
                <?php if (!empty($missing)) { ?>
                <div class="tw-mb-4">
                    <p class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-neutral-400 tw-mb-2">
                        <?= _l('profile_missing'); ?>
                    </p>
                    <?php foreach ($missing as $check) { ?>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-py-1.5 tw-border-b tw-border-neutral-100">
                        <i class="fa fa-times-circle" style="color:#ef4444;font-size:16px;flex-shrink:0;"></i>
                        <span class="tw-text-sm tw-font-medium tw-text-neutral-800"><?= e($check['label']); ?></span>
                    </div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <div class="tw-mb-4 tw-p-3 tw-rounded-lg" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="fa fa-check-circle" style="color:#16a34a;font-size:18px;"></i>
                        <span class="tw-text-sm tw-font-medium" style="color:#15803d;">
                            <?= _l('profile_complete_ready'); ?>
                        </span>
                    </div>
                </div>
                <?php } ?>

                <p class="tw-text-sm tw-text-neutral-500 tw-mb-0">
                    <?= _l('inactive_company_modal_desc'); ?>
                </p>
            </div>

            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:16px 28px;background:#fafafa;">
                <a href="<?= $back_url; ?>" class="btn btn-default">
                    <i class="fa fa-arrow-left tw-mr-1"></i><?= _l('inactive_company_modal_later'); ?>
                </a>
                <?php if ($percent < 100) { ?>
                <a href="<?= $edit_url; ?>" class="btn btn-primary" style="background:linear-gradient(135deg,#f59e0b,#ef4444);border:none;">
                    <i class="fa fa-edit tw-mr-1"></i><?= _l('inactive_company_modal_complete'); ?>
                </a>
                <?php } ?>
            </div>

        </div>
    </div>
</div>

<script>
(function() {
    var _restricted = <?= $restricted_js; ?>;
    var _path       = window.location.pathname + window.location.hash;

    function _isRestricted() {
        return _restricted.some(function(seg) {
            return _path.indexOf('/' + seg) !== -1;
        });
    }

    $(function() {
        if (_isRestricted()) {
            $('#inactive-company-modal').modal({ show: true, backdrop: 'static', keyboard: false });
        }
    });
})();
</script>
<?php
}
