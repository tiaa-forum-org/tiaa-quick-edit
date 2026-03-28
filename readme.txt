=== TIAA Quick Edit Fields ===
Contributors:      Lew Grothe
Tags:              quick edit, admin, posts, pages, menu order, excerpt
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.5.1
License:           GPL-2.0+
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Adds Sort Order and Excerpt fields to Quick Edit for target-category posts,
and an Excerpt field to Quick Edit for all Pages.

== Description ==

TIAA Quick Edit Fields is an internal WordPress admin utility for the TIAA Forum
(tiaa-forum.org) site rebuild. It streamlines content management for volunteer
editors who maintain Hot Topics posts, Discourse Category posts, Related
Organization posts, and site Pages without requiring them to open the full editor.

**What it adds:**

For **Posts** in target categories (hot-topics, discourse-categories, other-orgs):

* A **Sort Order** column in the Posts list table, showing the current
  `menu_order` value. The column is sortable.
* A **Sort Order** field in Quick Edit, allowing `menu_order` to be updated
  inline. A blank submission makes no change.
* An **Excerpt** field in Quick Edit, allowing `post_excerpt` to be edited
  inline. This is the text shown on Elementor Loop Grid cards.

For **Pages** (all pages, no category gate):

* An **Excerpt** column in the Pages list table, showing a 60-character
  truncated preview (or an em dash if no excerpt is set).
* An **Excerpt** field in Quick Edit, allowing `post_excerpt` to be set or
  updated inline. Used by Yoast SEO for meta descriptions.

Note: Sort Order is intentionally omitted from Page Quick Edit. WordPress
already manages page order natively via the Page Attributes panel.

**Why it exists:**

Elementor Loop Grids on the TIAA Forum homepage and archive pages sort by
`menu_order` and display `post_excerpt`. The standard WordPress post editor
requires opening a full edit screen to change either value. This plugin
surfaces both fields in the Quick Edit panel so editors can reorder posts
and update card descriptions without leaving the Posts list.

For Pages, the WordPress block editor hides the Excerpt field by default
(requires a Preferences toggle to expose it). This plugin surfaces the field
directly in Quick Edit, making it accessible to volunteers without editor
configuration changes.

**No data is stored or deleted on deactivation.** Deactivating the plugin
removes the UI fields but does not alter any `menu_order` or `post_excerpt`
values already in the database.

== Installation ==

1. Copy the `tiaa-quick-edit/` folder to `wp-content/plugins/`.
2. In WordPress Admin, go to **Plugins** and activate **TIAA Quick Edit Fields**.
3. Go to **Posts** — the Sort Order column and Quick Edit fields are now active.
4. Go to **Pages** — the Excerpt column and Quick Edit Excerpt field are active.

== Usage ==

= Updating Sort Order (Posts only) =
1. Hover over a post row and click **Quick Edit**.
2. The Sort Order field pre-fills with the current value (blank if unset).
3. Enter a number. Use gaps (10, 20, 30) to allow future insertions.
4. Click **Update**.

= Updating Excerpt (Posts and Pages) =
1. Hover over a post or page row and click **Quick Edit**.
2. The Excerpt field pre-fills with the current post excerpt.
3. Edit the text.
4. Click **Update**.

= Adding or Removing Target Categories (Posts) =
Edit the `TIAA_QE_CATEGORY_SLUGS` constant near the top of
`tiaa-quick-edit.php`. Slugs must match exactly what is shown in
**Posts > Categories > Slug** in the WordPress admin.

== Troubleshooting ==

= Fields don't appear in Quick Edit for a post =
Confirm the plugin is activated. Fields only appear for posts in the
configured target categories.

= Excerpt field doesn't appear in Quick Edit for a page =
Confirm the plugin is activated and that you are on the Pages list screen
(Pages > All Pages), not the Posts screen.

= Sort Order column is missing from the Posts list =
Click Screen Options (top-right on the Posts screen) and ensure
Sort Order is checked.

= Excerpt column is missing from the Pages list =
Click Screen Options (top-right on the Pages screen) and ensure
Excerpt is checked.

= Excerpt is not pre-filling =
Open browser DevTools > Network tab and look for a failed
admin-ajax.php request. Check the console for [TIAA-QE] error messages.
See the debug script section in the developer README for detailed diagnostics.

= Changes are not saving =
This can happen if the page has been open for more than 12 hours and
the nonce has expired. Refresh the page and retry.

== Changelog ==

= 1.5.1 — 2026-03-27 =
* Fixed: JS loading in Elementor editor context caused an inlineEditPost
  TypeError that interrupted Elementor's widget config AJAX request, leaving
  the editor sidebar greyed out on first load. Opening a template and closing
  it was a workaround that forced re-initialisation.
* Fix 1 (PHP): Tightened $typenow check in enqueue — ! empty( $typenow ) now
  correctly treats an empty $typenow as 'post' (safe on edit.php) while still
  blocking non-target post types.
* Fix 2 (JS): Added inlineEditPost guard at top of script. If the script loads
  outside edit.php for any reason, it exits immediately rather than throwing
  an uncaught error that can disrupt other scripts on the page.

= 1.5.0 — 2026-03-27 =
* Added Excerpt Quick Edit field for all Pages (no category gate required).
* Added Excerpt column to Pages list table (60-char truncated preview).
* Sort Order field intentionally omitted from Pages — WordPress manages
  page order natively via the Page Attributes panel.
* save_post handler extended to accept page post type.
* Enqueue hook extended to load on the Pages list screen.
* AJAX handler now returns post_type so JS adjusts field behaviour accordingly.
* JS updated to detect page context via tiaa-qe-fieldset-page CSS class.

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

= 1.5.1 =
Fixes Elementor editor sidebar greying out on first load. Safe to upgrade —
no database changes.

= 1.5.0 =
Adds Excerpt Quick Edit field and Excerpt column for all Pages. No database
changes — safe to upgrade.
