=== TIAA Quick Edit Fields ===
Contributors:      lewgrothe
Tags:              quick-edit, admin, menu-order, excerpt, posts
Requires at least: 6.5
Tested up to:      6.8
Requires PHP:      8.2
Stable tag:        1.5.2
License:           GPL-2.0+
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Adds Sort Order and Excerpt fields to the WordPress Quick Edit panel for Hot Topics and Discourse Category posts, and for all Pages.

== Description ==

TIAA Quick Edit Fields is an admin-only utility plugin for the TIAA Forum WordPress site. It adds two fields directly to the Quick Edit panel in the Posts and Pages list tables so volunteer editors can update card sort order and excerpt text without opening the full post editor.

**Sort Order (menu_order)**
Controls the display priority of Hot Topics and Discourse Category cards in Elementor Loop Grids. Lower numbers appear first. Blank input leaves the current value unchanged.

**Excerpt (post_excerpt)**
The summary text shown on Hot Topics and Discourse Category cards. Pages also get this field. Plain text only.

**Sort Order column**
A sortable Sort Order column is added to the Posts and Pages list tables so editors can see and sort by current values at a glance.

Fields are shown only for posts in the configured target categories (hot-topics and discourse-categories). All other posts show an em-dash in the column. Pages always show both fields.

This plugin produces no front-end output and is safe to deactivate without data loss.

== Installation ==

1. Upload the `tiaa-quick-edit` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress Admin → Plugins.
3. Go to Posts or Pages — the Sort Order column and Quick Edit fields are active.

== Frequently Asked Questions ==

= Does this affect posts outside the target categories? =

The Sort Order field in Quick Edit is hidden for posts outside the target categories (hot-topics and discourse-categories). The Excerpt field is shown for all posts and Pages. The Sort Order column shows an em-dash for non-target posts.

= Is it safe to deactivate? =

Yes. The plugin only reads and writes standard WordPress post fields (menu_order and post_excerpt). No custom database tables are used. Deactivating removes the UI only — no data is changed or deleted.

= Why does the Sort Order input appear blank for some posts? =

WordPress stores menu_order as 0 by default. The plugin treats 0 as unset and leaves the field blank so editors are not confused by a zero that means "no value". Entering a number and saving will set the value.

== Changelog ==

= 1.5.2 — 2026-05-10 =
* Fixed: Sort Order column was appearing on Elementor Saved Templates (elementor_library post type) and other custom post type list screens.
* Root cause: manage_posts_columns and manage_posts_custom_column filters fire for ALL post types, not just 'post' and 'page'.
* Fix: Replaced manage_posts_columns with manage_post_posts_columns + manage_page_posts_columns, and manage_posts_custom_column with manage_post_posts_custom_column + manage_page_posts_custom_column. Column now only appears on the standard Posts and Pages list screens.
* Also added manage_edit-page_sortable_columns alongside the existing manage_edit-post_sortable_columns for consistency.

= 1.5.1 — 2026-03-27 =
* Fixed: JS loading in Elementor editor context caused an inlineEditPost TypeError that interrupted Elementor's widget config AJAX request, leaving the editor sidebar greyed out on first load.
* Fix 1 (PHP): Tightened $typenow check — ! empty( $typenow ) now correctly treats an empty $typenow as 'post' (safe on edit.php) while still blocking non-target post types.
* Fix 2 (JS): Added inlineEditPost === 'undefined' guard at top of script as belt-and-suspenders defence.

= 1.5.0 — 2026-03-27 =
* Added Excerpt Quick Edit field and Excerpt column for all Pages (page post type).
* Extended enqueue to load on the Pages list screen in addition to Posts.

= 1.4.0 — 2026-03-17 =
* Added post-save Sort Order cell update — the column value refreshes after Quick Edit save without a full page reload.
* Added full PHPDoc blocks on all functions (@since, @param, @return, @package).
* Added JSDoc blocks on all JavaScript functions.
* Added CSS file-level docblock with design token references.

= 1.3.0 — 2026-03-06 =
* Added tiaa-quick-edit-debug.js diagnostic helper for troubleshooting Quick Edit field visibility issues.

= 1.2.0 — 2026-03-06 =
* Improved AJAX error handling and console logging.
* Stability fixes for Quick Edit field pre-fill.

= 1.1.0 — 2026-03-06 =
* Added tiaa-quick-edit.css with TIAA design tokens (Navy, Teal, Coral).
* Styled Sort Order badge and Quick Edit fieldset.
* Category-scoped display: Sort Order field visible only for target-category posts.

= 1.0.0 — 2026-03-06 =
* Initial release.
* Sort Order (menu_order) and Excerpt (post_excerpt) fields in Quick Edit.
* Sort Order column in Posts list table (sortable).
* AJAX pre-fill of current values when Quick Edit opens.

== Upgrade Notice ==

= 1.5.2 =
Fixes Sort Order column appearing on Elementor template list and other custom post type screens. No database changes — safe to upgrade by replacing plugin files and reactivating.
