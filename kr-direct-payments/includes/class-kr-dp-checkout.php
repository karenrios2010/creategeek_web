<?php
/**
 * Shopify-style checkout experience (no extra plugins).
 *
 * When enabled (option kr_dp_shopify_checkout = yes) the checkout page is
 * taken over with a dedicated full-screen template: store logo on top, the
 * form on the left and a sticky gray order summary on the right — rendered
 * with the NATIVE WooCommerce classic checkout underneath, so every hook of
 * this plugin (surcharge line, Bs total, gateways) keeps working untouched.
 * The theme/Elementor layout of the page is bypassed only on this screen.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_Checkout {

	/**
	 * Wire the hooks. Called on plugins_loaded once WooCommerce is ready.
	 */
	public static function init() {
		if ( 'yes' !== get_option( 'kr_dp_shopify_checkout', 'no' ) ) {
			return;
		}
		if ( ! apply_filters( 'kr_dp_shopify_checkout_enabled', true ) ) {
			return;
		}

		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_action( 'wp', array( __CLASS__, 'relocate_payment' ) );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'item_thumbnail' ), 10, 3 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'field_tweaks' ), 20 );
	}

	/**
	 * The screens we take over: the main checkout form only (never the
	 * order-received / order-pay endpoints).
	 *
	 * @return bool
	 */
	protected static function is_target() {
		return function_exists( 'is_checkout' )
			&& is_checkout()
			&& ! is_wc_endpoint_url( 'order-received' )
			&& ! is_wc_endpoint_url( 'order-pay' );
	}

	/**
	 * True while WooCommerce re-renders checkout fragments over AJAX
	 * (update_order_review), where is_checkout() is false.
	 *
	 * @return bool
	 */
	protected static function is_wc_ajax() {
		return ! empty( $_REQUEST['wc-ajax'] );
	}

	/**
	 * Swap in the full-screen template.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function template( $template ) {
		if ( self::is_target() ) {
			return KR_DP_PLUGIN_DIR . 'templates/checkout-shopify.php';
		}
		return $template;
	}

	/**
	 * Stylesheet for the takeover screen.
	 */
	public static function assets() {
		if ( ! self::is_target() ) {
			return;
		}
		wp_enqueue_style( 'kr-dp-checkout-shopify', KR_DP_PLUGIN_URL . 'assets/css/checkout-shopify.css', array(), KR_DP_VERSION );
	}

	/**
	 * Shopify places the payment section under the customer fields (left
	 * column), not inside the order summary. The AJAX fragment replaces
	 * .woocommerce-checkout-payment by class, so the new position keeps
	 * refreshing normally.
	 */
	public static function relocate_payment() {
		if ( ! self::is_target() ) {
			return;
		}
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 20 );
	}

	/**
	 * Product thumbnail with a quantity badge in the order summary,
	 * like Shopify. Active also during the AJAX fragment refresh.
	 *
	 * @param string $name      Item name HTML.
	 * @param array  $cart_item Cart item.
	 * @param string $key       Cart item key.
	 * @return string
	 */
	public static function item_thumbnail( $name, $cart_item = array(), $key = '' ) {
		if ( ! self::is_target() && ! self::is_wc_ajax() ) {
			return $name;
		}
		if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
			return $name;
		}
		$thumb = $cart_item['data']->get_image( 'woocommerce_thumbnail' );
		$qty   = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;

		return '<span class="krsc-item"><span class="krsc-thumb">' . $thumb . '<span class="krsc-qty">' . esc_html( $qty ) . '</span></span><span class="krsc-item-name">' . $name . '</span></span>';
	}

	/**
	 * Shopify-like fields: labels become placeholders (the label stays in
	 * the DOM for screen readers; CSS hides it visually).
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public static function field_tweaks( $fields ) {
		if ( ! self::is_target() && ! self::is_wc_ajax() ) {
			return $fields;
		}
		foreach ( $fields as $group => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( empty( $field['placeholder'] ) && ! empty( $field['label'] ) ) {
					$fields[ $group ][ $key ]['placeholder'] = wp_strip_all_tags( $field['label'] );
				}
			}
		}
		return $fields;
	}
}
