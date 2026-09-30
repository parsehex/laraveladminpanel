---
paths:
  - app/Http/Controllers/Admin/DashboardController.php
---

# Admin

## Dashboard activity uses user_actions
User Activity Statistics must aggregate from user_actions with the same action_type buckets as legacy dashboard.php (add_truck, delete_truck, add_appliance/create_appliance, delete_appliance, test_unit, deman_unit, repair_unit+test_unit for repaired, showroom_sent, mark_sold).

## Operations KPIs follow the period filter
Operations cards under the period filter must use the same date range: Units Added = truck_appliances.created_at in range; Inventory Value = SUM(price + parts) for those added units still not Sold/Show Room; Sold Units / Sales Total = Sold appliances with COALESCE(sold_at, updated_at) in range plus custom_sales.created_at in range.
