---
paths:
  - 'resources/views/components/admin/**'
---

# Components Admin

## Blade components cannot push layout stacks reliably
Do not rely on @push/@pushOnce from Blade components for JS/CSS — those stacks often never reach the layout. Prefer Alpine (already on admin layout) or Tailwind utilities in the component markup, or a layout/Vite entry loaded outside the component.
