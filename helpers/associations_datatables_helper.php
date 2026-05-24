<?php

defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ────────────────────────────────────────────────────────

hooks()->add_filter('surveyors_table_sql_where',          'associations_filter_surveyors_datatable_where');
hooks()->add_filter('surveyors_table_sql_join',           'associations_inject_membership_join');
hooks()->add_filter('surveyors_table_sql_columns',        'associations_inject_membership_column');
hooks()->add_filter('surveyors_table_columns',            'associations_inject_membership_header');
hooks()->add_filter('surveyors_table_row_data',           'associations_filter_surveyors_table_row_data', 10, 2);
hooks()->add_filter('surveyors_table_row_options',        'associations_inject_surveyor_row_option', 10, 2);
hooks()->add_filter('can_view_surveyor_profile',          'associations_can_view_surveyor_profile', 10, 2);

hooks()->add_filter('personnels_permits_datatable_where', 'associations_filter_permits_by_connection', 10, 2);
hooks()->add_filter('can_view_personnel_permit',          'associations_can_view_personnel_permit', 10, 2);
hooks()->add_filter('surveyors_permits_datatable_where',  'associations_filter_surveyor_permits_by_connection', 10, 2);
hooks()->add_filter('can_view_surveyor_permit',           'associations_can_view_surveyor_permit', 10, 2);

// ─── Helper Functions ──────────────────────────────────────────────────────────

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

// ─── Surveyor Datatable Hooks ──────────────────────────────────────────────────

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

// ─── Personnel Permits Hooks ───────────────────────────────────────────────────

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

    if (staff_can('view_own', 'personnels') || staff_can('view', 'personnels')) {
        return $can_view;
    }

    return 'not_connected';
}

// ─── Surveyor Permits Hooks ────────────────────────────────────────────────────

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
