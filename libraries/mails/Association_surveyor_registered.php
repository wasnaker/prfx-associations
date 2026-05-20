<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Association_surveyor_registered extends App_mail_template
{
    protected $for = 'staff';

    public $slug = 'surveyor-registered-to-association';

    public $rel_type = 'client';

    public int $surveyor_client_id    = 0;
    public int $association_client_id = 0;

    public function __construct($surveyor_client_id, $association_client_id)
    {
        parent::__construct();
        $this->surveyor_client_id    = (int) $surveyor_client_id;
        $this->association_client_id = (int) $association_client_id;
    }

    public function build()
    {
        $owner = $this->ci->db
            ->where('client_id',      $this->association_client_id)
            ->where('client_type',    'association')
            ->where('is_entity_owner', 1)
            ->where('is_not_staff',    1)
            ->get(db_prefix() . 'staff')->row();

        if (!$owner) { return; }

        $surveyor = $this->ci->db->where('userid', $this->surveyor_client_id)->get(db_prefix() . 'clients')->row();
        if (!$surveyor) { return; }

        $this->to($owner->email)
            ->set_rel_id($this->association_client_id)
            ->set_merge_fields('association_merge_fields', $this->association_client_id, null)
            ->set_merge_fields([
                '{contact_firstname}'  => e($owner->firstname),
                '{contact_lastname}'   => e($owner->lastname),
                '{surveyor_company}'   => e($surveyor->company),
                '{surveyors_list_url}' => admin_url('surveyors'),
            ]);
    }
}
