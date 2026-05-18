/**
 * WC Envíos Venezuela – Integración con WooCommerce Blocks (checkout Gutenberg).
 *
 * WooCommerce blocks checkout renderiza los métodos de envío como radio-inputs
 * dentro de .wc-block-components-radio-control__option.
 * Este script usa MutationObserver para inyectar el logo del carrier
 * junto a cada etiqueta de método una vez que los bloques renderizan.
 *
 * Los datos de logos (método_id:instance_id → URL) los entrega PHP
 * a través de WCEV_Blocks_Integration::get_script_data().
 */
( function () {
    'use strict';

    /* Los datos llegan desde PHP via IntegrationInterface::get_script_data() */
    var pluginData = window.wcevShippingData || window.wc_wcev_shipping_data || {};
    var logos      = pluginData.logos || {};

    if ( ! Object.keys( logos ).length ) return;

    /* ── Inyectar logo en un elemento de opción de envío ───────────────── */
    function injectLogo( optionEl ) {
        /* El input radio tiene value = "method_id:instance_id" */
        var input = optionEl.querySelector( 'input[type="radio"]' );
        if ( ! input ) return;

        var rateId = input.value; // ej: "wcev_mrw:1"
        var logo   = logos[ rateId ];
        if ( ! logo ) return;

        /* Evitar inyección duplicada */
        if ( optionEl.querySelector( '.wcev-block-logo' ) ) return;

        var labelEl = optionEl.querySelector( 'label, .wc-block-components-radio-control__label-group' );
        if ( ! labelEl ) return;

        var img = document.createElement( 'img' );
        img.src       = logo;
        img.alt       = '';
        img.className = 'wcev-block-logo';
        img.setAttribute( 'aria-hidden', 'true' );

        labelEl.insertBefore( img, labelEl.firstChild );
    }

    /* ── Escanear todos los options de envío visibles ───────────────────── */
    function scanShippingOptions() {
        var options = document.querySelectorAll(
            '.wc-block-components-radio-control__option, ' +
            '.wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__option'
        );
        options.forEach( injectLogo );
    }

    /* ── MutationObserver: reaccionar cuando blocks re-renderizan ──────── */
    function observe() {
        var target = document.body;
        var observer = new MutationObserver( function ( mutations ) {
            var relevant = mutations.some( function ( m ) {
                return m.addedNodes.length > 0;
            } );
            if ( relevant ) {
                scanShippingOptions();
            }
        } );
        observer.observe( target, { childList: true, subtree: true } );

        /* Primer escaneo al cargar */
        scanShippingOptions();
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', observe );
    } else {
        observe();
    }
} )();
