=== TIAA Quick Edit Fields ===
Contributors:      Lew Grothe
Tags:              quick edit, admin, posts, menu order, excerpt
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.4.0
License:           GPL-2.0+
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Adds Sort Order and Excerpt fields to the WordPress Quick Edit panel for posts
in the hot-topics and discourse-categories categories.

== Description ==

TIAA Quick Edit Fields is an internal WordPress admin utility for the TIAA Forum
(tiaa-forum.org) site rebuild. It streamlines content management for volunteer
editors who maintain Hot Topics posts and Discourse Category posts without
requiring them to open the full post editor.

**What it adds:**

* A **Sort Order** column in the Posts list table, showing the current
  `menu_order` value for target-category posts. The column is sortable.
* A **Sort Order** field in Quick Edit, allowing `menu_order` to be updated
  inline. A blank submission makes no change.
* An **Excerpt** field in Quick Edit, allowing `post_excerpt` to be edited
  inline. This is the text shown on Elementor Loop Grid cards.

Fields are shown in Quick Edit **only** for posts that belong to the
`hot-topics` or `discourse-categories` categories. All other posts see
standard WordPress Quick Edit behaviour with no TIAA fields.

**Why it exists:**

Elementor Loop Grids on the TIAA Forum homepage and archive pages sort by
`menu_order` and display `post_excerpt`. The standard WordPress post editor
requires opening a full edit screen to change either value. This plugin
surfaces both fields in the Quick Edit panel so editors can reorder posts
and update card descriptions without leaving the Posts list.

**No data is stored or deleted on deactivation.** Deactivating the plugin
removes the UI fields but does not alter any `menu_order` or `post_excerpt`
values already in the database.

== Installation ==

1. Copy the `tiaa-quick-edit/` folder to `wp-content/plugins/`.
2. In WordPress Admin, go to **Plugins** and activate **TIAA Quick Edit Fields**.
3. Go to **Posts** — the Sort Order column and Quick Edit fields are now active.

== Usage ==

= Updating Sort Order =
1. Hover over a post row and click **Quick Edit**.
2. The Sort Order field pre-fills with the current value (blank if unset).
3. Enter a number. Use gaps (10, 20, 30) to allow future insertions.
4. Click **Update**.

= Updating Excerpt =
1. Hover over a post row and click **Quick Edit**.
2. The Excerpt field pre-fills with the current post excerpt.
3. Edit the text (1–2 sentences works best for cards).
4. Click **Update**.

= Adding or Removing Target Categories =
Edit the `TIAA_QE_CATEGORY_SLUGS` constant near the top of
`tiaa-quick-edit.php`. Slugs must match exactly what is shown in
**Posts > Categories > Slug** in the WordPress admin.

== Troubleshooting ==

= Fields don't appear in Quick Edit =
Confirm the plugin is activated. Fields only appear for posts in the
configured target categories.

= Sort Order column is missing from the list table =
Click **Screen Options** (top-right on the Posts screen) and ensure
Sort Order is checked.

= Excerpt is not pre-filling =
Open browser DevTools > Network tab and look for a failed
`admin-ajax.php` request. Check the console for `[TIAA-QE]` error messages.
See the debug script section in the developer README for detailed diagnostics.

= Changes are not saving =
This can happen if the Posts page has been open for more than 12 hours and
the nonce has expired. Refresh the page and retry.

== Changelog ==

= 1.4.0 — 2026-03-17 =
* After Quick Edit saves, Sort Order column cell updates immediately without
  requiring a full page reload.
* Full WP-standard docblocks added to all PHP functions and JS functions.
* readme.txt added.

= 1.3.0 =
* Compatibility fix for WordPress 6.7+ where inlineEditPost.edit() receives
  the trigger button element instead of a numeric post ID.

= 1.2.0 =
* Added tiaa-quick-edit-debug.js diagnostic helper.
* Improved AJAX error handling and console logging.

= 1.1.0 =
* Added tiaa-quick-edit.css with TIAA design tokens (Navy, Teal, Coral).
* Styled Sort Order badge and Quick Edit fieldset.

= 1.0.0 =
* Initial release.
* Sort Order (menu_order) and Excerpt (post_excerpt) fields in Quick Edit.
* Sort Order column in Posts list table (sortable).
* AJAX pre-fill of current values when Quick Edit opens.
* Category-scoped display: fields visible only for target-category posts.

== Upgrade Notice ==

= 1.4.0 =
Adds post-save cell update and full docblocks. Safe to upgrade — no database
changes.
