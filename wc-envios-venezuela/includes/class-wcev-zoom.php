<?php
defined( 'ABSPATH' ) || exit;

class WCEV_Zoom extends WCEV_Shipping_Base {

    protected string $carrier_key = 'zoom';

    public function __construct( $instance_id = 0 ) {
        $this->id              = 'wcev_zoom';
        $this->method_title    = 'Zoom';
        parent::__construct( $instance_id );
    }

    protected function get_carrier_description(): string {
        return __( 'Envíos a través de Zoom Venezuela con opción de cobro a destino.', 'wc-envios-venezuela' );
    }
}
