<?php
/**
 * Binance (manual) payment method.
 *
 * Manual flow using Binance Pay ID / email + QR + buyer-submitted reference.
 * No API keys required.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_Binance extends KR_DP_Gateway_Base {

	public function __construct() {
		$this->id                 = 'kr_dp_binance';
		$this->method_title       = __( 'Binance', 'kr-direct-payments' );
		$this->method_description = __( 'Pago manual por Binance Pay (ID / email + QR) con verificacion y comprobante.', 'kr-direct-payments' );
		$this->kr_default_accent  = '#f0b90b';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = $this->common_form_fields();
	}

	protected function detail_form_fields() {
		return array(
			'pay_id'    => array(
				'title'   => __( 'Binance Pay ID', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'pay_email' => array(
				'title'   => __( 'Email / usuario Binance', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'network'   => array(
				'title'       => __( 'Red / moneda (opcional)', 'kr-direct-payments' ),
				'type'        => 'text',
				'default'     => 'USDT',
				'description' => __( 'Ej: USDT (BEP20), BTC, BNB.', 'kr-direct-payments' ),
			),
			'wallet'    => array(
				'title'       => __( 'Direccion de wallet (opcional)', 'kr-direct-payments' ),
				'type'        => 'text',
				'default'     => '',
			),
			'qr_url'    => array(
				'title'       => __( 'Imagen QR (URL)', 'kr-direct-payments' ),
				'type'        => 'kr_logo',
				'description' => __( 'Opcional. URL del QR de Binance Pay o de la wallet.', 'kr-direct-payments' ),
			),
		);
	}

	public function get_qr_url() {
		return esc_url( $this->get_option( 'qr_url', '' ) );
	}

	public function get_payment_rows( $order ) {
		return array(
			array( 'label' => __( 'Binance Pay ID', 'kr-direct-payments' ), 'value' => $this->get_option( 'pay_id', '' ), 'copy' => true ),
			array( 'label' => __( 'Email / usuario', 'kr-direct-payments' ), 'value' => $this->get_option( 'pay_email', '' ), 'copy' => true ),
			array( 'label' => __( 'Red / moneda', 'kr-direct-payments' ), 'value' => $this->get_option( 'network', '' ), 'copy' => true ),
			array( 'label' => __( 'Wallet', 'kr-direct-payments' ), 'value' => $this->get_option( 'wallet', '' ), 'copy' => true ),
			array( 'label' => __( 'Monto', 'kr-direct-payments' ), 'value' => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ), 'copy' => true ),
		);
	}

	public function get_verification_fields() {
		return array(
			'tx_id'      => array(
				'label'       => __( 'ID de transaccion / Order ID', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Pega el Transaction ID de Binance', 'kr-direct-payments' ),
			),
			'sender_user' => array(
				'label'       => __( 'Tu Pay ID o email Binance', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => '',
			),
			'amount'     => array(
				'label'       => __( 'Monto enviado', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => false,
				'placeholder' => '',
			),
		);
	}

	/**
	 * Binance brand-style diamond icon tinted with accent.
	 *
	 * @param int $size Height.
	 * @return string
	 */
	public function get_brand_icon_html( $size = 24 ) {
		$ap     = $this->get_appearance();
		$accent = $ap['accent'];
		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="%1$d" height="%1$d" style="vertical-align:middle"><rect width="48" height="48" rx="11" fill="%2$s"/><g fill="#fff"><path d="M24 12l4 4-4 4-4-4z"/><path d="M16 20l4 4-4 4-4-4z"/><path d="M32 20l4 4-4 4-4-4z"/><path d="M24 28l4 4-4 4-4-4z"/><path d="M21 24l3-3 3 3-3 3z"/></g></svg>',
			(int) $size,
			esc_attr( $accent )
		);
	}
}
