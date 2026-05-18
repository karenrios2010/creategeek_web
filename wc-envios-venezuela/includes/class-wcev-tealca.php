<?php
defined( 'ABSPATH' ) || exit;

class WCEV_Tealca extends WCEV_Shipping_Base {

    protected string $carrier_key = 'tealca';

    public function __construct( $instance_id = 0 ) {
        $this->id              = 'wcev_tealca';
        $this->method_title    = 'Tealca';
        parent::__construct( $instance_id );
    }

    protected function get_carrier_description(): string {
        return __( 'Envíos a través de Tealca Venezuela con opción de cobro a destino.', 'wc-envios-venezuela' );
    }
}
