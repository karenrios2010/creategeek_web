<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Integración con WooCommerce Blocks (Cart & Checkout Gutenberg).
 * Pasa los logos de cada instancia al frontend JS para inyectarlos
 * en los radio-buttons del checkout de bloques via MutationObserver.
 */
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
            'logos' => self::collect_logos(),
        ];
    }

    /**
     * Recorre todas las zonas de envío y recoge el logo de cada
     * instancia de método WCEV, indexado por "method_id:instance_id".
     */
    public static function collect_logos(): array {
        $logos = [];

        $all_zones   = WC_Shipping_Zones::get_zones();
        $all_zones[] = [ 'zone_id' => 0 ]; // Zona "resto del mundo"

        foreach ( $all_zones as $zone_data ) {
            $zone = new WC_Shipping_Zone( $zone_data['zone_id'] );
            foreach ( $zone->get_shipping_methods( true ) as $method ) {
                if ( 0 !== strpos( $method->id, 'wcev_' ) ) {
                    continue;
                }
                $logo = $method->get_option( 'logo', '' );
                if ( $logo ) {
                    $key          = $method->id . ':' . $method->instance_id;
                    $logos[ $key ] = esc_url( $logo );
                }
            }
        }

        return $logos;
    }

    public function initialize(): void {
        wp_register_script(
            'wcev-blocks-frontend',
            WCEV_URL . 'assets/js/blocks-frontend.js',
            [ 'wc-blocks-checkout', 'jquery' ],
            WCEV_VERSION,
            true
        );
    }
}

/* ── Registro de la integración en el hook correcto ───────────────────────── */
add_action( 'woocommerce_blocks_loaded', function () {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
        return;
    }
    require_once WCEV_DIR . 'includes/class-wcev-blocks-integration.php';
    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function ( $integration_registry ) {
            $integration_registry->register( new WCEV_Blocks_Integration() );
        }
    );
    add_action(
        'woocommerce_blocks_cart_block_registration',
        function ( $integration_registry ) {
            $integration_registry->register( new WCEV_Blocks_Integration() );
        }
    );
} );
