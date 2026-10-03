---
paths:
  - 'resources/views/admin/**/*.blade.php'
---

# Views Admin

## Use filter-bar and status-multiselect for list filters
List-page filters use <x-admin.filter-bar> (GET form, preserve sort/direction, Filter/Reset). Multi-status dropdowns use <x-admin.status-multiselect>. Appliance status queries that treat Triage as null/empty/Triage go through App\Support\ApplianceStatusFilter. Do not reintroduce per-page status-menu JS/CSS or Apply/Clear/Search label variants.
