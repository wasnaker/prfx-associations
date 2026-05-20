<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'demo/database/seeds/BaseSeeder.php';

class AssociationItemsSeeder extends BaseSeeder
{
    private array $prices = [2000000, 2050000, 2100000, 2150000];

    /**
     * @param array $association_ids  output dari AssociationsSeeder: [$apjk3, $aspek3_banten, $haki_jkt]
     * @param array $item_ids         output dari EquipmentsSeeder['item_ids']: 36 item, urutan group 1-6
     */
    public function run(array $association_ids = [], array $item_ids = []): array
    {
        $this->safe_delete('association_items');

        if (empty($association_ids) || empty($item_ids)) {
            return [];
        }

        [$apjk3, $aspek3_banten, $haki_jkt] = array_pad($association_ids, 3, 0);

        // 36 items dalam urutan 6 group × 6 item
        // APJK3 Nasional    → semua 36 item (6 group)
        // ASPEK3 Banten     → 24 item (group 1–4: Angkat Angkut, Tenaga Produksi, Uap Bejana, Proteksi Kebakaran)
        // HAKI Jakarta      → 18 item (group 1–3: Angkat Angkut, Tenaga Produksi, Uap Bejana)
        $plan = [
            $apjk3         => array_slice($item_ids, 0, 36),
            $aspek3_banten => array_slice($item_ids, 0, 24),
            $haki_jkt      => array_slice($item_ids, 0, 18),
        ];

        $inserted_ids = [];

        foreach ($plan as $assoc_id => $items) {
            if (!$assoc_id) { continue; }
            foreach ($items as $item_id) {
                $inserted_ids[] = $this->insert('association_items', [
                    'association_id' => $assoc_id,
                    'item_id'        => $item_id,
                    'minimum_price'  => $this->prices[array_rand($this->prices)],
                ]);
            }
        }

        return $inserted_ids;
    }
}
