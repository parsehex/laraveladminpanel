---
paths:
  - 'scripts/**'
---

# Scripts

## Dev DB restore from dumps
Use scripts/restore-dev-db.sh (composer restore:dev) to load a Postgres dump into the local dev DB. It auto-detects Dokploy gzipped -Fc dumps (*.sql.gz), custom PGDMP dumps, and plain SQL. After restore it migrates and seeds InventoryStatusSeeder, FlowSeeder, and local-only UserSeeder. Never point it at *_testing.
