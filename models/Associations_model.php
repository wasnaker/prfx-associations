<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Associations_model extends App_Model
{
    private $statuses = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');

        $this->statuses = hooks()->apply_filters('before_set_association_statuses', [
            'pending',
            'active',
            'inactive',
        ]);        
    }

    /**
     * Get one association or all associations from tblclients.
     */
    public function get($id = false)
    {
        if ($id) {
            return $this->db->get_where(db_prefix() . 'clients', ['userid' => $id])->row();
        }
        return $this->db->get(db_prefix() . 'clients')->result_array();
    }

    /**
     * Get association statuses
     *
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    /**
     * Check if a vat value is already used by another client.
     * Returns true if the vat is available (not a duplicate).
     *
     * @param string $vat
     * @param int    $exclude_id  userid to exclude (for updates)
     */
    public function is_vat_available($vat, $exclude_id = 0)
    {
        if ($vat === '') {
            return true; // empty is always allowed
        }
        $this->db->where('vat', $vat);
        if ($exclude_id) {
            $this->db->where('userid !=', $exclude_id);
        }
        return $this->db->count_all_results(db_prefix() . 'clients') === 0;
    }

    /**
     * Add a new association — delegates to core clients_model.
     * Returns new client ID on success, false on failure.
     */
    public function add($data)
    {
        return $this->clients_model->add($data);
    }

    /**
     * Update a association — delegates to core clients_model.
     * Core signature: update($data, $id) — note data comes first.
     */
    public function update($id, $data)
    {
        return $this->clients_model->update($data, $id);
    }

    /**
     * Delete a association — delegates to core clients_model.
     */
    public function delete($id)
    {
        return $this->clients_model->delete($id);
    }

    public static $permit_statuses = ['active', 'pending', 'expired', 'revoked'];

    public static $permit_status_labels = [
        'active'  => 'label-success',
        'pending' => 'label-warning',
        'expired' => 'label-danger',
        'revoked' => 'label-default',
    ];

    /**
     * Get permits count for badge.
     */
    public function get_permits_count($association_id)
    {
        return $this->db->where('association_id', $association_id)
            ->count_all_results(db_prefix() . 'association_permits');
    }

    /**
     * Get a single permit.
     */
    public function get_permit($id)
    {
        return $this->db->get_where(db_prefix() . 'association_permits', ['id' => $id])->row();
    }

    /**
     * Add a permit.
     */
    public function add_permit($data, $file_path = '')
    {
        $this->db->insert(db_prefix() . 'association_permits', [
            'association_id'  => (int) $data['association_id'],
            'number'       => $data['number'] ?? '',
            'groupid'      => (int) ($data['groupid'] ?? 0),
            'publish_date' => !empty($data['publish_date']) ? $data['publish_date'] : null,
            'expired_date' => !empty($data['expired_date']) ? $data['expired_date'] : null,
            'status'       => in_array($data['status'] ?? '', self::$permit_statuses) ? $data['status'] : 'active',
            'file'         => $file_path,
            'addedfrom'    => get_staff_user_id(),
            'datecreated'  => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    /**
     * Update a permit.
     */
    public function update_permit($id, $data, $file_path = null)
    {
        $update = [
            'number'       => $data['number'] ?? '',
            'groupid'      => (int) ($data['groupid'] ?? 0),
            'publish_date' => !empty($data['publish_date']) ? $data['publish_date'] : null,
            'expired_date' => !empty($data['expired_date']) ? $data['expired_date'] : null,
            'status'       => in_array($data['status'] ?? '', self::$permit_statuses) ? $data['status'] : 'active',
        ];
        if ($file_path !== null) {
            $update['file'] = $file_path;
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'association_permits', $update);
        return $this->db->affected_rows() >= 0;
    }

    /**
     * Delete a permit and its file.
     */
    public function delete_permit($id)
    {
        $permit = $this->get_permit($id);
        if (!$permit) { return false; }

        if (!empty($permit->file)) {
            $path = FCPATH . 'uploads/association_permits/' . $permit->file;
            if (file_exists($path)) { unlink($path); }
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'association_permits');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get activity log for a association.
     */
    public function get_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'association');
        $this->db->order_by('date', 'desc');
        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log an activity entry for a association.
     *
     * @param int    $id
     * @param string $description  lang key
     * @param string $additional_data  plain-text diff string (optional)
     */
    public function log_activity($id, $description = '', $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name($staffid);
        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'association',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Compare old and new data, return human-readable diff string.
     * Only includes fields that actually changed.
     *
     * @param array $old       old record as array
     * @param array $new       new POST data
     * @param array $fields    [ db_column => display_label ]
     * @return string
     */
    public function build_diff(array $old, array $new, array $fields)
    {
        $changes = [];
        foreach ($fields as $key => $label) {
            $oldVal = isset($old[$key]) ? trim((string)$old[$key]) : '';
            $newVal = isset($new[$key]) ? trim((string)$new[$key]) : '';
            if ($oldVal !== $newVal) {
                $changes[] = $label . ': "' . $oldVal . '" → "' . $newVal . '"';
            }
        }
        return implode("\n", $changes);
    }
}
