/**
 * tiaa-quick-edit-debug.js
 *
 * Optional diagnostic script for the TIAA Quick Edit Fields plugin.
 * Logs every step of the Quick Edit open sequence to the browser console
 * so problems can be isolated without modifying production code.
 *
 * HOW TO ENABLE (temporarily — do NOT leave in production):
 *
 *  1. Copy this file to wp-content/plugins/tiaa-quick-edit/ if it is not
 *     already there.
 *
 *  2. In tiaa-quick-edit.php, add the following lines inside
 *     tiaa_qe_enqueue_scripts(), immediately after the main wp_enqueue_script()
 *     call:
 *
 *       wp_enqueue_script(
 *           'tiaa-quick-edit-debug',
 *           plugin_dir_url( __FILE__ ) . 'tiaa-quick-edit-debug.js',
 *           array( 'tiaa-quick-edit' ),
 *           '1.0',
 *           true
 *       );
 *
 *  3. Open WordPress Admin > Posts.
 *  4. Open browser DevTools (F12) > Console tab.
 *  5. Click Quick Edit on any post.
 *  6. Copy the console output and share it for debugging.
 *
 * REMOVE the wp_enqueue_script() call added in step 2 when done.
 * This file itself can remain in the plugin directory safely — it has no
 * effect unless explicitly enqueued.
 *
 * @package   TIAAQuickEdit
 * @since     1.0.0
 * @version   1.0.0
 */

jQuery( function ( $ ) {

	console.log( '[TIAA-QE] Debug script loaded.' );

	/**
	 * Verify the tiaaQE localised object is available.
	 *
	 * If missing, the main plugin JS or wp_localize_script() failed to load.
	 */
	if ( typeof tiaaQE === 'undefined' ) {
		console.error(
			'[TIAA-QE] ERROR: tiaaQE JS object not found. ' +
			'The main plugin JS or wp_localize_script() is not loading. ' +
			'Check that the plugin is active and the JS file exists at: ' +
			'wp-content/plugins/tiaa-quick-edit/tiaa-quick-edit.js'
		);
		return;
	}
	console.log( '[TIAA-QE] tiaaQE object OK:', tiaaQE );

	/**
	 * Verify inlineEditPost is available.
	 *
	 * This object is registered by WordPress core on edit.php. If it is
	 * missing the debug script is running on the wrong admin page.
	 */
	if ( typeof inlineEditPost === 'undefined' ) {
		console.error(
			'[TIAA-QE] ERROR: inlineEditPost not found. ' +
			'This script must run on the Posts list screen (edit.php).'
		);
		return;
	}
	console.log( '[TIAA-QE] inlineEditPost OK.' );

	/**
	 * Verify the TIAA fieldset is present in the DOM.
	 *
	 * If the fieldset is absent, quick_edit_custom_box is not firing,
	 * most likely because the tiaa_sort_order column is not registered
	 * or is hidden via Screen Options.
	 */
	var $fieldsets = $( '.tiaa-qe-fieldset' );
	if ( $fieldsets.length === 0 ) {
		console.error(
			'[TIAA-QE] ERROR: .tiaa-qe-fieldset not found in DOM. ' +
			'The quick_edit_custom_box hook is not firing. ' +
			'Possible causes:\n' +
			'  - The "tiaa_sort_order" column is not registered (manage_posts_columns hook missing)\n' +
			'  - The column is hidden via Screen Options\n' +
			'  - The hook is firing for a different post type'
		);
	} else {
		console.log(
			'[TIAA-QE] Fieldset found in DOM.',
			'Count:', $fieldsets.length,
			'| Currently visible:', $fieldsets.filter( ':visible' ).length
		);
	}

	/**
	 * Wraps inlineEditPost.edit() to log every step of the Quick Edit
	 * open and AJAX fetch sequence.
	 *
	 * This is a diagnostic-only wrapper. It does not modify any behaviour —
	 * it calls the original edit() function first, then runs its own checks.
	 *
	 * @param {number|Element} id Post ID or trigger button element.
	 * @return {void}
	 */
	var _original = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		_original.apply( this, arguments );

		var postId = ( typeof id === 'object' )
			? parseInt( $( id ).closest( 'tr[id^="post-"]' ).attr( 'id' ).replace( 'post-', '' ), 10 )
			: parseInt( id, 10 );

		console.log( '[TIAA-QE] Quick Edit opened for post ID:', postId );

		if ( ! postId || isNaN( postId ) ) {
			console.error( '[TIAA-QE] Could not resolve post ID from argument:', id );
			return;
		}

		var $editRow  = $( '#edit-' + postId );
		var $fieldset = $editRow.find( '.tiaa-qe-fieldset' );

		console.log(
			'[TIAA-QE] Edit row found:', $editRow.length > 0,
			'| Fieldset found inside row:', $fieldset.length > 0
		);

		console.log( '[TIAA-QE] Firing AJAX to:', tiaaQE.ajaxurl );

		$.post(
			tiaaQE.ajaxurl,
			{
				action  : 'tiaa_qe_get_post_data',
				post_id : postId,
				nonce   : tiaaQE.nonce
			}
		)
		.done( function ( response ) {
			console.log( '[TIAA-QE] AJAX raw response:', response );

			if ( ! response ) {
				console.error( '[TIAA-QE] Empty response from server.' );
				return;
			}
			if ( ! response.success ) {
				console.error( '[TIAA-QE] Server returned error:', response.data );
				return;
			}

			var data = response.data;
			console.log( '[TIAA-QE] Post data from server:' );
			console.log( '  is_target :', data.is_target );
			console.log( '  excerpt   :', data.excerpt ? '"' + data.excerpt.substring( 0, 60 ) + '..."' : '(empty)' );
			console.log( '  menu_order:', data.menu_order );

			if ( ! data.is_target ) {
				console.warn(
					'[TIAA-QE] Post ' + postId + ' is NOT in a target category. ' +
					'Fieldset will stay hidden. ' +
					'Check that the post is assigned to "hot-topics" or "discourse-categories" ' +
					'and that those slugs exactly match TIAA_QE_CATEGORY_SLUGS in the PHP file.'
				);
			} else {
				console.log( '[TIAA-QE] Post IS in a target category — fieldset should show.' );
			}
		} )
		.fail( function ( jqXHR, textStatus, errorThrown ) {
			console.error(
				'[TIAA-QE] AJAX call failed entirely.',
				'Status:', textStatus,
				'Error:', errorThrown,
				'Response:', jqXHR.responseText
			);
		} );
	};

} );
