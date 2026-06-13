# Module: associations

## Recent Work

### DataTable Hooks Consolidation (2026-05-24)

### DataTable Hooks Consolidation (2026-05-24)

**File:** `helpers/associations_datatables_helper.php` (created)

**Change:** Consolidated all cross-module datatable filtering hooks into dedicated helper file.

**Contents:**
- Hook registrations for 11 filter callbacks
- Surveyors table: sql_where, sql_join, sql_columns, row_data, row_options, profile protection
- Personnel permits: where clause filtering + profile protection
- Surveyor permits: where clause filtering + profile protection
- Helper functions: _associations_is_association_entity_user, _associations_get_connected_surveyor_ids

**Pattern:** All cross-module datatable manipulation moved to dedicated helper; association entity users see only connected surveyors and related permits.

**Commit:** c65f172

---

### init_relation_options() Permission Fix (2026-05-24)

**File:** `helpers/association_relation_helpers.php`

**Change:** Updated `associations_init_relation_options()` to check both 'view' AND 'view_own' permissions.

**Pattern:** 
```php
if (!staff_can('view', 'associations') && !staff_can('view_own', 'associations')) {
    return [];
}
```

**Logic:** Deny access only when user has **neither** view nor view_own capability. Allow if user has either one (or both).

**Reason:** Previously only checked `has_permission(..., 'view')`, blocking staff with only `view_own` permission. This prevented them from accessing ajax-search relation selects.

**Commit:** 39ff3b7

**Related:** /public_html/audit_relation_helper.md (documentation for all 12 modules)

---

## Notes
- This fix was applied consistently across 12 modules
- Two modules were NOT changed: rfqs (already correct) and billing_payments (intentionally no guard)
- See architecture_permission_system.md in parent project memory for complete permission system documentation


---

### ⛔ DILARANG KERAS — JANGAN PERNAH GUNAKAN `selectpicker` DI FORM

**SANGAT KERAS. BAHKAN SANGAT KERAS SEKALI. TIDAK ADA PENGECUALIAN.**

#### ❌ SALAH — MENYEBABKAN DOUBLE INIT DAN FIELD DISABLED
```html
<select class="selectpicker ajax-search" ...>
<select class="selectpicker" name="some_field" ...>
```

#### ✅ BENAR — Ajax-search field
```html
<select class="ajax-search" data-live-search="true" data-width="100%" ...>
```

#### ✅ BENAR — Static select (non-ajax)
```html
<select class="display-block" name="status" ...>
```

**Kenapa:**
- `class="selectpicker"` menyebabkan `init_selectpicker()` global menginisialisasi field SEBELUM `init_ajax_search()` dipanggil
- Double init = ajaxSelectPicker conflict = field tidak bisa search = field tampak **disabled**
- `init_ajax_search()` sudah memanggil `.selectpicker()` secara programatik — tidak perlu class `selectpicker`
- Selector JS untuk ajax-search field WAJIB pakai `.ajax-search`, BUKAN `.selectpicker`

**Akibat yang pernah terjadi:**
- `requestor_id` disabled di semua form (Create + Edit)
- `commissions_policy_id` ikut disabled setelah class `selectpicker` dihapus karena selector JS belum diupdate
- Membutuhkan debugging panjang dengan log untuk menemukan root cause

**Rule tambahan — saat hapus/ubah class pada field:**
1. GREP JS dulu: `grep -rn "field_id.selectpicker\|field_id.ajax-search" assets/js/`
2. Update semua selector JS yang bergantung pada class tersebut
3. BARU ubah class di template

---
