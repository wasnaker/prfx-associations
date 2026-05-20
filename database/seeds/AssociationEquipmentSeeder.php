<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'demo/database/seeds/BaseSeeder.php';

/**
 * Static IDs: tblassociation_equipment 3001–3349
 *   Companies : 10 × 6  =  60 records  (3001–3060)
 *   Branches  : 38 × [5,8,10] = 289 records  (3061–3349)
 */
class AssociationEquipmentSeeder extends BaseSeeder
{
    private int $id_start = 3001;

    /**
     * Company assignments (index matches $association_ids order: 0=Krakatau…9=Pusri)
     * Each row: [item_idx, unit_code, serial, location, brand, model_type, proc_year, cert_exp]
     */
    private array $assignments = [
        // 0: PT. Krakatau Steel — heavy lifting + pressure vessels
        [
            [0,  'OHC-001', 'SN-OHC-2018001', 'Workshop A, Bay 1',     'Konecranes',   '5T Overhead Crane',      2018, '2025-06-30'],
            [2,  'FLT-001', 'SN-FLT-2019001', 'Warehouse B',           'Toyota',        '3T Forklift',            2019, '2025-09-30'],
            [12, 'BLR-001', 'SN-BLR-2017001', 'Boiler Room 1',         'Thermax',       'Fire Tube 10 Bar',       2017, '2025-03-31'],
            [14, 'VSL-001', 'SN-VSL-2020001', 'Compressor Station',    'ASME',          'Pressure Vessel 16Bar',  2020, '2026-01-31'],
            [24, 'MDP-001', 'SN-MDP-2019001', 'Substation 1',          'Schneider',     'Panel MDP 630A',         2019, '2025-12-31'],
            [30, 'ELV-001', 'SN-ELV-2016001', 'Main Office Building',  'Schindler',     'Passenger Lift 8P',      2016, '2025-08-31'],
        ],
        // 1: PT. Indofood CBP — process + food production
        [
            [6,  'CMP-001', 'SN-CMP-2018001', 'Utility Building',      'Atlas Copco',   '75kW Air Compressor',    2018, '2025-07-31'],
            [8,  'PMP-001', 'SN-PMP-2019001', 'Pump House 2',          'Grundfos',      'Centrifugal Pump',       2019, '2025-10-31'],
            [13, 'BLR-002', 'SN-BLR-2016001', 'Boiler Room 2',         'Cochran',       'Water Tube 16 Bar',      2016, '2025-04-30'],
            [15, 'HEX-001', 'SN-HEX-2020001', 'Process Area B',        'APV',           'Shell & Tube HEX',       2020, '2026-02-28'],
            [20, 'APR-001', 'SN-APR-2018001', 'Production Lab',        'Tuttnauer',     'Autoclave 50L',          2018, '2025-11-30'],
            [25, 'TRF-001', 'SN-TRF-2017001', 'Substation 2',          'Trafindo',      'Trafo 630 KVA',          2017, '2025-05-31'],
        ],
        // 2: PT. Astra Honda Motor — production lines + press
        [
            [1,  'MCR-001', 'SN-MCR-2019001', 'Production Yard',       'Liebherr',      '50T Mobile Crane',       2019, '2025-08-31'],
            [10, 'TRB-001', 'SN-TRB-2015001', 'Power Plant',           'GE',            'Steam Turbine 5MW',      2015, '2025-06-30'],
            [11, 'PRS-001', 'SN-PRS-2018001', 'Stamping Line 1',       'Schuler',       '500T Press Machine',     2018, '2025-09-30'],
            [21, 'APR-002', 'SN-APR-2019001', 'Quality Lab',           'Hirayama',      'Autoclave 100L',         2019, '2026-03-31'],
            [26, 'GNS-001', 'SN-GNS-2017001', 'Emergency Power Room',  'Caterpillar',   'Genset 500 kVA',         2017, '2025-12-31'],
            [32, 'ESC-001', 'SN-ESC-2018001', 'Assembly Building',     'KONE',          'Escalator 0.5m/s',       2018, '2025-07-31'],
        ],
        // 3: PT. Djarum — tobacco manufacturing
        [
            [3,  'HST-001', 'SN-HST-2019001', 'Warehouse C, Level 2',  'Demag',         '2T Electric Hoist',      2019, '2025-10-31'],
            [4,  'CVB-001', 'SN-CVB-2020001', 'Processing Line 3',     'Rexnord',       'Belt Conveyor 30m',      2020, '2026-01-31'],
            [7,  'GNS-002', 'SN-GNS-2018001', 'Utility Room',          'Perkins',       'Genset 250 kVA',         2018, '2025-11-30'],
            [19, 'HYD-001', 'SN-HYD-2019001', 'Fire Zone A',           'Viking',        'Hydrant Pillar 4"',      2019, '2025-08-31'],
            [27, 'PNP-001', 'SN-PNP-2019001', 'Lightning Protection',  'Erico',         'Penyalur Petir',        2019, '2025-08-31'],
            [31, 'ELV-002', 'SN-ELV-2018001', 'Warehouse Building',    'Mitsubishi',    'Freight Elevator 2T',    2018, '2025-05-31'],
        ],
        // 4: PT. Petrokimia Gresik — chemical/fertilizer
        [
            [5,  'GND-001', 'SN-GND-2019001', 'NPK Plant, Level 3',    'Maber',         'Construction Gondola',   2019, '2025-09-30'],
            [9,  'MTD-001', 'SN-MTD-2018001', 'Generator Room',        'Cummins',       'Diesel Engine 200HP',    2018, '2025-07-31'],
            [16, 'STK-001', 'SN-STK-2019001', 'Ammonia Tank Farm',     'Matrix',        'Storage Tank 100m3',     2019, '2025-11-30'],
            [22, 'APR-003', 'SN-APR-2017001', 'CO2 Bank',              'Kidde',         'APAR CO2 5kg',           2017, '2025-03-31'],
            [23, 'APR-004', 'SN-APR-2018002', 'Production Floor',      'Ansul',         'APAR Powder 6kg',        2018, '2025-06-30'],
            [28, 'GRD-001', 'SN-GRD-2020001', 'Substation 3',          'Siemens',       'Grounding System',       2020, '2026-02-28'],
        ],
        // 5: PT. Pertamina Hulu Rokan — oil & gas
        [
            [14, 'VSL-002', 'SN-VSL-2019001', 'Wellhead Platform A',   'ASME',          'Pressure Vessel 25Bar',  2019, '2025-11-30'],
            [16, 'STK-002', 'SN-STK-2018001', 'Tank Farm Rokan',       'Endress',       'Storage Tank 200m3',     2018, '2025-08-31'],
            [6,  'CMP-002', 'SN-CMP-2017001', 'Compressor Station',    'Ingersoll Rand','132kW Gas Compressor',   2017, '2025-05-31'],
            [24, 'MDP-002', 'SN-MDP-2020001', 'Control Building',      'ABB',           'Panel MDP 1000A',        2020, '2025-12-31'],
            [29, 'UPS-001', 'SN-UPS-2019001', 'Server Room',           'APC',           'UPS 10 kVA',             2019, '2025-09-30'],
            [8,  'PMP-002', 'SN-PMP-2018001', 'Pump Station B',        'Sulzer',        'Process Pump 200kW',     2018, '2026-01-31'],
        ],
        // 6: PT. Pupuk Kalimantan Timur — fertilizer (urea/ammonia)
        [
            [6,  'CMP-003', 'SN-CMP-2017002', 'Ammonia Plant',         'Ingersoll Rand','132kW Compressor',       2017, '2025-05-31'],
            [14, 'VSL-003', 'SN-VSL-2018002', 'Urea Plant',            'ASME',          'Pressure Vessel 20Bar',  2018, '2025-10-31'],
            [16, 'STK-003', 'SN-STK-2019002', 'Tank Farm',             'Endress',       'Storage Tank 100m3',     2019, '2026-01-31'],
            [8,  'PMP-003', 'SN-PMP-2018002', 'Pump Station C',        'Sulzer',        'Process Pump 150kW',     2018, '2025-07-31'],
            [25, 'TRF-002', 'SN-TRF-2018002', 'Substation 3',          'Schneider',     'Trafo 1000 KVA',         2018, '2025-08-31'],
            [0,  'OHC-002', 'SN-OHC-2019002', 'Maintenance Bay',       'Demag',         '10T Overhead Crane',     2019, '2026-02-28'],
        ],
        // 7: PT. Inalum — aluminum smelting
        [
            [0,  'OHC-003', 'SN-OHC-2016002', 'Casting Plant',         'ABB Cranes',    '20T Overhead Crane',     2016, '2025-04-30'],
            [1,  'MCR-002', 'SN-MCR-2018002', 'Pot Line Access',       'Liebherr',      '80T Mobile Crane',       2018, '2025-09-30'],
            [26, 'GNS-003', 'SN-GNS-2019002', 'Emergency Room',        'MAN',           'Genset 1000 kVA',        2019, '2026-03-31'],
            [24, 'MDP-003', 'SN-MDP-2017002', 'Substation 4',          'ABB',           'Panel MDP 2500A',        2017, '2025-06-30'],
            [28, 'GRD-002', 'SN-GRD-2018002', 'Pot Room',              'ABB',           'Grounding System HV',    2018, '2025-11-30'],
            [9,  'MTD-002', 'SN-MTD-2017002', 'Generator House',       'Wartsila',      'Diesel Engine 500HP',    2017, '2025-05-31'],
        ],
        // 8: PT. Industri Kapal Indonesia — shipbuilding
        [
            [1,  'MCR-003', 'SN-MCR-2017002', 'Dry Dock 1',            'Gottwald',      '100T Gantry Crane',      2017, '2025-08-31'],
            [0,  'OHC-004', 'SN-OHC-2018002', 'Assembly Hall',         'Konecranes',    '15T Overhead Crane',     2018, '2025-12-31'],
            [11, 'PRS-002', 'SN-PRS-2019002', 'Metal Workshop',        'AMADA',         '200T Press Machine',     2019, '2026-01-31'],
            [6,  'CMP-004', 'SN-CMP-2019002', 'Air Station',           'Kaeser',        '55kW Compressor',        2019, '2025-07-31'],
            [19, 'HYD-002', 'SN-HYD-2018002', 'Wharf Area',            'Viking',        'Hydrant Pillar 6"',      2018, '2025-10-31'],
            [26, 'GNS-004', 'SN-GNS-2018002', 'Dock Power',            'Cummins',       'Genset 750 kVA',         2018, '2025-04-30'],
        ],
        // 9: PT. Pusri — fertilizer (urea)
        [
            [16, 'STK-004', 'SN-STK-2017002', 'Ammonia Tank',          'Matrix',        'Storage Tank 200m3',     2017, '2025-06-30'],
            [14, 'VSL-004', 'SN-VSL-2018003', 'CO2 Recovery Unit',     'ASME',          'Pressure Vessel 25Bar',  2018, '2025-11-30'],
            [13, 'BLR-003', 'SN-BLR-2016002', 'Utility Plant',         'Thermax',       'Water Tube 20 Bar',      2016, '2025-03-31'],
            [8,  'PMP-004', 'SN-PMP-2019003', 'Transfer Station',      'Grundfos',      'Ammonia Pump 100kW',     2019, '2026-02-28'],
            [25, 'TRF-003', 'SN-TRF-2017003', 'Main Substation',       'Trafindo',      'Trafo 1600 KVA',         2017, '2025-09-30'],
            [7,  'GNS-005', 'SN-GNS-2016002', 'Emergency Power',       'Caterpillar',   'Genset 400 kVA',         2016, '2025-12-31'],
        ],
    ];

    /** Branch IDs in seeder order (matches $branches array in AssociationsSeeder) */
    private array $branch_ids = [
        2511, 2512, 2513, 2514,             // Krakatau (4)
        2515, 2516, 2517, 2518, 2519,       // Indofood (5)
        2520, 2521, 2522, 2523,             // Astra (4)
        2524, 2525, 2526,                   // Djarum (3)
        2527, 2528, 2529, 2530,             // Petrokimia (4)
        2531, 2532, 2533,                   // Pertamina (3)
        2534, 2535, 2536,                   // Pupuk Kaltim (3)
        2537, 2538, 2539, 2540,             // Inalum (4)
        2541, 2542, 2543,                   // IKI (3)
        2544, 2545, 2546, 2547, 2548,       // Pusri (5)
    ];

    private array $locations = [
        'Production Area', 'Utility Room', 'Warehouse', 'Workshop',
        'Storage Area', 'Maintenance Bay', 'Loading Dock', 'Substation',
        'Boiler Room', 'Pump House', 'Fire Station', 'Generator Room',
    ];

    private array $brands = [
        'Konecranes', 'Atlas Copco', 'Grundfos', 'Schneider', 'ABB',
        'Siemens', 'Caterpillar', 'Cummins', 'Thermax', 'Demag',
        'Liebherr', 'Ingersoll Rand', 'Sulzer', 'Viking', 'APC',
    ];

    private array $model_types = [
        // 0-5: Angkat/Angkut
        'Overhead Crane', 'Mobile Crane', 'Forklift', 'Hoist Listrik', 'Conveyor Belt', 'Gondola',
        // 6-11: Tenaga/Produksi
        'Kompresor Udara', 'Generator Set', 'Pompa Industri', 'Motor Diesel', 'Turbin Uap', 'Mesin Press',
        // 12-17: Uap/Bejana
        'Boiler Fire Tube', 'Boiler Water Tube', 'Bejana Tekan', 'Heat Exchanger', 'Storage Tank', 'Autoclave',
        // 18-23: Proteksi Kebakaran
        'APAR CO2', 'APAR Powder', 'Hydrant Pillar', 'Sprinkler System', 'Fire Alarm', 'Fire Suppression',
        // 24-29: Listrik/Petir
        'Panel MDP', 'Transformator', 'Genset Listrik', 'Penyalur Petir', 'Grounding System', 'UPS',
        // 30-35: Elevator
        'Elevator Penumpang', 'Elevator Barang', 'Eskalator', 'Moving Walk', 'Dumbwaiter', 'Lift Disabilitas',
    ];

    public function run(array $association_ids = [], array $equipments = []): array
    {
        $this->db->where('id >=', $this->id_start)
                 ->delete(db_prefix() . 'association_equipment');

        $item_ids  = $equipments['item_ids'] ?? [];
        $n_items   = count($item_ids);
        $inserted  = [];
        $static_id = $this->id_start;

        // ── 1. Company equipment (static assignments) ────────────────────────
        foreach ($association_ids as $ci => $client_id) {
            $addedfrom = $this->get_entity_staff_id($client_id, 'association');
            $rows = $this->assignments[$ci] ?? [];
            foreach ($rows as [
                $item_idx, $unit_code, $serial, $location,
                $brand, $model, $year, $cert_exp
            ]) {
                $item_id = $item_ids[$item_idx] ?? null;
                if (!$item_id) { $static_id++; continue; }

                $this->db->query(
                    "INSERT INTO " . db_prefix() . "association_equipment
                     (id, client_id, item_id, unit_code, serial_number, location, brand,
                      model_type, procurement_year, cert_expired_date, addedfrom, datecreated)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $static_id, $client_id, $item_id,
                        $unit_code, $serial, $location, $brand, $model,
                        $year, $cert_exp, $addedfrom, $this->now,
                    ]
                );

                $inserted[] = $static_id;
                $static_id++;
            }
        }

        // ── 2. Branch equipment (deterministic: 5, 8, or 10 per branch) ─────
        if ($n_items === 0) {
            return $inserted;
        }

        $counts = [5, 8, 10];

        foreach ($this->branch_ids as $bi => $branch_id) {
            $branch_addedfrom = $this->get_branch_staff_id($branch_id, 'association');
            $count = $counts[$bi % 3];

            for ($eq = 0; $eq < $count; $eq++) {
                $item_idx = (($bi * 11) + $eq) % $n_items;
                $item_id  = $item_ids[$item_idx];

                $location   = $this->locations[($bi + $eq) % count($this->locations)];
                $brand      = $this->brands[($bi * 3 + $eq) % count($this->brands)];
                $model_type = $this->model_types[$item_idx % count($this->model_types)];
                $proc_year  = 2015 + (($bi + $eq) % 8);
                $exp_year   = 2025 + (($bi + $eq) % 2);
                $exp_month  = sprintf('%02d', (($bi * 5 + $eq * 3) % 12) + 1);
                $exp_day    = sprintf('%02d', (($bi * 7 + $eq * 11) % 28) + 1);
                $cert_exp   = "$exp_year-$exp_month-$exp_day";

                $this->db->query(
                    "INSERT INTO " . db_prefix() . "association_equipment
                     (id, client_id, item_id, unit_code, serial_number, location, brand,
                      model_type, procurement_year, cert_expired_date, addedfrom, datecreated)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $static_id,
                        $branch_id,
                        $item_id,
                        sprintf('BR%02d-E%02d', $bi + 1, $eq + 1),
                        sprintf('SN-B%d-%02d', $branch_id, $eq + 1),
                        $location,
                        $brand,
                        $model_type,
                        $proc_year,
                        $cert_exp,
                        $branch_addedfrom,
                        $this->now,
                    ]
                );

                $inserted[] = $static_id;
                $static_id++;
            }
        }

        return $inserted;
    }
}
