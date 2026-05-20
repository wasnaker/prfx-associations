<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_associations
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($association_id)
    {
        $valAllowed = get_option('gdpr_contact_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $this->ci->db->where('clientid', $association_id);
        $associations = $this->ci->db->get(db_prefix().'associations')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'association');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($associations as $associationsKey => $association) {
            unset($associations[$associationsKey]['adminnote']);
            $associations[$associationsKey]['shipping_country'] = get_country($association['shipping_country']);
            $associations[$associationsKey]['billing_country']  = get_country($association['billing_country']);

            $associations[$associationsKey]['currency'] = $this->ci->currencies_model->get($association['currency']);

            $associations[$associationsKey]['items'] = _prepare_items_array_for_export(get_items_by_type('association', $association['id']), 'association');

            if (in_array('associations_notes', $valAllowed)) {
                // Notes
                $this->ci->db->where('rel_id', $association['id']);
                $this->ci->db->where('rel_type', 'association');

                $associations[$associationsKey]['notes'] = $this->ci->db->get(db_prefix().'notes')->result_array();
            }
            if (in_array('associations_activity_log', $valAllowed)) {
                // Activity
                $this->ci->db->where('rel_id', $association['id']);
                $this->ci->db->where('rel_type', 'association');

                $associations[$associationsKey]['activity'] = $this->ci->db->get(db_prefix().'sales_activity')->result_array();
            }
            $associations[$associationsKey]['views'] = get_views_tracking('association', $association['id']);

            $associations[$associationsKey]['tracked_emails'] = get_tracked_emails($association['id'], 'association');

            $associations[$associationsKey]['additional_fields'] = [];

            foreach ($custom_fields as $cf) {
                $associations[$associationsKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($association['id'], $cf['id'], 'association'),
                ];
            }
        }

        return $associations;
    }
}
