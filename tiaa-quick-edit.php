<?php
/**
 * Plugin Name:        TIAA Quick Edit Fields
 * Plugin URI:         https://github.com/tiaa-forum-org/tiaa-quick-edit
 * Description:        Adds Sort Order (menu_order) and Excerpt fields to the WordPress Quick Edit panel for posts in the Hot Topics and Discourse Categories categories, and for all Pages. Adds a sortable Sort Order column to the Posts and Pages list tables.
 * Version:            1.5.2
 * Requires at least:  6.5
 * Requires PHP:       8.2
 * Author:             Lew Grothe, TIAA Forum Admin Platform sub-team
 * Author URI:         https://tiaa-forum.org
 * License:            GPL-2.0+
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        tiaa-quick-edit
 *
 * @package TIAAQuickEdit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category slugs that get the Sort Order + Excerpt Quick Edit fields.
 * Posts outside these categories, and all Pages, get the Excerpt field only.
 *
 * @since 1.0.0
 */
define( 'TIAA_QE_CATEGORY_SLUGS', array( 'hot-topics', 'discourse-categories' ) );

// ─────────────────────────────────────────────────────────────────────────────
// 1. COLUMN REGISTRATION — Posts list and Pages list
//
// FIX v1.5.2: Replaced the broad manage_posts_columns / manage_posts_custom_column
// hooks (which fire for ALL post types, including elementor_library, product, etc.)
// with post-type-specific hooks that only fire for 'post' and 'page'.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Add the Sort Order column to the Posts list table.
 *
 * Uses the post-type-specific filter to avoid injecting the column into
 * Elementor templates, WooCommerce products, or any other custom post type.
 *
 * @since 1.0.0
 * @updated 1.5.2 — Changed from manage_posts_columns to manage_post_posts_columns.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns with Sort Order inserted after Title.
 */
add_filter( 'manage_post_posts_columns', 'tiaa_qe_add_columns' );
add_filter( 'manage_page_posts_columns', 'tiaa_qe_add_columns' );
function tiaa_qe_add_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['tiaa_sort_order'] = __( 'Sort Order', 'tiaa-quick-edit' );
		}
	}
	return $new;
}

/**
 * Render the Sort Order column value for a given post.
 *
 * Shows the menu_order value for posts in target categories.
 * Shows an em-dash for all other posts (Pages always show their value).
 *
 * Uses the post-type-specific action to avoid rendering on Elementor templates,
 * WooCommerce products, or any other custom post type list.
 *
 * @since 1.0.0
 * @updated 1.5.2 — Changed from manage_posts_custom_column to manage_post_posts_custom_column
 *                  and added manage_page_posts_custom_column for Pages.
 *
 * @param string $column  Current column key.
 * @param int    $post_id Current post ID.
 * @return void
 */
add_action( 'manage_post_posts_custom_column', 'tiaa_qe_render_column', 10, 2 );
add_action( 'manage_page_posts_custom_column', 'tiaa_qe_render_column', 10, 2 );
function tiaa_qe_render_column( string $column, int $post_id ): void {
	if ( 'tiaa_sort_order' !== $column ) {
		return;
	}

	$post_type = get_post_type( $post_id );

	// For standard posts, only show values for target-category posts.
	if ( 'post' === $post_type && ! tiaa_qe_is_target( $post_id ) ) {
		echo '<span class="tiaa-na">&mdash;</span>';
		return;
	}

	$order = intval( get_post_field( 'menu_order', $post_id ) );
	echo '<span class="tiaa-ord" data-order="' . esc_attr( $order ) . '">'
		. ( 0 === $order ? '&mdash;' : esc_html( $order ) )
		. '</span>';
}

/**
 * Make the Sort Order column sortable on the Posts and Pages list tables.
 *
 * @since 1.0.0
 *
 * @param array $columns Sortable columns.
 * @return array Modified sortable columns.
 */
add_filter( 'manage_edit-post_sortable_columns', 'tiaa_qe_sortable_columns' );
add_filter( 'manage_edit-page_sortable_columns', 'tiaa_qe_sortable_columns' );
function tiaa_qe_sortable_columns( array $columns ): array {
	$columns['tiaa_sort_order'] = 'menu_order';
	return $columns;
}

/**
 * Handle the menu_order orderby on the admin Posts / Pages list query.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The current WP_Query instance.
 * @return void
 */
add_action( 'pre_get_posts', 'tiaa_qe_sort_by_menu_order' );
function tiaa_qe_sort_by_menu_order( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'menu_order' === $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. QUICK EDIT FIELDS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inject the TIAA Sort Order and Excerpt fields into the Quick Edit panel.
 *
 * Triggered only when our custom column is rendered, ensuring the fields
 * appear exactly once per Quick Edit row.
 *
 * @since 1.0.0
 *
 * @param string $column_name The current column name.
 * @param string $post_type   The current post type.
 * @return void
 */
add_action( 'quick_edit_custom_box', 'tiaa_qe_quick_edit_fields', 10, 2 );
function tiaa_qe_quick_edit_fields( string $column_name, string $post_type ): void {
	if ( 'tiaa_sort_order' !== $column_name ) {
		return;
	}

	wp_nonce_field( 'tiaa_qe_save', 'tiaa_qe_nonce' );
	?>
	<fieldset class="inline-edit-col-right tiaa-qe-fieldset">
		<div class="inline-edit-col">
			<div class="tiaa-qe-label-hdr"><?php esc_html_e( 'TIAA Fields', 'tiaa-quick-edit' ); ?></div>

			<label class="tiaa-qe-label tiaa-qe-sort-label">
				<span class="title"><?php esc_html_e( 'Sort Order', 'tiaa-quick-edit' ); ?></span>
				<span class="tiaa-qe-hint"><?php esc_html_e( 'Lower = higher on page (e.g. 10, 20, 30). Leave blank to keep current value.', 'tiaa-quick-edit' ); ?></span>
				<input type="number"
				       name="tiaa_menu_order"
				       class="tiaa-qe-input"
				       min="0"
				       step="1"
				       placeholder="<?php esc_attr_e( 'e.g. 10', 'tiaa-quick-edit' ); ?>" />
			</label>

			<label class="tiaa-qe-label tiaa-qe-excerpt-label">
				<span class="title"><?php esc_html_e( 'Excerpt', 'tiaa-quick-edit' ); ?></span>
				<span class="tiaa-qe-hint"><?php esc_html_e( 'Short description shown on cards. Plain text only.', 'tiaa-quick-edit' ); ?></span>
				<textarea name="tiaa_post_excerpt"
				          class="tiaa-qe-textarea"
				          rows="3"
				          placeholder="<?php esc_attr_e( 'Short description for this card…', 'tiaa-quick-edit' ); ?>"></textarea>
			</label>
		</div>
	</fieldset>
	<?php
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. SAVE
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Save Sort Order and Excerpt values submitted via Quick Edit.
 *
 * Validates nonce and capabilities before writing. Sort Order is only
 * written if a non-empty value is submitted (blank = no change).
 *
 * @since 1.0.0
 *
 * @param int $post_id The post ID being saved.
 * @return void
 */
add_action( 'save_post', 'tiaa_qe_save_post' );
function tiaa_qe_save_post( int $post_id ): void {
	// Bail on autosave and revisions.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Nonce check.
	if ( ! isset( $_POST['tiaa_qe_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tiaa_qe_nonce'] ) ), 'tiaa_qe_save' ) ) {
		return;
	}

	// Capability check.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save Sort Order — only if a value was submitted (blank = no change).
	if ( isset( $_POST['tiaa_menu_order'] ) && '' !== $_POST['tiaa_menu_order'] ) {
		$order = absint( $_POST['tiaa_menu_order'] );
		// Use a direct DB update to avoid triggering save_post recursion.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'menu_order' => $order ),
			array( 'ID' => $post_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	// Save Excerpt — always save, including blank (clearing is intentional).
	if ( isset( $_POST['tiaa_post_excerpt'] ) ) {
		$excerpt = sanitize_textarea_field( wp_unslash( $_POST['tiaa_post_excerpt'] ) );
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'post_excerpt' => $excerpt ),
			array( 'ID' => $post_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. AJAX — Pre-fill Quick Edit fields with current values
// ─────────────────────────────────────────────────────────────────────────────

/**
 * AJAX handler: return current menu_order and post_excerpt for a post.
 *
 * Called by tiaa-quick-edit.js when Quick Edit opens so the fields
 * pre-fill with existing data rather than appearing blank.
 *
 * @since 1.0.0
 *
 * @return void Outputs JSON and exits.
 */
add_action( 'wp_ajax_tiaa_qe_get_post_data', 'tiaa_qe_ajax_get_post_data' );
function tiaa_qe_ajax_get_post_data(): void {
	check_ajax_referer( 'tiaa_qe_nonce', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Invalid post or insufficient permissions.' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		wp_send_json_error( array( 'message' => 'Post not found.' ) );
	}

	wp_send_json_success( array(
		'menu_order'   => intval( $post->menu_order ),
		'post_excerpt' => $post->post_excerpt,
		'is_target'    => tiaa_qe_is_target( $post_id ),
	) );
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. ENQUEUE SCRIPTS AND STYLES
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Enqueue the Quick Edit JS and CSS on the Posts and Pages list screens only.
 *
 * Scoped to edit.php with an explicit post-type allowlist so the script
 * never loads in the Elementor editor or on any other admin page.
 *
 * @since 1.0.0
 * @updated 1.5.1 — Tightened $typenow check; treat empty $typenow as 'post'
 *                  (safe on edit.php when no post_type param is in the URL).
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
add_action( 'admin_enqueue_scripts', 'tiaa_qe_enqueue_scripts' );
function tiaa_qe_enqueue_scripts( string $hook ): void {
	// Only load on the list screens, never on post.php (Elementor editor).
	if ( 'edit.php' !== $hook ) {
		return;
	}

	global $typenow;
	// $typenow can be empty on edit.php when no post_type param is in the URL
	// (WordPress defaults to 'post'). Treat empty as 'post' — safe on edit.php.
	if ( ! empty( $typenow ) && ! in_array( $typenow, array( 'post', 'page' ), true ) ) {
		return;
	}

	wp_enqueue_script(
		'tiaa-quick-edit',
		plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit.js',
		array( 'jquery', 'inline-edit-post' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'tiaa-quick-edit.js' ),
		true
	);

	wp_localize_script(
		'tiaa-quick-edit',
		'tiaaQE',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tiaa_qe_nonce' ),
		)
	);

	wp_enqueue_style(
		'tiaa-quick-edit',
		plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit.css',
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . 'tiaa-quick-edit.css' )
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. HELPERS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Check whether a post belongs to one of the TIAA Quick Edit target categories.
 *
 * Uses wp_get_post_terms() rather than has_category() because has_category()
 * returns false in AJAX and admin list table contexts.
 *
 * @since 1.1.0
 *
 * @param int $post_id Post ID to check.
 * @return bool True if the post is in a target category.
 */
function tiaa_qe_is_target( int $post_id ): bool {
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
