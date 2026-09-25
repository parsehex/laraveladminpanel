---
paths:
  - 'resources/views/{layouts,components}/**'
---

# Interface Rules

## Use Fancy Tooltips Over Built-In

When tooltips are requested, use nicer tooltips than the built-in browser-provided ones. You can find an example of these tooltips in the nav/sidebar when it's collapsed / is set to icons-only.

## Sidebar link folders open from the chevron
Some sidebar rows are both a page link and a folder (Kits, Users). The label navigates; only the chevron toggles the folder. Folder-only rows (Inventory, Manage, Procedures) toggle from the label. Open state is an html class `sidebar-folder-{id}-open`, set before paint from localStorage or `sidebarFoldersOpenForRequest()`. A child page must be included there so the parent folder highlights and opens. Do not force a link-folder open just because its own page is active.
