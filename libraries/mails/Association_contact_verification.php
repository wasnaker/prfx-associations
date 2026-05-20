<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Association_contact_verification extends App_mail_template
{
    protected $for = 'association';

    protected $contact;

    public $slug = 'association-verification-email';

    public $rel_type = 'contact';

    public function __construct($contact)
    {
        parent::__construct();
        $this->contact = $contact;
    }

    public function build()
    {
        $this->to($this->contact->email)
        ->set_rel_id($this->contact->id)
        ->set_merge_fields('association_merge_fields', $this->contact->userid, $this->contact->id);
    }
}
