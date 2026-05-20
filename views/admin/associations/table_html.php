<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$table_data = [
    _l('client_company'),
    _l('client_phonenumber'),
    _l('client_state'),
    _l('client_city'),
    _l('active'),
];

$table_data = hooks()->apply_filters('associations_table_columns', $table_data);

render_datatable($table_data, 'associations', [], ['id' => 'associations']);
