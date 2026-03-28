<?php
/**
 * Plugin Name:       TIAA Quick Edit Fields
 * Plugin URI:        https://github.com/tiaa-forum-org/tiaa-wpsite-v3
 * Description:       Adds Sort Order (menu_order) and Excerpt fields to the WordPress Quick Edit
 *                    panel for posts in the hot-topics, discourse-categories, and other-orgs
 *                    categories. Also adds an Excerpt field to the Pages Quick Edit panel (all
 *                    pages), and adds a sortable Sort Order column to the Posts list table.
 * Version:           1.5.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Lew Grothe, TIAA Forum Admin Platform Sub-team
 * Author URI:        https://tiaa-forum.org
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tiaa-quick-edit
 *
 * @package           TIAAQuickEdit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category slugs whose posts will show TIAA Quick Edit fields.
 *
 * Sort Order and Excerpt fields only appear in the Quick Edit panel for posts
 * that belong to at least one of these category slugs.
 *
 * TO ADD a category: append its slug, e.g. 'resources'
 * TO REMOVE:         delete its line
 *
 * Slugs must match exactly what is shown in Posts > Categories > Slug in WP Admin.
 *
 * @since 1.0.0
 */
define( 'TIAA_QE_CATEGORY_SLUGS', array(
	'hot-topics',
	'discourse-categories',
	'other-orgs',
) );


/* ═══════════════════════════════════════════════════════════════════
 * HELPER
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Determines whether a post belongs to one of the target categories.
 *
 * Uses wp_get_post_terms() rather than has_category() because has_category()
 * requires the global $post to be set, which is not guaranteed on the admin
 * list screen or during AJAX requests.
 *
 * @since 1.0.0
 *
 * @param int $post_id WordPress post ID to test.
 * @return bool True if the post is in at least one TIAA_QE_CATEGORY_SLUGS category.
 */
function tiaa_qe_is_target_post( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}
	foreach ( TIAA_QE_CATEGORY_SLUGS as $slug ) {
		if ( in_array( $slug, $terms, true ) ) {
			return true;
		}
	}
	return false;
}


/* ═══════════════════════════════════════════════════════════════════
 * 1. POSTS LIST TABLE COLUMN
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Registers the Sort Order column in the Posts list table.
 *
 * Inserts the column immediately after the Title column so it is
 * visible at a glance when browsing the post list.
 *
 * @since 1.0.0
 *
 * @param array $columns Existing column definitions.
 * @return array Modified column definitions with Sort Order inserted after Title.
 */
add_filter( 'manage_posts_columns', 'tiaa_qe_add_columns' );
function tiaa_qe_add_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['tiaa_sort_order'] = 'Sort Order';
		}
	}
	return $new;
}

/**
 * Renders the Sort Order column cell for each post row.
 *
 * Displays a styled badge with the current menu_order value for posts in
 * target categories; shows an em dash for all other posts.
 *
 * @since 1.0.0
 *
 * @param string $column  Column identifier being rendered.
 * @param int    $post_id WordPress post ID for the current row.
 * @return void
 */
add_action( 'manage_posts_custom_column', 'tiaa_qe_render_column', 10, 2 );
function tiaa_qe_render_column( $column, $post_id ) {
	if ( $column !== 'tiaa_sort_order' ) {
		return;
	}

	if ( ! tiaa_qe_is_target_post( $post_id ) ) {
		echo '<span class="tiaa-sort-order-na">&#8212;</span>';
		return;
	}

	$raw     = intval( get_post_field( 'menu_order', $post_id ) );
	$display = ( $raw === 0 ) ? '&#8212;' : esc_html( $raw );
	echo '<span class="tiaa-sort-order-value" data-order="' . esc_attr( $raw ) . '">' . $display . '</span>';
}

/**
 * Makes the Sort Order column sortable in the Posts list table.
 *
 * Registers the column against the native menu_order query parameter so
 * WordPress handles the ORDER BY automatically.
 *
 * @since 1.0.0
 *
 * @param array $cols Existing sortable column definitions.
 * @return array Modified sortable columns with tiaa_sort_order added.
 */
add_filter( 'manage_edit-post_sortable_columns', 'tiaa_qe_sortable_columns' );
function tiaa_qe_sortable_columns( $cols ) {
	$cols['tiaa_sort_order'] = 'menu_order';
	return $cols;
}


/* ═══════════════════════════════════════════════════════════════════
 * 1b. PAGES LIST TABLE COLUMN
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Registers the Excerpt column in the Pages list table.
 *
 * Inserts the column immediately after the Title column to make the
 * excerpt status visible at a glance when browsing the page list.
 *
 * @since 1.5.0
 *
 * @param array $columns Existing column definitions.
 * @return array Modified column definitions with Excerpt inserted after Title.
 */
add_filter( 'manage_pages_columns', 'tiaa_qe_add_page_columns' );
function tiaa_qe_add_page_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['tiaa_page_excerpt'] = 'Excerpt';
		}
	}
	return $new;
}

/**
 * Renders the Excerpt column cell for each page row.
 *
 * Displays a truncated excerpt (60 chars) if one exists, or an em dash
 * if the page has no excerpt set — making it easy to spot pages that
 * still need one.
 *
 * @since 1.5.0
 *
 * @param string $column  Column identifier being rendered.
 * @param int    $post_id WordPress post ID for the current row.
 * @return void
 */
add_action( 'manage_pages_custom_column', 'tiaa_qe_render_page_column', 10, 2 );
function tiaa_qe_render_page_column( $column, $post_id ) {
	if ( $column !== 'tiaa_page_excerpt' ) {
		return;
	}
	$excerpt = get_post_field( 'post_excerpt', $post_id );
	if ( $excerpt ) {
		echo '<span class="tiaa-page-excerpt-preview">' . esc_html( mb_strimwidth( $excerpt, 0, 60, '…' ) ) . '</span>';
	} else {
		echo '<span class="tiaa-sort-order-na">&#8212;</span>';
	}
}


/* ═══════════════════════════════════════════════════════════════════
 * 2. QUICK EDIT FIELDS — POSTS
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Renders the TIAA Quick Edit fieldset inside the WordPress Quick Edit panel
 * for Posts.
 *
 * Hooked to quick_edit_custom_box, which fires once per registered custom
 * column. The fieldset is initially hidden (display:none) and shown via JS
 * only for posts in target categories after an AJAX check confirms membership.
 *
 * A nonce field is included for save_post verification.
 *
 * @since 1.0.0
 *
 * @param string $column_name The column identifier that triggered this callback.
 * @param string $post_type   The current post type.
 * @return void
 */
add_action( 'quick_edit_custom_box', 'tiaa_qe_quick_edit_fields', 10, 2 );
function tiaa_qe_quick_edit_fields( $column_name, $post_type ) {
	if ( $post_type === 'page' ) {
		return; // Pages handled separately below.
	}
	if ( $column_name !== 'tiaa_sort_order' ) {
		return;
	}
	wp_nonce_field( 'tiaa_qe_save', 'tiaa_qe_nonce' );
	?>
	<fieldset class="inline-edit-col-right tiaa-qe-fieldset" style="display:none;">
		<div class="inline-edit-col">
			<div class="tiaa-qe-section-label">TIAA Fields</div>

			<label class="tiaa-qe-label">
				<span class="title">Sort Order</span>
				<span class="tiaa-qe-hint">Controls card display order. Lower numbers appear first (e.g. 10, 20, 30). Leave blank to make no change.</span>
				<input type="number"
				       name="tiaa_menu_order"
				       class="tiaa-menu-order"
				       value=""
				       placeholder="not set"
				       min="0"
				       step="1" />
			</label>

			<label class="tiaa-qe-label">
				<span class="title">Excerpt</span>
				<span class="tiaa-qe-hint">Short summary shown on cards and archive pages.</span>
				<textarea name="tiaa_post_excerpt"
				          class="tiaa-post-excerpt"
				          rows="3"></textarea>
			</label>
		</div>
	</fieldset>
	<?php
}


/* ═══════════════════════════════════════════════════════════════════
 * 2b. QUICK EDIT FIELDS — PAGES
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Renders the TIAA Excerpt Quick Edit field inside the WordPress Quick Edit
 * panel for Pages.
 *
 * Hooked to quick_edit_custom_box, triggered by the tiaa_page_excerpt column.
 * The Excerpt field is shown for all pages — no category gate is needed.
 *
 * Note: Sort Order is intentionally omitted for pages. WordPress already
 * exposes menu_order natively via the Page Attributes panel.
 *
 * The fieldset is always visible (no display:none) because pages do not need
 * the JS category check that posts require.
 *
 * @since 1.5.0
 *
 * @param string $column_name The column identifier that triggered this callback.
 * @param string $post_type   The current post type.
 * @return void
 */
add_action( 'quick_edit_custom_box', 'tiaa_qe_page_quick_edit_fields', 10, 2 );
function tiaa_qe_page_quick_edit_fields( $column_name, $post_type ) {
	if ( $post_type !== 'page' ) {
		return;
	}
	if ( $column_name !== 'tiaa_page_excerpt' ) {
		return;
	}
	wp_nonce_field( 'tiaa_qe_save', 'tiaa_qe_nonce' );
	?>
	<fieldset class="inline-edit-col-right tiaa-qe-fieldset tiaa-qe-fieldset-page">
		<div class="inline-edit-col">
			<div class="tiaa-qe-section-label">TIAA Fields</div>

			<label class="tiaa-qe-label">
				<span class="title">Excerpt</span>
				<span class="tiaa-qe-hint">Used by Yoast SEO and can be surfaced in Elementor via the Post Excerpt dynamic tag.</span>
				<textarea name="tiaa_post_excerpt"
				          class="tiaa-post-excerpt tiaa-page-excerpt"
				          rows="4"></textarea>
			</label>
		</div>
	</fieldset>
	<?php
}


/* ═══════════════════════════════════════════════════════════════════
 * 3. SAVE
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Persists Sort Order and Excerpt values submitted via Quick Edit.
 *
 * Hooked to save_post. Validates the nonce and capability before writing.
 * Handles both 'post' and 'page' post types.
 *
 * menu_order is updated only for posts in target categories and only when a
 * non-empty value is submitted (blank = no change). It is never updated for
 * pages — WordPress manages page order natively.
 *
 * post_excerpt is updated for all posts and pages when the nonce is present,
 * allowing it to be cleared by submitting an empty textarea.
 *
 * Uses $wpdb->update() directly rather than wp_update_post() to avoid
 * re-triggering save_post and causing an infinite loop.
 *
 * @since 1.0.0
 * @updated 1.5.0 — extended to handle 'page' post type.
 *
 * @param int     $post_id WordPress post ID being saved.
 * @param WP_Post $post    Full post object.
 * @return void
 */
add_action( 'save_post', 'tiaa_qe_save_quick_edit', 10, 2 );
function tiaa_qe_save_quick_edit( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return;
	}
	if ( ! isset( $_POST['tiaa_qe_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['tiaa_qe_nonce'], 'tiaa_qe_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	global $wpdb;

	// Update menu_order — target posts only; blank = no change; never for pages.
	if ( $post->post_type === 'post' && tiaa_qe_is_target_post( $post_id ) && isset( $_POST['tiaa_menu_order'] ) ) {
		$v = trim( $_POST['tiaa_menu_order'] );
		if ( $v !== '' ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => intval( $v ) ),
				array( 'ID' => $post_id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	// Update post_excerpt — all posts and pages; allows clearing the field.
	if ( isset( $_POST['tiaa_post_excerpt'] ) ) {
		$wpdb->update(
			$wpdb->posts,
			array( 'post_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['tiaa_post_excerpt'] ) ) ),
			array( 'ID' => $post_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}


/* ═══════════════════════════════════════════════════════════════════
 * 4. ENQUEUE
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * Enqueues the plugin's JS and CSS on the Posts and Pages list screens.
 *
 * Loads tiaa-quick-edit.js (depends on jquery and inline-edit-post) and
 * tiaa-quick-edit.css on edit.php (Posts) and edit.php?post_type=page (Pages).
 *
 * Also localises tiaaQE with the AJAX URL and a nonce for the
 * tiaa_qe_get_post_data AJAX action.
 *
 * @since 1.0.0
 * @updated 1.5.0 — extended to load on the Pages list screen.
 * @updated 1.5.1 — tightened $typenow check to prevent loading on non-list screens
 *                  such as the Elementor editor, where $typenow can be empty.
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
add_action( 'admin_enqueue_scripts', 'tiaa_qe_enqueue_scripts' );
function tiaa_qe_enqueue_scripts( $hook ) {
	// Only load on the Posts and Pages list screens (edit.php).
	// The Elementor editor runs on post.php, not edit.php, so this check
	// is the primary guard against loading in the wrong context.
	if ( 'edit.php' !== $hook ) {
		return;
	}
	global $typenow;
	// $typenow is empty on edit.php when no post_type param is in the URL,
	// which means WordPress defaults to 'post' — safe to allow.
	// Use ! empty() so that an empty string passes through (treated as 'post')
	// but any explicit non-target post type is rejected.
	if ( ! empty( $typenow ) && ! in_array( $typenow, array( 'post', 'page' ), true ) ) {
		return;
	}

	wp_enqueue_script(
		'tiaa-quick-edit',
		plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit.js',
		array( 'jquery', 'inline-edit-post' ),
		'1.5.1',
		true
	);
	wp_localize_script( 'tiaa-quick-edit', 'tiaaQE', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'tiaa_qe_get_post_data' ),
	) );
	wp_enqueue_style(
		'tiaa-quick-edit',
		plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit.css',
		array(),
		'1.5.1'
	);
}


/* ═══════════════════════════════════════════════════════════════════
 * 5. AJAX
 * ═══════════════════════════════════════════════════════════════════ */

/**
 * AJAX handler: returns current post/page data for pre-filling Quick Edit fields.
 *
 * Accepts a POST request with post_id and nonce. Responds with JSON containing:
 *   - is_target  (bool)        — whether to show TIAA fields; always true for pages
 *   - excerpt    (string)      — current post_excerpt value
 *   - menu_order (int|null)    — current menu_order value; null when 0 (unset)
 *   - post_type  (string)      — 'post' or 'page'
 *
 * Returns a JSON error and exits on nonce failure or insufficient capability.
 *
 * @since 1.0.0
 * @updated 1.5.0 — returns post_type; is_target always true for pages.
 *
 * @return void Sends JSON response and exits.
 */
add_action( 'wp_ajax_tiaa_qe_get_post_data', 'tiaa_qe_ajax_get_post_data' );
function tiaa_qe_ajax_get_post_data() {
	check_ajax_referer( 'tiaa_qe_get_post_data', 'nonce' );

	$post_id = intval( $_POST['post_id'] ?? 0 );
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'Unauthorised' );
	}

	$post      = get_post( $post_id );
	$raw_order = $post ? intval( $post->menu_order ) : 0;
	$post_type = $post ? $post->post_type : 'post';

	// Pages always show the excerpt field — no category gate needed.
	$is_target = ( $post_type === 'page' ) ? true : tiaa_qe_is_target_post( $post_id );

	wp_send_json_success( array(
		'is_target'  => $is_target,
		'excerpt'    => $post ? $post->post_excerpt : '',
		'menu_order' => ( $raw_order !== 0 ) ? $raw_order : null,
		'post_type'  => $post_type,
	) );
}
