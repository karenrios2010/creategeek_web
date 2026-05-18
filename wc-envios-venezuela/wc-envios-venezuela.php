<?php
/**
 * Plugin Name: WC Envíos Venezuela
 * Plugin URI:  https://creategeek.agency
 * Description: Métodos de envío para Venezuela (MRW, Zoom, Tealca, Personalizado) con cobro a destino y popup de promociones.
 * Version:     1.1.0
 * Author:      CreateGeek Agency
 * Text Domain: wc-envios-venezuela
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.9
 */

defined( 'ABSPATH' ) || exit;

define( 'WCEV_VERSION', '1.1.0' );
define( 'WCEV_FILE',    __FILE__ );
define( 'WCEV_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WCEV_URL',     plugin_dir_url( __FILE__ ) );

/* ── Compatibilidad HPOS ──────────────────────────────────────────────────── */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/* ── Arranque ─────────────────────────────────────────────────────────────── */
add_action( 'plugins_loaded', 'wcev_init', 20 );

function wcev_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'WC Envíos Venezuela requiere WooCommerce activo.', 'wc-envios-venezuela' ) . '</p></div>';
        } );
        return;
    }

    require_once WCEV_DIR . 'includes/class-wcev-shipping-base.php';
    require_once WCEV_DIR . 'includes/class-wcev-mrw.php';
    require_once WCEV_DIR . 'includes/class-wcev-zoom.php';
    require_once WCEV_DIR . 'includes/class-wcev-tealca.php';
    require_once WCEV_DIR . 'includes/class-wcev-custom.php';
    require_once WCEV_DIR . 'includes/class-wcev-popup.php';
    require_once WCEV_DIR . 'includes/class-wcev-rate-data.php';

    add_filter( 'woocommerce_shipping_methods', 'wcev_register_methods' );
    add_action( 'wp_enqueue_scripts',    'wcev_frontend_assets' );
    add_action( 'admin_enqueue_scripts', 'wcev_admin_assets' );
    add_action( 'wp_ajax_wcev_dismiss_popup',        'wcev_ajax_dismiss_popup' );
    add_action( 'wp_ajax_nopriv_wcev_dismiss_popup', 'wcev_ajax_dismiss_popup' );

    WCEV_Popup::init();
}

function wcev_register_methods( $methods ) {
    $methods['wcev_mrw']    = 'WCEV_MRW';
    $methods['wcev_zoom']   = 'WCEV_Zoom';
    $methods['wcev_tealca'] = 'WCEV_Tealca';
    $methods['wcev_custom'] = 'WCEV_Custom';
    return $methods;
}

/* ── Assets frontend ──────────────────────────────────────────────────────── */
function wcev_frontend_assets() {
    wp_enqueue_style( 'wcev-frontend', WCEV_URL . 'assets/css/frontend.css', [], WCEV_VERSION );

    wp_enqueue_script( 'wcev-frontend', WCEV_URL . 'assets/js/frontend.js', [ 'jquery' ], WCEV_VERSION, true );
    wp_localize_script( 'wcev-frontend', 'wcevData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'wcev_popup' ),
    ] );

    // Script para checkout de bloques (Gutenberg)
    wp_enqueue_script( 'wcev-blocks', WCEV_URL . 'assets/js/blocks.js', [], WCEV_VERSION, true );
    wp_localize_script( 'wcev-blocks', 'wcevBlocks', [
        'rates' => wcev_collect_rate_data(),
    ] );
}

/* ── Assets admin ─────────────────────────────────────────────────────────── */
function wcev_admin_assets( $hook ) {
    if ( 'woocommerce_page_wc-settings' !== $hook ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style( 'wcev-admin', WCEV_URL . 'admin/assets/admin.css', [], WCEV_VERSION );
    wp_enqueue_script( 'wcev-admin', WCEV_URL . 'admin/assets/admin.js', [ 'jquery', 'jquery-ui-datepicker' ], WCEV_VERSION, true );
}

/* ── AJAX: cerrar popup ───────────────────────────────────────────────────── */
function wcev_ajax_dismiss_popup() {
    check_ajax_referer( 'wcev_popup', 'nonce' );
    setcookie( 'wcev_popup_dismissed', '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
    wp_send_json_success();
}

/* ── Activación ───────────────────────────────────────────────────────────── */
register_activation_hook( __FILE__, function () {
    // Opciones predeterminadas del popup
    $defaults = [
        'wcev_popup_enabled'      => 'no',
        'wcev_popup_message'      => '',
        'wcev_popup_subtitle'     => '',
        'wcev_popup_date_from'    => '',
        'wcev_popup_date_to'      => '',
        'wcev_popup_bg_color'     => '#1a1a2e',
        'wcev_popup_text_color'   => '#ffffff',
        'wcev_popup_accent_color' => '#e94560',
        'wcev_popup_position'     => 'center',
    ];
    foreach ( $defaults as $key => $val ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $val );
        }
    }
} );
