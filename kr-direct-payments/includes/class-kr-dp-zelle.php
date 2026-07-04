<?php
/**
 * Zelle payment method.
 *
 * Uses id "kr_zelle" so existing settings from the standalone plugin carry over.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_Zelle extends KR_DP_Gateway_Base {

	public function __construct() {
		$this->id                 = 'kr_zelle';
		$this->method_title       = __( 'Zelle', 'kr-direct-payments' );
		$this->method_description = __( 'Pago manual por Zelle con verificacion y comprobante.', 'kr-direct-payments' );
		$this->kr_default_accent  = '#6d1ed4';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = $this->common_form_fields();
	}

	protected function detail_form_fields() {
		return array(
			'account_name'  => array(
				'title'   => __( 'Nombre del titular', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'account_email' => array(
				'title'   => __( 'Email Zelle', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'account_phone' => array(
				'title'   => __( 'Telefono Zelle', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	public function get_payment_rows( $order ) {
		return array(
			array( 'label' => __( 'Titular', 'kr-direct-payments' ), 'value' => $this->get_option( 'account_name', '' ), 'copy' => true ),
			array( 'label' => __( 'Email', 'kr-direct-payments' ), 'value' => $this->get_option( 'account_email', '' ), 'copy' => true ),
			array( 'label' => __( 'Telefono', 'kr-direct-payments' ), 'value' => $this->get_option( 'account_phone', '' ), 'copy' => true ),
			array( 'label' => __( 'Monto', 'kr-direct-payments' ), 'value' => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ), 'copy' => true ),
		);
	}

	public function get_verification_fields() {
		return array(
			'confirmation' => array(
				'label'       => __( 'Numero de confirmacion Zelle', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ej: ABC123XYZ', 'kr-direct-payments' ),
			),
			'sender_name'  => array(
				'label'       => __( 'Nombre de quien envia', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => false,
				'placeholder' => '',
			),
		);
	}
}
