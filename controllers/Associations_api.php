<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Api_base')) {
    require_once(FCPATH . 'modules/apps/controllers/Api_base.php');
}

/**
 * Associations API — surveyor membership management.
 *
 * GET    /api/v1/associations/my                    — surveyor views their registrations
 * GET    /api/v1/associations/{id}/registrations    — association owner views surveyor registrations
 * GET    /api/v1/associations/{id}/registrations/{surveyor_id} — view specific registration
 * POST   /api/v1/associations/{id}/registrations/{surveyor_id} — register/re-register surveyor
 * PATCH  /api/v1/associations/{id}/registrations/{surveyor_id} — approve/reject registration
 */
class Associations_api extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('associations_model');
        $this->load->helper('associations/associations');
    }

    // ── GET /api/v1/associations/my ────────────────────────────────────────────
    /**
     * Surveyor views their association registrations.
     */
    public function my_registrations()
    {
        $this->authenticate();

        // Only surveyors can view their own registrations
        if ($this->api_client_type !== 'surveyor') {
            $this->error('Access denied.', 403);
        }

        $rows = $this->db
            ->select('sa.id, sa.association_id, sa.status, sa.date_registered, sa.date_approved, sa.reject_reason, c.company as association_name')
            ->from(db_prefix() . 'surveyors_associations sa')
            ->join(db_prefix() . 'clients c', 'c.userid = sa.association_id', 'left')
            ->where('sa.surveyor_id', (int) $this->api_client_id)
            ->order_by('sa.date_registered', 'DESC')
            ->get()
            ->result_array();

        $data = array_map(function ($row) {
            return $this->_registration_resource((object) $row);
        }, $rows);

        $this->json([
            'data' => $data,
        ]);
    }

    // ── GET /api/v1/associations/{id}/registrations ────────────────────────────
    /**
     * Association owner views surveyor registrations for their association.
     */
    public function registrations($association_id)
    {
        $this->authenticate();

        $association_id = (int) $association_id;

        // Verify association exists
        $association = $this->db
            ->get_where(db_prefix() . 'clients', ['userid' => $association_id, 'client_type' => 'association'])
            ->row();
        if (!$association) {
            $this->error('Association not found.', 404);
        }

        // Only association owner or staff can view registrations
        if (!$this->_is_association_owner($association_id) && !$this->_is_staff_admin()) {
            $this->error('Access denied.', 403);
        }

        $status = $this->input->get('status');

        $this->db
            ->select('sa.id, sa.surveyor_id, sa.status, sa.date_registered, sa.date_approved, sa.reject_reason, c.company as surveyor_name')
            ->from(db_prefix() . 'surveyors_associations sa')
            ->join(db_prefix() . 'clients c', 'c.userid = sa.surveyor_id', 'left')
            ->where('sa.association_id', $association_id);

        if ($status !== null) {
            $this->db->where('sa.status', trim($status));
        }

        $rows = $this->db
            ->order_by('sa.date_registered', 'DESC')
            ->get()
            ->result_array();

        $data = array_map(function ($row) {
            return $this->_registration_resource((object) $row);
        }, $rows);

        $this->json([
            'data'       => $data,
            'association' => [
                'id'   => (int) $association->userid,
                'name' => $association->company,
            ],
        ]);
    }

    // ── GET /api/v1/associations/{id}/registrations/{surveyor_id} ──────────────
    /**
     * View specific registration (surveyor or association owner).
     */
    public function registration($association_id, $surveyor_id)
    {
        $this->authenticate();

        $association_id = (int) $association_id;
        $surveyor_id = (int) $surveyor_id;

        $registration = $this->db
            ->select('sa.*, c.company as surveyor_name')
            ->from(db_prefix() . 'surveyors_associations sa')
            ->join(db_prefix() . 'clients c', 'c.userid = sa.surveyor_id', 'left')
            ->where('sa.association_id', $association_id)
            ->where('sa.surveyor_id', $surveyor_id)
            ->get()
            ->row();

        if (!$registration) {
            $this->error('Registration not found.', 404);
        }

        // Surveyor can only view their own, or association owner
        if ($this->api_client_type === 'surveyor' && (int) $this->api_client_id !== $surveyor_id) {
            $this->error('Access denied.', 403);
        }
        if ($this->api_client_type === 'surveyor' && (int) $registration->association_id !== $association_id) {
            $this->error('Access denied.', 403);
        }
        if (!$this->_is_association_owner($association_id) && !$this->_is_staff_admin() && $this->api_client_type === 'surveyor') {
            // OK, surveyor viewing their own
        } elseif (!$this->_is_association_owner($association_id) && !$this->_is_staff_admin()) {
            $this->error('Access denied.', 403);
        }

        $this->json([
            'data' => $this->_registration_resource($registration),
        ]);
    }

    // ── POST /api/v1/associations/{id}/registrations/{surveyor_id} ────────────
    /**
     * Register or re-register surveyor with association.
     * Creates new record if not exists, or resets to pending if rejected.
     */
    public function register($association_id, $surveyor_id)
    {
        $this->authenticate();

        if (!in_array($this->input->server('REQUEST_METHOD'), ['POST', 'PUT'])) {
            $this->error('Method not allowed.', 405);
        }

        $association_id = (int) $association_id;
        $surveyor_id = (int) $surveyor_id;

        // Only surveyor can register themselves
        if ($this->api_client_type !== 'surveyor' || (int) $this->api_client_id !== $surveyor_id) {
            $this->error('Access denied.', 403);
        }

        // Verify association exists
        $association = $this->db
            ->get_where(db_prefix() . 'clients', ['userid' => $association_id, 'client_type' => 'association'])
            ->row();
        if (!$association) {
            $this->error('Association not found.', 404);
        }

        // Verify surveyor exists
        $surveyor = $this->db
            ->get_where(db_prefix() . 'clients', ['userid' => $surveyor_id, 'client_type' => 'surveyor'])
            ->row();
        if (!$surveyor) {
            $this->error('Surveyor not found.', 404);
        }

        // Check if registration exists
        $existing = $this->db
            ->get_where(db_prefix() . 'surveyors_associations', [
                'association_id' => $association_id,
                'surveyor_id' => $surveyor_id,
            ])
            ->row();

        if ($existing) {
            // Can only re-register if rejected
            if ($existing->status !== 'rejected') {
                $this->error('Already registered or pending approval.', 422);
            }
            // Reset to pending
            $this->db->where('id', (int) $existing->id)->update(db_prefix() . 'surveyors_associations', [
                'status' => 'pending',
                'date_registered' => date('Y-m-d H:i:s'),
                'approved_by' => 0,
                'reject_reason' => null,
            ]);
        } else {
            // Create new registration
            $this->db->insert(db_prefix() . 'surveyors_associations', [
                'association_id' => $association_id,
                'surveyor_id' => $surveyor_id,
                'status' => 'pending',
                'date_registered' => date('Y-m-d H:i:s'),
                'registered_by' => (int) $this->api_staff_id,
            ]);
        }

        $registration = $this->db
            ->get_where(db_prefix() . 'surveyors_associations', [
                'association_id' => $association_id,
                'surveyor_id' => $surveyor_id,
            ])
            ->row();

        $this->json([
            'success' => true,
            'data' => $this->_registration_resource($registration),
        ]);
    }

    // ── PATCH /api/v1/associations/{id}/registrations/{surveyor_id} ────────────
    /**
     * Association owner approves or rejects registration.
     */
    public function update_registration($association_id, $surveyor_id)
    {
        $this->authenticate();

        if (!in_array($this->input->server('REQUEST_METHOD'), ['PATCH', 'POST', 'PUT'])) {
            $this->error('Method not allowed.', 405);
        }

        $association_id = (int) $association_id;
        $surveyor_id = (int) $surveyor_id;

        // Only association owner or staff can approve/reject
        if (!$this->_is_association_owner($association_id) && !$this->_is_staff_admin()) {
            $this->error('Access denied.', 403);
        }

        $body = $this->_json_body();
        $action = trim($body['action'] ?? '');

        if (!in_array($action, ['approve', 'reject'])) {
            $this->error('Invalid action. Must be "approve" or "reject".', 422);
        }

        $registration = $this->db
            ->get_where(db_prefix() . 'surveyors_associations', [
                'association_id' => $association_id,
                'surveyor_id' => $surveyor_id,
            ])
            ->row();

        if (!$registration) {
            $this->error('Registration not found.', 404);
        }

        if ($registration->status !== 'pending') {
            $this->error('Only pending registrations can be approved or rejected.', 422);
        }

        $new_status = $action === 'approve' ? 'active' : 'rejected';
        $update = [
            'status' => $new_status,
            'date_approved' => date('Y-m-d H:i:s'),
            'approved_by' => (int) $this->api_staff_id,
        ];

        if ($new_status === 'rejected') {
            $update['reject_reason'] = trim($body['reason'] ?? '');
        }

        $this->db->where('id', (int) $registration->id)->update(db_prefix() . 'surveyors_associations', $update);

        $updated = $this->db
            ->get_where(db_prefix() . 'surveyors_associations', ['id' => (int) $registration->id])
            ->row();

        $this->json([
            'success' => true,
            'data' => $this->_registration_resource($updated),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function _registration_resource(object $reg): array
    {
        return [
            'id'                => (int) $reg->id,
            'surveyor_id'       => isset($reg->surveyor_id) ? (int) $reg->surveyor_id : null,
            'surveyor_name'     => $reg->surveyor_name ?? '',
            'association_id'    => (int) $reg->association_id,
            'association_name'  => $reg->association_name ?? '',
            'status'            => $reg->status,
            'date_registered'   => $reg->date_registered,
            'date_approved'     => $reg->date_approved,
            'reject_reason'     => $reg->reject_reason ?? '',
        ];
    }

    private function _is_association_owner($association_id): bool
    {
        if ($this->api_client_type !== 'association') {
            return false;
        }
        return (int) $this->api_client_id === (int) $association_id;
    }

    private function _is_staff_admin(): bool
    {
        return $this->api_client_type === '' || $this->api_client_type === null;
    }

    private function _json_body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) { return $decoded; }
        }
        return $this->input->post() ?: [];
    }

    protected function json(array $response)
    {
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    protected function error(string $message, int $http_status = 400)
    {
        http_response_code($http_status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
