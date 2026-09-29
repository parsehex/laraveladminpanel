---
paths:
  - 'app/Imports/**'
---

# Imports

## Appliance CSV import uses preview then confirm
Appliance CSV import is a two-step preview-then-confirm flow: upload stages the file, review shows create/update/error tables with field diffs, confirm re-parses and commits. Do not write appliances on the initial POST.
