<?php
/**
 * Pago Movil / InstaPago payment method.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_PagoMovil extends KR_DP_Gateway_Base {

	public function __construct() {
		$this->id                 = 'kr_dp_pagomovil';
		$this->method_title       = __( 'Pago Movil', 'kr-direct-payments' );
		$this->method_description = __( 'Pago Movil / InstaPago con QR, verificacion y comprobante.', 'kr-direct-payments' );
		$this->kr_default_accent  = '#0ea5e9';
		$this->kr_default_fee     = '5';
		$this->kr_default_show_bs = 'yes';
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = $this->common_form_fields();
	}

	protected function detail_form_fields() {
		return array(
			'bank_code'  => array(
				'title'   => __( 'Codigo del banco', 'kr-direct-payments' ),
				'type'    => 'select',
				'options' => kr_dp_venezuela_banks(),
				'default' => '0134',
			),
			'doc_id'     => array(
				'title'   => __( 'Cedula / RIF', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'phone'      => array(
				'title'   => __( 'Telefono', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),
			'qr_url'     => array(
				'title'       => __( 'Imagen QR (URL)', 'kr-direct-payments' ),
				'type'        => 'kr_logo',
				'description' => __( 'Opcional. URL de la imagen del codigo QR.', 'kr-direct-payments' ),
			),
		);
	}

	public function get_qr_url() {
		return esc_url( $this->get_option( 'qr_url', '' ) );
	}

	public function get_payment_rows( $order ) {
		$banks     = kr_dp_venezuela_banks();
		$bank_code = $this->get_option( 'bank_code', '' );
		$bank_lbl  = isset( $banks[ $bank_code ] ) ? $banks[ $bank_code ] : $bank_code;

		return array(
			array( 'label' => __( 'Codigo del Banco', 'kr-direct-payments' ), 'value' => $bank_lbl, 'copy' => true ),
			array( 'label' => __( 'Cedula-RIF', 'kr-direct-payments' ), 'value' => $this->get_option( 'doc_id', '' ), 'copy' => true ),
			array( 'label' => __( 'Telefono', 'kr-direct-payments' ), 'value' => $this->get_option( 'phone', '' ), 'copy' => true ),
			array( 'label' => __( 'Monto', 'kr-direct-payments' ), 'value' => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ), 'copy' => true ),
		);
	}

	public function get_verification_fields() {
		return array(
			'client_phone' => array(
				'label'       => __( 'Telefono del Cliente', 'kr-direct-payments' ),
				'type'        => 'tel',
				'required'    => true,
				'placeholder' => __( 'Ej: 04121234567', 'kr-direct-payments' ),
			),
			'doc_type'     => array(
				'label'    => __( 'Tipo de documento', 'kr-direct-payments' ),
				'type'     => 'select',
				'required' => true,
				'options'  => kr_dp_document_types(),
			),
			'client_doc'   => array(
				'label'       => __( 'Cedula o RIF del Cliente', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => __( 'Ej: 12345678', 'kr-direct-payments' ),
			),
			'client_bank'  => array(
				'label'    => __( 'Codigo del Banco del Cliente', 'kr-direct-payments' ),
				'type'     => 'select',
				'required' => true,
				'options'  => kr_dp_venezuela_banks(),
			),
			'reference'    => array(
				'label'       => __( 'Numero de la Referencia Bancaria', 'kr-direct-payments' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => '',
			),
		);
	}
}
