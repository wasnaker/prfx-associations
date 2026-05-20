<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Associations_new_registration_to_admins extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $client_id;

    protected $staffid;

    public $slug = 'new-association-registered-to-admin';

    public $rel_type = 'staff';

    public function __construct($staff_email, $client_id, $staffid)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->client_id   = $client_id;
        $this->staffid     = $staffid;
    }

    public function build()
    {
        $primary_staffid = get_primary_staff_user_id($this->client_id);

        $staff = $primary_staffid
            ? $this->ci->db->where('staffid', $primary_staffid)->get(db_prefix() . 'staff')->row()
            : null;

        $this->to($this->staff_email)
        ->set_rel_id($this->staffid)
        ->set_merge_fields('association_merge_fields', $this->client_id, null)
        ->set_merge_fields([
            '{contact_firstname}' => $staff ? e($staff->firstname) : '',
            '{contact_lastname}'  => $staff ? e($staff->lastname)  : '',
            '{contact_email}'     => $staff ? e($staff->email)     : '',
        ]);
    }
}
