<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ── Mobile API — Associations ──────────────────────────────────────────────
$route['api/v1/associations/my']                           = 'associations/associations_api/my_registrations';
$route['api/v1/associations/(:num)/registrations']         = 'associations/associations_api/registrations/$1';
$route['api/v1/associations/(:num)/registrations/(:num)']  = 'associations/associations_api/registration/$1/$2';
