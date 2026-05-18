/* WC Envíos Venezuela – Popup */
( function ( $ ) {
    'use strict';

    var COOKIE = 'wcev_popup_dismissed';

    function getCookie( name ) {
        var m = document.cookie.match( '(?:^|;)\\s*' + name + '=([^;]*)' );
        return m ? decodeURIComponent( m[1] ) : null;
    }

    function closePopup( $el ) {
        $el.addClass( 'wcev-hiding' ).removeClass( 'wcev-visible' );
        $.post( wcevData.ajaxUrl, { action: 'wcev_dismiss_popup', nonce: wcevData.nonce } );
        setTimeout( function () { $el.remove(); }, 350 );
    }

    function initCountdown( $popup ) {
        var $timer = $popup.find( '.wcev-countdown-timer' );
        if ( ! $timer.length ) return;
        var end = new Date( $popup.find( '.wcev-countdown' ).data( 'date' ) + 'T23:59:59' );

        function tick() {
            var d = end - Date.now();
            if ( d <= 0 ) { $timer.closest( '.wcev-countdown' ).hide(); return; }
            var h = Math.floor( d / 3600000 ),
                m = Math.floor( ( d % 3600000 ) / 60000 ),
                s = Math.floor( ( d % 60000 ) / 1000 );
            $timer.text( pad( h ) + ':' + pad( m ) + ':' + pad( s ) );
        }
        function pad( n ) { return n < 10 ? '0' + n : n; }

        tick();
        var iv = setInterval( tick, 1000 );
        $popup.one( 'click', '.wcev-popup-close', function () { clearInterval( iv ); } );
    }

    $( function () {
        var $popup = $( '#wcev-popup' );
        if ( ! $popup.length ) return;
        if ( getCookie( COOKIE ) === '1' ) { $popup.remove(); return; }

        // Mostrar con pequeño delay para no interrumpir render
        setTimeout( function () {
            $popup.addClass( 'wcev-visible' );
            initCountdown( $popup );
        }, 700 );

        $popup.on( 'click', '.wcev-popup-close', function () { closePopup( $popup ); } );

        // Cerrar al hacer click fuera (solo modal centrado)
        $popup.on( 'click', function ( e ) {
            if ( $( e.target ).is( $popup ) ) { closePopup( $popup ); }
        } );

        $( document ).on( 'keydown.wcev', function ( e ) {
            if ( e.key === 'Escape' ) { closePopup( $popup ); $( document ).off( 'keydown.wcev' ); }
        } );
    } );

} )( jQuery );
