/* KR Direct Payments — classic checkout.
   Recalcula los totales cuando cambia el metodo de pago, para que el
   recargo fijo y la fila del total en Bs. (tasa BCV) se actualicen. */
( function () {
	'use strict';

	if ( typeof jQuery === 'undefined' ) {
		return;
	}

	jQuery( function ( $ ) {
		$( document.body ).on( 'change', 'input[name="payment_method"]', function () {
			$( document.body ).trigger( 'update_checkout' );
		} );
	} );
}() );
