<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'demo/database/seeds/BaseSeeder.php';

/**
 * Fixed IDs
 *   Branches : 2511–2548  (3–5 branch per perusahaan, beda provinsi)
 *   Staff    : 5011–5048  (branch admin, is_branch_owner=1, is_entity_owner=0)
 */
class AssociationBranchesSeeder extends BaseSeeder
{
    private array $branches = [
        // ── Krakatau Steel (parent 2501) ─────────────────────────────────────
        [2511,2501,'PT. Krakatau Steel Divisi Bandung',       '022-4201234','Kota Bandung',       'Jawa Barat',        '40111',['Ahmad Fauzi',    'K3 Supervisor',  '081311110001']],
        [2512,2501,'PT. Krakatau Steel Kantor Jakarta',        '021-5204100','Kota Jakarta Selatan','DKI Jakarta',     '12920',['Rina Kusuma',    'Legal Officer',  '081311110002']],
        [2513,2501,'PT. Krakatau Steel Divisi Surabaya',       '031-5671234','Kota Surabaya',      'Jawa Timur',       '60271',['Heri Santoso',   'HSE Supervisor', '081311110003']],
        [2514,2501,'PT. Krakatau Steel Cabang Balikpapan',     '0542-731234','Kota Balikpapan',    'Kalimantan Timur', '76112',['Siti Aminah',    'Safety Officer', '081311110004']],
        // ── Indofood (parent 2502) ───────────────────────────────────────────
        [2515,2502,'PT. Indofood CBP Pabrik Tangerang',        '021-5930-2288','Kota Tangerang',   'Banten',           '15119',['Andi Prasetya',  'K3 Manager',     '081311110005']],
        [2516,2502,'PT. Indofood CBP Pabrik Semarang',         '024-7612345','Kota Semarang',      'Jawa Tengah',      '50241',['Lestari Ningrum','HSE Coordinator','081311110006']],
        [2517,2502,'PT. Indofood CBP Pabrik Surabaya',         '031-8421234','Kota Surabaya',      'Jawa Timur',       '60293',['Wahyu Nugroho',  'K3 Officer',     '081311110007']],
        [2518,2502,'PT. Indofood CBP Pabrik Medan',            '061-4512345','Kota Medan',         'Sumatera Utara',   '20238',['Sri Mulyani',    'Safety Officer', '081311110008']],
        [2519,2502,'PT. Indofood CBP Distribusi Makassar',     '0411-441234','Kota Makassar',      'Sulawesi Selatan', '90222',['Nurul Hidayah',  'HSE Officer',    '081311110009']],
        // ── Astra Honda Motor (parent 2503) ─────────────────────────────────
        [2520,2503,'PT. Astra Honda Motor Plant Jakarta',      '021-6510540','Kota Jakarta Utara', 'DKI Jakarta',     '14350',['Bambang Eko',    'Plant Manager',  '081311110010']],
        [2521,2503,'PT. Astra Honda Motor Plant Semarang',     '024-7601234','Kota Semarang',      'Jawa Tengah',      '50144',['Fitri Handayani','K3 Officer',     '081311110011']],
        [2522,2503,'PT. Astra Honda Motor Plant Malang',       '0341-481234','Kota Malang',        'Jawa Timur',       '65122',['Dedi Kurniawan', 'Safety Officer', '081311110012']],
        [2523,2503,'PT. Astra Honda Motor Cabang Palembang',   '0711-321234','Kota Palembang',     'Sumatera Selatan', '30127',['Maya Sari',      'K3 Coordinator', '081311110013']],
        // ── Djarum (parent 2504) ─────────────────────────────────────────────
        [2524,2504,'PT. Djarum Kantor Jakarta',                '021-5221234','Kota Jakarta Pusat', 'DKI Jakarta',     '10340',['Yusuf Hidayat',  'GA Manager',     '081311110014']],
        [2525,2504,'PT. Djarum Pabrik Surabaya',               '031-7421234','Kota Surabaya',      'Jawa Timur',       '60181',['Endang Susanti', 'K3 Supervisor',  '081311110015']],
        [2526,2504,'PT. Djarum Kantor Bandung',                '022-4231234','Kota Bandung',       'Jawa Barat',       '40252',['Iman Santoso',   'HSE Officer',    '081311110016']],
        // ── Petrokimia Gresik (parent 2505) ─────────────────────────────────
        [2527,2505,'PT. Petrokimia Gresik Unit Cilegon',       '0254-381234','Kota Cilegon',       'Banten',           '42411',['Tono Hadiyanto', 'K3 Supervisor',  '081311110017']],
        [2528,2505,'PT. Petrokimia Gresik Cabang Palembang',   '0711-431234','Kota Palembang',     'Sumatera Selatan', '30113',['Ratna Dewi',     'HSE Coordinator','081311110018']],
        [2529,2505,'PT. Petrokimia Gresik Cabang Banjarbaru',  '0511-781234','Kota Banjarbaru',    'Kalimantan Selatan','70714',['Gunawan Hadi',   'Safety Officer', '081311110019']],
        [2530,2505,'PT. Petrokimia Gresik Cabang Makassar',    '0411-851234','Kota Makassar',      'Sulawesi Selatan', '90111',['Umar Bakri',     'K3 Officer',     '081311110020']],
        // ── Pertamina Hulu Rokan (parent 2506) ──────────────────────────────
        [2531,2506,'PT. Pertamina Hulu Rokan Field Balikpapan','0542-421234','Kota Balikpapan',    'Kalimantan Timur', '76111',['Irwan Maulana',  'K3 Field Eng',   '081311110021']],
        [2532,2506,'PT. Pertamina Hulu Rokan Area Palembang',  '0711-511234','Kota Palembang',     'Sumatera Selatan', '30118',['Dewi Pratiwi',   'HSE Supervisor', '081311110022']],
        [2533,2506,'PT. Pertamina Hulu Rokan Kantor Jakarta',  '021-3815000','Kota Jakarta Selatan','DKI Jakarta',    '12190',['Fajar Nugroho',  'Safety Manager', '081311110023']],
        // ── Pupuk Kaltim (parent 2507) ───────────────────────────────────────
        [2534,2507,'PT. Pupuk Kalimantan Timur Unit Gresik',   '031-3981900','Kabupaten Gresik',   'Jawa Timur',       '61151',['Agung Setiawan', 'K3 Supervisor',  '081311110024']],
        [2535,2507,'PT. Pupuk Kalimantan Timur Distribusi Makassar','0411-321567','Kota Makassar','Sulawesi Selatan',  '90111',['Novi Rahayu',    'HSE Officer',    '081311110025']],
        [2536,2507,'PT. Pupuk Kalimantan Timur Distribusi Medan','061-4231234','Kota Medan',       'Sumatera Utara',   '20113',['Bastian Hutabarat','Safety Officer','081311110026']],
        // ── Inalum (parent 2508) ─────────────────────────────────────────────
        [2537,2508,'PT. Inalum Kantor Jakarta',                '021-2512000','Kota Jakarta Pusat', 'DKI Jakarta',     '10350',['Rendy Sitorus',  'Legal Manager',  '081311110027']],
        [2538,2508,'PT. Inalum Unit Pontianak',                '0561-741234','Kota Pontianak',     'Kalimantan Barat', '78113',['Linda Manurung', 'K3 Supervisor',  '081311110028']],
        [2539,2508,'PT. Inalum Unit Palu',                     '0451-421234','Kota Palu',          'Sulawesi Tengah',  '94111',['Sahat Siagian',  'HSE Officer',    '081311110029']],
        [2540,2508,'PT. Inalum Unit Jayapura',                 '0967-531234','Kota Jayapura',      'Papua',            '99114',['Felix Numberi',  'Safety Officer', '081311110030']],
        // ── IKI (parent 2509) ────────────────────────────────────────────────
        [2541,2509,'PT. Industri Kapal Indonesia Kantor Jakarta','021-6541234','Kota Jakarta Utara','DKI Jakarta',    '14310',['Syahrul Ramadhan','GA Manager',    '081311110031']],
        [2542,2509,'PT. Industri Kapal Indonesia Unit Surabaya','031-3291234','Kota Surabaya',     'Jawa Timur',       '60177',['Tri Wahyuni',    'K3 Officer',     '081311110032']],
        [2543,2509,'PT. Industri Kapal Indonesia Unit Banjarmasin','0511-3361234','Kota Banjarmasin','Kalimantan Selatan','70114',['Hamid Basri',  'HSE Supervisor', '081311110033']],
        // ── Pusri (parent 2510) ──────────────────────────────────────────────
        [2544,2510,'PT. Pusri Kantor Jakarta',                 '021-5221567','Kota Jakarta Pusat', 'DKI Jakarta',     '10220',['Amrizal Tanjung','K3 Manager',     '081311110034']],
        [2545,2510,'PT. Pusri Distribusi Bandung',             '022-4201567','Kota Bandung',       'Jawa Barat',       '40135',['Citra Lestari',  'HSE Officer',    '081311110035']],
        [2546,2510,'PT. Pusri Distribusi Surabaya',            '031-5671567','Kota Surabaya',      'Jawa Timur',       '60271',['Hardiansyah',    'K3 Officer',     '081311110036']],
        [2547,2510,'PT. Pusri Distribusi Banjarmasin',         '0511-3361567','Kota Banjarmasin',  'Kalimantan Selatan','70113',['Irmawati',       'Safety Officer', '081311110037']],
        [2548,2510,'PT. Pusri Distribusi Makassar',            '0411-441567','Kota Makassar',      'Sulawesi Selatan', '90145',['Zulkarnain',     'HSE Coordinator','081311110038']],
    ];

    public function run(array $association_ids = []): array
    {
        $r          = $this->db->get_where(db_prefix() . 'roles', ['name' => 'Association Branch Admin'])->row();
        $rid_branch = $r ? (int) $r->roleid : 0;

        $branch_ids = [];
        $branch_sid = 5011; // branch staff: 5011–5048

        foreach ($this->branches as $b) {
            [$branchid, $parentid, $bcompany, $bphone,
             $bcity, $bstate, $bzip, $bcontact] = $b;

            [$bcname, , $bcphone] = $bcontact;
            $this->_insert_branch_owner($branch_sid, $bcname, $bcphone, $branchid, $rid_branch);

            $this->insert('clients', [
                'userid'      => $branchid,
                'company'     => $bcompany,
                'phonenumber' => $bphone,
                'city'        => $bcity,
                'state'       => $bstate,
                'zip'         => $bzip,
                'country'     => $this->country,
                'client_type' => 'association',
                'company_id'  => $parentid,
                'active'      => 1,
                'addedfrom'   => $branch_sid,
                'datecreated' => $this->now,
            ]);

            $branch_ids[] = $branchid;
            $branch_sid++;
        }

        return $branch_ids;
    }

    private function _insert_branch_owner(int $staffid, string $name, string $phone,
                                           int $client_id, int $role_id): void
    {
        $parts = explode(' ', trim($name), 2);
        $this->insert('staff', [
            'staffid'             => $staffid,
            'email'               => 'staff' . $staffid . '@demo.local',
            'firstname'           => $parts[0] ?? '',
            'lastname'            => $parts[1] ?? '',
            'position'            => $this->_random_positions('association'),
            'phonenumber'         => $phone,
            'password'            => app_hash_password('Demo1234!'),
            'role'                => $role_id,
            'active'              => 1,
            'is_not_staff'        => 1,
            'is_entity_owner'     => 0,
            'is_branch_owner'     => 1,
            'client_id'           => $client_id,
            'client_type'         => 'association',
            'registration_status' => 'approved',
            'datecreated'         => $this->now,
        ]);
        $this->insert('staff_permissions', [
            'staff_id'   => $staffid,
            'feature'    => 'personnels',
            'capability' => 'create',
        ]);
    }
}
