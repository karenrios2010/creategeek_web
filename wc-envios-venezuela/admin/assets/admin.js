/* WC Envíos Venezuela – Admin JS */
( function ( $ ) {
    'use strict';

    /* ── Datepicker en campos de fecha ────────────────────────────────────── */
    $( document ).on( 'focus', '.wcev-datepicker', function () {
        $( this ).datepicker( {
            dateFormat:      'yy-mm-dd',
            changeMonth:     true,
            changeYear:      true,
            showButtonPanel: true,
        } );
    } );

    /* ── Media uploader para logos ────────────────────────────────────────── */
    var mediaFrame;

    $( document ).on( 'click', '.wcev-upload-logo', function ( e ) {
        e.preventDefault();
        var $btn    = $( this );
        var target  = $btn.data( 'target' );
        var $input  = $( '#' + target );
        var $field  = $btn.closest( '.wcev-logo-field' );
        var $preview = $field.find( '.wcev-logo-preview' );
        var $remove  = $field.find( '.wcev-remove-logo' );

        if ( mediaFrame ) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media( {
            title:    'Seleccionar logo del carrier',
            button:   { text: 'Usar esta imagen' },
            library:  { type: [ 'image' ] },
            multiple: false,
        } );

        mediaFrame.on( 'select', function () {
            var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
            var url = attachment.url;

            $input.val( url );
            $preview.attr( 'src', url ).show();
            $remove.show();
        } );

        mediaFrame.open();

        // Resetear frame para que funcione con distintos botones
        mediaFrame.on( 'close', function () {
            mediaFrame = null;
        } );
    } );

    /* ── Eliminar logo ────────────────────────────────────────────────────── */
    $( document ).on( 'click', '.wcev-remove-logo', function ( e ) {
        e.preventDefault();
        var $btn    = $( this );
        var target  = $btn.data( 'target' );
        var $field  = $btn.closest( '.wcev-logo-field' );

        $( '#' + target ).val( '' );
        $field.find( '.wcev-logo-preview' ).attr( 'src', '' ).hide();
        $btn.hide();
    } );

} )( jQuery );
