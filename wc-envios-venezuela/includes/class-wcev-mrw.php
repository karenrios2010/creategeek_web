<?php
defined( 'ABSPATH' ) || exit;

class WCEV_MRW extends WCEV_Shipping_Base {

    protected string $carrier_key = 'mrw';

    public function __construct( $instance_id = 0 ) {
        $this->id              = 'wcev_mrw';
        $this->method_title    = 'MRW';
        parent::__construct( $instance_id );
    }

    protected function get_carrier_description(): string {
        return __( 'Envíos a través de MRW Venezuela con opción de cobro a destino.', 'wc-envios-venezuela' );
    }
}
