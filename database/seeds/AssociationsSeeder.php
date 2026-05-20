<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'demo/database/seeds/BaseSeeder.php';

/**
 * 10 asosiasi K3 sungguhan (nasional + regional).
 * IDs dinamis dari max(existing) + 1 setelah cleanup.
 * No VAT | Email as.*@demo.local | password: Demo1234!
 */
class AssociationsSeeder extends BaseSeeder
{
    const CLIENT_START = 600;   // client IDs: 601..610 (10 HQ)
    const STAFF_START  = 3000;  // staff IDs : 3001..3020 (10 owners + 10 regular)

    private array $names = [
        'Nugraha','Prakoso','Rahmat','Saputro','Triyono','Utomo','Wicaksono','Yulianto','Zulkarnaen','Ardian',
        'Baskoro','Cahyadi','Darmanto','Efendi','Firmansyah','Gunarto','Haryanto','Iskandar','Junaedi','Kusuma',
        'Lesmana','Mahendra','Nasution','Oktavian','Purnomo','Qodri','Rasyid','Suharto','Tarmizi','Usman',
        'Vebrianto','Winarno','Yudhistira','Zainuddin','Amin','Bahar','Candra','Daryono','Eman','Fikri',
    ];

    private array $companies = [
        // [email, staff_lastname, company, phone, address, city, state, zip, staff]
        ['as.apjk3@demo.local',         'APJK3',
            'APJK3 (Asosiasi Perusahaan Jasa K3 Indonesia)',
            '021-5795-1234', 'Menara Bidakara, Jl. Jend. Gatot Subroto Kav.71-73',
            'Jakarta Selatan', 'DKI Jakarta', '12870',
            ['Bambang Irawan',   'Sekretaris Jenderal', '083111220001']],

        ['as.aspek3banten@demo.local',   'ASPEK3 Banten',
            'ASPEK3 Banten (Asosiasi Perusahaan K3 Banten)',
            '0254-218765', 'Jl. Jenderal Sudirman No.12',
            'Kota Serang', 'Banten', '42112',
            ['Nia Kusuma',       'Ketua Bidang',        '083111220002']],

        ['as.hakijkt@demo.local',        'HAKI Jakarta',
            'HAKI Jakarta (Himpunan Ahli K3 Indonesia DKI Jakarta)',
            '021-3141592', 'Jl. Pramuka Raya No.33',
            'Jakarta Timur', 'DKI Jakarta', '13140',
            ['Feri Yusuf',       'Wakil Ketua',         '083111220003']],

        ['as.hakijabar@demo.local',      'HAKI Jabar',
            'HAKI Jawa Barat (Himpunan Ahli K3 Indonesia Jawa Barat)',
            '022-7201234', 'Jl. Riau No.25, Bandung',
            'Kota Bandung', 'Jawa Barat', '40115',
            ['Lestari Kartini',  'Bendahara',           '083111220004']],

        ['as.hakijatim@demo.local',      'HAKI Jatim',
            'HAKI Jawa Timur (Himpunan Ahli K3 Indonesia Jawa Timur)',
            '031-5021234', 'Jl. Embong Malang No.45, Surabaya',
            'Kota Surabaya', 'Jawa Timur', '60261',
            ['Irwan Basuki',     'Koordinator',         '083111220005']],

        ['as.aspek3jateng@demo.local',   'ASPEK3 Jateng',
            'ASPEK3 Jawa Tengah (Asosiasi Perusahaan K3 Jawa Tengah)',
            '024-3561234', 'Jl. Pemuda No.14, Semarang',
            'Kota Semarang', 'Jawa Tengah', '50132',
            ['Dewi Ratnasari',   'Sekretaris',          '083111220006']],

        ['as.apjk3jatim@demo.local',     'APJK3 Jatim',
            'APJK3 Jawa Timur (Asosiasi Perusahaan Jasa K3 Jawa Timur)',
            '031-8281234', 'Jl. Raya Darmo No.23, Surabaya',
            'Kota Surabaya', 'Jawa Timur', '60241',
            ['Gita Purnomo',     'Anggota Dewan',       '083111220007']],

        ['as.hakisulsel@demo.local',     'HAKI Sulsel',
            'HAKI Sulawesi Selatan (Himpunan Ahli K3 Indonesia Sulawesi Selatan)',
            '0411-871234', 'Jl. AP Pettarani No.8, Makassar',
            'Kota Makassar', 'Sulawesi Selatan', '90222',
            ['Lukman Hakim',     'Koordinator',         '083111220008']],

        ['as.aspek3kaltim@demo.local',   'ASPEK3 Kaltim',
            'ASPEK3 Kalimantan Timur (Asosiasi Perusahaan K3 Kalimantan Timur)',
            '0542-741234', 'Jl. Jend. Sudirman No.17, Balikpapan',
            'Kota Balikpapan', 'Kalimantan Timur', '76112',
            ['Mega Pertiwi',     'Bendahara',           '083111220009']],

        ['as.pjk3sumut@demo.local',      'PJK3 Sumut',
            'PJK3 Sumatera Utara (Perhimpunan Jasa K3 Sumatera Utara)',
            '061-4571234', 'Jl. Gatot Subroto No.212, Medan',
            'Kota Medan', 'Sumatera Utara', '20113',
            ['Niko Santoso',     'Ketua',               '083111220010']],
    ];

    public function run(): array
    {
        $this->_clean();
        $this->_reset_names();

        $r         = $this->db->get_where(db_prefix() . 'roles', ['name' => 'Association Admin'])->row();
        $rid_admin = $r ? (int) $r->roleid : 0;
        $r         = $this->db->get_where(db_prefix() . 'roles', ['name' => 'Association'])->row();
        $rid_base  = $r ? (int) $r->roleid : 0;

        $i  = self::CLIENT_START;
        $j  = self::STAFF_START;
        $n  = count($this->companies);
        $ids = [];

        foreach ($this->companies as $idx => $c) {
            [$email, $staff_ln, $company,
             $phone, $address, $city, $state, $zip, $staff] = $c;

            $userid  = $i + 1 + $idx;
            $staffid = $j + 1 + $idx;

            $this->upsert('clients', 'userid', [
                'userid'      => $userid,
                'company'     => $company,
                'phonenumber' => $phone,
                'address'     => $address,
                'city'        => $city,
                'state'       => $state,
                'zip'         => $zip,
                'country'     => $this->country,
                'client_type' => 'association',
                'active'      => 1,
                'addedfrom'   => $staffid,
                'datecreated' => $this->now,
            ]);

            $this->upsert('staff', 'staffid', [
                'staffid'             => $staffid,
                'email'               => $email,
                'firstname'           => 'Admin',
                'lastname'            => $staff_ln,
                'password'            => app_hash_password('Demo1234!'),
                'role'                => $rid_admin,
                'active'              => 1,
                'is_not_staff'        => 1,
                'is_entity_owner'     => 1,
                'client_id'           => $userid,
                'client_type'         => 'association',
                'registration_status' => 'approved',
                'datecreated'         => $this->now,
            ]);
            $this->insert('staff_permissions', [
                'staff_id'   => $staffid,
                'feature'    => 'personnels',
                'capability' => 'create',
            ]);

            [, $spos, $sphone] = $staff;
            [$sfirst, $slast]  = $this->_random_name($this->names);
            $staff_sid = $j + 1 + $n + $idx;
            $this->_insert_staff($staff_sid, $sfirst . ' ' . $slast, $spos, $sphone, $userid, $rid_base);

            $ids[] = $userid;
        }

        return $ids;
    }

    private function _clean(): void
    {
        $this->no_debug(function () {
            $this->db->where('client_type', 'association')
                     ->where('company_id IS NOT NULL', null, false)
                     ->delete(db_prefix() . 'clients');
            $this->safe_delete('clients', ['client_type' => 'association']);
        });
        $this->no_debug(function () {
            $this->db->query(
                "DELETE sp FROM " . db_prefix() . "staff_permissions sp
                 INNER JOIN " . db_prefix() . "staff s ON s.staffid = sp.staff_id
                 WHERE s.client_type = 'association'"
            );
            $this->db->query("DELETE FROM " . db_prefix() . "staff WHERE client_type = 'association'");
        });
    }

    private function _insert_staff(int $staffid, string $name, string $position,
                                    string $phone, int $client_id, int $role_id): void
    {
        $parts = explode(' ', trim($name), 2);
        $this->upsert('staff', 'staffid', [
            'staffid'             => $staffid,
            'email'               => 'as.staff' . $staffid . '@demo.local',
            'firstname'           => $parts[0] ?? '',
            'lastname'            => $parts[1] ?? '',
            'position'            => $this->_random_positions('association'),
            'phonenumber'         => $phone,
            'password'            => app_hash_password('Demo1234!'),
            'role'                => $role_id,
            'active'              => 1,
            'is_not_staff'        => 1,
            'is_entity_owner'     => 0,
            'client_id'           => $client_id,
            'client_type'         => 'association',
            'registration_status' => 'approved',
            'datecreated'         => $this->now,
        ]);
    }
}
