<?php
/**
 * Bank Transfer (Venezuela) payment method.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_Bank extends KR_DP_Gateway_Base {

	public function __construct() {
		$this->id                 = 'kr_dp_bank';
		$this->method_title       = __( 'Transferencia Bancaria', 'kr-direct-payments' );
		$this->method_description = __( 'Transferencia bancaria con formulario de verificacion y comprobante.', 'kr-direct-payments' );
		$this->kr_default_accent  = '#1d4ed8';
		$this->kr_default_fee     = '5';
		$this->kr_default_show_bs = 'yes';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = $this->common_form_fields();
	}

	protected function detail_form_fields() {
		return array(
			'bank_name'    => array(
				'title'   => __( 'Nombre del banco', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'account_num'  => array(
				'title'   => __( 'Numero de cuenta', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'rif'          => array(
				'title'   => __( 'RIF', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'beneficiary'  => array(
				'title'   => __( 'Beneficiario', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	public function get_payment_rows( $order ) {
		return array(
			array( 'label' => __( 'Nombre del Banco', 'kr-direct-payments' ), 'value' => $this->get_option( 'bank_name', '' ), 'copy' => true ),
			array( 'label' => __( 'Numero de Cuenta', 'kr-direct-payments' ), 'value' => $this->get_option( 'account_num', '' ), 'copy' => true ),
			array( 'label' => __( 'RIF', 'kr-direct-payments' ), 'value' => $this->get_option( 'rif', '' ), 'copy' => true ),
			array( 'label' => __( 'Beneficiario', 'kr-direct-payments' ), 'value' => $this->get_option( 'beneficiary', '' ), 'copy' => true ),
			array( 'label' => __( 'Monto', 'kr-direct-payments' ), 'value' => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ), 'copy' => true ),
		);
	}

	public function get_verification_fields() {
		return array(
			'transfer_date' => array(
				'label'    => __( 'Fecha de transferencia', 'kr-direct-payments' ),
				'type'     => 'date',
				'required' => true,
			),
			'reference'     => array(
				'label'       => __( 'Numero de referencia', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ingresa el numero de referencia', 'kr-direct-payments' ),
			),
			'sender_id'     => array(
				'label'       => __( 'Cedula de identidad emisor', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ingresa tu cedula', 'kr-direct-payments' ),
			),
			'phone_code'    => array(
				'label'    => __( 'Codigo de telefono', 'kr-direct-payments' ),
				'type'     => 'select',
				'required' => true,
				'options'  => kr_dp_venezuela_phone_codes(),
			),
			'phone_number'  => array(
				'label'       => __( 'Numero de telefono', 'kr-direct-payments' ),
				'type'        => 'tel',
				'required'    => true,
				'placeholder' => __( 'Ingresa el numero de telefono', 'kr-direct-payments' ),
			),
			'sender_bank'   => array(
				'label'    => __( 'Banco emisor', 'kr-direct-payments' ),
				'type'     => 'select',
				'required' => true,
				'options'  => kr_dp_venezuela_banks(),
			),
			'receiver_bank' => array(
				'label'    => __( 'Banco receptor', 'kr-direct-payments' ),
				'type'     => 'select',
				'required' => true,
				'options'  => kr_dp_venezuela_banks(),
			),
			'account_num'   => array(
				'label'       => __( 'Numero de cuenta bancaria', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ingresa el numero de cuenta', 'kr-direct-payments' ),
			),
			'amount'        => array(
				'label'       => __( 'Monto transferido', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ingresa el monto transferido', 'kr-direct-payments' ),
			),
		);
	}
}
