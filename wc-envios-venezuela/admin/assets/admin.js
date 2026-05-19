/* WC Envíos Venezuela – Admin v1.1.3 */
( function ( $ ) {
    'use strict';

    /* ── Datepicker en campos de fecha ────────────────────────────────────── */
    $( document ).on( 'focus', '.wcev-datepicker', function () {
        if ( $( this ).hasClass( 'hasDatepicker' ) ) return;
        $( this ).datepicker( {
            dateFormat:  'yy-mm-dd',
            changeMonth: true,
            changeYear:  true,
        } );
        $( this ).datepicker( 'show' );
    } );

    /* ── Media uploader para logos ────────────────────────────────────────── */
    /* Importante: usamos delegación en document para que funcione
       dentro de modales de WC que se cargan vía AJAX. */
    $( document ).on( 'click', '.wcev-upload-logo', function ( e ) {
        e.preventDefault();
        e.stopPropagation();

        var $btn     = $( this );
        var targetId = $btn.data( 'target' );
        var $wrap    = $btn.closest( '.wcev-logo-field' );

        if ( ! targetId ) {
            console.error( 'WCEV: data-target faltante en el botón de logo' );
            return;
        }

        if ( typeof wp === 'undefined' || ! wp.media ) {
            console.error( 'WCEV: wp.media no está disponible. Recarga la página con Ctrl+F5.' );
            window.alert( 'WordPress Media library no se cargó. Recarga la página completa (Ctrl+F5).' );
            return;
        }

        /* Crear un media frame nuevo en cada click (evita problemas de estado) */
        var frame = wp.media( {
            title:    'Seleccionar logo del carrier',
            button:   { text: 'Usar esta imagen' },
            library:  { type: [ 'image' ] },
            multiple: false,
        } );

        frame.on( 'select', function () {
            var attachment = frame.state().get( 'selection' ).first().toJSON();
            var url        = attachment.url || '';

            if ( ! url ) {
                console.error( 'WCEV: la imagen seleccionada no tiene URL', attachment );
                return;
            }

            /* Setear hidden input */
            var $input = $( '#' + targetId );
            $input.val( url ).trigger( 'change' );

            /* Mostrar preview */
            var $preview = $wrap.find( '.wcev-logo-preview' );
            $preview.attr( 'src', url ).css( 'display', 'block' );

            /* Mostrar botón "Quitar" */
            $wrap.find( '.wcev-remove-logo' ).css( 'display', 'inline-block' );

            /* Feedback visual: borde verde temporal */
            $wrap.css( {
                outline:        '2px solid #46b450',
                outlineOffset:  '4px',
                transition:     'outline .2s',
            } );
            setTimeout( function () {
                $wrap.css( 'outline', '' );
            }, 1200 );

            console.log( 'WCEV: logo seleccionado →', url );
        } );

        frame.open();
    } );

    /* ── Eliminar logo ────────────────────────────────────────────────────── */
    $( document ).on( 'click', '.wcev-remove-logo', function ( e ) {
        e.preventDefault();
        e.stopPropagation();
        var $btn   = $( this );
        var target = $btn.data( 'target' );
        var $wrap  = $btn.closest( '.wcev-logo-field' );

        $( '#' + target ).val( '' ).trigger( 'change' );
        $wrap.find( '.wcev-logo-preview' ).css( 'display', 'none' ).attr( 'src', '' );
        $btn.css( 'display', 'none' );
    } );

} )( jQuery );
