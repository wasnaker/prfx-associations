<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$table_data = [
    _l('surveyor_name'),
    _l('client_state'),
    _l('client_city'),
];
render_datatable($table_data, 'association-surveyor-registration-list', [], ['id' => 'association-surveyor-registration-list']);
