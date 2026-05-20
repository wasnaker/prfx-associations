<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Association_pdf extends App_pdf
{
    protected $association;

    private $association_number;

    public function __construct($association, $tag = '')
    {
        $this->load_language($association->userid);

        $association                = hooks()->apply_filters('association_html_pdf_data', $association);
        $GLOBALS['association_pdf'] = $association;

        parent::__construct();

        $this->tag             = $tag;
        $this->association        = $association;
        $this->association_number = e($association->company);

        $this->SetTitle($this->association_number);
    }

    public function prepare()
    {
        $this->set_view_vars([
            'status'          => $this->association->active == 1 ? 'active' : 'inactive',
            'association_number' => $this->association_number,
            'association'        => $this->association,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'association';
    }

    protected function file_path()
    {
        $theme      = active_clients_theme();
        $customPath = APPPATH . 'views/themes/' . $theme . '/views/my_associationpdf.php';
        $actualPath = FCPATH . 'modules/associations/views/themes/' . $theme . '/views/associationpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
