/**
 * WC Envíos Venezuela – Checkout de bloques (Gutenberg).
 *
 * Inyecta el logo del carrier en cada opción de envío y
 * reemplaza el texto "Gratis / Free" por el label de cobro a destino
 * cuando el método tiene esa opción activa.
 *
 * Usa MutationObserver porque React re-renderiza el árbol en cada cambio.
 * wcevBlocks.rates lo provee PHP via wp_localize_script.
 */
( function () {
    'use strict';

    var cfg   = window.wcevBlocks || {};
    var rates = cfg.rates || {};

    // Textos que WooCommerce muestra cuando el precio es 0
    var FREE = [ 'gratis', 'free', 'free!', 'gratuito', '0', '$0.00', '0.00', 'bs.0.00', 'bs0.00' ];

    function isFree( txt ) {
        return FREE.indexOf( ( txt || '' ).replace( /\s/g, '' ).toLowerCase() ) !== -1;
    }

    /* ── Procesar un radio-option de envío ──────────────────────────────── */
    function processOption( el ) {
        var input = el.querySelector( 'input[type="radio"]' );
        if ( ! input || ! input.value ) return;

        var info = rates[ input.value ];
        if ( ! info ) return;

        /* 1. Inyectar logo (solo si no existe ya) */
        if ( info.logo && ! el.querySelector( '.wcev-block-logo' ) ) {
            var lbl = el.querySelector( 'label' );
            if ( lbl ) {
                var img       = document.createElement( 'img' );
                img.src       = info.logo;
                img.alt       = '';
                img.className = 'wcev-block-logo';
                img.setAttribute( 'aria-hidden', 'true' );
                lbl.insertBefore( img, lbl.firstChild );
            }
        }

        /* 2. Reemplazar "Gratis" en cobro a destino */
        if ( info.cobro_destino === 'yes' ) {
            /* WC Blocks usa .wc-block-components-radio-control__secondary-label
               para mostrar el precio de cada opción */
            var priceEl = el.querySelector( [
                '.wc-block-components-radio-control__secondary-label',
                '[class*="secondary-label"]',
                '[class*="secondaryLabel"]',
            ].join( ',' ) );

            if ( priceEl ) {
                var txt = priceEl.textContent || priceEl.innerText || '';
                if ( isFree( txt ) ) {
                    /* Limpiar contenido y poner badge personalizado */
                    priceEl.textContent = '';
                    var badge       = document.createElement( 'span' );
                    badge.className = 'wcev-cobro-tag';
                    badge.textContent = info.cobro_destino_label || 'Cobro a destino';
                    priceEl.appendChild( badge );
                    priceEl.setAttribute( 'data-wcev-fixed', '1' );
                }
            }
        }
    }

    /* ── Escanear todos los options visibles ─────────────────────────────── */
    function scan() {
        /* Selector que cubre WC Blocks 8.x / 9.x / 10.x / 11.x */
        var opts = document.querySelectorAll(
            '.wc-block-components-radio-control__option, [class*="radio-control__option"]'
        );
        opts.forEach( processOption );
    }

    /* ── MutationObserver con rAF y debounce ─────────────────────────────── */
    var rafId;
    var observer = new MutationObserver( function ( mutations ) {
        var changed = mutations.some( function ( m ) {
            return m.addedNodes.length > 0 || m.type === 'characterData';
        } );
        if ( ! changed ) return;

        cancelAnimationFrame( rafId );
        /* rAF: esperar el próximo frame de pintura de React */
        rafId = requestAnimationFrame( function () {
            /* setTimeout extra de 0ms garantiza ejecución después de React commit */
            setTimeout( scan, 0 );
        } );
    } );

    function start() {
        observer.observe( document.body, {
            childList:     true,
            subtree:       true,
            characterData: true, /* detecta cambios de texto (React puede actualizar textContent) */
        } );
        scan(); /* escaneo inicial */
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', start );
    } else {
        start();
    }
} )();
