<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Generate association PDF
 * @param  object $association association object from database
 * @param  string $tag       tag for bulk pdf exporter
 * @return object
 */
function association_pdf($association, $tag = '')
{
    return app_pdf('association', FCPATH . 'modules/associations/libraries/pdf/Association_pdf', $association, $tag);
}

/**
 * Get Association short_url
 * @since  Version 2.7.3
 * @param  object $association
 * @return string Url
 */
function get_association_shortlink($association)
{
    $long_url = site_url("association/{$association->id}/{$association->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if association has short link, if yes return short link
    if (!empty($association->short_link)) {
        return $association->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url' => $long_url,
        'title'    => format_association_number($association->id),
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $association->id);
        $CI->db->update(db_prefix() . 'associations', [
            'short_link' => $short_link,
        ]);

        return $short_link;
    }

    return $long_url;
}

/**
 * Check association restrictions - hash, clientid
 * @param  mixed $id   association id
 * @param  string $hash association hash
 */
function check_association_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('associations_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_association_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $association = $CI->associations_model->get($id);
    if (!$association || ($association->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_association_only_logged_in') == 1) {
            if ($association->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if association email template for expiry reminders is enabled
 * @return boolean
 */
function is_associations_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'association-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending association expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_associations_expiry_reminders_enabled()
{
    return is_associations_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_ASSOCIATION_EXP_REMINDER);
}

/**
 * Return RGBa association status color for PDF documents
 * @param  mixed $status_id current association status
 * @return string
 */
function association_status_color_pdf($status_id)
{
    if ($status_id === 'active') {
        $statusColor = '0, 191, 54';
    } elseif ($status_id === 'inactive') {
        $statusColor = '252, 45, 66';
    } else {
        // pending
        $statusColor = '255, 111, 0';
    }

    return hooks()->apply_filters('association_status_pdf_color', $statusColor, $status_id);
}

/**
 * Format association status
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_association_status($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = association_status_color_class($status);
    $status      = association_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status association-status-' . $id . ' association-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Return association status translated by passed status id
 * @param  mixed $id association status id
 * @return string
 */
function association_status_by_id($id)
{
    $map = [
        'pending'  => _l('association_status_pending'),
        'active'   => _l('association_status_active'),
        'inactive' => _l('association_status_inactive'),
    ];
    $status = $map[$id] ?? ucfirst((string) $id);

    return hooks()->apply_filters('association_status_label', $status, $id);
}

/**
 * Return association status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function association_status_color_class($id, $replace_default_by_muted = false)
{
    $map = [
        'pending'  => 'warning',
        'active'   => 'success',
        'inactive' => 'danger',
    ];
    $class = $map[$id] ?? ($replace_default_by_muted ? 'muted' : 'default');

    return hooks()->apply_filters('association_status_color_class', $class, $id);
}

/**
 * Check if the association id is last invoice
 * @param  mixed  $id associationid
 * @return boolean
 */
function is_last_association($id)
{
    $CI = &get_instance();
    $CI->db->select('id')->from(db_prefix() . 'associations')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $row = $query->row();
    $last_association_id = $row ? $row->id : null;
    if ($last_association_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format association number based on description
 * @param  mixed $id
 * @return string
 */
function format_association_number($id)
{
    if (is_object($id)) {
        $company = $id->company ?? ($id->userid ?? '');
        return hooks()->apply_filters('format_association_number', e($company), ['id' => $id->userid ?? 0, 'association' => $id]);
    }

    $CI       = &get_instance();
    $association = $CI->db->get_where(db_prefix() . 'clients', ['userid' => (int) $id, 'client_type' => 'association'])->row();

    if (!$association) { return ''; }

    return hooks()->apply_filters('format_association_number', e($association->company), ['id' => $id, 'association' => $association]);
}


/**
 * Function that return association item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_association_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'association');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Calculate associations percent by status
 * @param  mixed $status          association status
 * @return array
 */
function get_associations_percent_by_status($status, $project_id = null)
{
    $CI    = get_instance();
    $total = total_rows(db_prefix() . 'clients', ['client_type' => 'association']);

    $active_val      = ($status === 'active') ? 1 : 0;
    $total_by_status = ($status === 'pending')
        ? total_rows(db_prefix() . 'clients', ['client_type' => 'association', 'active' => 0])
        : total_rows(db_prefix() . 'clients', ['client_type' => 'association', 'active' => $active_val]);

    if ($status === 'inactive') {
        $total_by_status = total_rows(db_prefix() . 'clients', ['client_type' => 'association', 'active' => 0]);
    } elseif ($status === 'active') {
        $total_by_status = total_rows(db_prefix() . 'clients', ['client_type' => 'association', 'active' => 1]);
    } else {
        $total_by_status = 0;
    }

    $percent                 = ($total > 0 ? number_format(($total_by_status * 100) / $total, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total;

    return $data;
}

function get_associations_where_sql_for_staff($staff_id)
{
    $CI                                  = &get_instance();
    $has_permission_view_own             = staff_can('view_own',  'associations');
    $allow_staff_view_associations_assigned = get_option('allow_staff_view_associations_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'associations.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'associations.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "associations" AND capability="view_own"))';
        if ($allow_staff_view_associations_assigned == 1) {
            $whereUser .= ' OR sale_agent=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'sale_agent=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned associations / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_associations($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-associations-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'associations', ['sale_agent' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-associations-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view association
 * @param  mixed $id association id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_association($id, $staff_id = false)
{
    $CI       = &get_instance();
    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    $client = $CI->db->get_where(db_prefix() . 'clients', [
        'userid'      => (int) $id,
        'client_type' => 'association',
    ])->row();

    if (!$client) { return false; }

    if (has_permission('associations', $staff_id, 'view')) { return true; }

    $_me = get_staff($staff_id);
    if (!$_me) { return false; }

    // Association entity staff: own company
    if ($_me->client_type === 'association' && $_me->client_id) {
        $my_id = (int) $_me->client_id;
        $cid   = (int) $client->userid;
        if ($my_id === $cid) { return true; }
        if ((int) $client->company_id === $my_id) { return true; }
        $me_client = $CI->db->get_where(db_prefix() . 'clients', ['userid' => $my_id])->row();
        if ($me_client && (int) $me_client->company_id === $cid) { return true; }
        return false;
    }

    if (has_permission('associations', $staff_id, 'view_own')) { return true; }

    return false;
}
