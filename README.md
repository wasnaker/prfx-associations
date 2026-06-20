# Associations Module

Association (professional body/organization) management with surveyor registration approval workflow for Wasnaker CRM.

## Category
Actors

## Key Features

- **Association CRUD** — DataTable + Pipeline (kanban) views, statuses (pending/active/inactive)
- **Registration** — Web (Associationauth) + API (Associations_api) with anti-spam (rate-limit, honeypot, CSRF, timing, reCAPTCHA)
- **Surveyor Registration Management** — Surveyors register to associations; association staff can approve/reject/mark-as-pending
- **Surveyor Permits View** — See permits of connected surveyors
- **Equipment Tracking** — Track unit_code, serial_number, location, cert_expired_date
- **Permits** — Manage association permits (active/pending/expired/revoked)
- **VAT** — Stored on `tblclients.vat` (global uniqueness check)
- **enforce_minimum_price** — Association-specific pricing policy
- **Signature & Numbering** — Custom association numbers (prefix + formatted), signature management
- **Activity Log** — Sales activity with diff tracking
- **Inactive Company Modal** — Profile completeness check (7 fields) + restricted access until complete
- **Permit Expiry Reminder** — Cron-based to connected customers
- **Auto-expire** — Cron auto-expires overdue associations (status Sent → Expired)
- **Connection-based Access** — Staff sees only connected associations
- **Logo Upload** — AJAX upload/delete endpoint (light/dark)
- **Settings** — Finance settings tab
- **Email Scheduling** — Scheduled notifications

## Controllers

| Controller | Route | Description |
|---|---|---|
| Associations | `/admin/associations/` | Main CRUD, DataTable, pipeline, logo uploads, numbering |
| Associationauth | `/authentication/register/association` | Web registration with anti-spam |
| Associations_api | `/api/v1/associations/` | REST API for surveyor registrations (register/approve/reject) |
| Email_schedule_association | `/admin/email_schedule_association/` | Scheduled email management |

## API Endpoints (Associations_api via Api_base)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/associations/my` | Surveyor views their association registrations |
| GET | `/api/v1/associations/{id}/registrations` | Association owner views surveyor registrations |
| POST | `/api/v1/associations/{id}/registrations/{surveyor_id}` | Register/re-register surveyor |
| PATCH | `/api/v1/associations/{id}/registrations/{surveyor_id}` | Approve/reject registration |

## Helpers (8)

- `associations_menu_helper` — Sidebar menu
- `associations_capability_helpers` — Permissions, entity-owner scoping
- `associations_email_templates_helper` — Email template registration
- `associations_datatables_helper` — DataTable filters + connection-based access
- `association_relation_helpers` — Relation data hooks
- `associations_helper` — PDF, format, numbering functions
- `associations_global_search_helper` — Global search integration

## Database Tables

- `tblclients` (client_type='association')
- `tblassociations` — Association numbering (prefix, formatted_number)
- `tblsurveyors_associations` — Junction: surveyor ↔ association registration (status: pending/active/rejected)
- `tblassociation_permits` — Permits with file upload
- `tblassociation_equipment` — Equipment records
- `tblassociation_activity` — Activity log
- `tblreg_ratelimit` — Registration rate-limit

## Key Differences from Surveyors Module

| Aspect | Surveyors | Associations |
|---|---|---|
| API base | `REST_Controller` (REST Server) | `Api_base` (JWT + Apps) |
| Legal docs | 8 types (nib/npwp/akte/bpjs) | None |
| Unique feature | Inactive company modal (8 fields) | Surveyor registration approval workflow |
| Pricing policy | No | `enforce_minimum_price` |
| Numbering | No dedicated table | `tblassociations` with prefix/formatted_number |
| Signature | No | `clear_signature` |
| Logo upload | Form POST only | AJAX upload/delete endpoint |

## Dependencies

- `apps` module (Api_base, entity helpers)
- Core: `clients_model`, `staff_model`, `invoice_items_model`
