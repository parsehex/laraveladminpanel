---
paths:
  - app/Http/Controllers/Admin/DashboardController.php
  - app/Http/Controllers/Admin/InventoryController.php
---

# Admin

## Dashboard activity uses user_actions
User Activity Statistics must aggregate from user_actions with the same action_type buckets as legacy dashboard.php (add_truck, delete_truck, add_appliance/create_appliance, delete_appliance, test_unit, deman_unit, repair_unit+test_unit for repaired, showroom_sent, mark_sold).

## Operations KPIs follow the period filter
Operations cards under the period filter must use the same date range: Units Added = truck_appliances.created_at in range; Inventory Value = SUM(price + parts) for those added units still not Sold/Show Room; Sold Units / Sales Total = Sold appliances with COALESCE(sold_at, updated_at) in range plus custom_sales.created_at in range.

## Appliance add-part requires real part numbers
When adding a part on the appliance detail page, selecting an existing catalog part (part_id) is enough. Freeform adds must supply part_number; do not auto-generate fake part numbers. New numbers must be unique among active parts (soft-deleted may be restored).

## Input Requests are a feedback tab
Input requests are `suggestions.kind = input_request`, shown as the Input Requests tab inside Website Feedback (`feedback_kind` query). Create/complete with `suggestions.complete`; staff can reply with dashboard access. No notifications — show pending count as a colored badge on the tab. Staff FAB/forms always create `kind = suggestion`.
