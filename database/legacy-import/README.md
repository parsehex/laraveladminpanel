# Legacy import

Import appliance-world data from a legacy `pg_dump` **data-only** SQL file.

## Quick start

1. Copy a fresh dump into `database/legacy-import/dumps/` (gitignored).
2. Point `database/legacy-import/manifest.json` at it, or pass `--data=`.
3. Review patches in `database/legacy-import/patches/`.
4. Run report → dry-run → import.

```bash
php artisan legacy:import --report-only
php artisan legacy:import --dry-run
php artisan legacy:import --reset
php artisan legacy:import --verify
```

## Patches (your edits)

| File | Purpose |
| --- | --- |
| `patches/users.php` | Map legacy usernames → existing users or `create_inactive` |
| `patches/categories.php` | Override category name normalization |
| `patches/models.php` | Stub orphan `model_number` values (`'stub'`) |
| `patches/testing.php` | Force re-eval rows into `repair_results` |

## Scope

**Imports:** trucks, appliances, models, parts, status history, user actions, testing/repair results, suggestions, custom sales.

**Does not touch:** kits, kit inventory, roles/permissions (except inactive users created by patch).

**IDs:** `truck_appliances.id` is set to the legacy `truck_items.id` so existing QR codes keep resolving.

`--reset` truncates appliance-world tables and `legacy_id_map`, then re-imports.

## Reports

Written to `storage/legacy-import/reports/{timestamp}/report.json`.

## Photos

Photo binaries are **not** imported by this command. Legacy paths are stored as-is in `truck_appliances.photos` until a separate photo step copies files onto the `public` disk.
