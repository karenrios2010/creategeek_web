/* KR Direct Payments — WooCommerce Checkout Blocks registration.
   One generic registration driven by per-method settings data.
   Adds: fixed-surcharge note, live Bs. (BCV rate) total and server sync
   of the chosen payment method so the fee is applied/removed instantly. */
( function () {
	'use strict';

	var registry = window.wc && window.wc.wcBlocksRegistry;
	var settingsApi = window.wc && window.wc.wcSettings;
	var element = window.wp && window.wp.element;
	var htmlEntities = window.wp && window.wp.htmlEntities;
	var wpData = window.wp && window.wp.data;
	var blocksCheckout = window.wc && window.wc.blocksCheckout;

	if ( ! registry || ! settingsApi || ! element ) {
		return;
	}

	var createElement = element.createElement;
	var RawHTML = element.RawHTML;
	var decodeEntities = htmlEntities ? htmlEntities.decodeEntities : function ( s ) { return s; };

	var ids = [ 'kr_zelle', 'kr_dp_bank', 'kr_dp_pagomovil', 'kr_dp_binance' ];

	function formatBs( amount ) {
		var formatted;
		try {
			formatted = amount.toLocaleString( 'es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
		} catch ( e ) {
			formatted = amount.toFixed( 2 );
		}
		return 'Bs. ' + formatted;
	}

	/* Aviso al servidor cuando cambia el metodo de pago elegido, para que
	   la sesion recalcule el recargo fijo (se aplica o se quita al momento). */
	if ( wpData && wpData.subscribe && blocksCheckout && blocksCheckout.extensionCartUpdate ) {
		var lastMethod = null;
		wpData.subscribe( function () {
			var store = wpData.select( 'wc/store/payment' );
			if ( ! store || typeof store.getActivePaymentMethod !== 'function' ) {
				return;
			}
			var active = store.getActivePaymentMethod();
			if ( ! active || active === lastMethod ) {
				return;
			}
			lastMethod = active;
			var request = blocksCheckout.extensionCartUpdate( {
				namespace: 'kr-direct-payments',
				data: { payment_method: active }
			} );
			if ( request && typeof request.catch === 'function' ) {
				request.catch( function () {} );
			}
		} );
	}

	ids.forEach( function ( id ) {
		var data = settingsApi.getSetting( id + '_data', null );
		if ( ! data ) {
			return;
		}

		var title = decodeEntities( data.title || '' );

		var Label = function () {
			return createElement(
				'span',
				{ style: { display: 'flex', alignItems: 'center', gap: '8px' } },
				data.icon ? createElement( 'img', {
					src: data.icon,
					alt: '',
					style: { height: '24px', width: 'auto' },
					onError: function ( e ) { e.target.style.display = 'none'; }
				} ) : null,
				createElement( 'span', null, title )
			);
		};

		var Content = function () {
			var totals = null;
			if ( wpData && wpData.useSelect ) {
				totals = wpData.useSelect( function ( select ) {
					var cart = select( 'wc/store/cart' );
					return cart && typeof cart.getCartTotals === 'function' ? cart.getCartTotals() : null;
				}, [] );
			}

			var children = [];

			if ( data.description ) {
				children.push( createElement( RawHTML, { key: 'desc' }, data.description ) );
			}

			if ( data.feeText ) {
				children.push(
					createElement(
						'p',
						{ key: 'fee', style: { margin: '8px 0 0', fontSize: '0.9em' } },
						decodeEntities( data.feeText )
					)
				);
			}

			if ( data.showBs && data.rate > 0 && totals && totals.total_price ) {
				var minor = typeof totals.currency_minor_unit === 'number' ? totals.currency_minor_unit : 2;
				var total = parseInt( totals.total_price, 10 ) / Math.pow( 10, minor );
				var bs = total * data.rate;

				children.push(
					createElement(
						'div',
						{
							key: 'bs',
							style: {
								marginTop: '10px',
								padding: '10px 12px',
								borderRadius: '8px',
								border: '1px solid rgba(127,127,127,0.3)',
								background: 'rgba(127,127,127,0.08)'
							}
						},
						createElement( 'strong', { style: { display: 'block' } }, decodeEntities( data.bsLabel || '' ) + ' ' + formatBs( bs ) ),
						data.rateText
							? createElement( 'small', { style: { opacity: 0.8 } }, decodeEntities( data.rateText ) )
							: null
					)
				);
			}

			return createElement( 'div', { className: 'kr-dp-blocks-content' }, children );
		};

		registry.registerPaymentMethod( {
			name: data.name,
			label: createElement( Label, null ),
			content: createElement( Content, null ),
			edit: createElement( Content, null ),
			canMakePayment: function () { return true; },
			ariaLabel: title,
			supports: {
				features: ( data.supports ) || [ 'products' ]
			}
		} );
	} );
}() );
