---
paths:
  - app/Support/InventoryCostRange.php
---

# Support

## Inventory cost ranges count units added, status as of end date
The Inventory cost structure and Trucks breakdown panels share App\Support\InventoryCostRange (cost_period presets daily/weekly/monthly/yearly/all matching the dashboard, or custom cost_from/cost_date). A range includes units whose created_at falls in it; status comes from inventory_status_histories only when the end date is before today, otherwise the live status. Both panels must build rows via rowsQuery() so their totals agree.
