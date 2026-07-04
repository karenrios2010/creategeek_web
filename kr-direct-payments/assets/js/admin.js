/* KR Direct Payments — admin settings helpers. */
jQuery( function ( $ ) {
	'use strict';

	// Sync native color picker <-> text input.
	$( document ).on( 'input', '.kr-dp-color-pick', function () {
		$( this ).closest( 'td' ).find( '.kr-dp-color-text' ).val( $( this ).val() );
	} );
	$( document ).on( 'input', '.kr-dp-color-text', function () {
		var v = $( this ).val();
		if ( /^#[0-9a-fA-F]{6}$/.test( v ) ) {
			$( this ).closest( 'td' ).find( '.kr-dp-color-pick' ).val( v );
		}
	} );

	// Media Library picker for logo / QR fields.
	$( document ).on( 'click', '.kr-dp-logo-pick', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $url = $btn.closest( 'td' ).find( '.kr-dp-logo-url' );
		var $prev = $btn.closest( 'td' ).find( '.kr-dp-logo-preview' );

		var frame = wp.media( {
			title: 'Seleccionar imagen',
			button: { text: 'Usar esta imagen' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$url.val( attachment.url );
			if ( $prev.length ) {
				$prev.attr( 'src', attachment.url );
			} else {
				$btn.after( '<div style="margin-top:8px"><img src="' + attachment.url + '" class="kr-dp-logo-preview" style="max-height:40px;background:#eee;padding:4px;border-radius:6px" /></div>' );
			}
		} );

		frame.open();
	} );
} );
