---
paths:
  - 'database/seeders/**'
---

# Seeders

## Local-only default admin user
UserSeeder creates admin@yopmail.com / admin@123 only when APP_ENV=local. It is a no-op on production/staging/testing so a renamed or rotated admin is never recreated. Do not forceFill/reset that user on every seed; firstOrCreate attributes only. Deploy scripts seed InventoryStatusSeeder and FlowSeeder only; local `composer restore:dev` also runs UserSeeder after loading a dump.
