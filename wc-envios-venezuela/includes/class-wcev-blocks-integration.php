<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class WCEV_Blocks_Integration implements IntegrationInterface {

    public function get_name(): string {
        return 'wcev_shipping';
    }

    public function get_script_handles(): array {
        return [ 'wcev-blocks-frontend' ];
    }

    public function get_editor_script_handles(): array {
        return [];
    }

    public function get_script_data(): array {
        return [
            'rates' => self::collect_rate_data(),
        ];
    }

    /**
     * Devuelve logo + datos de cobro a destino por cada instancia WCEV,
     * indexado por "method_id:instance_id" para que el JS pueda hacer el match.
     */
    public static function collect_rate_data(): array {
        $data = [];

        $all_zones   = WC_Shipping_Zones::get_zones();
        $all_zones[] = [ 'zone_id' => 0 ]; // Zona "Resto del mundo"

        foreach ( $all_zones as $zone_data ) {
            $zone = new WC_Shipping_Zone( $zone_data['zone_id'] );
            foreach ( $zone->get_shipping_methods( true ) as $method ) {
                if ( 0 !== strpos( $method->id, 'wcev_' ) ) {
                    continue;
                }
                $key          = $method->id . ':' . $method->instance_id;
                $data[ $key ] = [
                    'logo'               => esc_url( $method->get_option( 'logo', '' ) ),
                    'cobro_destino'      => $method->get_option( 'cobro_destino', 'no' ),
                    'cobro_destino_label'=> $method->get_option( 'cobro_destino_label', __( 'Cobro a destino', 'wc-envios-venezuela' ) ),
                ];
            }
        }

        return $data;
    }

    /** Mantener retrocompatibilidad con llamadas antiguas a collect_logos() */
    public static function collect_logos(): array {
        $logos = [];
        foreach ( self::collect_rate_data() as $key => $d ) {
            if ( $d['logo'] ) {
                $logos[ $key ] = $d['logo'];
            }
        }
        return $logos;
    }

    public function initialize(): void {
        wp_register_script(
            'wcev-blocks-frontend',
            WCEV_URL . 'assets/js/blocks-frontend.js',
            [],
            WCEV_VERSION,
            true
        );
    }
}

/* ── Registro en WooCommerce Blocks ───────────────────────────────────────── */
add_action( 'woocommerce_blocks_loaded', function () {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
        return;
    }
    $register = function ( $registry ) {
        $registry->register( new WCEV_Blocks_Integration() );
    };
    add_action( 'woocommerce_blocks_checkout_block_registration', $register );
    add_action( 'woocommerce_blocks_cart_block_registration',     $register );
} );
