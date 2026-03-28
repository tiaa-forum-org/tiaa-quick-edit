# TIAA Quick Edit Fields

**Version:** 1.5.1  
**Updated:** 2026-03-27  
**Location in repo:** `plugins/tiaa-quick-edit/`  
**Plugin slug:** `tiaa-quick-edit`  
**Text domain:** `tiaa-quick-edit`

---

## What This Does

Adds two fields to the WordPress **Quick Edit** panel for posts in the
`hot-topics`, `other-orgs` and `discourse-categories` categories, and adds a sortable
**Sort Order** column to the Posts list table.

Also adds an **Excerpt** field to the Quick Edit panel for **all Pages**, and
adds an **Excerpt** column to the Pages list table so excerpt status is visible
at a glance.

| Field | Post types | Controls | Why it matters |
|---|---|---|---|
| **Sort Order** | Posts (target categories only) | `menu_order` in `wp_posts` | Controls the order cards appear in Elementor Loop Grids sorted by Menu Order |
| **Excerpt** | Posts (target categories) + all Pages | `post_excerpt` in `wp_posts` | The summary text shown on cards; used by Yoast SEO for meta descriptions |

---

## File Structure

```
tiaa-quick-edit/
├── tiaa-quick-edit.php          # Main plugin file — hooks, AJAX, save
├── tiaa-quick-edit.js           # Quick Edit population + post-save cell update
├── tiaa-quick-edit.css          # Admin styles (badge, fieldset, inputs)
├── tiaa-quick-edit-debug.js     # Optional diagnostic script (see Debugging below)
├── readme.txt                   # WP plugin repository standard readme
└── README.md                    # This file
```

---

## Installation

1. Copy the `tiaa-quick-edit/` folder into `wp-content/plugins/`.
2. In WordPress Admin → **Plugins**, activate **TIAA Quick Edit Fields**.
3. Go to **Posts** — the Sort Order column and Quick Edit fields are active.
4. Go to **Pages** — the Excerpt column and Quick Edit Excerpt field are active.

---

## How to Use

### Updating Sort Order (Posts only)

1. Hover over any post row → click **Quick Edit**.
2. The **Sort Order** field pre-fills with the current value (blank if unset).
3. Enter a number. Leave blank to make no change.
4. Click **Update**.

> **Tip:** Use gaps between values (10, 20, 30) so you can insert new items
> later without renumbering everything.

### Updating Excerpt (Posts and Pages)

1. Hover over any post or page row → click **Quick Edit**.
2. The **Excerpt** field pre-fills with the current text.
3. Edit as needed.
4. Click **Update**.

> **Note for Pages:** The Sort Order field does not appear in Page Quick Edit.
> WordPress already exposes page order via the native Page Attributes panel.
> Only the Excerpt field is shown for pages.

### Adding or Removing Target Categories (Posts)

Edit `TIAA_QE_CATEGORY_SLUGS` near the top of `tiaa-quick-edit.php`.
Slugs must match exactly what is shown in **Posts > Categories > Slug**
in WP Admin.

```php
define( 'TIAA_QE_CATEGORY_SLUGS', array(
    'hot-topics',
    'discourse-categories',
    'other-orgs',
    // 'resources',  // <-- add new slugs here
) );
```

---

## How Elementor Loop Grids Use These Fields

**Sort Order** → Loop Grid widget → Query tab → Order By: **Menu Order** → ASC

**Excerpt** → Loop Item template → Post Excerpt widget, or dynamic tag
`Post Excerpt` in any Text widget.

---

## Architecture Notes

### Why standalone?

This plugin deliberately does not live inside `tiaa-wpplugin` or
`tiaa-elementor-forms-invite-action`. It has no dependency on Discourse,
Elementor, or any external service. Keeping it separate means it can be
activated, deactivated, and upgraded independently without risk to the
membership or form-action flows.

### Pages vs Posts

Posts use a category gate — the TIAA fieldset is hidden by default and shown
via AJAX only after confirming the post belongs to a target category. Pages
skip this gate entirely: the Excerpt fieldset is always visible for all pages,
because all pages may benefit from an excerpt (used by Yoast SEO for meta
descriptions and surfaceable in Elementor via the Post Excerpt dynamic tag).

Sort Order is intentionally omitted from Page Quick Edit. WordPress natively
manages page order via the Page Attributes panel (`menu_order`), so duplicating
that field would be redundant.

### Pages Excerpt column

An Excerpt column is added to the Pages list table showing a 60-character
truncated preview of the excerpt (or an em dash if none is set). This makes it
easy to spot pages that still need an excerpt without opening each page.

### No data deleted on deactivation

Deactivating this plugin hides the Quick Edit fields but does **not** alter
any `menu_order` or `post_excerpt` values already stored in the database.
All data persists and remains in effect for Elementor Loop Grids and Yoast SEO.

### No database tables

Uses only the existing `menu_order` and `post_excerpt` columns in `wp_posts`.
No custom tables are created.

### Save approach

`save_post` hook uses `$wpdb->update()` directly rather than `wp_update_post()`
to avoid re-triggering the `save_post` hook and causing an infinite loop.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Fields don't appear in Quick Edit for a post | Confirm the plugin is activated; confirm the post is in a target category |
| Excerpt field doesn't appear in Quick Edit for a page | Confirm the plugin is activated; confirm you are on the Pages list screen |
| Sort Order column is missing | Click **Screen Options** (top-right on Posts screen) and check Sort Order |
| Excerpt column missing on Pages | Click **Screen Options** on the Pages screen and check Excerpt |
| Excerpt not pre-filling | Check DevTools Network tab for a failed `admin-ajax.php` call |
| Changes not saving | Nonce may have expired (page open > 12 hrs) — refresh and retry |

---

## Debugging

A diagnostic script (`tiaa-quick-edit-debug.js`) is included in the plugin
folder. It logs every step of the Quick Edit sequence to the browser console.

**To enable temporarily:**

1. In `tiaa-quick-edit.php`, inside `tiaa_qe_enqueue_scripts()`, add after
   the main `wp_enqueue_script()` call:

```php
wp_enqueue_script(
    'tiaa-quick-edit-debug',
    plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit-debug.js',
    array( 'tiaa-quick-edit' ),
    '1.0',
    true
);
```

2. Open **Posts** or **Pages** and open browser DevTools → Console tab.
3. Click Quick Edit on any post or page.
4. Copy the `[TIAA-QE]` log lines and share for diagnosis.

**Remove the extra `wp_enqueue_script()` call when done.**

---

## Changelog

### 1.5.1 — 2026-03-27
- Fixed: JS loading in Elementor editor context caused `inlineEditPost` TypeError
  that interrupted Elementor's widget config AJAX request, leaving the editor
  sidebar greyed out on first load.
- Fix 1 (PHP): Tightened `$typenow` check in enqueue — `! empty( $typenow )`
  now correctly treats an empty `$typenow` as `'post'` (safe on `edit.php`)
  while still blocking non-target post types.
- Fix 2 (JS): Added `inlineEditPost === 'undefined'` guard at top of script.
  Belt-and-suspenders defence — if the script ever loads outside `edit.php`,
  it exits immediately rather than throwing an uncaught error.

### 1.5.0 — 2026-03-27
- Added Excerpt Quick Edit field for all Pages (no category gate).
- Added Excerpt column to Pages list table (60-char truncated preview).
- Sort Order field intentionally omitted from Pages (WordPress manages page
  order natively via Page Attributes).
- `save_post` handler extended to accept `page` post type.
- Enqueue hook extended to load on the Pages list screen.
- AJAX handler returns `post_type` so JS can adjust field behaviour.
- JS updated to detect page context via `tiaa-qe-fieldset-page` CSS class.

### 1.4.0 — 2026-03-17
- After Quick Edit saves, the Sort Order column cell updates immediately
  without requiring a full page reload.
- Full WP-standard docblocks added to all PHP functions and JS functions.
- `readme.txt` added (WP plugin repository standard format).
- Developer `README.md` restructured.

### 1.3.0
- Compatibility fix for WordPress 6.7+ where `inlineEditPost.edit()` receives
  the trigger button element instead of a numeric post ID.

### 1.2.0
- Added `tiaa-quick-edit-debug.js` diagnostic helper.
- Improved AJAX error handling and console output.

### 1.1.0
- Added `tiaa-quick-edit.css` with TIAA design tokens.
- Styled Sort Order badge and Quick Edit fieldset.

### 1.0.0
- Initial release.

---

## License

GPL-2.0+  
https://www.gnu.org/licenses/gpl-2.0.html
