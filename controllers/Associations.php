<?php

use app\services\associations\AssociationsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Associations extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('associations_model');
    }

    /* Get all associations in case user go on index page */
    public function index($id = '')
    {
        $this->list_associations($id);
    }

    /* List all associations datatables */
    public function list_associations($id = '')
    {
        if (staff_cant('view', 'associations') && staff_cant('view_own', 'associations') && get_option('allow_staff_view_associations_assigned') == '0') {
            access_denied('associations');
        }

        $isPipeline = $this->session->userdata('association_pipeline') == 'true';

        $data['association_statuses'] = $this->associations_model->get_statuses();
        $data['associations_table'] = App_table::find('associations');
        
        if ($isPipeline && !$this->input->get('status') && !$this->input->get('filter')) {
            $data['title']           = _l('associations_pipeline');
            $data['bodyclass']       = 'associations-pipeline associations-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['associationid'] = $id;
            } else {
                $data['associationid'] = $this->session->flashdata('associationid');
            }

            $this->load->view('admin/associations/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('status') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['associationid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('associations');
            $data['bodyclass']             = 'associations-total-manual';
            $this->load->view('admin/associations/manage', $data);
        }
    }

    public function table()
    {
        if (!$this->input->is_ajax_request()) { ajax_access_denied(); }

        $_me = get_staff(get_staff_user_id());
        $owner_client_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;
        if (!$owner_client_id && staff_cant('view', 'associations') && !has_permission('associations', '', 'view_own')) {
            ajax_access_denied();
        }

        App_table::find('associations')->output([]);
    }

    public function search_surveyors()
    {
        if (!$this->input->is_ajax_request()) { ajax_access_denied(); }

        $client_id = (int) $this->input->get_post('client_id');
        $q         = $this->input->get_post('q');

        $this->db
            ->select('c.userid as id, c.company as name')
            ->from(db_prefix() . 'clients c');

        if ($client_id) {
            $this->db
                ->join(db_prefix() . 'client_connections cc',
                    '(cc.client_id_a = ' . $client_id . ' AND cc.client_id_b = c.userid)
                    OR (cc.client_id_b = ' . $client_id . ' AND cc.client_id_a = c.userid)')
                ->where('cc.status', 'active');
        }

        $this->db->where('c.client_type', 'surveyor');

        if ($q) {
            $this->db->like('c.company', $q);
        }

        echo json_encode($this->db->get()->result_array());
    }

    public function get_equipment_data($id = '')
    {
        if (!$this->input->is_ajax_request()) { ajax_access_denied(); }
        if (staff_cant('view', 'associations')) { ajax_access_denied(); }

        $id = (int) $id;
        $row = $this->db
            ->select('ce.id, ce.unit_code, ce.serial_number, ce.location, ce.cert_expired_date, i.description as item_name')
            ->from(db_prefix() . 'association_equipment ce')
            ->join(db_prefix() . 'items i', 'i.id = ce.item_id')
            ->where('ce.id', $id)
            ->get()->row_array();

        echo json_encode($row ?: []);
    }

    public function add_edit_association()
    {
        if (!$this->input->post()) {
            redirect(admin_url('associations'));
        }

        $postData = $this->input->post();

        $map_lat  = $this->input->post('map_latitude');
        $map_lng  = $this->input->post('map_longitude');
        $map_addr = $this->input->post('map_address') ?? '';

        foreach (['isedit', 'save_and_send_later', 'userid',
                  'map_latitude', 'map_longitude', 'map_address'] as $_strip) {
            unset($postData[$_strip]);
        }

        if (!empty($this->input->post('userid'))) {
            $id  = (int) $this->input->post('userid');
            $me  = get_staff(get_staff_user_id());
            if (staff_cant('edit', 'associations') && !$this->_can_edit_own($id)) {
                access_denied('associations');
            }

            $vat = isset($postData['vat']) ? trim($postData['vat']) : '';
            if ($vat !== '' && !$this->associations_model->is_vat_available($vat, $id)) {
                set_alert('danger', _l('vat_already_exists'));
                redirect(admin_url('associations/association/' . $id));
                return;
            }

            // Snapshot old values before update
            $old = $this->db->get_where(db_prefix() . 'clients', ['userid' => $id])->row_array();

            $postData['enforce_minimum_price'] = isset($postData['enforce_minimum_price']) ? 1 : 0;

            $this->associations_model->update($id, $postData);
            $diff = $this->associations_model->build_diff($old, $postData, [
                'company'               => _l('client_company'),
                'vat'                   => _l('client_vat_number'),
                'phonenumber'           => _l('client_phonenumber'),
                'website'               => _l('client_website'),
                'address'               => _l('client_address'),
                'state'                 => _l('client_state'),
                'city'                  => _l('client_city'),
                'zip'                   => _l('client_postal_code'),
                'enforce_minimum_price' => _l('association_enforce_minimum_price'),
            ]);
            $this->associations_model->log_activity($id, 'association_activity_updated', $diff);
            if ($map_lat !== false && $map_lat !== '' && $map_lng !== false && $map_lng !== '') {
                save_entity_coordinates((int) $id, 'association', (float) $map_lat, (float) $map_lng, (string) $map_addr);
            }
            set_alert('success', _l('association_saved'));
            $this->_handle_entity_files($id);
            redirect(admin_url('associations/list_associations/' . $id));

        } else {
            if (staff_cant('create', 'associations')) {
                access_denied('associations');
            }

            $vat = isset($postData['vat']) ? trim($postData['vat']) : '';
            if ($vat !== '' && !$this->associations_model->is_vat_available($vat)) {
                set_alert('danger', _l('vat_already_exists'));
                redirect(admin_url('associations/association'));
                return;
            }

            $postData['client_type'] = 'association';
            $id = $this->associations_model->add($postData);
            if ($id) {
                $this->_handle_entity_files((int) $id);
                $this->associations_model->log_activity((int) $id, 'association_activity_created', $postData['company'] ?? '');
                if ($map_lat !== false && $map_lat !== '' && $map_lng !== false && $map_lng !== '') {
                    save_entity_coordinates((int) $id, 'association', (float) $map_lat, (float) $map_lng, (string) $map_addr);
                }
                set_alert('success', _l('association_saved'));
                redirect(admin_url('associations/list_associations/' . $id));
            } else {
                redirect(admin_url('associations/association'));
            }
        }
    }

    /* Add new association or update existing */
    public function association($id = '')
    {
        if ($this->input->post()) {
            $association_data = $this->input->post();

            // Strip fields not in tblclients
            foreach (['isedit', 'save_and_send_later', 'userid'] as $_strip) {
                unset($association_data[$_strip]);
            }

            $save_and_send_later = false;

            if ($id == '') {
                $me = get_staff(get_staff_user_id());
                if (staff_cant('create', 'associations')) {
                    access_denied('associations');
                }
                $id = $this->associations_model->add($association_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('association')));

                    $redUrl = admin_url('associations/list_associations/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_association_pipeline_autoload($id) ? $redUrl : admin_url('associations/list_associations/')
                    );
                }
            } else {
                $me = $me ?? get_staff(get_staff_user_id());
                if (staff_cant('edit', 'associations') && !$this->_can_edit_own((int) $id)) {
                    access_denied('associations');
                }
                // Association may only submit edit while ASSOCIATION is still Draft
                if (($me->client_type ?? '') === 'association') {
                    $_association_chk = $this->associations_model->get((int)$id);
                    if (!$_association_chk) {
                        access_denied('associations');
                    }
                }
                $success = $this->associations_model->update($id, $association_data);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('association')));
                }
                if ($this->set_association_pipeline_autoload($id)) {
                    redirect(admin_url('associations/list_associations/'));
                } else {
                    redirect(admin_url('associations/list_associations/' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_association');
        } else {
            $association = $this->associations_model->get($id);

            if (!$association || !user_can_view_association($id)) {
                log_message('error', '[association()] id=' . $id
                    . ' association=' . ($association ? 'found' : 'null')
                    . ' can_view=' . (user_can_view_association($id) ? 'true' : 'false')
                    . ' uri=' . $this->uri->uri_string());
                blank_page(_l('association_not_found'));
            }

            $data['association'] = $association;
            $data['client']   = $association;
            $data['edit']     = true;
            $title            = _l('edit', _l('association'));
        }

        if ($this->input->get('association_id')) {
            $data['association_id'] = $this->input->get('association_id');
        } elseif (!isset($data['association'])) {
            // Auto-fill client_id from logged-in association staff
            $logged = $this->db->get_where(db_prefix() . 'staff', ['staffid' => get_staff_user_id()])->row();
            if ($logged && !empty($logged->client_id)) {
                $data['association_id'] = (int) $logged->client_id;
            }
        }

        // Pre-load equipment units from bulk select (copy-estimate pattern)
        $preset_equipment = [];
        $ceids_raw = $this->input->get('association_equipment_ids');
        if (!empty($ceids_raw)) {
            $ceids = array_filter(array_map('intval', explode(',', $ceids_raw)));
            if (!empty($ceids)) {
                $preset_equipment = $this->db
                    ->select('ce.id, ce.unit_code, ce.serial_number, ce.location, ce.cert_expired_date, i.description as item_name')
                    ->from(db_prefix() . 'association_equipment ce')
                    ->join(db_prefix() . 'items i', 'i.id = ce.item_id')
                    ->where_in('ce.id', $ceids)
                    ->get()->result_array();
            }
        }
        $data['preset_equipment'] = $preset_equipment;

        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        // Load requestor options: same company staff if association, all active staff if admin
        $logged_staff = $this->db->get_where(db_prefix() . 'staff', ['staffid' => get_staff_user_id()])->row();
        $client_id    = ($logged_staff && !empty($logged_staff->client_id)) ? (int) $logged_staff->client_id : 0;
        if ($client_id) {
            $data['requestor_staff'] = $this->db
                ->where('client_id', $client_id)
                ->where('active', 1)
                ->get(db_prefix() . 'staff')->result_array();
        } else {
            $data['requestor_staff'] = $this->staff_model->get('', ['active' => 1]);
        }

        $this->load->model('invoice_items_model');

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['association_statuses'] = $this->associations_model->get_statuses();
        $data['title']             = $title;
        $this->load->view('admin/associations/association', $data);
    }

    // ── File uploads: logo ────────────────────────────────────────────────────

    private function _sanitize_filename(string $name): string
    {
        $name = sanitize_file_name($name); // Perfex: strip dangerous chars
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = strtolower(pathinfo($name, PATHINFO_FILENAME));
        $base = preg_replace('/[^a-z0-9_\-]/', '_', $base); // spaces → underscore
        $base = trim($base, '_');
        return ($base ?: 'file') . '.' . $ext;
    }

    private function _handle_entity_files(int $id)
    {
        $logo_path = FCPATH . 'uploads/client_logos/' . $id . '/';
        _maybe_create_upload_path($logo_path);

        foreach (['logo_light', 'logo_dark'] as $field) {
            if (empty($_FILES[$field]['name'])) { continue; }
            if (_perfex_upload_error($_FILES[$field]['error'])) { continue; }
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) { continue; }
            $old = $this->db->select($field)->get_where(db_prefix() . 'clients', ['userid' => $id])->row();
            if ($old && !empty($old->$field) && file_exists($logo_path . $old->$field)) {
                unlink($logo_path . $old->$field);
            }
            $filename = unique_filename($logo_path, $this->_sanitize_filename($_FILES[$field]['name']));
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $logo_path . $filename)) {
                $this->db->where('userid', $id)->update(db_prefix() . 'clients', [$field => $filename]);
            }
        }

    }

    public function upload_logo($id, $type = 'light')
    {
        header('Content-Type: application/json');
        if (staff_cant('edit', 'associations') && !$this->_can_edit_own((int) $id)) {
            echo json_encode(['success' => false, 'message' => 'Access denied']); die();
        }
        $id  = (int) $id;
        $col = 'logo_' . (in_array($type, ['light', 'dark']) ? $type : 'light');

        if (empty($_FILES['file']['name'])) {
            echo json_encode(['success' => false, 'message' => 'No file received']); die();
        }
        if (_perfex_upload_error($_FILES['file']['error'])) {
            echo json_encode(['success' => false, 'message' => _perfex_upload_error($_FILES['file']['error'])]); die();
        }
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo json_encode(['success' => false, 'message' => 'Extension not allowed.']); die();
        }
        $path = FCPATH . 'uploads/client_logos/' . $id . '/';
        _maybe_create_upload_path($path);
        $old = $this->db->select($col)->get_where(db_prefix() . 'clients', ['userid' => $id])->row();
        if ($old && !empty($old->$col) && file_exists($path . $old->$col)) {
            unlink($path . $old->$col);
        }
        $filename = unique_filename($path, $this->_sanitize_filename($_FILES['file']['name']));
        if (move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename)) {
            $this->db->where('userid', $id)->update(db_prefix() . 'clients', [$col => $filename]);
            echo json_encode(['success' => true, 'url' => base_url('uploads/client_logos/' . $id . '/' . $filename)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
        die();
    }

    public function delete_logo($id, $type = 'light')
    {
        if (staff_cant('edit', 'associations') && !$this->_can_edit_own((int) $id)) {
            redirect(admin_url('associations/list_associations/' . $id));
        }
        $id  = (int) $id;
        $col = 'logo_' . (in_array($type, ['light', 'dark']) ? $type : 'light');
        $row  = $this->db->select($col)->get_where(db_prefix() . 'clients', ['userid' => $id])->row();
        $path = FCPATH . 'uploads/client_logos/' . $id . '/';
        if ($row && !empty($row->$col) && file_exists($path . $row->$col)) {
            unlink($path . $row->$col);
        }
        $this->db->where('userid', $id)->update(db_prefix() . 'clients', [$col => null]);
        redirect(admin_url('associations/association/' . $id . '?tab=general'));
    }

    public function clear_signature($id)
    {
        if (staff_can('delete',  'associations')) {
            $this->associations_model->clear_signature($id);
        }

        redirect(admin_url('associations/list_associations/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        
        if (staff_can('edit',  'associations')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'associations', [
                'prefix' => $this->input->post('prefix'),
            ]);

            if ($this->db->affected_rows() > 0) {
                $this->associations_model->save_formatted_number($id);

                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('association'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_association_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'associations', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->associations_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all association data used when user click on association number in a datatable left side*/
    public function get_association_data_ajax($id)
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('associations'));
        }

        if (staff_cant('view', 'associations') && !$this->_can_view_own((int) $id)) {
            ajax_access_denied();
        }

        $can_view = hooks()->apply_filters('can_view_association_profile', true, (int) $id);
        if (!$can_view) {
            $this->load->view('associations/admin/associations/not_connected');
            return;
        }

        if (!$id || !is_numeric($id)) {
            show_404();
        }

        $data['association'] = $this->db->get_where(db_prefix() . 'clients', ['userid' => $id])->row();
        if (!$data['association']) {
            show_404();
        }

        $country = null;
        if (!empty($data['association']->country)) {
            $country = $this->db->get_where(db_prefix() . 'countries', ['country_id' => $data['association']->country])->row();
        }
        $data['country_name'] = $country ? $country->short_name : '';
        $data['is_own']       = $this->_can_view_own((int) $id) && staff_cant('view', 'associations');
        $data['totalNotes']   = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'association']);
        $data['members']      = $this->staff_model->get('', ['active' => 1]);
        $data['activity']     = $this->associations_model->get_activity($id);

        $data['my_registration'] = null;
        $_me_preview = get_staff(get_staff_user_id());
        if ($_me_preview && $_me_preview->client_type === 'surveyor' && !empty($_me_preview->client_id)) {
            if ($_me_preview->is_entity_owner == 1 || $_me_preview->is_branch_owner == 1) {
                $data['my_registration'] = $this->db->get_where(db_prefix() . 'surveyors_associations', [
                    'surveyor_id'    => (int) $_me_preview->client_id,
                    'association_id' => (int) $id,
                ])->row();
                $data['can_self_register'] = true;
            }
        }

        $data['coordinates'] = get_entity_coordinates((int) $id, 'association');

        $this->load->view('admin/associations/association_preview_template', $data);
    }

    public function get_associations_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->associations_model->get_associations_total($this->input->post());

            unset($data['totals']['currencyid']);
            $this->load->view('admin/associations/associations_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_association($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'association', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_association($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'association');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_status($status, $id)
    {
        $_me_ma  = get_staff(get_staff_user_id());
        $_ct_ma  = $_me_ma ? ($_me_ma->client_type ?? '') : '';
        $_status = (int) $status;
        if ($_ct_ma === 'association') {
            if (!in_array($_status, [ASSOCIATION_STATUS_DRAFT, ASSOCIATION_STATUS_SENT])) {
                access_denied('associations');
            }
        } elseif ($_ct_ma === 'surveyor') {
            if (!in_array($_status, [ASSOCIATION_STATUS_DECLINED, ASSOCIATION_STATUS_ACCEPTED, ASSOCIATION_STATUS_EXPIRED])) {
                access_denied('associations');
            }
        } elseif (staff_cant('mark_as', 'associations')) {
            access_denied('associations');
        }
        $success = $this->associations_model->mark_action_status($status, $id);

        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success'     => (bool) $success,
                'message'     => $success ? _l('association_status_changed_success') : _l('association_status_changed_fail'),
                'status_html' => $success ? format_association_status($status, 'mtop5 inline-block') : '',
            ]);
            return;
        }

        if ($success) {
            set_alert('success', _l('association_status_changed_success'));
        } else {
            set_alert('danger', _l('association_status_changed_fail'));
        }
        if ($this->set_association_pipeline_autoload($id)) {
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('associations/list_associations/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_association($id);
        if (!$canView) {
            access_denied('Associations');
        } else {
            if (staff_cant('view', 'associations') && staff_cant('view_own', 'associations') && $canView == false) {
                access_denied('Associations');
            }
        }

        $success = $this->associations_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_association_pipeline_autoload($id)) {
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('associations/list_associations/' . $id));
        }
    }

    /* Send association to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_association($id);
        if (!$canView) {
            access_denied('associations');
        } else {
            if (staff_cant('view', 'associations') && staff_cant('view_own', 'associations') && $canView == false) {
                access_denied('associations');
            }
        }

        try {
            $success = $this->associations_model->send_association_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('association_sent_to_client_success'));
        } else {
            set_alert('danger', _l('association_sent_to_client_fail'));
        }
        if ($this->set_association_pipeline_autoload($id)) {
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('associations/list_associations/' . $id));
        }
    }

    /* Convert ASSOCIATION to Quotation (surveyor adds prices per equipment item) */
    public function convert_to_quotation($id)
    {
        if (staff_cant('convert_to_quotation', 'associations')) {
            access_denied('associations');
        }
        // Only allowed when ASSOCIATION is Sent
        $_association_cq = $this->associations_model->get((int)$id);
        if (!$_association_cq || (int)$_association_cq->status !== ASSOCIATION_STATUS_SENT) {
            set_alert('danger', _l('association_convert_to_quotation_invalid_status'));
            redirect(admin_url('associations/list_associations/' . $id));
        }
        if (!$id) {
            redirect(admin_url('associations'));
        }
        $quotation_id = $this->associations_model->convert_to_quotation($id);
        if ($quotation_id) {
            set_alert('success', _l('association_converted_to_quotation_successfully'));
            redirect(admin_url('quotations/quotation/' . $quotation_id));
        } else {
            set_alert('danger', _l('association_converted_to_quotation_failed'));
            redirect(admin_url('associations/list_associations/' . $id));
        }
    }

    public function copy($id)
    {
        if (staff_cant('create', 'associations')) {
            access_denied('associations');
        }
        if (!$id) {
            die('No association found');
        }
        $new_id = $this->associations_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('association_copied_successfully'));
            if ($this->set_association_pipeline_autoload($new_id)) {
                redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('associations/association/' . $new_id));
            }
        }
        set_alert('danger', _l('association_copied_fail'));
        if ($this->set_association_pipeline_autoload($id)) {
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('associations/association/' . $id));
        }
    }

    /* Delete association */
    public function delete($id)
    {
        if (staff_cant('delete', 'associations')) {
            access_denied('associations');
        }
        if (!$id) {
            redirect(admin_url('associations/list_associations'));
        }
        $success = $this->associations_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_quoted_association_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('association')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('association_lowercase')));
        }
        redirect(admin_url('associations/list_associations'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'associations', get_acceptance_info_array(true));
        }

        redirect(admin_url('associations/list_associations/' . $id));
    }

    /* Generates association PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_association($id);
        if (!$canView) {
            access_denied('Associations');
        } else {
            if (staff_cant('view', 'associations') && staff_cant('view_own', 'associations') && $canView == false) {
                access_denied('Associations');
            }
        }
        if (!$id) {
            redirect(admin_url('associations/list_associations'));
        }
        $association        = $this->associations_model->get($id);
        $association_number = e($association->company);

        try {
            $pdf = association_pdf($association);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('association_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($association_number)) . '.pdf',
                            'association'  => $association,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (staff_can('view',  'associations') || staff_can('view_own',  'associations') || get_option('allow_staff_view_associations_assigned') == '1') {
            $data['association_statuses'] = $this->associations_model->get_statuses();
            $this->load->view('admin/associations/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_association($id);
        if (!$canView) {
            access_denied('Associations');
        } else {
            if (staff_cant('view', 'associations') && staff_cant('view_own', 'associations') && $canView == false) {
                access_denied('Associations');
            }
        }

        $data['id']       = $id;
        $data['association'] = $this->get_association_data_ajax($id, true);
        $this->load->view('admin/associations/pipeline/association', $data);
    }

    public function update_pipeline()
    {
        if (staff_can('edit',  'associations')) {
            $this->associations_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'association_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('associations/list_associations'));
        }
    }

    public function pipeline_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $associations = (new AssociationsPipeline($status))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($associations as $association) {
            $this->load->view('admin/associations/pipeline/_kanban_card', [
                'association' => $association,
                'status'   => $status,
            ]);
        }
    }

    public function set_association_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('association_pipeline')
                && $this->session->userdata('association_pipeline') == 'true') {
            $this->session->set_flashdata('associationid', $id);

            return true;
        }

        return false;
    }


    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('association_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('association_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }

    private function _can_view_own(int $client_id): bool
    {
        if (!$this->_is_own_entity($client_id, 'association')) { return false; }
        $me = get_staff(get_staff_user_id());
        if ($me && $me->client_type === 'association') { return true; }
        return has_permission('associations', '', 'view_own');
    }

    private function _can_edit_own(int $client_id): bool
    {
        return can_do_on_entity('edit', 'associations', $client_id, 'association');
    }

    private function _is_own_entity($client_id, $client_type)
    {
        if (!get_staff_user_id()) { return false; }
        $staff = $this->db->get_where(db_prefix() . 'staff', [
            'staffid'     => get_staff_user_id(),
            'client_type' => $client_type,
        ])->row();
        if (!$staff || !$staff->client_id) { return false; }
        $my_client_id = (int) $staff->client_id;
        $client_id    = (int) $client_id;
        if ($my_client_id === $client_id) { return true; }
        $branch = $this->db->get_where(db_prefix() . 'clients', [
            'userid'      => $client_id,
            'company_id'  => $my_client_id,
            'client_type' => $client_type,
        ])->row();
        if ($branch) { return true; }
        $me_client = $this->db->get_where(db_prefix() . 'clients', ['userid' => $my_client_id])->row();
        if ($me_client && (int) $me_client->company_id === $client_id) { return true; }
        return false;
    }

    // ─── Registration Approval ────────────────────────────────────────────────

    public function pending_approvals()
    {
        if (!is_admin()) { access_denied('associations'); }

        // Stage 1: newly registered, user not yet activated
        $data['stage1'] = $this->db
            ->select('s.staffid, s.firstname, s.lastname, s.email, s.datecreated, s.client_id, c.company, c.vat')
            ->from(db_prefix() . 'staff s')
            ->join(db_prefix() . 'clients c', 'c.userid = s.client_id', 'left')
            ->where('s.client_type', 'association')
            ->where('s.registration_status', 'pending')
            ->order_by('s.datecreated', 'DESC')
            ->get()->result_array();

        // Stage 2: user active but company still inactive — regardless of how user was created
        $rows = $this->db
            ->select('s.staffid, s.firstname, s.lastname, s.email, s.datecreated, s.client_id,
                      c.company, c.vat, c.phonenumber, c.state, c.city, c.address,
                      c.billing_street, c.billing_city, c.billing_state,
                      c.logo_light, c.logo_dark')
            ->from(db_prefix() . 'staff s')
            ->join(db_prefix() . 'clients c', 'c.userid = s.client_id', 'left')
            ->where('s.client_type', 'association')
            ->where('s.active', 1)
            ->where('s.is_entity_owner', 1)
            ->where('c.active', 0)
            ->where('s.registration_status !=', 'rejected')
            ->where('s.registration_status !=', 'pending')
            ->order_by('s.datecreated', 'DESC')
            ->get()->result_array();

        foreach ($rows as &$row) {
            $row['_checks'] = $this->_completeness_checks($row);
            $row['_ready']  = !in_array(false, array_column($row['_checks'], 'ok'));
        }
        unset($row);
        $data['stage2'] = $rows;

        $data['title'] = _l('pending_registrations');
        $this->load->view('associations/admin/associations/pending_approvals', $data);
    }

    // Stage 1: activate user account, company stays inactive
    public function activate_user($staff_id)
    {
        if (!is_admin()) { access_denied('associations'); }

        $staff_id = (int) $staff_id;
        $staff = $this->db->get_where(db_prefix() . 'staff', [
            'staffid'             => $staff_id,
            'client_type'         => 'association',
            'registration_status' => 'pending',
        ])->row();

        if (!$staff) { show_404(); }

        $this->db->where('staffid', $staff_id)->update(db_prefix() . 'staff', [
            'registration_status' => 'user_activated',
            'active'              => 1,
            'is_not_staff'        => 1,
        ]);

        $this->_send_registration_email($staff, 'approved');

        set_alert('success', _l('registration_user_activated'));
        redirect(admin_url('associations/pending_approvals'));
    }

    // Stage 2: approve company — activate client record + grant permissions
    public function approve_registration($staff_id)
    {
        if (!is_admin()) { access_denied('associations'); }

        $staff_id = (int) $staff_id;
        $staff = $this->db->get_where(db_prefix() . 'staff', [
            'staffid'             => $staff_id,
            'client_type'         => 'association',
            'registration_status' => 'user_activated',
        ])->row();

        if (!$staff) { show_404(); }

        $this->db->where('staffid', $staff_id)->update(db_prefix() . 'staff', [
            'registration_status' => 'approved',
        ]);

        $this->db->where('userid', $staff->client_id)->update(db_prefix() . 'clients', [
            'active' => 1,
        ]);

        $already = $this->db->get_where(db_prefix() . 'staff_permissions', [
            'staff_id'   => $staff_id,
            'feature'    => 'personnels',
            'capability' => 'create',
        ])->row();
        if (!$already) {
            $this->db->insert(db_prefix() . 'staff_permissions', [
                'staff_id'   => $staff_id,
                'feature'    => 'personnels',
                'capability' => 'create',
            ]);
        }

        $this->_send_registration_email($staff, 'approved');

        set_alert('success', _l('registration_approved_success'));
        redirect(admin_url('associations/pending_approvals'));
    }

    public function reject_registration($staff_id)
    {
        if (!is_admin()) { access_denied('associations'); }

        $staff_id = (int) $staff_id;
        $staff = $this->db->get_where(db_prefix() . 'staff', [
            'staffid'     => $staff_id,
            'client_type' => 'association',
        ])->row();

        if (!$staff) { show_404(); }

        $this->db->where('staffid', $staff_id)->update(db_prefix() . 'staff', [
            'registration_status' => 'rejected',
            'active'              => 0,
        ]);

        $this->_send_registration_email($staff, 'rejected');

        set_alert('success', _l('registration_rejected_success'));
        redirect(admin_url('associations/pending_approvals'));
    }

    private function _completeness_checks($row)
    {
        return [
            ['label' => _l('association_vat'),        'ok' => !empty($row['vat'])],
            ['label' => _l('client_phonenumber'),  'ok' => !empty($row['phonenumber'])],
            ['label' => _l('client_address'),      'ok' => !empty($row['address'])],
            ['label' => _l('client_state'),        'ok' => !empty($row['state'])],
            ['label' => _l('client_city'),         'ok' => !empty($row['city'])],
            ['label' => _l('billing_address'),     'ok' => !empty($row['billing_street']) && !empty($row['billing_city']) && !empty($row['billing_state'])],
            ['label' => _l('association_logo_light'), 'ok' => !empty($row['logo_light']) || !empty($row['logo_dark'])],
        ];
    }

    public function mark_member_status($action, $surveyor_id)
    {
        $me            = get_staff(get_staff_user_id());
        $is_assoc_user = $me && $me->client_type === 'association' && !empty($me->client_id);
        $is_admin_perm = staff_can('approve_surveyor_registration', 'associations') || is_admin();

        if (!$is_assoc_user && !$is_admin_perm) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
            }
            access_denied('associations');
        }

        $surveyor_id    = (int) $surveyor_id;
        $association_id = $is_assoc_user
            ? (int) $me->client_id
            : (int) $this->input->post('association_id');

        if (!in_array($action, ['approve', 'reject', 'pending'])) {
            show_404();
        }

        // pending action: only association users who own the relationship
        if ($action === 'pending' && !$is_assoc_user) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
            }
            access_denied('associations');
        }

        $reason = $this->input->post('reason') ?: '';

        ob_start();
        if ($action === 'approve') {
            $this->db->where('surveyor_id',    $surveyor_id);
            $this->db->where('association_id', $association_id);
            $this->db->update(db_prefix() . 'surveyors_associations', [
                'status'        => 'active',
                'date_approved' => date('Y-m-d H:i:s'),
                'approved_by'   => get_staff_user_id(),
            ]);

            $this->_log_membership_activity($surveyor_id, 'surveyor_membership_approved', $me->company ?? '');
            send_mail_template('Association_approved_surveyor_registration', 'associations', $surveyor_id, $association_id);
        } elseif ($action === 'pending') {
            $this->db->where('surveyor_id',    $surveyor_id);
            $this->db->where('association_id', $association_id);
            $this->db->update(db_prefix() . 'surveyors_associations', ['status' => 'pending']);

            $this->_log_membership_activity($surveyor_id, 'surveyor_membership_set_pending', $me->company ?? '');
            send_mail_template('Association_surveyor_set_pending', 'associations', $surveyor_id, $association_id);
        } else {
            $this->db->where('surveyor_id',    $surveyor_id);
            $this->db->where('association_id', $association_id);
            $this->db->update(db_prefix() . 'surveyors_associations', [
                'status'        => 'rejected',
                'reject_reason' => $reason,
                'approved_by'   => get_staff_user_id(),
            ]);

            $this->_log_membership_activity($surveyor_id, 'surveyor_membership_rejected', $reason);
            send_mail_template('Association_rejected_surveyor_registration', 'associations', $surveyor_id, $association_id, $reason);
        }
        $mail_debug = ob_get_clean();
        if (!empty($mail_debug)) {
            log_message('error', '[associations mail_debug] ' . $mail_debug);
        }

        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => true]);
            return;
        }
        redirect(admin_url('surveyors'));
    }

    private function _log_membership_activity($surveyor_id, $description, $additional_data = '')
    {
        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $surveyor_id,
            'rel_type'        => 'surveyor',
            'staffid'         => get_staff_user_id(),
            'full_name'       => get_staff_full_name(get_staff_user_id()),
            'additional_data' => $additional_data,
        ]);
    }

    public function register_to_association($association_id)
    {
        if (!$this->input->is_ajax_request()) { redirect(admin_url('associations')); }

        $me = get_staff(get_staff_user_id());
        if (!$me || $me->client_type !== 'surveyor' || empty($me->client_id)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }
        if (!$me->is_entity_owner && !$me->is_branch_owner) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }

        $association_id = (int) $association_id;
        $surveyor_id    = (int) $me->client_id;

        $existing = $this->db->get_where(db_prefix() . 'surveyors_associations', [
            'surveyor_id'    => $surveyor_id,
            'association_id' => $association_id,
        ])->row();

        if ($existing && $existing->status !== 'rejected') {
            echo json_encode(['success' => false, 'message' => _l('assoc_already_registered')]); return;
        }

        if ($existing && $existing->status === 'rejected') {
            // Re-registration after rejection → reset to pending
            $this->db->where('surveyor_id',    $surveyor_id)
                     ->where('association_id', $association_id)
                     ->update(db_prefix() . 'surveyors_associations', [
                         'status'          => 'pending',
                         'date_registered' => date('Y-m-d H:i:s'),
                         'date_approved'   => null,
                         'reject_reason'   => null,
                         'registered_by'   => get_staff_user_id(),
                         'approved_by'     => 0,
                     ]);
            $success = $this->db->affected_rows() >= 0;
        } else {
            $this->db->insert(db_prefix() . 'surveyors_associations', [
                'surveyor_id'     => $surveyor_id,
                'association_id'  => $association_id,
                'status'          => 'pending',
                'date_registered' => date('Y-m-d H:i:s'),
                'registered_by'   => get_staff_user_id(),
            ]);
            $success = (bool) $this->db->insert_id();
        }

        if ($success) {
            ob_start();
            send_mail_template('Association_surveyor_registered', 'associations', $surveyor_id, $association_id);
            $mail_debug = ob_get_clean();
            if (!empty($mail_debug)) {
                log_message('error', '[associations register mail_debug] ' . $mail_debug);
            }
            echo json_encode(['success' => true, 'message' => _l('assoc_register_success')]);
        } else {
            echo json_encode(['success' => false, 'message' => _l('something_went_wrong')]);
        }
    }

    public function my_associations()
    {
        $me = get_staff(get_staff_user_id());
        if (!$me || $me->client_type !== 'surveyor' || empty($me->client_id)) {
            access_denied('associations');
        }

        $data['title']            = _l('my_associations');
        $data['surveyor_id']      = (int) $me->client_id;
        $data['my_memberships']   = $this->db
            ->select('sa.*, c.company as association_name, c.phonenumber, c.city, c.state')
            ->from(db_prefix() . 'surveyors_associations sa')
            ->join(db_prefix() . 'clients c', 'c.userid = sa.association_id', 'left')
            ->where('sa.surveyor_id', (int) $me->client_id)
            ->get()->result();

        $this->load->view('admin/associations/my_associations', $data);
    }

    private function _send_registration_email($staff, $status)
    {

        $this->load->model('clients_model');
        $entity = $this->clients_model->get($staff->client_id);

        if (!$entity) {
            return;
        }


        if ($status === 'approved') {
            $result = send_mail_template('Entity_staff_registration_confirmed', 'associations', $staff->staffid);
        } else {
            $result = send_mail_template('Entity_staff_registration_rejected', 'associations', $staff->staffid);
        }
    }

    // ─── Surveyor Registration ────────────────────────────────────────────────

    public function list_surveyor_registrations($association_id = '')
    {
        $_me            = get_staff(get_staff_user_id());
        $owner_assoc_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;

        if (!$owner_assoc_id && staff_cant('view', 'associations') && !is_platform()) {
            if ($this->input->is_ajax_request()) { ajax_access_denied(); }
            access_denied('associations');
            return;
        }

        if ($this->input->is_ajax_request()) {
            App_table::find('association_surveyor_registrations')->output([]);
            return;
        }

        $data['title']           = _l('association_surveyor_registrations_tab');
        $data['bodyclass']       = 'association-surveyor-registration-page';
        $data['filter_assoc_id'] = (is_numeric($association_id) && $association_id > 0)
            ? (int)$association_id : ($owner_assoc_id ?: 0);

        $this->load->view('associations/admin/surveyor_registrations/manage', $data);
    }

    public function get_surveyor_registration_data_ajax($sa_id)
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('associations/list_surveyor_registrations'));
        }
        $sa = $this->db->get_where(db_prefix() . 'surveyors_associations', ['id' => (int)$sa_id])->row();
        if (!$sa) { show_404(); }

        if (staff_cant('view', 'associations') && !$this->_is_own_assoc($sa->association_id)) {
            ajax_access_denied();
        }

        $surveyor    = $this->db->get_where(db_prefix() . 'clients', ['userid' => (int)$sa->surveyor_id])->row();
        $association = $this->db->get_where(db_prefix() . 'clients', ['userid' => (int)$sa->association_id])->row();
        if (!$surveyor || !$association) { show_404(); }

        $rows = $this->db
            ->where('client_id',   (int)$sa->surveyor_id)
            ->where('client_type', 'surveyor')
            ->get(db_prefix() . 'client_legal_docs')->result();
        $legal_docs = [];
        foreach ($rows as $row) {
            $legal_docs[$row->doc_type] = $row;
        }

        $data['sa']          = $sa;
        $data['surveyor']    = $surveyor;
        $data['association'] = $association;
        $data['legal_docs']  = $legal_docs;
        $this->load->view(
            'associations/admin/surveyor_registrations/preview_template',
            $data
        );
    }

    public function mark_surveyor_registration($sa_id)
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('associations/list_surveyor_registrations'));
        }

        $action = $this->input->post('action');
        if (!in_array($action, ['approve', 'reject'])) {
            echo json_encode(['success' => false, 'message' => _l('something_went_wrong')]); return;
        }

        $sa = $this->db->get_where(db_prefix() . 'surveyors_associations', ['id' => (int)$sa_id])->row();
        if (!$sa) {
            echo json_encode(['success' => false, 'message' => _l('something_went_wrong')]); return;
        }

        if (staff_cant('view', 'associations') && !$this->_is_own_assoc($sa->association_id)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }

        if ($sa->status !== 'pending') {
            echo json_encode(['success' => false, 'message' => _l('something_went_wrong')]); return;
        }

        $new_status = ($action === 'approve') ? 'active' : 'inactive';
        $update = ['status' => $new_status, 'date_approved' => date('Y-m-d H:i:s')];
        $reason = $this->input->post('reason');
        if ($reason) { $update['reject_reason'] = xss_clean($reason); }
        $this->db->where('id', (int)$sa_id)->update(db_prefix() . 'surveyors_associations', $update);

        echo json_encode([
            'success' => true,
            'message' => _l($action === 'approve'
                ? 'assoc_registration_approved_success'
                : 'assoc_registration_rejected_success'),
        ]);
    }

    // ─── Surveyor Permits ─────────────────────────────────────────────────────

    public function list_surveyor_permits($association_id = '')
    {
        $_me            = get_staff(get_staff_user_id());
        $owner_assoc_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int) $_me->client_id : null;

        if (!$owner_assoc_id && staff_cant('view', 'associations') && !is_platform()) {
            if ($this->input->is_ajax_request()) { ajax_access_denied(); }
            access_denied('associations');
            return;
        }

        if ($this->input->is_ajax_request()) {
            App_table::find('association_surveyor_permits')->output([]);
            return;
        }

        $data['title']           = _l('assoc_surveyor_permits');
        $data['bodyclass']       = 'association-surveyor-permit-page';
        $data['filter_assoc_id'] = (is_numeric($association_id) && $association_id > 0)
            ? (int)$association_id : ($owner_assoc_id ?: 0);

        $this->load->view('associations/admin/surveyor_permits/manage', $data);
    }

    public function get_surveyor_permit_data_ajax($permit_id)
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('associations/list_surveyor_permits'));
        }

        $permit = $this->db->get_where(db_prefix() . 'surveyor_permits', ['id' => (int)$permit_id])->row();
        if (!$permit) { show_404(); }

        $_me            = get_staff(get_staff_user_id());
        $owner_assoc_id = ($_me && $_me->client_type === 'association' && $_me->client_id)
            ? (int)$_me->client_id : null;

        if (staff_cant('view', 'associations')) {
            if (!$owner_assoc_id) { ajax_access_denied(); }
            $prefix = db_prefix();
            $sid    = (int)$permit->surveyor_id;
            $linked = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM {$prefix}surveyors_associations sa
                 WHERE sa.association_id = ?
                   AND sa.status = 'active'
                   AND sa.surveyor_id IN (
                       SELECT c2.userid FROM {$prefix}clients c2
                       WHERE c2.userid = ? OR c2.company_id = ?
                   )",
                [$owner_assoc_id, $sid, $sid]
            )->row()->cnt;
            if (!$linked) { ajax_access_denied(); }
        }

        $surveyor    = $this->db->get_where(db_prefix() . 'clients',      ['userid' => (int)$permit->surveyor_id])->row();
        $group       = $this->db->get_where(db_prefix() . 'items_groups', ['id'     => (int)$permit->groupid])->row();
        $sa          = $this->db->where('surveyor_id', (int)$permit->surveyor_id)
                                ->where('status', 'active')
                                ->get(db_prefix() . 'surveyors_associations')->row();
        $association = $sa ? $this->db->get_where(db_prefix() . 'clients', ['userid' => (int)$sa->association_id])->row() : null;

        $data['permit']      = $permit;
        $data['surveyor']    = $surveyor;
        $data['group']       = $group;
        $data['association'] = $association;

        $this->load->view('associations/admin/surveyor_permits/preview_template', $data);
    }

    public function mark_surveyor_permit($permit_id)
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('associations/list_surveyor_permits'));
        }

        if (staff_cant('approve_surveyor_permit', 'associations')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }

        $permit = $this->db->get_where(db_prefix() . 'surveyor_permits', ['id' => (int)$permit_id])->row();
        if (!$permit || $permit->status !== 'pending') {
            echo json_encode(['success' => false, 'message' => _l('something_went_wrong')]); return;
        }

        $this->db->where('id', (int)$permit_id)
                 ->update(db_prefix() . 'surveyor_permits', ['status' => 'active']);

        echo json_encode([
            'success' => (bool)$this->db->affected_rows(),
            'message' => $this->db->affected_rows() ? _l('assoc_sp_approved_success') : _l('assoc_sp_approved_fail'),
        ]);
    }

    private function _is_own_assoc($association_id)
    {
        $me = get_staff(get_staff_user_id());
        if (!$me || $me->client_type !== 'association' || !$me->client_id) { return false; }
        return (int)$me->client_id === (int)$association_id;
    }

    public function save_assoc_item()
    {
        if (!$this->input->is_ajax_request()) { show_404(); }

        $assoc_item_id  = (int) $this->input->post('assoc_item_id');
        $association_id = (int) $this->input->post('association_id');

        if (staff_cant('edit', 'associations') && !$this->_can_edit_own($association_id)) { ajax_access_denied(); }
        $item_id        = (int) $this->input->post('item_id');
        $minimum_price  = (float) $this->input->post('minimum_price');

        if (!$association_id || !$item_id) {
            echo json_encode(['success' => false, 'message' => _l('fill_all_required_fields')]);
            return;
        }

        // Validasi minimum_price tidak boleh di bawah standard rate
        $item = $this->db->get_where(db_prefix() . 'items', ['id' => $item_id])->row();
        if ($item && $minimum_price < (float) $item->rate) {
            echo json_encode(['success' => false, 'message' => _l('minimum_price_below_standard_rate')]);
            return;
        }

        if ($assoc_item_id) {
            $this->db->where('id', $assoc_item_id)->update(db_prefix() . 'association_items', [
                'minimum_price' => $minimum_price,
            ]);
            $success = $this->db->affected_rows() >= 0;
        } else {
            $existing = $this->db->get_where(db_prefix() . 'association_items', [
                'association_id' => $association_id,
                'item_id'        => $item_id,
            ])->row();

            if ($existing) {
                $this->db->where('id', $existing->id)->update(db_prefix() . 'association_items', [
                    'minimum_price' => $minimum_price,
                ]);
            } else {
                $this->db->insert(db_prefix() . 'association_items', [
                    'association_id' => $association_id,
                    'item_id'        => $item_id,
                    'minimum_price'  => $minimum_price,
                    'qty'            => 1,
                    'rate'           => 0,
                    'item_order'     => 1,
                ]);
            }
            $success = true;
        }

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('updated_successfully', _l('item')) : _l('something_went_wrong'),
        ]);
    }
}
