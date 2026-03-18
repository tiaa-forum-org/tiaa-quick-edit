/**
 * tiaa-quick-edit.js
 *
 * Handles Quick Edit population and post-save cell updates for the
 * TIAA Quick Edit Fields plugin.
 *
 * Responsibilities:
 *  1. Intercepts inlineEditPost.edit() to fire an AJAX request that
 *     fetches current menu_order and post_excerpt values for the post
 *     being edited, then pre-fills the Quick Edit fields.
 *  2. After the user clicks Update, polls for the post row to reappear
 *     and writes the new Sort Order value back into the list table cell
 *     so the page reflects the change without a full reload.
 *
 * Dependencies: jquery, inline-edit-post (both registered by WordPress core).
 * Localised object: tiaaQE { ajaxurl, nonce }
 *
 * @package   TIAAQuickEdit
 * @since     1.0.0
 * @version   1.4.0
 */

jQuery( function ( $ ) {

	/**
	 * Resolves a WordPress post ID from the argument passed to inlineEditPost.edit().
	 *
	 * WordPress 6.7+ passes the clicked button element rather than a numeric ID.
	 * Earlier versions pass the numeric ID directly. This helper handles both forms.
	 *
	 * @since 1.3.0
	 *
	 * @param {number|Element} id Numeric post ID or the Quick Edit trigger button element.
	 * @return {number} Resolved post ID, or NaN if resolution fails.
	 */
	function resolvePostId( id ) {
		if ( typeof id === 'object' ) {
			// WP 6.7+: id is the clicked button — walk up to the post row.
			var $row = $( id ).closest( 'tr[id^="post-"]' );
			if ( $row.length ) {
				return parseInt( $row.attr( 'id' ).replace( 'post-', '' ), 10 );
			}
			return NaN;
		}
		return parseInt( id, 10 );
	}

	/**
	 * Updates the Sort Order column cell in the post list table row.
	 *
	 * Called after a successful Quick Edit save to reflect the new value
	 * without requiring a full page reload. Handles both the case where a
	 * badge span already exists (update in place) and where the value is
	 * being set for the first time (replace the em-dash placeholder).
	 *
	 * If newValue is blank, null, or undefined the cell is left unchanged,
	 * because a blank submission means "make no change".
	 *
	 * @since 1.4.0
	 *
	 * @param {number} postId   WordPress post ID whose row should be updated.
	 * @param {string} newValue The menu_order value submitted in the Quick Edit form.
	 * @return {void}
	 */
	function updateSortOrderCell( postId, newValue ) {
		var $cell = $( '#post-' + postId ).find( '.column-tiaa_sort_order' );
		if ( ! $cell.length ) {
			return;
		}

		if ( newValue === '' || newValue === null || newValue === undefined ) {
			// Field was left blank — leave the cell as-is (no change semantics).
			return;
		}

		var numVal = parseInt( newValue, 10 );
		var $span  = $cell.find( '.tiaa-sort-order-value' );

		if ( $span.length ) {
			// Update existing badge.
			$span.text( numVal ).attr( 'data-order', numVal );
		} else {
			// First time a value is being set — replace the em-dash placeholder.
			$cell.html(
				'<span class="tiaa-sort-order-value" data-order="' + numVal + '">' + numVal + '</span>'
			);
		}
	}

	/**
	 * Wraps inlineEditPost.edit() to add TIAA Quick Edit field population.
	 *
	 * When Quick Edit opens:
	 *  - Immediately hides and clears the TIAA fieldset to prevent stale
	 *    values from a previous session appearing during the AJAX load.
	 *  - Fires an admin-ajax.php request to tiaa_qe_get_post_data.
	 *  - On success, shows the fieldset (target posts only) and pre-fills
	 *    menu_order and post_excerpt.
	 *  - Attaches a one-time click listener to the Update button that
	 *    captures the submitted menu_order value and, once WordPress
	 *    finishes its own inline-save AJAX, writes it into the cell.
	 *
	 * Uses .one() on the Update listener so only a single handler fires
	 * per Quick Edit open, preventing listener stacking on repeated opens.
	 *
	 * @since 1.0.0
	 *
	 * @param {number|Element} id Post ID or trigger button (see resolvePostId).
	 * @return {void}
	 */
	var $wp_inline_edit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {

		$wp_inline_edit.apply( this, arguments );

		var postId = resolvePostId( id );
		if ( ! postId || isNaN( postId ) ) {
			return;
		}

		var $editRow  = $( '#edit-' + postId );
		var $fieldset = $editRow.find( '.tiaa-qe-fieldset' );

		// Hide and clear to prevent stale values from a previous session.
		$fieldset.hide();
		$editRow.find( 'input.tiaa-menu-order' ).val( '' );
		$editRow.find( 'textarea.tiaa-post-excerpt' ).val( '' );

		// Fetch current values from the server.
		$.post(
			tiaaQE.ajaxurl,
			{
				action  : 'tiaa_qe_get_post_data',
				post_id : postId,
				nonce   : tiaaQE.nonce
			},
			function ( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					return;
				}

				var data = response.data;

				if ( data.is_target ) {
					$fieldset.show();

					if ( data.menu_order !== null && data.menu_order !== undefined ) {
						$editRow.find( 'input.tiaa-menu-order' ).val( data.menu_order );
					}
				}

				if ( data.excerpt ) {
					$editRow.find( 'textarea.tiaa-post-excerpt' ).val( data.excerpt );
				}
			}
		);

		/**
		 * Listens for the Quick Edit Update button click.
		 *
		 * Captures the menu_order value at click time, then polls until
		 * WordPress's own inline-save AJAX completes and the post row
		 * reappears, at which point the cell is updated.
		 *
		 * Polling interval: 100 ms. Maximum attempts: 20 (2 seconds total).
		 */
		$editRow.find( '.save' ).one( 'click', function () {
			var submittedOrder = $editRow.find( 'input.tiaa-menu-order' ).val().trim();

			var attempts = 0;
			var interval = setInterval( function () {
				attempts++;
				var $postRow = $( '#post-' + postId );

				if ( $postRow.is( ':visible' ) || attempts > 20 ) {
					clearInterval( interval );
					if ( submittedOrder !== '' ) {
						updateSortOrderCell( postId, submittedOrder );
					}
				}
			}, 100 );
		} );
	};

} );
