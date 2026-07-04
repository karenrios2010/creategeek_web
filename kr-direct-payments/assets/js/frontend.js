/* KR Direct Payments — order-received interactions (vanilla JS, no jQuery). */
( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		// Copy buttons.
		document.querySelectorAll( '.kr-dp-copy' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var text = btn.getAttribute( 'data-copy' ) || '';
				var done = function () {
					var original = btn.textContent;
					btn.textContent = '\u2713';
					btn.classList.add( 'is-done' );
					setTimeout( function () {
						btn.textContent = original;
						btn.classList.remove( 'is-done' );
					}, 1500 );
				};
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text ).then( done ).catch( done );
				} else {
					var ta = document.createElement( 'textarea' );
					ta.value = text;
					document.body.appendChild( ta );
					ta.select();
					try { document.execCommand( 'copy' ); } catch ( e ) {}
					document.body.removeChild( ta );
					done();
				}
			} );
		} );

		// Verification form submit.
		var form = document.querySelector( '.kr-dp-form' );
		if ( ! form || typeof window.KR_DP === 'undefined' ) {
			return;
		}

		var msg = form.querySelector( '.kr-dp-msg' );
		var submit = form.querySelector( '.kr-dp-submit' );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( msg ) {
				msg.textContent = '';
				msg.className = 'kr-dp-msg';
			}

			// Client-side required check (fields + required file).
			var invalid = false;
			form.querySelectorAll( '[data-required="1"]' ).forEach( function ( el ) {
				if ( el.type === 'file' ) {
					if ( ! el.files || el.files.length === 0 ) { invalid = true; }
				} else if ( ! el.value.trim() ) {
					invalid = true;
				}
			} );
			if ( invalid ) {
				if ( msg ) {
					msg.textContent = 'Completa los campos obligatorios.';
					msg.classList.add( 'is-error' );
				}
				return;
			}

			var data = new FormData( form );
			data.append( 'action', 'kr_dp_submit' );
			data.append( 'nonce', form.querySelector( '#kr_dp_nonce' ).value );
			data.append( 'order_id', form.getAttribute( 'data-order' ) );
			data.append( 'order_key', form.getAttribute( 'data-key' ) );

			if ( submit ) { submit.disabled = true; }

			fetch( window.KR_DP.ajax_url, {
				method: 'POST',
				body: data,
				credentials: 'same-origin'
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success ) {
						form.innerHTML = '<div class="kr-dp-done">' + ( res.data && res.data.message ? res.data.message : 'Pago registrado.' ) + '</div>';
					} else {
						if ( msg ) {
							msg.textContent = ( res && res.data && res.data.message ) ? res.data.message : 'Ocurrio un error. Intenta de nuevo.';
							msg.classList.add( 'is-error' );
						}
						if ( submit ) { submit.disabled = false; }
					}
				} )
				.catch( function () {
					if ( msg ) {
						msg.textContent = 'No se pudo conectar. Intenta de nuevo.';
						msg.classList.add( 'is-error' );
					}
					if ( submit ) { submit.disabled = false; }
				} );
		} );

		// Two-column layout: place the form beside the order summary.
		layoutBeside();
	} );

	function layoutBeside() {
		var box = document.querySelector( '.kr-dp[data-layout="beside"]' );
		if ( ! box ) {
			return;
		}
		var details  = document.querySelector( '.woocommerce-order-details' );
		var customer = document.querySelector( '.woocommerce-customer-details' );
		if ( ! details ) {
			return; // Nothing to sit beside.
		}
		if ( box.closest( '.kr-dp-cols' ) ) {
			return; // Already arranged.
		}

		var wrap  = document.createElement( 'div' );
		wrap.className = 'kr-dp-cols';
		var left  = document.createElement( 'div' );
		left.className = 'kr-dp-col-left';
		var right = document.createElement( 'div' );
		right.className = 'kr-dp-col-right';

		// Insert the wrapper where the order details currently are.
		details.parentNode.insertBefore( wrap, details );

		// Left column: order details + customer addresses.
		left.appendChild( details );
		if ( customer ) {
			left.appendChild( customer );
		}
		// Right column: the payment form.
		right.appendChild( box );

		wrap.appendChild( left );
		wrap.appendChild( right );
	}
}() );

