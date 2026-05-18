# tiaa-quick-edit — Claude Code Context
# Last updated: 2026-05-17

## What This Is

Adds **Sort Order** (`menu_order`) and **Excerpt** fields to the WordPress
Quick Edit panel, and a sortable Sort Order column to the Posts and Pages list
tables. Scoped to posts in the `hot-topics` and `discourse-categories`
categories, and to all Pages.

Standalone admin utility — no dependency on other TIAA plugins and no
Discourse integration. Part of the tiaa-v3 project (see `../CLAUDE.md`).

---

## File Structure

```
tiaa-quick-edit/
├── tiaa-quick-edit.php        ← all plugin logic (single-file plugin)
├── tiaa-quick-edit.css        ← column and quick-edit field styles
├── tiaa-quick-edit.js         ← Quick Edit JS (populates fields from inline data)
└── tiaa-quick-edit-debug.js   ← debug build of the JS
```

---

## Scoping Rules

Defined by `TIAA_QE_CATEGORY_SLUGS = ['hot-topics', 'discourse-categories']`:

| Post type | Sort Order field | Excerpt field |
|---|---|---|
| Posts in scoped categories | Yes | Yes |
| Posts outside scoped categories | No | Yes |
| Pages | Yes | Yes |

---

## Architecture Notes

**Hook specificity (v1.5.2 fix):** Uses post-type-specific column hooks
(`manage_post_posts_columns`, `manage_page_posts_columns`) instead of the
broad `manage_posts_columns`. The broad hook fires for ALL post types including
Elementor templates and WooCommerce products — the specific hooks do not.
Do not revert to the broad hooks.

**Quick Edit JS pattern:** WordPress Quick Edit doesn't re-render from the
server — JS must read inline data attributes from the list table row and
populate the Quick Edit fields client-side on open. `tiaa-quick-edit.js`
handles this. When adding new fields, follow the same inline-data → JS pattern.

---

## Code Style

- Single-file procedural plugin (no namespace, no classes)
- WordPress coding standards
- Docblock author: `Lew Grothe, TIAA Forum Admin Platform sub-team`
- Conventional commits: `feat:`, `fix:`, `chore:`
- Dates: YYYY-MM-DD