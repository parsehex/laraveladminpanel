---
paths:
  - app/Support/InventoryCostRange.php
  - 'app/Support/{DataTable,UserPreferences}.php'
  - app/Support/PageSize.php
  - app/Support/TruckUnitLabels.php
---

# Support

## Inventory cost ranges count units added, status as of end date
The Inventory cost structure and Trucks breakdown panels share App\Support\InventoryCostRange (cost_period presets daily/weekly/monthly/yearly/all matching the dashboard, or custom cost_from/cost_date). A range includes units whose created_at falls in it; status comes from inventory_status_histories only when the end date is before today, otherwise the live status. Both panels must build rows via rowsQuery() so their totals agree.

## Per-user prefs live in user_preferences; DataTable sort/columns use them
UI preferences that must follow the user across browsers go in `user_preferences` via `App\Support\UserPreferences` (key/value JSON). DataTable sorts use `data_table.sort.{storageKey}` via `resolveSortRequest()` (clear with `?sort=`). Column visibility uses `data_table.columns.{storageKey}`, hydrated into Alpine from the server and saved through `PUT/DELETE admin/preferences` (`UserPreferenceController`). localStorage stays a write-through cache / one-time migrate source for columns. Sidebar folder/collapse state remains browser localStorage for now.

## Page size prefs are global under page_size
Rows-per-page is one shared preference (`user_preferences` key `page_size`) via `PageSize::resolve()`. A valid `?limit=` on any list saves it; when absent, that same value is used everywhere. No per-table page-size keys. URL `limit` still wins when present.

## Unit labels are {truck name}-{###}
Unit labels use the truck name plus a trailing number padded to at least 3 digits (Gamma-001). The developer-only fix on the truck page rewrites them. Sort Unit Label keeps each item's trailing number when it is unique; an item already in {name}-{###} wins a collision, and leftovers take the lowest free numbers. Any other sort renumbers 001…n in that order.
