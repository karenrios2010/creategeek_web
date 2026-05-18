/* WC Envíos Venezuela – Frontend JS */
( function ( $ ) {
    'use strict';

    var COOKIE_NAME = 'wcev_popup_dismissed';

    /* ── Leer cookie ──────────────────────────────────────────────────────── */
    function getCookie( name ) {
        var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
        return match ? decodeURIComponent( match[1] ) : null;
    }

    /* ── Inicializar popup ────────────────────────────────────────────────── */
    function initPopup() {
        var $overlay = $( '#wcev-popup-overlay' );
        if ( ! $overlay.length ) return;

        // Si ya fue cerrado hoy, no mostrar
        if ( getCookie( COOKIE_NAME ) === '1' ) {
            $overlay.remove();
            return;
        }

        // Mostrar con pequeño delay para no interrumpir carga de página
        setTimeout( function () {
            $overlay.css( 'display', 'flex' );
        }, 800 );

        // Cerrar al pulsar botón
        $overlay.on( 'click', '.wcev-popup-close', function () {
            closePopup( $overlay );
        } );

        // Cerrar al pulsar fuera del box (solo en modo center)
        $overlay.on( 'click', function ( e ) {
            if ( $( e.target ).is( $overlay ) ) {
                closePopup( $overlay );
            }
        } );

        // Cerrar con ESC
        $( document ).on( 'keydown.wcev', function ( e ) {
            if ( e.key === 'Escape' ) {
                closePopup( $overlay );
            }
        } );

        // Iniciar cuenta regresiva si existe
        initCountdown( $overlay );
    }

    function closePopup( $overlay ) {
        $overlay.addClass( 'wcev-hidden' );

        // Guardar cookie vía AJAX (también se setea server-side)
        $.post( wcevData.ajaxUrl, {
            action: 'wcev_dismiss_popup',
            nonce:  wcevData.nonce
        } );

        setTimeout( function () {
            $overlay.remove();
            $( document ).off( 'keydown.wcev' );
        }, 350 );
    }

    /* ── Cuenta regresiva ─────────────────────────────────────────────────── */
    function initCountdown( $overlay ) {
        var $timer = $overlay.find( '.wcev-countdown-timer' );
        if ( ! $timer.length ) return;

        var $cd    = $overlay.find( '.wcev-countdown' );
        var endStr = $cd.data( 'date' ); // YYYY-MM-DD
        if ( ! endStr ) return;

        // Fin del día de la fecha indicada (23:59:59 hora local)
        var endDate = new Date( endStr + 'T23:59:59' );

        function update() {
            var now  = new Date();
            var diff = endDate - now;

            if ( diff <= 0 ) {
                $cd.hide();
                return;
            }

            var d  = Math.floor( diff / 86400000 );
            var h  = Math.floor( ( diff % 86400000 ) / 3600000 );
            var m  = Math.floor( ( diff % 3600000 ) / 60000 );
            var s  = Math.floor( ( diff % 60000 ) / 1000 );

            var parts = [];
            if ( d > 0 ) parts.push( d + 'd' );
            parts.push( pad( h ) + 'h' );
            parts.push( pad( m ) + 'm' );
            parts.push( pad( s ) + 's' );

            $timer.text( parts.join( ' ' ) );
        }

        function pad( n ) {
            return n < 10 ? '0' + n : n;
        }

        update();
        var interval = setInterval( update, 1000 );

        // Limpiar si se cierra
        $overlay.on( 'click', '.wcev-popup-close', function () {
            clearInterval( interval );
        } );
    }

    /* ── Arranque ─────────────────────────────────────────────────────────── */
    $( document ).ready( function () {
        initPopup();
    } );

} )( jQuery );
