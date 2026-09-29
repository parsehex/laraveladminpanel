---
paths:
  - 'app/Imports/**'
---

# Imports

## Admin CSV imports use preview then confirm
Admin CSV imports (trucks, appliances, parts, kit-parts) are two-step: upload stages a file, review shows create/update/error tables, confirm re-parses and commits. Use `StagedCsvImport` for staging. Do not write on the initial POST.
