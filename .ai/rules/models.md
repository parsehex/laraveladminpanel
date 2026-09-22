---
paths:
  - app/Models/InventoryStatus.php
  - app/Models/TruckAppliance.php
---

# Models

## Inventory statuses are a locked-name catalog
Seeded inventory status names are system/locked because workflows still compare those strings (Testing, Sold, Demanufacture, etc.). Admins can add custom statuses, set optional auto_location, and archive unused non-system statuses. Items keep storing the status name string — no status_id FK yet.

## Locations stay free-text; auto-set only on status change
Item location stays a free-text string on truck_appliances (no locations table/FK). Consolidate duplicate labels via the Manage → Locations rename/merge UI. Auto-location from a status applies only when status changes, so a later per-item location edit sticks.

## Total Cost is price plus live parts sum
Appliance Total Cost = Our Cost (price) + SUM(appliance_parts.cost). Do not store total_cost or total_parts_cost on truck_appliances — always compute from attached parts. Demanufacture/Scrap always add parts (no sign flip). Use partsCost()/totalCost() or withSum('parts as parts_sum_cost', 'cost') for display and partsCostSql()/totalCostSql() for sorts/aggregates.
