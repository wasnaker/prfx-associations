<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Association_rejected_surveyor_registration extends App_mail_template
{
    protected $for = 'staff';

    public $slug = 'association-rejected-surveyor-registration';

    public $rel_type = 'client';

    public int    $surveyor_client_id    = 0;
    public int    $association_client_id = 0;
    public string $rejection_reason      = '';

    public function __construct($surveyor_client_id, $association_client_id, $reason)
    {
        parent::__construct();
        $this->surveyor_client_id    = (int) $surveyor_client_id;
        $this->association_client_id = (int) $association_client_id;
        $this->rejection_reason      = $reason;
    }

    public function build()
    {
        $staff = $this->ci->db
            ->where('client_id',      $this->surveyor_client_id)
            ->where('client_type',    'surveyor')
            ->where('is_entity_owner', 1)
            ->where('is_not_staff',    1)
            ->get(db_prefix() . 'staff')->row();

        if (!$staff) { return; }

        $association = $this->ci->db->where('userid', $this->association_client_id)->get(db_prefix() . 'clients')->row();

        if (!$association) { return; }

        $this->to($staff->email)
            ->set_rel_id($this->association_client_id)
            ->set_merge_fields('association_merge_fields', $this->association_client_id, null)
            ->set_merge_fields([
                '{contact_firstname}'    => e($staff->firstname),
                '{contact_lastname}'     => e($staff->lastname),
                '{contact_email}'        => e($staff->email),
                '{association_company}'  => e($association->company),
                '{rejection_reason}'     => nl2br(e($this->rejection_reason)),
            ]);
    }
}
