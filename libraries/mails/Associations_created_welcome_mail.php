<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Associations_created_welcome_mail extends App_mail_template
{
    protected $for = 'association';

    protected $client_id;

    public $slug = 'new-association-created';

    public $rel_type = 'client';

    public function __construct($client_id)
    {
        parent::__construct();
        $this->client_id = $client_id;
    }

    public function build()
    {
        $primary_staffid = get_primary_staff_user_id($this->client_id);

        $staff = $primary_staffid
            ? $this->ci->db->where('staffid', $primary_staffid)->get(db_prefix() . 'staff')->row()
            : null;

        if (!$staff) {
            return;
        }

        $this->to($staff->email)
        ->set_rel_id($this->client_id)
        ->set_merge_fields('association_merge_fields', $this->client_id, null)
        ->set_merge_fields([
            '{contact_firstname}' => e($staff->firstname),
            '{contact_lastname}'  => e($staff->lastname),
            '{contact_email}'     => e($staff->email),
        ]);
    }
}
