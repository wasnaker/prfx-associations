<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Entity_staff_registration_rejected extends App_mail_template
{
    protected $for = 'staff';

    public $slug = 'association-registration-rejected';

    public $rel_type = 'client';

    public function __construct($staffid)
    {
        parent::__construct();
        $this->staff_id = (int) $staffid;
    }

    public function build()
    {
        $staff = $this->ci->db->where('staffid', $this->staff_id)->get(db_prefix() . 'staff')->row();
        if (!$staff) {
            log_message('error', '[Entity_staff_registration_rejected] staff not found: ' . $this->staff_id);
            return;
        }

        $client = $this->ci->db->where('userid', $staff->client_id)->get(db_prefix() . 'clients')->row();

        $this->to($staff->email)
        ->set_rel_id($staff->client_id)
        ->set_merge_fields('association_merge_fields', $staff->client_id, null)
        ->set_merge_fields([
            '{contact_firstname}' => e($staff->firstname),
            '{contact_lastname}'  => e($staff->lastname),
            '{contact_email}'     => e($staff->email),
            '{client_company}'    => $client ? e($client->company) : '',
        ]);
    }
}
