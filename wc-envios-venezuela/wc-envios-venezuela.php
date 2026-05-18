<?php
/**
 * Plugin Name: WC Envíos Venezuela
 * Plugin URI:  https://creategeek.agency
 * Description: Métodos de envío personalizados para Venezuela: MRW, Zoom y Tealca con cobro a destino y banner de promociones con rango de fechas.
 * Version:     1.0.0
 * Author:      CreateGeek Agency
 * Text Domain: wc-envios-venezuela
 * Domain Path: /languages
 * Requires WC: 5.0
 * WC tested up to: 8.5
 */

defined( 'ABSPATH' ) || exit;

define( 'WCEV_VERSION',  '1.0.0' );
define( 'WCEV_FILE',     __FILE__ );
define( 'WCEV_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WCEV_URL',      plugin_dir_url( __FILE__ ) );

/* ─── Compatibilidad HPOS ─────────────────────────────────────────────────── */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', __FILE__, true
        );
    }
} );

/* ─── Arranque ────────────────────────────────────────────────────────────── */
add_action( 'plugins_loaded', 'wcev_init', 20 );

function wcev_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'WC Envíos Venezuela requiere WooCommerce activo.', 'wc-envios-venezuela' )
                . '</p></div>';
        } );
        return;
    }

    require_once WCEV_DIR . 'includes/class-wcev-shipping-base.php';
    require_once WCEV_DIR . 'includes/class-wcev-mrw.php';
    require_once WCEV_DIR . 'includes/class-wcev-zoom.php';
    require_once WCEV_DIR . 'includes/class-wcev-tealca.php';
    require_once WCEV_DIR . 'includes/class-wcev-custom.php';
    require_once WCEV_DIR . 'includes/class-wcev-popup.php';
    require_once WCEV_DIR . 'includes/class-wcev-blocks-integration.php';

    // Registrar métodos de envío
    add_filter( 'woocommerce_shipping_methods', 'wcev_register_methods' );

    // Assets frontend
    add_action( 'wp_enqueue_scripts', 'wcev_frontend_assets' );

    // Assets admin
    add_action( 'admin_enqueue_scripts', 'wcev_admin_assets' );

    // AJAX popup dismiss
    add_action( 'wp_ajax_wcev_dismiss_popup',        'wcev_ajax_dismiss_popup' );
    add_action( 'wp_ajax_nopriv_wcev_dismiss_popup', 'wcev_ajax_dismiss_popup' );

    // Inicializar popup
    WCEV_Popup::init();
}

function wcev_register_methods( $methods ) {
    $methods['wcev_mrw']    = 'WCEV_MRW';
    $methods['wcev_zoom']   = 'WCEV_Zoom';
    $methods['wcev_tealca'] = 'WCEV_Tealca';
    $methods['wcev_custom'] = 'WCEV_Custom';
    return $methods;
}

function wcev_frontend_assets() {
    wp_enqueue_style(
        'wcev-frontend',
        WCEV_URL . 'assets/css/frontend.css',
        [],
        WCEV_VERSION
    );
    wp_enqueue_script(
        'wcev-frontend',
        WCEV_URL . 'assets/js/frontend.js',
        [ 'jquery' ],
        WCEV_VERSION,
        true
    );
    wp_localize_script( 'wcev-frontend', 'wcevData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'wcev_popup' ),
    ] );

    // Datos para checkout de bloques (Gutenberg)
    if ( class_exists( 'WCEV_Blocks_Integration' ) ) {
        wp_enqueue_script(
            'wcev-blocks-frontend',
            WCEV_URL . 'assets/js/blocks-frontend.js',
            [],
            WCEV_VERSION,
            true
        );
        wp_localize_script( 'wcev-blocks-frontend', 'wcevShippingData', [
            'rates' => WCEV_Blocks_Integration::collect_rate_data(),
        ] );
    }
}

function wcev_admin_assets( $hook ) {
    $shipping_pages = [ 'woocommerce_page_wc-settings' ];
    if ( ! in_array( $hook, $shipping_pages, true ) ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style(
        'wcev-admin',
        WCEV_URL . 'admin/assets/admin.css',
        [],
        WCEV_VERSION
    );
    wp_enqueue_script(
        'wcev-admin',
        WCEV_URL . 'admin/assets/admin.js',
        [ 'jquery', 'jquery-ui-datepicker' ],
        WCEV_VERSION,
        true
    );
}

function wcev_ajax_dismiss_popup() {
    check_ajax_referer( 'wcev_popup', 'nonce' );
    setcookie( 'wcev_popup_dismissed', '1', time() + DAY_IN_SECONDS, '/' );
    wp_send_json_success();
}

/* ─── Activación / desactivación ─────────────────────────────────────────── */
register_activation_hook( __FILE__, function () {
    add_option( 'wcev_popup_settings', [
        'enabled'       => 'no',
        'message'       => '',
        'date_from'     => '',
        'date_to'       => '',
        'bg_color'      => '#1a1a2e',
        'text_color'    => '#ffffff',
        'accent_color'  => '#e94560',
    ] );
} );
