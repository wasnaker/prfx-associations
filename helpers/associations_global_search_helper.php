<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ─── Hook Registrations ────────────────────────────────────────────────────────

hooks()->add_filter('global_search_result_query',  'associations_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'associations_global_search_result_output', 10, 2);

// ─── Global Search: Query ──────────────────────────────────────────────────────

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

// ─── Global Search: Output ─────────────────────────────────────────────────────

function associations_global_search_result_output($output, $data)
{
    if ($data['type'] == 'associations') {
        $output = '<a href="' . admin_url('associations/list_associations/' . $data['result']['id']) . '">'
            . format_association_number($data['result']['id']) . '</a>';
    }
    return $output;
}
