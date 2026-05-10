# TIAA Quick Edit Fields

**Version:** 1.5.2
**Updated:** 2026-05-10
**Location in repo:** `plugins/tiaa-quick-edit/`
**Plugin slug:** `tiaa-quick-edit`
**Text domain:** `tiaa-quick-edit`

---

## What This Does

Adds two fields to the WordPress **Quick Edit** panel for posts in the
`hot-topics` and `discourse-categories` categories, and for all Pages.
Adds a sortable **Sort Order** column to the Posts and Pages list tables.

| Field | Controls | Why it matters |
|---|---|---|
| **Sort Order** | `menu_order` in `wp_posts` | Controls the order cards appear in Elementor Loop Grids sorted by Menu Order |
| **Excerpt** | `post_excerpt` in `wp_posts` | The summary text shown on Hot Topics and Discourse Category cards |

Fields are shown **only** for posts that belong to a target category (or for Pages).
All other posts see an em-dash in the Sort Order column and no Sort Order field in Quick Edit.

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

## WordPress Hooks Used

| Hook | Type | Purpose |
|---|---|---|
| `manage_post_posts_columns` | filter | Add Sort Order column to Posts list |
| `manage_page_posts_columns` | filter | Add Sort Order column to Pages list |
| `manage_post_posts_custom_column` | action | Render Sort Order value in Posts list |
| `manage_page_posts_custom_column` | action | Render Sort Order value in Pages list |
| `manage_edit-post_sortable_columns` | filter | Make Sort Order column sortable on Posts |
| `manage_edit-page_sortable_columns` | filter | Make Sort Order column sortable on Pages |
| `pre_get_posts` | action | Handle menu_order sort on list queries |
| `quick_edit_custom_box` | action | Inject Sort Order + Excerpt fields |
| `save_post` | action | Save submitted values |
| `wp_ajax_tiaa_qe_get_post_data` | action | AJAX: return current values for pre-fill |
| `admin_enqueue_scripts` | action | Enqueue JS + CSS on edit.php only |

> **Note on v1.5.2 fix:** `manage_posts_columns` and `manage_posts_custom_column` are broad
> filters that fire for *all* post types, including Elementor templates (`elementor_library`),
> WooCommerce products, and any other CPT. They were replaced with the post-type-specific
> variants (`manage_post_posts_columns`, `manage_page_posts_columns`, etc.) to prevent the
> Sort Order column appearing on unrelated admin screens.

---

## Installation

1. Copy the `tiaa-quick-edit/` folder into `wp-content/plugins/`.
2. In WordPress Admin → **Plugins**, activate **TIAA Quick Edit Fields**.
3. Go to **Posts** or **Pages** — the Sort Order column and Quick Edit fields are active.

---

## How to Use

### Updating Sort Order

1. Hover over any post row → click **Quick Edit**.
2. The **Sort Order** field pre-fills with the current value (blank if unset or zero).
3. Enter a number. Leave blank to make no change.
4. Click **Update**.

> **Tip:** Use gaps between values (10, 20, 30) so you can insert new items
> later without renumbering everything.

### Updating Excerpt

1. Hover over any post row → click **Quick Edit**.
2. The **Excerpt** field pre-fills with the current text.
3. Edit the text. Saving an empty excerpt will clear it.
4. Click **Update**.

---

## Category Configuration

Target categories are defined as a constant in `tiaa-quick-edit.php`:

```php
define( 'TIAA_QE_CATEGORY_SLUGS', array( 'hot-topics', 'discourse-categories' ) );
```

To add or remove a category, edit this array and save. No other changes needed.

---

## Debugging

A diagnostic script is included at `tiaa-quick-edit-debug.js`. To use it:

1. Temporarily enqueue it by adding to the `tiaa_qe_enqueue_scripts()` function:
   ```php
   wp_enqueue_script(
       'tiaa-quick-edit-debug',
       plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit-debug.js',
       array( 'tiaa-quick-edit' ),
       '1.0',
       true
   );
   ```
2. Open **Posts**, open browser DevTools → Console.
3. Click **Quick Edit** on any post.
4. Review console output for load confirmation, AJAX responses, and field population.

Remove the enqueue line when done — do not leave debug script active in production.

---

## Known Issues and Constraints

- **Sort Order blank for value 0:** WordPress stores `menu_order` as `0` by default. The plugin treats `0` as unset and leaves the field blank to avoid confusing editors. Entering `0` explicitly and saving will store `0`.
- **Excerpt save clears value if blank:** Saving an empty Excerpt field will clear the stored excerpt. This is intentional — use with care.
- **Pages show both fields always:** The Sort Order field is shown for all Pages regardless of category. This is expected — Pages don't use categories.

---

## Changelog

### 1.5.2 — 2026-05-10
- **Fixed:** Sort Order column was appearing on Elementor Saved Templates (`elementor_library`
  post type) and other custom post type list screens.
- **Root cause:** `manage_posts_columns` and `manage_posts_custom_column` filters fire for
  *all* post types, not just `post` and `page`.
- **Fix:** Replaced `manage_posts_columns` with `manage_post_posts_columns` +
  `manage_page_posts_columns`, and `manage_posts_custom_column` with
  `manage_post_posts_custom_column` + `manage_page_posts_custom_column`. Column now only
  appears on the standard Posts and Pages list screens.
- Also added `manage_edit-page_sortable_columns` alongside `manage_edit-post_sortable_columns`
  for consistency.

### 1.5.1 — 2026-03-27
- Fixed: JS loading in Elementor editor context caused an `inlineEditPost` TypeError that
  interrupted Elementor's widget config AJAX request, leaving the editor sidebar greyed out
  on first load. Opening a template and closing it was a workaround that forced re-initialisation.
- Fix 1 (PHP): Tightened `$typenow` check — `! empty( $typenow )` now correctly treats an
  empty `$typenow` as `'post'` (safe on `edit.php`) while still blocking non-target post types.
- Fix 2 (JS): Added `inlineEditPost === 'undefined'` guard at top of script as
  belt-and-suspenders defence against loading in the wrong admin context.

### 1.5.0 — 2026-03-27
- Added Excerpt Quick Edit field and Excerpt column for all Pages (`page` post type).
- Extended enqueue to load on the Pages list screen in addition to Posts.

### 1.4.0 — 2026-03-17
- Added post-save Sort Order cell update — column refreshes after Quick Edit save without
  a full page reload.
- Added full PHPDoc blocks on all functions.
- Added JSDoc blocks on all JavaScript functions.
- Added CSS file-level docblock with design token references.

### 1.3.0 — 2026-03-06
- Added `tiaa-quick-edit-debug.js` diagnostic helper.

### 1.2.0 — 2026-03-06
- Improved AJAX error handling and console logging.

### 1.1.0 — 2026-03-06
- Added `tiaa-quick-edit.css` with TIAA design tokens (Navy, Teal, Coral).
- Styled Sort Order badge and Quick Edit fieldset.
- Category-scoped display: Sort Order field visible only for target-category posts.

### 1.0.0 — 2026-03-06
- Initial release.
- Sort Order (`menu_order`) and Excerpt (`post_excerpt`) fields in Quick Edit.
- Sort Order column in Posts list table (sortable).
- AJAX pre-fill of current values when Quick Edit opens.
- Category-scoped display: fields visible only for target-category posts.
