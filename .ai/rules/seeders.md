---
paths:
  - 'database/seeders/**'
  - database/seeders/RolePermissionSeeder.php
---

# Seeders

## Local-only default developer user
UserSeeder creates/reasserts admin@yopmail.com / admin@123 as the `developer` role only when APP_ENV=local (including after `composer restore:dev`). It is a no-op on production/staging/testing so a renamed or rotated prod admin is never recreated. Keep the local account on developer (not admin) so dump restores stay aligned with local feedback-complete access. Deploy scripts seed InventoryStatusSeeder and FlowSeeder only.

## Developer role owns suggestions.complete
The `developer` staff role is admin-tier (same permissions as admin) plus exclusive `suggestions.complete`. Admins must not receive that permission. Gate mark-complete UI/route with `can('suggestions.complete')` / `permission:suggestions.complete`. Keep `developer` in config/authorization.php staff_roles, protected_role_names, and legacy_admin_role_values.
