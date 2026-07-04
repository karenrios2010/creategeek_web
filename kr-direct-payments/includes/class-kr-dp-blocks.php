<?php
/**
 * WooCommerce Cart/Checkout Blocks integration for KR Direct Payments.
 *
 * One instance is registered per gateway id.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class KR_DP_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Constructor.
	 *
	 * @param string $gateway_id Gateway id.
	 */
	public function __construct( $gateway_id ) {
		$this->name = $gateway_id;
	}

	/**
	 * Load settings.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}

	/**
	 * Whether the gateway is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Register the block script.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$handle = 'kr-dp-blocks';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script(
				$handle,
				KR_DP_PLUGIN_URL . 'assets/js/blocks.js',
				array( 'wc-blocks-registry', 'wc-blocks-checkout', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n', 'wp-data' ),
				KR_DP_VERSION,
				true
			);
		}
		return array( $handle );
	}

	/**
	 * Data passed to the block script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		$gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;

		$title       = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->name;
		$description = isset( $this->settings['description'] ) ? $this->settings['description'] : '';
		$icon        = $gateway && method_exists( $gateway, 'get_brand_icon_url' ) ? $gateway->get_brand_icon_url() : '';

		// Recargo fijo por metodo.
		$fee = 0.0;
		if ( $gateway && 'yes' === $gateway->get_option( 'enable_fee', 'no' ) ) {
			$fee = (float) $gateway->get_option( 'fee_amount', 0 );
		}
		$fee_text = '';
		if ( $fee > 0 ) {
			$fee_text = sprintf(
				/* translators: %s: formatted fee amount. */
				__( 'Este metodo aplica un recargo fijo de %s.', 'kr-direct-payments' ),
				html_entity_decode( wp_strip_all_tags( wc_price( $fee ) ) )
			);
		}

		// Conversion a Bs. segun la tasa BCV.
		$show_bs   = $gateway && 'yes' === $gateway->get_option( 'show_bs', 'no' );
		$source    = $gateway ? $gateway->get_option( 'bcv_source', 'euro' ) : 'euro';
		$rate      = ( $show_bs && method_exists( $gateway, 'get_bcv_rate' ) ) ? $gateway->get_bcv_rate() : 0.0;
		$src_label = ( 'dolar' === $source ) ? 'USD' : 'EUR';

		return array(
			'name'        => $this->name,
			'title'       => $title,
			'description' => wpautop( wp_kses_post( $description ) ),
			'icon'        => $icon,
			'supports'    => array( 'products' ),
			'feeText'     => $fee_text,
			'showBs'      => ( $show_bs && $rate > 0 ),
			'rate'        => $rate,
			'bsLabel'     => __( 'Total a pagar en Bs.:', 'kr-direct-payments' ),
			'rateText'    => $rate > 0
				? sprintf(
					/* translators: 1: EUR/USD, 2: formatted BCV rate. */
					__( 'Tasa BCV (%1$s) del dia: %2$s', 'kr-direct-payments' ),
					$src_label,
					kr_dp_format_bs( $rate )
				)
				: '',
		);
	}
}
