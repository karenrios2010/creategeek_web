<?php
defined( 'ABSPATH' ) || exit;

/**
 * Método de envío personalizado: el admin pone el nombre, precio y logo
 * que quiera. Útil para couriers propios, moto-delivery, retiro, etc.
 */
class WCEV_Custom extends WCEV_Shipping_Base {

    protected string $carrier_key = 'custom';

    public function __construct( $instance_id = 0 ) {
        $this->id           = 'wcev_custom';
        $this->method_title = __( 'Envío personalizado', 'wc-envios-venezuela' );
        parent::__construct( $instance_id );
    }

    protected function get_carrier_description(): string {
        return __( 'Método de envío completamente personalizable: define el nombre, logo, precio y si aplica cobro a destino.', 'wc-envios-venezuela' );
    }
}
