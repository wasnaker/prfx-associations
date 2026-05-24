# Module: associations

## Recent Work

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
