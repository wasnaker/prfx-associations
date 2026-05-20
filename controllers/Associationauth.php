<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Associationauth extends App_Controller
{
    const RATE_LIMIT_MAX      = 5;   // max attempts per IP
    const RATE_LIMIT_WINDOW   = 600; // 10 minutes in seconds
    const RATE_LIMIT_BLOCK    = 1800; // 30 minute block

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper('form');
        $this->lang->load('associations', 'english', false, true, module_dir_path('associations'));
    }

    public function index()
    {
        if (is_staff_logged_in()) {
            redirect(admin_url());
        }

        if ($this->input->method() === 'post') {

            // 1. Timing check
            $min_seconds  = (int) get_option('association_registration_min_seconds') ?: 8;
            $form_loaded  = (int) $this->session->userdata('reg_form_loaded_at');
            $elapsed      = $form_loaded ? (time() - $form_loaded) : 0;
            $this->session->unset_userdata('reg_form_loaded_at');
            if ($min_seconds > 0 && $elapsed < $min_seconds) {
                $this->session->set_flashdata('form_errors', 'Submission too fast. Please try again.');
                redirect(site_url('authentication/register/association'));
                return;
            }

            // 2. Honeypot check
            if ($this->input->post('website_url') !== '') {
                redirect(site_url('authentication/register/association'));
                return;
            }

            // 2. CSRF token check
            $submitted_token = $this->input->post('reg_csrf_token');
            $session_token   = $this->session->userdata('reg_csrf_token');
            $this->session->unset_userdata('reg_csrf_token');
            if (!$submitted_token || !$session_token || $submitted_token !== $session_token) {
                $this->session->set_flashdata('form_errors', 'Invalid request. Please try again.');
                redirect(site_url('authentication/register/association'));
                return;
            }

            // 3. Rate limit check
            if ($this->_is_rate_limited()) {
                $this->session->set_flashdata('form_errors', 'Too many registration attempts. Please try again later.');
                redirect(site_url('authentication/register/association'));
                return;
            }

            // 4. reCAPTCHA check
            if ($this->_recaptcha_enabled()) {
                $response = $this->input->post('g-recaptcha-response');
                if (!do_recaptcha_validation($response)) {
                    $this->session->set_flashdata('form_errors', _l('recaptcha_error'));
                    $this->session->set_flashdata('old_input', $this->input->post(null, true));
                    redirect(site_url('authentication/register/association'));
                    return;
                }
            }

            if ($this->_validate_registration()) {
                $this->_process_registration();
            } else {
                $this->_increment_rate_limit();
                $this->session->set_flashdata('form_errors', validation_errors());
                $this->session->set_flashdata('old_input', $this->input->post(null, true));
                redirect(site_url('authentication/register/association'));
            }
            return;
        }

        // GET — generate CSRF token + record form load time
        $token = bin2hex(random_bytes(32));
        $this->session->set_userdata('reg_csrf_token', $token);
        $this->session->set_userdata('reg_form_loaded_at', time());

        $data['old_input']        = $this->session->flashdata('old_input') ?? [];
        $data['form_errors']      = $this->session->flashdata('form_errors') ?? '';
        $data['success']          = $this->session->flashdata('registration_success') ?? '';
        $data['csrf_token']       = $token;
        $data['recaptcha_enabled'] = $this->_recaptcha_enabled();
        $data['recaptcha_site_key'] = get_option('recaptcha_site_key');

        hooks()->do_action('admin_auth_init');
        $this->load->view('associations/admin/auth/register', $data);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function _validate_registration()
    {
        $this->form_validation->set_rules('company_name', _l('registration_association_name'),
            'required|trim|callback__check_company_unique');
        $this->form_validation->set_rules('firstname', _l('firstname'), 'required|trim');
        $this->form_validation->set_rules('lastname',  _l('lastname'),  'required|trim');
        $this->form_validation->set_rules('email', _l('email_address'),
            'required|trim|valid_email|callback__check_email_unique');
        $this->form_validation->set_rules('password', _l('password'),
            'required|min_length[8]|callback__check_password_strength');
        $this->form_validation->set_rules('password_confirm', _l('repeat_password'),
            'required|matches[password]');
        return $this->form_validation->run();
    }

    public function _check_company_unique($company_name)
    {
        $exists = $this->db->where('company', $company_name)
            ->count_all_results(db_prefix() . 'clients');
        if ($exists > 0) {
            $this->form_validation->set_message('_check_company_unique', _l('registration_company_exists'));
            return false;
        }
        return true;
    }

    public function _check_email_unique($email)
    {
        if ($this->db->where('email', $email)->count_all_results(db_prefix() . 'staff') > 0
         || $this->db->where('email', $email)->count_all_results(db_prefix() . 'contacts') > 0) {
            $this->form_validation->set_message('_check_email_unique', _l('registration_email_exists'));
            return false;
        }
        return true;
    }

    public function _check_password_strength($password)
    {
        if (!preg_match('/[A-Z]/', $password)) {
            $this->form_validation->set_message('_check_password_strength',
                _l('registration_password_uppercase'));
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) {
            $this->form_validation->set_message('_check_password_strength',
                _l('registration_password_number'));
            return false;
        }
        return true;
    }

    // ── Process ───────────────────────────────────────────────────────────────

    private function _process_registration()
    {
        $company_name = $this->input->post('company_name', true);
        $firstname    = $this->input->post('firstname', true);
        $lastname     = $this->input->post('lastname', true);
        $email        = $this->input->post('email', true);
        $password     = $this->input->post('password', false);

        $this->db->insert(db_prefix() . 'clients', [
            'company'         => $company_name,
            'client_type'     => 'association',
            'active'          => 0,
            'datecreated'     => date('Y-m-d H:i:s'),
            'country'         => 0,
            'billing_country' => 0,
            'addedfrom'       => 0,
        ]);
        $client_userid = $this->db->insert_id();

        if (!$client_userid) {
            $this->session->set_flashdata('form_errors', 'Failed to create record. Please try again.');
            redirect(site_url('authentication/register/association'));
            return;
        }

        $role    = $this->db->get_where(db_prefix() . 'roles', ['name' => 'Association Admin'])->row();
        $role_id = $role ? (int) $role->roleid : null;

        $this->db->insert(db_prefix() . 'staff', [
            'email'               => $email,
            'firstname'           => $firstname,
            'lastname'            => $lastname,
            'password'            => app_hash_password($password),
            'role'                => $role_id,
            'client_id'           => $client_userid,
            'client_type'         => 'association',
            'is_entity_owner'     => 1,
            'registration_status' => 'pending',
            'active'              => 0,
            'datecreated'         => date('Y-m-d H:i:s'),
            'is_not_staff'        => 1,
            'admin'               => 0,
        ]);
        $staff_id = $this->db->insert_id();

        if ($staff_id) {
            $this->db->where('userid', $client_userid)
                     ->update(db_prefix() . 'clients', ['addedfrom' => $staff_id]);
        }

        // Clear rate limit on success
        $this->_clear_rate_limit();

        $this->_notify_admins($client_userid);
        send_mail_template('Associations_created_welcome_mail', 'associations', $client_userid);

        $this->session->set_flashdata('registration_success', _l('registration_pending_message'));
        redirect(site_url('authentication/register/association'));
    }

    // ── Security helpers ──────────────────────────────────────────────────────

    private function _recaptcha_enabled()
    {
        return get_option('recaptcha_site_key') && get_option('recaptcha_secret_key');
    }

    private function _get_ip()
    {
        return $this->input->ip_address();
    }

    private function _is_rate_limited()
    {
        $ip  = $this->_get_ip();
        $row = $this->db->get_where(db_prefix() . 'reg_ratelimit', ['ip' => $ip])->row();

        if (!$row) { return false; }

        // Blocked?
        if ($row->blocked_until && strtotime($row->blocked_until) > time()) {
            return true;
        }

        // Window expired — reset
        if (strtotime($row->last_attempt) < time() - self::RATE_LIMIT_WINDOW) {
            $this->db->where('ip', $ip)->delete(db_prefix() . 'reg_ratelimit');
            return false;
        }

        return $row->attempts >= self::RATE_LIMIT_MAX;
    }

    private function _increment_rate_limit()
    {
        $ip  = $this->_get_ip();
        $row = $this->db->get_where(db_prefix() . 'reg_ratelimit', ['ip' => $ip])->row();
        $now = date('Y-m-d H:i:s');

        if (!$row) {
            $this->db->insert(db_prefix() . 'reg_ratelimit', [
                'ip'           => $ip,
                'attempts'     => 1,
                'last_attempt' => $now,
            ]);
            return;
        }

        $attempts = $row->attempts + 1;
        $blocked  = $attempts >= self::RATE_LIMIT_MAX
            ? date('Y-m-d H:i:s', time() + self::RATE_LIMIT_BLOCK)
            : null;

        $this->db->where('ip', $ip)->update(db_prefix() . 'reg_ratelimit', [
            'attempts'      => $attempts,
            'last_attempt'  => $now,
            'blocked_until' => $blocked,
        ]);
    }

    private function _clear_rate_limit()
    {
        $this->db->where('ip', $this->_get_ip())->delete(db_prefix() . 'reg_ratelimit');
    }

    private function _notify_admins($client_id)
    {
        $this->load->model('staff_model');
        $admins = $this->staff_model->get('', ['active' => 1, 'admin' => 1]);

        foreach ($admins as $admin) {
            send_mail_template('Associations_new_registration_to_admins', 'associations',
                $admin['email'], $client_id, $admin['staffid']);
        }
    }

}
