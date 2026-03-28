/**
 * tiaa-quick-edit.js
 *
 * Handles Quick Edit population and post-save cell updates for the
 * TIAA Quick Edit Fields plugin.
 *
 * Responsibilities:
 *  1. Intercepts inlineEditPost.edit() to fire an AJAX request that
 *     fetches current menu_order and post_excerpt values for the post
 *     or page being edited, then pre-fills the Quick Edit fields.
 *  2. After the user clicks Update on a Post, polls for the post row to
 *     reappear and writes the new Sort Order value back into the list
 *     table cell so the page reflects the change without a full reload.
 *     (Pages do not have a Sort Order cell — no polling needed.)
 *
 * Dependencies: jquery, inline-edit-post (both registered by WordPress core).
 * Localised object: tiaaQE { ajaxurl, nonce }
 *
 * @package   TIAAQuickEdit
 * @since     1.0.0
 * @version   1.5.1
 */

jQuery( function ( $ ) {

	/**
	 * Safety guard — bail immediately if inlineEditPost is not available.
	 *
	 * inlineEditPost is registered by WordPress core only on the Posts and
	 * Pages list screens (edit.php). If this script somehow loads on another
	 * admin screen — such as the Elementor editor — attempting to wrap
	 * inlineEditPost.edit() would throw an uncaught TypeError that can
	 * interrupt other scripts on the page, including Elementor's own
	 * initialisation sequence.
	 *
	 * The PHP enqueue hook is the primary guard (edit.php check). This is a
	 * belt-and-suspenders defence in case the script loads in an unexpected
	 * context.
	 *
	 * @since 1.5.1
	 */
	if ( typeof inlineEditPost === 'undefined' ) {
		return;
	}

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
	 * Called after a successful Quick Edit save on a Post to reflect the new
	 * value without requiring a full page reload. Not used for Pages (which
	 * have no Sort Order column).
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
			return;
		}

		var numVal = parseInt( newValue, 10 );
		var $span  = $cell.find( '.tiaa-sort-order-value' );

		if ( $span.length ) {
			$span.text( numVal ).attr( 'data-order', numVal );
		} else {
			$cell.html(
				'<span class="tiaa-sort-order-value" data-order="' + numVal + '">' + numVal + '</span>'
			);
		}
	}

	/**
	 * Wraps inlineEditPost.edit() to add TIAA Quick Edit field population.
	 *
	 * When Quick Edit opens for a Post:
	 *  - Hides and clears the TIAA fieldset to prevent stale values.
	 *  - Fires an AJAX request to fetch current values.
	 *  - Shows the fieldset and pre-fills fields if the post is a target.
	 *  - Attaches a one-time Update button listener to update the Sort Order
	 *    cell after save without a full page reload.
	 *
	 * When Quick Edit opens for a Page:
	 *  - The fieldset is always visible (no display:none hiding needed).
	 *  - Fires an AJAX request to fetch the current excerpt.
	 *  - Pre-fills the excerpt textarea.
	 *  - No Sort Order cell polling needed.
	 *
	 * Uses .one() on the Update listener so only a single handler fires
	 * per Quick Edit open, preventing listener stacking on repeated opens.
	 *
	 * @since 1.0.0
	 * @updated 1.5.0 — handles page post type.
	 *
	 * @param {number|Element} id Post/page ID or trigger button (see resolvePostId).
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

		// Detect whether this is a page based on the fieldset CSS class added
		// by the page-specific PHP Quick Edit callback.
		var isPage = $fieldset.hasClass( 'tiaa-qe-fieldset-page' );

		// For posts: hide fieldset and clear fields until AJAX confirms target status.
		// For pages: fieldset is always visible; just clear the textarea to prevent
		// stale values from a previous Quick Edit session appearing briefly.
		if ( ! isPage ) {
			$fieldset.hide();
			$editRow.find( 'input.tiaa-menu-order' ).val( '' );
		}
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

				if ( isPage ) {
					// Pages: fieldset always shown; just pre-fill excerpt.
					if ( data.excerpt ) {
						$editRow.find( 'textarea.tiaa-post-excerpt' ).val( data.excerpt );
					}
				} else {
					// Posts: show fieldset only if in a target category.
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
			}
		);

		// Posts only: poll after Update to refresh the Sort Order cell without reload.
		// Pages have no Sort Order column so polling is skipped entirely.
		if ( ! isPage ) {
			/**
			 * Listens for the Quick Edit Update button click on Posts.
			 *
			 * Captures the menu_order value at click time, then polls until
			 * WordPress's own inline-save AJAX completes and the post row
			 * reappears, at which point the Sort Order cell is updated.
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
		}
	};

} );
