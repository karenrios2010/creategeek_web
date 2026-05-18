/**
 * WC Envíos Venezuela – integración con WooCommerce Blocks checkout.
 *
 * Problemas que resuelve:
 *  1. Inyecta el logo del carrier junto a la etiqueta del método de envío.
 *  2. Reemplaza el texto "Gratis / Free" por el label de cobro a destino
 *     cuando el método tiene cobro_destino habilitado y costo = 0.
 *
 * El bloque de checkout Gutenberg re-renderiza en React, por eso usamos
 * MutationObserver en lugar de un evento único DOMContentLoaded.
 */
( function () {
    'use strict';

    /* PHP inyecta: wcevShippingData.rates = { "wcev_mrw:1": { logo, cobro_destino, cobro_destino_label }, ... } */
    var pluginData = window.wcevShippingData || {};
    var rates      = pluginData.rates || {};

    if ( ! Object.keys( rates ).length ) return;

    /* Textos que WooCommerce muestra cuando el precio es 0 */
    var FREE_TEXTS = [ 'gratis', 'free', 'free!', 'gratuito', '$0.00', '0,00 $', 'bs0.00', 'bs. 0.00', '0.00' ];

    function isFreeText( txt ) {
        return FREE_TEXTS.indexOf( txt.toLowerCase().trim() ) !== -1;
    }

    /* ── Procesar un elemento de opción de envío ─────────────────────────── */
    function processOption( el ) {
        /* WC Blocks renderiza el rate-id en el atributo value del input radio.
           Selector robusto que cubre varias versiones de WC Blocks. */
        var input = el.querySelector( 'input[type="radio"]' );
        if ( ! input ) return;

        var rateId   = input.value;                    // "wcev_mrw:1"
        var rateData = rates[ rateId ];
        if ( ! rateData ) return;

        /* ── Inyectar logo ───────────────────────────────────────────────── */
        if ( rateData.logo && ! el.querySelector( '.wcev-block-logo' ) ) {
            /* El label puede estar en distintos nodos según la versión de WC Blocks */
            var labelText = el.querySelector(
                '.wc-block-components-radio-control__label, ' +
                '.wc-block-components-radio-control__label-group, ' +
                'label span:first-child, label'
            );
            if ( labelText ) {
                var img       = document.createElement( 'img' );
                img.src       = rateData.logo;
                img.alt       = '';
                img.className = 'wcev-block-logo';
                img.setAttribute( 'aria-hidden', 'true' );
                labelText.parentNode.insertBefore( img, labelText );
            }
        }

        /* ── Reemplazar "Gratis" en cobro a destino ──────────────────────── */
        if ( rateData.cobro_destino === 'yes' ) {
            /* WC Blocks muestra el precio en un span separado */
            var priceEl = el.querySelector(
                '.wc-block-components-radio-control__secondary-label, ' +
                '.wc-block-components-shipping-rates-control__package-rate-price, ' +
                '[class*="secondary-label"], [class*="price"]'
            );
            if ( priceEl && isFreeText( priceEl.textContent ) ) {
                priceEl.textContent = rateData.cobro_destino_label || 'Cobro a destino';
                priceEl.classList.add( 'wcev-cobro-destino-label' );
            }
        }
    }

    /* ── Escanear todos los options visibles en la página ────────────────── */
    function scan() {
        /* Selector amplio: cubre .wc-block-components-radio-control__option
           y también posibles variaciones de clase */
        var options = document.querySelectorAll( '[class*="radio-control__option"]' );
        options.forEach( processOption );
    }

    /* ── MutationObserver: reacciona cuando React re-renderiza ───────────── */
    var timer;
    var observer = new MutationObserver( function () {
        /* Debounce: agrupar múltiples mutaciones en una sola pasada */
        clearTimeout( timer );
        timer = setTimeout( scan, 80 );
    } );

    function start() {
        observer.observe( document.body, { childList: true, subtree: true } );
        scan(); /* Primer escaneo inmediato */
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', start );
    } else {
        start();
    }
} )();
