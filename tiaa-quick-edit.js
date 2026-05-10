/**
 * TIAA Quick Edit Fields — tiaa-quick-edit.js
 *
 * Intercepts the WordPress Quick Edit open event to pre-fill the
 * Sort Order and Excerpt fields with current post values via AJAX.
 * After a Quick Edit save, updates the Sort Order cell in the list
 * table without a full page reload.
 *
 * @package TIAAQuickEdit
 * @since   1.0.0
 * @version 1.5.2
 */

jQuery( function ( $ ) {

	/**
	 * Safety guard — bail if not on a list screen.
	 *
	 * inlineEditPost is registered by WordPress core only on the Posts and
	 * Pages list screens (edit.php). If this script somehow loads on another
	 * admin screen — such as the Elementor editor — attempting to wrap
	 * inlineEditPost.edit() would throw an uncaught TypeError that can
	 * interrupt other scripts on the page, including Elementor's own
	 * initialisation sequence.
	 *
	 * The PHP enqueue hook is the primary guard (edit.php check + $typenow
	 * allowlist). This is belt-and-suspenders defence in case the script
	 * loads in an unexpected context.
	 *
	 * @since 1.5.1
	 */
	if ( typeof inlineEditPost === 'undefined' ) {
		return;
	}

	/**
	 * Resolve a WordPress post ID from the argument passed to
	 * inlineEditPost.edit(). WordPress passes either the raw post ID
	 * (number) or the row <tr> DOM element, depending on context.
	 *
	 * @since 1.0.0
	 *
	 * @param {number|HTMLElement} id - Raw ID or row element.
	 * @returns {number} Resolved integer post ID, or 0 on failure.
	 */
	function resolvePostId( id ) {
		if ( typeof id === 'object' ) {
			var attr = $( id ).attr( 'id' ) || '';
			return parseInt( attr.replace( 'post-', '' ), 10 ) || 0;
		}
		return parseInt( id, 10 ) || 0;
	}

	/**
	 * Wrap inlineEditPost.edit to intercept Quick Edit open.
	 *
	 * Fires an AJAX request to retrieve the current menu_order and
	 * post_excerpt values, then populates the Quick Edit fields.
	 *
	 * @since 1.0.0
	 */
	var _originalEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		// Call the original WordPress handler first.
		_originalEdit.apply( this, arguments );

		var postId   = resolvePostId( id );
		if ( ! postId ) {
			return;
		}

		var $editRow  = $( '#edit-' + postId );
		var $fieldset = $editRow.find( '.tiaa-qe-fieldset' );

		// No fieldset means this post type / category doesn't get our fields.
		if ( ! $fieldset.length ) {
			return;
		}

		// Reset fields before populating so stale values don't persist
		// if the user opens Quick Edit on a second row without refreshing.
		$fieldset.find( 'input[name="tiaa_menu_order"]' ).val( '' );
		$fieldset.find( 'textarea[name="tiaa_post_excerpt"]' ).val( '' );

		// Fetch current values from the server.
		$.post(
			tiaaQE.ajaxurl,
			{
				action  : 'tiaa_qe_get_post_data',
				post_id : postId,
				nonce   : tiaaQE.nonce,
			}
		).done( function ( response ) {
			if ( ! response || ! response.success ) {
				return;
			}

			var data = response.data;

			// Only populate Sort Order if a non-zero value is stored.
			var order = parseInt( data.menu_order, 10 );
			if ( order > 0 ) {
				$fieldset.find( 'input[name="tiaa_menu_order"]' ).val( order );
			}

			// Always populate Excerpt (including empty — user may want to clear it).
			$fieldset.find( 'textarea[name="tiaa_post_excerpt"]' ).val( data.post_excerpt || '' );

			// Hide Sort Order field for posts outside target categories.
			// Pages always show both fields.
			if ( ! data.is_target ) {
				$fieldset.find( '.tiaa-qe-sort-label' ).hide();
			} else {
				$fieldset.find( '.tiaa-qe-sort-label' ).show();
			}
		} );
	};

	/**
	 * Update the Sort Order cell after a successful Quick Edit save.
	 *
	 * WordPress replaces the row HTML on save. We listen for the AJAX
	 * response and update the cell from the submitted field value so the
	 * user sees the new number immediately without a page reload.
	 *
	 * @since 1.4.0
	 */
	$( document ).ajaxSuccess( function ( event, xhr, settings ) {
		// Only act on the inline-save action.
		if ( settings.data && settings.data.indexOf( 'action=inline-save' ) === -1 ) {
			return;
		}

		// Parse the saved Sort Order from the form data that was submitted.
		var params   = {};
		( settings.data || '' ).split( '&' ).forEach( function ( pair ) {
			var parts = pair.split( '=' );
			params[ decodeURIComponent( parts[0] ) ] = decodeURIComponent( parts[1] || '' );
		} );

		var postId = parseInt( params.post_ID, 10 );
		var order  = params.tiaa_menu_order;

		if ( ! postId || order === undefined || '' === order ) {
			return;
		}

		var displayValue = parseInt( order, 10 ) === 0 ? '&mdash;' : parseInt( order, 10 );

		// WordPress replaces the row HTML after save — target the refreshed row.
		$( '#post-' + postId + ' .tiaa-ord' ).html( displayValue );
	} );

} );
