---
paths:
  - app/Models/InventoryStatus.php
  - app/Models/TruckAppliance.php
---

# Models

## Inventory statuses are a locked-name catalog
Seeded inventory status names are system/locked because workflows still compare those strings (Testing, Sold, Demanufacture, etc.). Admins can add custom statuses, set optional auto_location, and archive unused non-system statuses. Items keep storing the status name string — no status_id FK yet. Picker lists (`activeNames` / `assignableNames`) are alphabetical; Manage → Statuses and status-count chips still use `sort_order`.

## Locations: free-text on items, optional catalog for floor places
Item location stays a free-text string on truck_appliances (no location_id FK). Manage → Locations maintains an `inventory_locations` catalog (add/rename) so names like Inventory exist before any item uses them. Pickers suggest catalog ∪ currently used labels. Status auto-locations (SOLD, SHOWROOM, Scrap, …) are not catalogued and keep writing free-text on status change; a later per-item location edit sticks.

## Total Cost is price plus live parts sum
Appliance Total Cost = Our Cost (price) + SUM(appliance_parts.cost). Do not store total_cost or total_parts_cost on truck_appliances — always compute from attached parts. Demanufacture/Scrap always add parts (no sign flip). Use partsCost()/totalCost() or withSum('parts as parts_sum_cost', 'cost') for display and partsCostSql()/totalCostSql() for sorts/aggregates.
