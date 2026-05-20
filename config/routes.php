<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['associations/(:num)/(:any)']                   = 'association/index/$1/$2';
$route['authentication/register/association']           = 'associations/associationauth/index';
$route['admin/associations/pending_approvals']         = 'associations/associations/pending_approvals';
$route['admin/associations/activate_user/(:num)']        = 'associations/associations/activate_user/$1';
$route['admin/associations/approve_registration/(:num)'] = 'associations/associations/approve_registration/$1';
$route['admin/associations/reject_registration/(:num)']  = 'associations/associations/reject_registration/$1';
$route['admin/associations/register_to_association/(:num)'] = 'associations/associations/register_to_association/$1';
$route['admin/associations/my_associations']                = 'associations/associations/my_associations';
$route['admin/associations/mark_member_status/(:any)/(:num)'] = 'associations/associations/mark_member_status/$1/$2';
$route['admin/associations/save_assoc_item'] = 'associations/associations/save_assoc_item';
$route['admin/associations/list_surveyor_registrations']               = 'associations/associations/list_surveyor_registrations';
$route['admin/associations/list_surveyor_registrations/(:num)']        = 'associations/associations/list_surveyor_registrations/$1';
$route['admin/associations/get_surveyor_registration_data_ajax/(:num)'] = 'associations/associations/get_surveyor_registration_data_ajax/$1';
$route['admin/associations/mark_surveyor_registration/(:num)']         = 'associations/associations/mark_surveyor_registration/$1';
$route['admin/associations/list_surveyor_permits']                    = 'associations/associations/list_surveyor_permits';
$route['admin/associations/list_surveyor_permits/(:num)']             = 'associations/associations/list_surveyor_permits/$1';
$route['admin/associations/get_surveyor_permit_data_ajax/(:num)']     = 'associations/associations/get_surveyor_permit_data_ajax/$1';
$route['admin/associations/mark_surveyor_permit/(:num)']              = 'associations/associations/mark_surveyor_permit/$1';
