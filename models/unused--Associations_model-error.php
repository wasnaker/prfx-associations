<?php

use app\services\AbstractKanban;
use app\services\associations\AssociationsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Associations_model extends App_Model
{
    private $statuses;
    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->statuses = hooks()->apply_filters('before_set_association_statuses', [
            1,
            2,
            5,
            3,
            4,
        ]);
    }

    /**
     * Get unique sale agent for associations / Used for filters
     *
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'associations JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'associations.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get association/s
     *
     * @param mixed $id    association id
     * @param array $where perform where
     *
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*, ' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'associations.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'associations');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'associations.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'associations.id', $id);
            $association = $this->db->get()->row();
            if ($association) {
                $association->attachments                           = $this->get_attachments($id);
                $association->visible_attachments_to_association_found = false;

                foreach ($association->attachments as $attachment) {
                    if ($attachment['visible_to_association'] == 1) {
                        $association->visible_attachments_to_association_found = true;

                        break;
                    }
                }

                $association->items     = get_items_by_type('association', $id);
                $association->equipment = $this->get_association_equipment($id);

                if ($association->project_id) {
                    $this->load->model('projects_model');
                    $association->project_data = $this->projects_model->get($association->project_id);
                }

                $association->client = $this->clients_model->get($association->clientid);

                if (! $association->client) {
                    $association->client          = new stdClass();
                    $association->client->company = $association->deleted_association_name;
                }

                $this->load->model('email_schedule_model');
                $association->scheduled_email = $this->email_schedule_model->get($id, 'association');
            }

            return $association;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get association statuses
     *
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $association = $this->db->get(db_prefix() . 'associations')->row();

        if ($association) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'associations', ['signature' => null]);

            if (! empty($association->signature)) {
                unlink(get_upload_path_by_type('association') . $id . '/' . $association->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Convert association to invoice
     *
     * @param mixed $id            association id
     * @param mixed $client
     * @param mixed $draft_invoice
     *
     * @return mixed New invoice ID
     */
    public function convert_to_invoice($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_association = $this->get($id);

        $new_invoice_data = [];
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid']   = $_association->clientid;
        $new_invoice_data['project_id'] = $_association->project_id;
        $new_invoice_data['number']     = get_option('next_invoice_number');
        $new_invoice_data['date']       = _d(date('Y-m-d'));
        $new_invoice_data['duedate']    = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_association->show_quantity_as;
        $new_invoice_data['currency']         = $_association->currency;
        $new_invoice_data['subtotal']         = $_association->subtotal;
        $new_invoice_data['total']            = $_association->total;
        $new_invoice_data['adjustment']       = $_association->adjustment;
        $new_invoice_data['discount_percent'] = $_association->discount_percent;
        $new_invoice_data['discount_total']   = $_association->discount_total;
        $new_invoice_data['discount_type']    = $_association->discount_type;
        $new_invoice_data['sale_agent']       = $_association->sale_agent;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_association->billing_street);
        $new_invoice_data['billing_city']     = $_association->billing_city;
        $new_invoice_data['billing_state']    = $_association->billing_state;
        $new_invoice_data['billing_zip']      = $_association->billing_zip;
        $new_invoice_data['billing_country']  = $_association->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_association->shipping_street);
        $new_invoice_data['shipping_city']    = $_association->shipping_city;
        $new_invoice_data['shipping_state']   = $_association->shipping_state;
        $new_invoice_data['shipping_zip']     = $_association->shipping_zip;
        $new_invoice_data['shipping_country'] = $_association->shipping_country;

        if ($_association->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }

        $new_invoice_data['show_shipping_on_invoice'] = $_association->show_shipping_on_association;
        $new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status']    = 1;
        $new_invoice_data['adminnote'] = '';

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];

        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems']              = [];
        $custom_fields_items                       = get_custom_fields('items');
        $key                                       = 1;

        foreach ($_association->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_association_item_taxes($item['id']);

            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate']  = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];

            foreach ($custom_fields_items as $cf) {
                $new_invoice_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (! defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Association accepted the association and is auto converted to invoice
            if (! is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete(db_prefix() . 'sales_activity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_association', true, serialize([
                    '<a href="' . admin_url('associations/list_associations/' . $_association->id) . '">' . format_association_number($_association->id) . '</a>',
                ]));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'addedfrom'  => $_association->addedfrom,
                'sale_agent' => $_association->sale_agent,
            ]);

            // Update association with the new invoice data and set to status accepted
            $this->db->where('id', $_association->id);
            $this->db->update(db_prefix() . 'associations', [
                'quoted_date' => date('Y-m-d H:i:s'),
                'quotationid'     => $id,
                'status'        => 4,
            ]);

            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'association');
                $this->db->where('active', 1);
                $cfAssociations = $this->db->get(db_prefix() . 'customfields')->result_array();

                foreach ($cfAssociations as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');

                        $this->db->group_start();
                        $this->db->like('slug', 'invoice_' . $tmpSlug[1], 'after');
                        $this->db->where('type', $field['type']);
                        $this->db->where('options', $field['options']);
                        $this->db->where('active', 1);
                        $this->db->group_end();

                        // $this->db->where('slug LIKE "invoice_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                        $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($_association->id, $field['id'], 'association', false);

                            if ($value == '') {
                                continue;
                            }

                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $id,
                                'fieldid' => $cfTransfer[0]['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                }
            }

            if ($client == false) {
                $this->log_association_activity($_association->id, 'association_activity_converted', false, serialize([
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
                ]));
            }

            hooks()->do_action('association_converted_to_invoice', ['invoice_id' => $id, 'association_id' => $_association->id]);
        }

        return $id;
    }

    /**
     * Convert CUSTOMER to a draft Quotation.
     * Equipment units become line items at rate=0 so the surveyor can fill prices.
     */
    public function convert_to_quotation($association_id)
    {
        $_association = $this->get($association_id);
        $this->load->model('quotations/Quotations_model', 'quotations_model');

        $data = [
            'clientid'         => $_association->clientid,
            'project_id'       => $_association->project_id,
            'number'           => get_option('next_quotation_number'),
            'date'             => _d(date('Y-m-d')),
            'expirydate'       => !empty($_association->expirydate) ? _d($_association->expirydate) : null,
            'show_quantity_as' => $_association->show_quantity_as,
            'currency'         => $_association->currency,
            'subtotal'         => 0,
            'total'            => 0,
            'total_tax'        => 0,
            'adjustment'       => 0,
            'discount_percent' => 0,
            'discount_total'   => 0,
            'discount_type'    => $_association->discount_type,
            'billing_street'   => clear_textarea_breaks($_association->billing_street),
            'billing_city'     => $_association->billing_city,
            'billing_state'    => $_association->billing_state,
            'billing_zip'      => $_association->billing_zip,
            'billing_country'  => $_association->billing_country,
            'shipping_street'  => clear_textarea_breaks($_association->shipping_street),
            'shipping_city'    => $_association->shipping_city,
            'shipping_state'   => $_association->shipping_state,
            'shipping_zip'     => $_association->shipping_zip,
            'shipping_country' => $_association->shipping_country,
            'include_shipping' => $_association->include_shipping,
            'terms'            => get_option('predefined_terms_quotation'),
            'clientnote'       => get_option('predefined_clientnote_quotation'),
            'status'           => 1, // draft — surveyor adds rates before sending
            'adminnote'        => '',
            'reference_no'     => $_association->reference_no,
            'sale_agent'       => get_staff_user_id(),
            'newitems'         => [],
        ];

        $key = 1;

        // Convert each equipment unit to a line item (rate=0, surveyor fills in)
        foreach (($_association->equipment ?? []) as $eq) {
            $details = array_filter([
                !empty($eq['unit_code'])        ? 'Unit: ' . $eq['unit_code'] : null,
                !empty($eq['serial_number'])    ? 'S/N: ' . $eq['serial_number'] : null,
                !empty($eq['location'])         ? 'Location: ' . $eq['location'] : null,
                !empty($eq['cert_expired_date'])? 'Cert Exp: ' . $eq['cert_expired_date'] : null,
            ]);
            $data['newitems'][$key] = [
                'description'           => $eq['item_name'],
                'long_description'      => implode("\n", $details),
                'qty'                   => 1,
                'rate'                  => 0,
                'unit'                  => '',
                'taxname'               => [],
                'order'                 => $key,
                'association_equipment_id' => $eq['association_equipment_id'],
            ];
            $key++;
        }

        // Copy existing CUSTOMER line items (with their rates and taxes)
        $custom_fields_items = get_custom_fields('items');
        foreach (($_association->items ?? []) as $item) {
            $taxes = get_association_item_taxes($item['id']);
            $taxnames = array_column($taxes, 'taxname');
            $data['newitems'][$key] = [
                'description'      => $item['description'],
                'long_description' => clear_textarea_breaks($item['long_description']),
                'qty'              => $item['qty'],
                'rate'             => $item['rate'],
                'unit'             => $item['unit'],
                'taxname'          => $taxnames,
                'order'            => $item['item_order'],
            ];
            foreach ($custom_fields_items as $cf) {
                $data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);
                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }

        $quotation_id = $this->quotations_model->add($data);

        if ($quotation_id) {
            $this->db->where('id', $association_id);
            $this->db->update(db_prefix() . 'associations', [
                'quotationid' => $quotation_id,
            ]);

            $this->db->where('id', $quotation_id);
            $this->db->update(db_prefix() . 'quotations', [
                'association_id' => $association_id,
            ]);

            $this->log_association_activity($association_id, 'association_activity_converted_to_quotation', false, serialize([
                '<a href="' . admin_url('quotations/list_quotations/' . $quotation_id) . '">' . format_quotation_number($quotation_id) . '</a>',
            ]));
        }

        return $quotation_id;
    }

    /**
     * Copy association
     *
     * @param mixed $id association id to copy
     *
     * @return mixed
     */
    public function copy($id)
    {
        $_association                       = $this->get($id);
        $new_association_data               = [];
        $new_association_data['clientid']   = $_association->clientid;
        $new_association_data['project_id'] = $_association->project_id;
        $new_association_data['number']     = get_option('next_association_number');
        $new_association_data['date']       = _d(date('Y-m-d'));
        $new_association_data['expirydate'] = null;

        if ($_association->expirydate && get_option('association_due_after') != 0) {
            $new_association_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('association_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_association_data['show_quantity_as'] = $_association->show_quantity_as;
        $new_association_data['currency']         = $_association->currency;
        $new_association_data['subtotal']         = $_association->subtotal;
        $new_association_data['total']            = $_association->total;
        $new_association_data['adminnote']        = $_association->adminnote;
        $new_association_data['adjustment']       = $_association->adjustment;
        $new_association_data['discount_percent'] = $_association->discount_percent;
        $new_association_data['discount_total']   = $_association->discount_total;
        $new_association_data['discount_type']    = $_association->discount_type;
        $new_association_data['terms']            = $_association->terms;
        $new_association_data['sale_agent']       = $_association->sale_agent;
        $new_association_data['reference_no']     = $_association->reference_no;
        // Since version 1.0.6
        $new_association_data['billing_street']   = clear_textarea_breaks($_association->billing_street);
        $new_association_data['billing_city']     = $_association->billing_city;
        $new_association_data['billing_state']    = $_association->billing_state;
        $new_association_data['billing_zip']      = $_association->billing_zip;
        $new_association_data['billing_country']  = $_association->billing_country;
        $new_association_data['shipping_street']  = clear_textarea_breaks($_association->shipping_street);
        $new_association_data['shipping_city']    = $_association->shipping_city;
        $new_association_data['shipping_state']   = $_association->shipping_state;
        $new_association_data['shipping_zip']     = $_association->shipping_zip;
        $new_association_data['shipping_country'] = $_association->shipping_country;
        if ($_association->include_shipping == 1) {
            $new_association_data['include_shipping'] = $_association->include_shipping;
        }
        $new_association_data['show_shipping_on_association'] = $_association->show_shipping_on_association;
        // Set to unpaid status automatically
        $new_association_data['status']     = 1;
        $new_association_data['clientnote'] = $_association->clientnote;
        $new_association_data['adminnote']  = '';
        $new_association_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;

        foreach ($_association->items as $item) {
            $new_association_data['newitems'][$key]['description']      = $item['description'];
            $new_association_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_association_data['newitems'][$key]['qty']              = $item['qty'];
            $new_association_data['newitems'][$key]['unit']             = $item['unit'];
            $new_association_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_association_item_taxes($item['id']);

            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_association_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_association_data['newitems'][$key]['rate']  = $item['rate'];
            $new_association_data['newitems'][$key]['order'] = $item['item_order'];

            foreach ($custom_fields_items as $cf) {
                $new_association_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (! defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_association_data);
        if ($id) {
            $custom_fields = get_custom_fields('association');

            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_association->id, $field['id'], 'association', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'association',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_association->id, 'association');
            handle_tags_save($tags, $id, 'association');

            log_activity('Copied Association ' . format_association_number($_association->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs associations totals status
     *
     * @param array $data
     *
     * @return array
     */
    public function get_associations_total($data)
    {
        $statuses            = $this->get_statuses();
        $has_permission_view = staff_can('view', 'associations');
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['association_id']) && $data['association_id'] != '') {
            $currencyid = $this->clients_model->get_association_default_currency($data['association_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $currency = get_currency($currencyid);
        $where    = '';
        if (isset($data['association_id']) && $data['association_id'] != '') {
            $where = ' AND clientid=' . $data['association_id'];
        }

        if (isset($data['project_id']) && $data['project_id'] != '') {
            $where .= ' AND project_id=' . $data['project_id'];
        }

        if (! $has_permission_view) {
            $where .= ' AND ' . get_associations_where_sql_for_staff(get_staff_user_id());
        }

        $sql = 'SELECT';

        foreach ($statuses as $association_status) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'associations WHERE status=' . $association_status;
            $sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $association_status . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;

        foreach ($result as $key => $val) {
            foreach ($val as $status => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['status']        = $status;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * Insert new association to database
     *
     * @param array $data invoiec data
     *
     * @return mixed - false if not insert, association ID if succes
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $data['prefix'] = get_option('association_prefix');

        $data['number_format'] = get_option('association_number_format');

        $save_and_send = isset($data['save_and_send']);

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();
        $tags         = $data['tags'] ?? '';
        unset($data['tags']);

        // Default requestor to logged-in staff if not set
        if (empty($data['requestor_id'])) {
            $data['requestor_id'] = get_staff_user_id();
        }

        // Always use base currency — currency field not shown in form
        $this->load->model('currencies_model');
        $data['currency'] = $this->currencies_model->get_base_currency()->id;

        // Unset removed/unused form fields
        unset($data['discount_type'], $data['discount_percent'], $data['discount_total']);

        foreach (_get_sales_feature_unused_names() as $name) {
            unset($data[$name]);
        }

        $equipment = [];
        if (isset($data['equipment'])) {
            $equipment = $data['equipment'];
            unset($data['equipment']);
        }

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $hook = hooks()->apply_filters('before_association_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'associations', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->save_formatted_number($insert_id);

            // Update next association number in settings
            $this->db->where('name', 'next_association_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');



            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'association');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'association')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'association');
                }
            }

            $this->save_association_equipment($insert_id, $equipment);
            $this->log_association_activity($insert_id, 'association_activity_created');

            hooks()->do_action('after_association_added', $insert_id);

            if ($save_and_send === true) {
                $this->send_association_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    public function get_association_equipment($association_id)
    {
        return $this->db
            ->select('re.association_equipment_id, ce.unit_code, ce.serial_number, ce.location, ce.cert_expired_date, i.description as item_name')
            ->from(db_prefix() . 'association_doc_equipment re')
            ->join(db_prefix() . 'association_equipment ce', 'ce.id = re.association_equipment_id')
            ->join(db_prefix() . 'items i', 'i.id = ce.item_id')
            ->where('re.association_id', $association_id)
            ->get()->result_array();
    }

    public function save_association_equipment($association_id, $equipment)
    {
        $this->db->where('association_id', $association_id)->delete(db_prefix() . 'association_doc_equipment');
        if (empty($equipment)) { return; }
        foreach ($equipment as $row) {
            $eq_id = (int) ($row['association_equipment_id'] ?? 0);
            if ($eq_id > 0) {
                $this->db->insert(db_prefix() . 'association_doc_equipment', [
                    'association_id'          => $association_id,
                    'association_equipment_id' => $eq_id,
                ]);
            }
        }
    }

    /**
     * Get item by id
     *
     * @param mixed $id item id
     *
     * @return object
     */
    public function get_association_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    /**
     * Update association data
     *
     * @param array $data association data
     * @param mixed $id   associationid
     *
     * @return bool
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['number'] = trim($data['number']);

        $original_association = $this->get($id);

        $original_status = $original_association->status;

        $original_number = $original_association->number;

        $original_number_formatted = format_association_number($id);

        $save_and_send = isset($data['save_and_send']);

        $equipment = [];
        if (isset($data['equipment'])) {
            $equipment = $data['equipment'];
            unset($data['equipment']);
        }

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'association')) {
                $affectedRows++;
            }
            unset($data['tags']);
        }

        // Removed columns — unset to prevent update errors
        unset($data['currency'], $data['discount_type'], $data['discount_percent'], $data['discount_total']);

        foreach (_get_sales_feature_unused_names() as $name) {
            unset($data[$name]);
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_association_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => $data['removed_items'] ?? [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_association_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'association')) {
                $affectedRows++;
                $this->log_association_activity($id, 'association_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'associations', $data);

        $this->save_formatted_number($id);

        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_association_activity($original_association->id, 'not_association_status_updated', false, serialize([
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>',
                ]));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'associations', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_association_activity($original_association->id, 'association_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_association_number($original_association->id),
                ]));
            }
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            $item = array_merge($item, [
                'is_optional' => $isOptional = isset($item['is_optional']) ? 1 : 0,
                'is_selected' => ! $isOptional ? 1 : (isset($item['is_selected']) ? 1 : 0),
            ]);
            $original_item = $this->get_association_item($item['itemid']);

            if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                $this->log_association_activity($id, 'association_activity_updated_item_rate', false, serialize([
                    $original_item->rate,
                    $item['rate'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                $this->log_association_activity($id, 'association_activity_updated_qty_item', false, serialize([
                    $item['description'],
                    $original_item->qty,
                    $item['qty'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'description')) {
                $this->log_association_activity($id, 'association_activity_updated_item_short_description', false, serialize([
                    $original_item->description,
                    $item['description'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                $this->log_association_activity($id, 'association_activity_updated_item_long_description', false, serialize([
                    $original_item->long_description,
                    $item['long_description'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'is_optional')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'is_selected')) {
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (! isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'association')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_association_item_taxes($item['itemid']);
                $_item_taxes_names = [];

                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }

                $i = 0;

                foreach ($_item_taxes_names as $_item_tax) {
                    if (! in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'association')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'association')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'association');
                $this->log_association_activity($id, 'association_activity_added_item', false, serialize([
                    $item['description'],
                ]));
                $affectedRows++;
            }
        }

        $this->save_association_equipment($id, $equipment);

        if ($save_and_send === true) {
            $this->send_association_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_association_updated', $id);

            return true;
        }

        return false;
    }

    public function mark_action_status($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'associations', [
            'status' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $association = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $association->addedfrom);
                $this->db->or_where('staffid', $association->sale_agent);
                $staff_association = $this->db->get(db_prefix() . 'staff')->result_array();

                $quotationid = false;
                $quoted    = false;

                $contact_id = ! is_client_logged_in()
                    ? get_primary_contact_user_id($association->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('association_auto_convert_to_quotation_on_client_accept') == 1) {
                        $quotationid = $this->convert_to_quotation($id);
                        if ($quotationid) {
                            $quoted = true;
                            $this->log_association_activity($id, 'association_activity_surveyor_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('quotations/list_quotations/' . $quotationid) . '">' . format_quotation_number($quotationid) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_association_activity($id, 'association_activity_surveyor_accepted', true);
                    }

                    // Send thank you email to all contacts with permission associations
                    $contacts = $this->clients_model->get_contacts($association->clientid, ['active' => 1, 'association_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('association_accepted_to_association', 'associations', $association, $contact);
                    }

                    foreach ($staff_association as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_association_surveyor_accepted',
                            'link'            => 'associations/list_associations/' . $id,
                            'additional_data' => serialize([
                                format_association_number($association->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('association_accepted_to_staff', 'associations', $association, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('association_accepted', $id);

                    return [
                        'quoted'      => $quoted,
                        'quotationid' => $quotationid,
                    ];
                }
                if ($action == 3) {
                    foreach ($staff_association as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_association_surveyor_declined',
                            'link'            => 'associations/list_associations/' . $id,
                            'additional_data' => serialize([
                                format_association_number($association->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that association declined association
                        send_mail_template('association_declined_to_staff', 'associations', $association, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_association_activity($id, 'association_activity_surveyor_declined', true);
                    hooks()->do_action('association_declined', $id);

                    return [
                        'quoted'      => $quoted,
                        'quotationid' => $quotationid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'associations', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked association
                $this->log_association_activity($id, 'association_activity_marked', false, serialize([
                    '<status>' . $action . '</status>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get association attachments
     *
     * @param mixed  $association_id
     * @param string $id          attachment id
     *
     * @return mixed
     */
    public function get_attachments($association_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $association_id);
        }
        $this->db->where('rel_type', 'association');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete association attachment
     *
     * @param mixed $id attachmentid
     *
     * @return bool
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('association') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Association Attachment Deleted [AssociationID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('association') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('association') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('association') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete association items and all connections
     *
     * @param mixed $id           associationid
     * @param mixed $simpleDelete
     *
     * @return bool
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_association') == 1 && $simpleDelete == false) {
            if (! is_last_association($id)) {
                return false;
            }
        }
        $association = $this->get($id);
        if (! is_null($association->quotationid) && $simpleDelete == false) {
            return [
                'is_quoted_association_delete_error' => true,
            ];
        }
        hooks()->do_action('before_association_deleted', $id);

        $number = format_association_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'associations');

        if ($this->db->affected_rows() > 0) {
            if (! is_null($association->short_link)) {
                app_archive_short_link($association->short_link);
            }

            if (get_option('association_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_association_number = get_option('next_association_number');
                if ($current_next_association_number > 1) {
                    // Decrement next association number to
                    $this->db->where('name', 'next_association_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                'association_id' => $id,
            ]) > 0) {
                $this->db->where('association_id', $id);
                $association = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $association->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'association_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'association');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="association" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'association');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'association');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'association');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'association');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'association');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'association');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'association');
            $this->db->delete(db_prefix() . 'sales_activity');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'association');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $attachments = $this->get_attachments($id);

            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'association');
            $this->db->delete('scheduled_emails');


            if ($simpleDelete == false) {
                log_activity('Associations Deleted [Number: ' . $number . ']');
            }

            hooks()->do_action('after_association_deleted', $id);

            return true;
        }

        return false;
    }

    public function save_formatted_number($id)
    {
        $formattedNumber = format_association_number($id);

        $this->db->where('id', $id);
        $this->db->update('associations', ['formatted_number' => $formattedNumber]);
    }

    /**
     * Set association to sent when email is successfuly sended to client
     *
     * @param mixed $id          associationid
     * @param mixed $emails_sent
     */
    public function set_association_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'associations', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_association_activity($id, 'association_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update association status to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'associations', [
            'status' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'association');
        $this->db->delete('scheduled_emails');
    }

    /**
     * Send expiration reminder to association
     *
     * @param mixed $id association id
     *
     * @return bool
     */
    public function send_expiry_reminder($id)
    {
        $association        = $this->get($id);
        $association_number = format_association_number($association->id);
        set_mailing_constant();
        $pdf              = association_pdf($association);
        $attach           = $pdf->Output($association_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'associations', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($association->clientid, ['active' => 1, 'association_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('association_expiration_reminder', 'associations', $association, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $association_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($association->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ASSOCIATION_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_association_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_association_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send association to client
     *
     * @param mixed  $id            associationid
     * @param string $template      email template to sent
     * @param bool   $attachpdf     attach association pdf or not
     * @param mixed  $template_name
     * @param mixed  $cc
     * @param mixed  $manually
     *
     * @return bool
     */
    public function send_association_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $association = $this->get($id);

        if ($template_name == '') {
            $template_name = $association->sent == 0 ?
                'association_send_to_surveyor' :
                'association_send_to_surveyor_already_sent';
        }

        $association_number = format_association_number($association->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the association via add/edit area button Save & Send
        if (! defined('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['scheduled_email_contacts'])) {
            $send_to = $GLOBALS['scheduled_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $association->clientid,
                ['active' => 1, 'association_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $status_auto_updated = false;
        $status_now          = $association->status;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update status to sent in case when user sends the association is with status draft
            if ($status_now == 1) {
                $this->db->where('id', $association->id);
                $this->db->update(db_prefix() . 'associations', [
                    'status' => 2,
                ]);
                $status_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_association = $this->get($association->id);
                set_mailing_constant();
                $pdf = association_pdf($_pdf_association);

                $attach = $pdf->Output($association_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (! empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (! $contact) {
                        continue;
                    }

                    $template = mail_template($template_name, 'associations', $association, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_association_to_association_file_name', [
                            'file_name' => str_replace('/', '-', $association_number . '.pdf'),
                            'association'  => $_pdf_association,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_association_sent($id, $emails_sent);
            hooks()->do_action('association_sent', $id);

            return true;
        }

        if ($status_auto_updated) {
            // Association not send to association but the status was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $association->id);
            $this->db->update(db_prefix() . 'associations', [
                'status' => 1,
            ]);
        }

        return false;
    }

    /**
     * All association activity
     *
     * @param mixed $id associationid
     *
     * @return array
     */
    public function get_association_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'association');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log association activity to database
     *
     * @param mixed  $id              associationid
     * @param string $description     activity description
     * @param mixed  $client
     * @param mixed  $additional_data
     */
    public function log_association_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (defined('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'association',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     *
     * @param mixe $data $_POST data
     *
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['associationid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'associations', $data['status']);
    }

    /**
     * Get association unique year for filtering
     *
     * @return array
     */
    public function get_associations_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'associations ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (! isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_association'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_association']) && ($data['show_shipping_on_association'] == 1 || $data['show_shipping_on_association'] == 'on')) {
                $data['show_shipping_on_association'] = 1;
            } else {
                $data['show_shipping_on_association'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('Associations_model::do_kanban_query', '2.9.2', 'AssociationsPipeline class');

        $kanBan = (new AssociationsPipeline($status))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }
}
