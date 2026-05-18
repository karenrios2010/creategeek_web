/* WC Envíos Venezuela – Admin */
( function ( $ ) {
    'use strict';

    /* ── Datepicker ───────────────────────────────────────────────────────── */
    $( document ).on( 'focus', '.wcev-datepicker', function () {
        if ( $( this ).hasClass( 'hasDatepicker' ) ) return;
        $( this ).datepicker( { dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true } );
        $( this ).datepicker( 'show' );
    } );

    /* ── Media uploader para logos ────────────────────────────────────────── */
    $( document ).on( 'click', '.wcev-upload-logo', function ( e ) {
        e.preventDefault();
        var $btn     = $( this );
        var targetId = $btn.data( 'target' );
        var $wrap    = $btn.closest( '.wcev-logo-field' );

        wp.media( {
            title:    'Seleccionar logo del carrier',
            button:   { text: 'Usar esta imagen' },
            library:  { type: [ 'image' ] },
            multiple: false,
        } ).on( 'select', function () {
            var att = this.state().get( 'selection' ).first().toJSON();
            $( '#' + targetId ).val( att.url );
            $wrap.find( '.wcev-logo-preview' ).attr( 'src', att.url ).show();
            $wrap.find( '.wcev-remove-logo' ).show();
        } ).open();
    } );

    /* ── Eliminar logo ────────────────────────────────────────────────────── */
    $( document ).on( 'click', '.wcev-remove-logo', function ( e ) {
        e.preventDefault();
        var $wrap = $( this ).closest( '.wcev-logo-field' );
        $( '#' + $( this ).data( 'target' ) ).val( '' );
        $wrap.find( '.wcev-logo-preview' ).hide().attr( 'src', '' );
        $( this ).hide();
    } );

} )( jQuery );
