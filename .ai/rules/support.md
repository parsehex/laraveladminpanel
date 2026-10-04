---
paths:
  - app/Support/InventoryCostRange.php
  - 'app/Support/{DataTable,UserPreferences}.php'
  - app/Support/PageSize.php
---

# Support

## Inventory cost ranges count units added, status as of end date
The Inventory cost structure and Trucks breakdown panels share App\Support\InventoryCostRange (cost_period presets daily/weekly/monthly/yearly/all matching the dashboard, or custom cost_from/cost_date). A range includes units whose created_at falls in it; status comes from inventory_status_histories only when the end date is before today, otherwise the live status. Both panels must build rows via rowsQuery() so their totals agree.

## Per-user prefs live in user_preferences; DataTable sorts use them
UI preferences that must follow the user across browsers go in `user_preferences` via `App\Support\UserPreferences` (key/value JSON), not localStorage. DataTable sorts use key `data_table.sort.{storageKey}` and are resolved through `DataTable::resolveSortRequest()` before `applySorting()`. Clearing sort uses `?sort=` (empty) so the preference is forgotten. Column visibility and sidebar state remain browser localStorage for now.

## Page size prefs are global under page_size
Rows-per-page is one shared preference (`user_preferences` key `page_size`) via `PageSize::resolve()`. A valid `?limit=` on any list saves it; when absent, that same value is used everywhere. No per-table page-size keys. URL `limit` still wins when present.
