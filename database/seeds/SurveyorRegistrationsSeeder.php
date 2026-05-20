<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'demo/database/seeds/BaseSeeder.php';

/**
 * Seeds tblsurveyors_associations — surveyor registration to associations.
 * Each surveyor HQ is registered to 2–3 associations with mixed statuses.
 * Requires: SurveyorsSeeder + AssociationsSeeder already run.
 */
class SurveyorRegistrationsSeeder extends BaseSeeder
{
    public function run(array $surveyor_ids = [], array $association_ids = []): array
    {
        // Load from DB if not passed
        if (empty($surveyor_ids)) {
            $surveyor_ids = array_column(
                $this->db->select('userid')->where('client_type', 'surveyor')
                    ->where('active', 1)
                    ->where('(company_id IS NULL OR company_id = 0)', null, false)
                    ->get(db_prefix() . 'clients')->result_array(),
                'userid'
            );
        }
        if (empty($association_ids)) {
            $association_ids = array_column(
                $this->db->select('userid')->where('client_type', 'association')
                    ->where('active', 1)
                    ->get(db_prefix() . 'clients')->result_array(),
                'userid'
            );
        }

        // Get Surveyor Admin staff keyed by client_id
        $surveyor_admins = [];
        $rows = $this->db->select('staffid, client_id')
            ->join(db_prefix() . 'roles r', 'r.roleid = ' . db_prefix() . 'staff.role')
            ->where('r.name', 'Surveyor Admin')->where('active', 1)
            ->get(db_prefix() . 'staff')->result_array();
        foreach ($rows as $r) { $surveyor_admins[(int)$r['client_id']] = (int)$r['staffid']; }

        // Get Association Admin staff keyed by client_id
        $assoc_admins = [];
        $rows = $this->db->select('staffid, client_id')
            ->join(db_prefix() . 'roles r', 'r.roleid = ' . db_prefix() . 'staff.role')
            ->where('r.name', 'Association Admin')->where('active', 1)
            ->get(db_prefix() . 'staff')->result_array();
        foreach ($rows as $r) { $assoc_admins[(int)$r['client_id']] = (int)$r['staffid']; }

        // Cleanup: remove stale rows whose surveyor_id no longer exists in tblclients
        if (!empty($surveyor_ids)) {
            $this->db->where_not_in('surveyor_id', $surveyor_ids)
                ->delete(db_prefix() . 'surveyors_associations');
        }

        // Status distribution per surveyor (cycles through associations)
        // Each surveyor gets 3 registrations: active, pending, rejected
        $status_cycle = ['active', 'pending', 'rejected'];

        $inserted = [];
        $assoc_count = count($association_ids);

        foreach ($surveyor_ids as $idx => $surveyor_id) {
            $registered_by = $surveyor_admins[$surveyor_id] ?? 1;

            for ($i = 0; $i < 3; $i++) {
                $assoc_id  = $association_ids[($idx * 3 + $i) % $assoc_count];
                $status    = $status_cycle[$i];
                $approved_by  = $assoc_admins[$assoc_id] ?? 1;

                // Skip if already exists (correct IDs, real data kept)
                $exists = $this->db->where('surveyor_id', $surveyor_id)
                    ->where('association_id', $assoc_id)
                    ->count_all_results(db_prefix() . 'surveyors_associations');
                if ($exists) { continue; }

                $row = [
                    'surveyor_id'     => $surveyor_id,
                    'association_id'  => $assoc_id,
                    'status'          => $status,
                    'date_registered' => date('Y-m-d H:i:s', strtotime("-{$i} months")),
                    'date_approved'   => $status === 'active'   ? date('Y-m-d H:i:s', strtotime("-{$i} months +3 days")) : null,
                    'reject_reason'   => $status === 'rejected' ? 'Dokumen tidak lengkap, mohon upload ulang.' : null,
                    'registered_by'   => $registered_by,
                    'approved_by'     => in_array($status, ['active', 'rejected']) ? $approved_by : 0,
                ];

                $id = $this->insert('surveyors_associations', $row);
                $inserted[] = $id;
            }
        }

        return $inserted;
    }
}
