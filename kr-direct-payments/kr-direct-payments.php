<?php
/**
 * Plugin Name: KR Direct Payments
 * Description: Metodos de pago manuales para WooCommerce (Zelle, Transferencia Bancaria, Pago Movil y Binance) con apariencia configurable, formulario de verificacion, carga de comprobante, recargo fijo por metodo y total en Bs. segun la tasa BCV. Compatible con HPOS y Checkout Blocks.
 * Version: 2.7.1
 * Author: Karen Rios
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * WC requires at least: 7.0
 * WC tested up to: 10.2
 * Requires Plugins: woocommerce
 * Text Domain: kr-direct-payments
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KR_DP_VERSION', '2.7.1' );
define( 'KR_DP_PLUGIN_FILE', __FILE__ );
define( 'KR_DP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KR_DP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Gateway ids handled by this plugin.
 *
 * Unified plugin: Zelle, Bank Transfer, Pago Movil and Binance.
 *
 * @return array
 */
function kr_dp_gateway_ids() {
	return array( 'kr_zelle', 'kr_dp_bank', 'kr_dp_pagomovil', 'kr_dp_binance' );
}

/* ------------------------------------------------------------------ *
 * HPOS + Blocks compatibility
 * ------------------------------------------------------------------ */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/* ------------------------------------------------------------------ *
 * Load classes once WooCommerce is ready
 * ------------------------------------------------------------------ */
add_action( 'plugins_loaded', 'kr_dp_init', 11 );
function kr_dp_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'KR Direct Payments requiere WooCommerce activo.', 'kr-direct-payments' ) . '</p></div>';
			}
		);
		return;
	}

	require_once KR_DP_PLUGIN_DIR . 'includes/kr-dp-data.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-bcv.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-gateway-base.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-zelle.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-bank.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-pagomovil.php';
	require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-binance.php';
}

/* ------------------------------------------------------------------ *
 * Register the gateways
 * ------------------------------------------------------------------ */
add_filter(
	'woocommerce_payment_gateways',
	function ( $gateways ) {
		$gateways[] = 'KR_DP_Zelle';
		$gateways[] = 'KR_DP_Bank';
		$gateways[] = 'KR_DP_PagoMovil';
		$gateways[] = 'KR_DP_Binance';
		return $gateways;
	}
);

/* ------------------------------------------------------------------ *
 * Register Blocks integrations
 * ------------------------------------------------------------------ */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once KR_DP_PLUGIN_DIR . 'includes/class-kr-dp-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( $registry ) {
					foreach ( kr_dp_gateway_ids() as $gid ) {
						$registry->register( new KR_DP_Blocks_Support( $gid ) );
					}
				}
			);
		}

		// Sincroniza el metodo de pago elegido en el Checkout Blocks con la
		// sesion para que el recargo fijo se aplique/quite al instante.
		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => 'kr-direct-payments',
					'callback'  => function ( $data ) {
						if ( empty( $data['payment_method'] ) || ! WC()->session ) {
							return;
						}
						$method    = wc_clean( wp_unslash( $data['payment_method'] ) );
						$available = WC()->payment_gateways() ? array_keys( WC()->payment_gateways()->get_available_payment_gateways() ) : array();
						if ( is_string( $method ) && in_array( $method, $available, true ) ) {
							WC()->session->set( 'chosen_payment_method', $method );
						}
					},
				)
			);
		}
	}
);

/* ------------------------------------------------------------------ *
 * BCV rate: background refresh via WP-Cron so the checkout never waits
 * for bcv.org.ve. Interval follows the "bcv_cache_minutes" setting.
 * ------------------------------------------------------------------ */
add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['kr_dp_bcv'] = array(
			'interval' => class_exists( 'KR_DP_BCV' ) ? KR_DP_BCV::cache_seconds() : 30 * MINUTE_IN_SECONDS,
			'display'  => 'KR Direct Payments - tasa BCV',
		);
		return $schedules;
	}
);

add_action( 'kr_dp_bcv_refresh', 'kr_dp_bcv_do_refresh' );
add_action( 'kr_dp_bcv_refresh_single', 'kr_dp_bcv_do_refresh' );
function kr_dp_bcv_do_refresh() {
	if ( class_exists( 'KR_DP_BCV' ) ) {
		KR_DP_BCV::refresh();
	}
}

add_action(
	'init',
	function () {
		if ( class_exists( 'KR_DP_BCV' ) && ! wp_next_scheduled( 'kr_dp_bcv_refresh' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'kr_dp_bcv', 'kr_dp_bcv_refresh' );
		}
	}
);

/**
 * Re-arm the recurring refresh (used when the interval setting changes).
 */
function kr_dp_bcv_reschedule() {
	wp_clear_scheduled_hook( 'kr_dp_bcv_refresh' );
	wp_schedule_event( time() + MINUTE_IN_SECONDS, 'kr_dp_bcv', 'kr_dp_bcv_refresh' );
}

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'kr_dp_bcv_refresh' );
		wp_clear_scheduled_hook( 'kr_dp_bcv_refresh_single' );
	}
);

/* ------------------------------------------------------------------ *
 * Classic checkout: refresh totals when the payment method changes
 * and show the Bs. (BCV) row under the order total.
 * ------------------------------------------------------------------ */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		wp_enqueue_script( 'kr-dp-checkout', KR_DP_PLUGIN_URL . 'assets/js/checkout.js', array( 'jquery' ), KR_DP_VERSION, true );
	}
);

/* ------------------------------------------------------------------ *
 * Recargo/descuento por metodo de pago. Registrado globalmente (no en
 * el constructor de cada pasarela) porque WooCommerce puede construir
 * las pasarelas despues de calcular los totales; aqui las cargamos
 * explicitamente antes de aplicar el ajuste.
 * ------------------------------------------------------------------ */
add_action( 'woocommerce_cart_calculate_fees', 'kr_dp_apply_gateway_adjustments', 20 );
function kr_dp_apply_gateway_adjustments( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( ! WC()->session || ! WC()->payment_gateways() ) {
		return;
	}
	$chosen = WC()->session->get( 'chosen_payment_method' );
	if ( ! in_array( $chosen, kr_dp_gateway_ids(), true ) ) {
		return;
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = isset( $gateways[ $chosen ] ) ? $gateways[ $chosen ] : null;
	if ( ! $gateway ) {
		return;
	}
	if ( method_exists( $gateway, 'maybe_apply_discount' ) ) {
		$gateway->maybe_apply_discount( $cart );
	}
	if ( method_exists( $gateway, 'maybe_apply_fee' ) ) {
		$gateway->maybe_apply_fee( $cart );
	}
}

add_action( 'woocommerce_review_order_after_order_total', 'kr_dp_checkout_bs_row' );
function kr_dp_checkout_bs_row() {
	if ( ! WC()->session || ! WC()->cart || ! WC()->payment_gateways() ) {
		return;
	}
	$chosen = WC()->session->get( 'chosen_payment_method' );
	if ( ! in_array( $chosen, kr_dp_gateway_ids(), true ) ) {
		return;
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = isset( $gateways[ $chosen ] ) ? $gateways[ $chosen ] : null;
	if ( ! $gateway ) {
		return;
	}

	// Detalle del recargo: fila propia garantizada aunque la plantilla del
	// tema no dibuje las lineas de fees estandar de WooCommerce.
	if ( 'yes' === $gateway->get_option( 'enable_fee', 'no' ) ) {
		$fee_amount = (float) $gateway->get_option( 'fee_amount', 0 );
		if ( $fee_amount > 0 ) {
			$fee_label = trim( (string) $gateway->get_option( 'fee_label', '' ) );
			if ( '' === $fee_label ) {
				$fee_label = sprintf( __( 'Comision %s', 'kr-direct-payments' ), $gateway->get_option( 'title', $gateway->get_method_title() ) );
			}
			echo '<tr class="kr-dp-fee-detail">';
			echo '<th>' . esc_html( $fee_label ) . '</th>';
			echo '<td data-title="' . esc_attr( $fee_label ) . '">+' . wp_kses_post( wc_price( $fee_amount ) ) . ' <small>' . esc_html__( '(incluida en el total)', 'kr-direct-payments' ) . '</small></td>';
			echo '</tr>';
		}
	}

	if ( 'yes' !== $gateway->get_option( 'show_bs', 'no' ) ) {
		return;
	}
	$source = $gateway->get_option( 'bcv_source', 'euro' );
	$rate   = method_exists( $gateway, 'get_bcv_rate' ) ? $gateway->get_bcv_rate() : KR_DP_BCV::get_rate( $source );
	if ( $rate <= 0 ) {
		return;
	}
	$total_bs  = (float) WC()->cart->get_total( 'edit' ) * $rate;
	$src_label = ( 'dolar' === $source ) ? 'USD' : 'EUR';
	echo '<tr class="kr-dp-bs-total">';
	echo '<th>' . esc_html__( 'Total a pagar en Bs.', 'kr-direct-payments' ) . '</th>';
	echo '<td data-title="' . esc_attr__( 'Total en Bs.', 'kr-direct-payments' ) . '"><strong>' . esc_html( kr_dp_format_bs( $total_bs ) ) . '</strong><br /><small>' . esc_html( sprintf( __( 'Tasa BCV (%1$s) del dia: %2$s', 'kr-direct-payments' ), $src_label, kr_dp_format_bs( $rate ) ) ) . '</small></td>';
	echo '</tr>';
}

/* ------------------------------------------------------------------ *
 * Front-end assets (order-received page only)
 * ------------------------------------------------------------------ */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
			return;
		}
		wp_enqueue_style( 'kr-dp-frontend', KR_DP_PLUGIN_URL . 'assets/css/frontend.css', array(), KR_DP_VERSION );
		wp_enqueue_script( 'kr-dp-frontend', KR_DP_PLUGIN_URL . 'assets/js/frontend.js', array(), KR_DP_VERSION, true );
		wp_localize_script(
			'kr-dp-frontend',
			'KR_DP',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}
);

/* ------------------------------------------------------------------ *
 * Admin media picker for the logo/QR fields
 * ------------------------------------------------------------------ */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'kr-dp-admin', KR_DP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), KR_DP_VERSION, true );
	}
);

/* ------------------------------------------------------------------ *
 * AJAX: buyer submits verification data + proof upload
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_kr_dp_submit', 'kr_dp_handle_submit' );
add_action( 'wp_ajax_nopriv_kr_dp_submit', 'kr_dp_handle_submit' );
function kr_dp_handle_submit() {
	check_ajax_referer( 'kr_dp_submit', 'nonce' );

	$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
	$order     = $order_id ? wc_get_order( $order_id ) : false;

	if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
		wp_send_json_error( array( 'message' => __( 'Pedido no valido.', 'kr-direct-payments' ) ), 400 );
	}

	$method   = $order->get_payment_method();
	$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
	$gateway  = isset( $gateways[ $method ] ) ? $gateways[ $method ] : null;

	if ( ! $gateway || ! method_exists( $gateway, 'get_verification_fields' ) ) {
		wp_send_json_error( array( 'message' => __( 'Metodo no valido.', 'kr-direct-payments' ) ), 400 );
	}

	$fields      = (array) $gateway->get_verification_fields();
	$labels      = array();
	$saved       = array();
	$missing     = false;

	foreach ( $fields as $fkey => $f ) {
		$post_key = 'kr_dp_f_' . $fkey;
		$value    = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
		if ( ! empty( $f['required'] ) && '' === $value ) {
			$missing = true;
		}
		$label              = isset( $f['label'] ) ? $f['label'] : $fkey;
		$labels[ $fkey ]    = $label;
		$saved[ $fkey ]     = $value;
	}

	if ( $missing ) {
		wp_send_json_error( array( 'message' => __( 'Completa los campos obligatorios.', 'kr-direct-payments' ) ), 422 );
	}

	// Proof upload (optional / required).
	$require_proof = method_exists( $gateway, 'get_option' ) && 'yes' === $gateway->get_option( 'require_proof', 'no' );
	$proof_id      = 0;

	if ( ! empty( $_FILES['kr_dp_proof'] ) && ! empty( $_FILES['kr_dp_proof']['name'] ) ) {
		$proof_id = kr_dp_store_proof( $_FILES['kr_dp_proof'], $order_id );
		if ( is_wp_error( $proof_id ) ) {
			wp_send_json_error( array( 'message' => $proof_id->get_error_message() ), 422 );
		}
	} elseif ( $require_proof ) {
		wp_send_json_error( array( 'message' => __( 'Debes adjuntar el comprobante.', 'kr-direct-payments' ) ), 422 );
	}

	// Persist.
	foreach ( $saved as $fkey => $value ) {
		$order->update_meta_data( '_kr_dp_v_' . $fkey, $value );
	}
	$order->update_meta_data( '_kr_dp_v_labels', $labels );
	$order->update_meta_data( '_kr_dp_verified', 'yes' );
	if ( $proof_id ) {
		$order->update_meta_data( '_kr_dp_proof_id', $proof_id );
	}

	// Order note summary.
	$note_lines = array();
	foreach ( $saved as $fkey => $value ) {
		if ( '' !== $value ) {
			$note_lines[] = $labels[ $fkey ] . ': ' . $value;
		}
	}
	if ( $proof_id ) {
		$note_lines[] = __( 'Comprobante adjuntado.', 'kr-direct-payments' );
	}
	$order->add_order_note( __( 'Datos de pago enviados por el cliente:', 'kr-direct-payments' ) . "\n" . implode( "\n", $note_lines ) );
	$order->save();

	wp_send_json_success( array( 'message' => __( 'Recibimos los datos de tu pago. Lo verificaremos en breve.', 'kr-direct-payments' ) ) );
}

/**
 * Validate and store an uploaded proof, returning attachment ID or WP_Error.
 *
 * @param array $file     $_FILES entry.
 * @param int   $order_id Order ID.
 * @return int|WP_Error
 */
function kr_dp_store_proof( $file, $order_id ) {
	$max_bytes = 5 * 1024 * 1024;
	if ( isset( $file['size'] ) && $file['size'] > $max_bytes ) {
		return new WP_Error( 'too_big', __( 'El comprobante supera los 5 MB.', 'kr-direct-payments' ) );
	}

	$allowed = array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'pdf'      => 'application/pdf',
	);
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) || ! in_array( $check['type'], $allowed, true ) ) {
		return new WP_Error( 'bad_type', __( 'Formato no permitido. Usa JPG, PNG, WEBP o PDF.', 'kr-direct-payments' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	add_filter( 'upload_mimes', 'kr_dp_upload_mimes' );
	$overrides = array(
		'test_form' => false,
		'mimes'     => $allowed,
	);
	$moved = wp_handle_upload( $file, $overrides );
	remove_filter( 'upload_mimes', 'kr_dp_upload_mimes' );

	if ( isset( $moved['error'] ) || empty( $moved['file'] ) ) {
		return new WP_Error( 'upload_failed', isset( $moved['error'] ) ? $moved['error'] : __( 'No se pudo subir el archivo.', 'kr-direct-payments' ) );
	}

	$attachment = array(
		'post_mime_type' => $moved['type'],
		'post_title'     => sprintf( __( 'Comprobante pedido #%d', 'kr-direct-payments' ), $order_id ),
		'post_content'   => '',
		'post_status'    => 'private',
	);
	$attach_id = wp_insert_attachment( $attachment, $moved['file'] );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return new WP_Error( 'attach_failed', __( 'No se pudo guardar el comprobante.', 'kr-direct-payments' ) );
	}
	$meta = wp_generate_attachment_metadata( $attach_id, $moved['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );

	return (int) $attach_id;
}

/**
 * Restrict allowed mimes during proof upload.
 *
 * @param array $mimes Mimes.
 * @return array
 */
function kr_dp_upload_mimes( $mimes ) {
	return array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'pdf'      => 'application/pdf',
	);
}

/* ------------------------------------------------------------------ *
 * Admin: show submitted verification data + proof on the order screen
 * ------------------------------------------------------------------ */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'kr_dp_admin_order_data', 10, 1 );
function kr_dp_admin_order_data( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return;
	}
	if ( ! in_array( $order->get_payment_method(), kr_dp_gateway_ids(), true ) ) {
		return;
	}

	$labels   = $order->get_meta( '_kr_dp_v_labels' );
	$proof_id = (int) $order->get_meta( '_kr_dp_proof_id' );
	$verified = $order->get_meta( '_kr_dp_verified' );

	echo '<div class="kr-dp-admin" style="margin-top:12px;padding:12px;border:1px solid #e0e0e0;border-radius:8px;background:#fafafa">';
	echo '<h4 style="margin:0 0 8px">' . esc_html__( 'Verificacion de pago (KR Direct Payments)', 'kr-direct-payments' ) . '</h4>';

	$bcv_rate = (float) $order->get_meta( '_kr_dp_bcv_rate' );
	$total_bs = (float) $order->get_meta( '_kr_dp_total_bs' );
	if ( $bcv_rate > 0 && $total_bs > 0 ) {
		$bcv_src = 'dolar' === $order->get_meta( '_kr_dp_bcv_source' ) ? 'USD' : 'EUR';
		echo '<p style="margin:0 0 8px"><strong>' . esc_html__( 'Total en Bs.:', 'kr-direct-payments' ) . '</strong> ' . esc_html( kr_dp_format_bs( $total_bs ) ) . ' <span style="color:#777">(' . esc_html( sprintf( __( 'tasa BCV %s del dia de la compra', 'kr-direct-payments' ), $bcv_src ) ) . ': ' . esc_html( kr_dp_format_bs( $bcv_rate ) ) . ')</span></p>';
	}

	if ( ! $verified ) {
		echo '<p style="margin:0;color:#777">' . esc_html__( 'El cliente aun no ha enviado los datos del pago.', 'kr-direct-payments' ) . '</p>';
		echo '</div>';
		return;
	}

	if ( is_array( $labels ) && $labels ) {
		echo '<table class="widefat striped" style="margin-bottom:10px"><tbody>';
		foreach ( $labels as $fkey => $label ) {
			$value = $order->get_meta( '_kr_dp_v_' . $fkey );
			if ( '' === $value || null === $value ) {
				continue;
			}
			echo '<tr><th style="text-align:left;width:45%">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	if ( $proof_id ) {
		$url  = wp_get_attachment_url( $proof_id );
		$mime = get_post_mime_type( $proof_id );
		echo '<p style="margin:0 0 6px"><strong>' . esc_html__( 'Comprobante:', 'kr-direct-payments' ) . '</strong></p>';
		if ( $url && 0 === strpos( (string) $mime, 'image/' ) ) {
			$thumb = wp_get_attachment_image( $proof_id, 'medium' );
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . wp_kses_post( $thumb ) . '</a>';
		} elseif ( $url ) {
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="button">' . esc_html__( 'Ver comprobante (PDF)', 'kr-direct-payments' ) . '</a>';
		}
	}

	echo '</div>';
}

/* ------------------------------------------------------------------ *
 * Safety net: if the legacy standalone "KR Zelle Gateway" plugin is still
 * active, it ALSO renders a Zelle form on the order-received page (both use
 * the kr_zelle id). We remove its render callback so only this unified
 * plugin draws the form, and we show an admin notice recommending removal.
 * ------------------------------------------------------------------ */
add_action( 'woocommerce_thankyou_kr_zelle', 'kr_dp_dedupe_legacy_zelle', 1 );
function kr_dp_dedupe_legacy_zelle( $order_id ) {
	if ( ! class_exists( 'KR_WC_Gateway_Zelle' ) ) {
		return;
	}
	global $wp_filter;
	$hook = 'woocommerce_thankyou_kr_zelle';
	if ( empty( $wp_filter[ $hook ] ) ) {
		return;
	}
	foreach ( $wp_filter[ $hook ]->callbacks as $prio => $callbacks ) {
		foreach ( $callbacks as $id => $cb ) {
			$fn = isset( $cb['function'] ) ? $cb['function'] : null;
			if ( is_array( $fn ) && isset( $fn[0] ) && is_object( $fn[0] ) && $fn[0] instanceof KR_WC_Gateway_Zelle ) {
				unset( $wp_filter[ $hook ]->callbacks[ $prio ][ $id ] );
			}
		}
	}
}

add_action(
	'admin_notices',
	function () {
		if ( ! class_exists( 'KR_WC_Gateway_Zelle' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>KR Direct Payments:</strong> '
			. esc_html__( 'Tienes activo tambien el plugin "KR Zelle Gateway", que duplica el formulario de Zelle. Este plugin unificado ya incluye Zelle: por favor desactiva y elimina "KR Zelle Gateway" para evitar el duplicado.', 'kr-direct-payments' )
			. ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Ir a Plugins', 'kr-direct-payments' ) . '</a></p></div>';
	}
);

/* ------------------------------------------------------------------ *
 * Translation layer. Source strings are written without accents to keep
 * the source ASCII-safe; this maps them to properly accented Spanish by
 * default, or to English when the language is set to "en".
 * Controlled by the per-method "Idioma / Language" setting (mirrored into
 * the global "kr_dp_ui_lang" option on save).
 * ------------------------------------------------------------------ */
add_filter( 'gettext', 'kr_dp_translate', 10, 3 );
function kr_dp_translate( $translated, $text, $domain ) {
	if ( 'kr-direct-payments' !== $domain ) {
		return $translated;
	}
	static $lang = null;
	if ( null === $lang ) {
		$lang = get_option( 'kr_dp_ui_lang', 'es' );
	}
	$map = kr_dp_i18n_map( $lang );
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}

/**
 * Build the translation map for a language.
 *
 * @param string $lang Language code.
 * @return array
 */
function kr_dp_i18n_map( $lang ) {
	static $cache = array();
	if ( isset( $cache[ $lang ] ) ) {
		return $cache[ $lang ];
	}

	if ( 'en' === $lang ) {
		$cache[ $lang ] = array(
			'Ya casi, %s!'                                   => 'Almost there, %s!',
			'Ya casi!'                                       => 'Almost there!',
			'Envia tu pago para completar tu pedido'         => 'Send your payment to complete your order',
			'Pedido realizado'                               => 'Order placed',
			'Envia el pago'                                  => 'Send payment',
			'Accion requerida'                               => 'Action required',
			'Envia %1$s via %2$s'                            => 'Send %1$s via %2$s',
			'Incluye en la nota'                             => 'Include in memo',
			'Copiar'                                         => 'Copy',
			'Comprobante / captura del pago'                 => 'Payment proof / screenshot',
			'JPG, PNG, WEBP o PDF. Maximo 5 MB.'             => 'JPG, PNG, WEBP or PDF. Max 5 MB.',
			'Registrar mi pago'                              => 'Submit my payment',
			'Completa estos datos para verificar tu pago'    => 'Complete these details to verify your payment',
			'Ya recibimos los datos de tu pago. Lo estamos verificando.' => 'We received your payment details. We are verifying it.',
			'Que pasa despues?'                              => 'What happens next?',
			'Soporte:'                                       => 'Support:',
			'Seleccione'                                     => 'Select',
			'Realiza el pago con los siguientes datos y luego registra tu comprobante.' => 'Make the payment with the details below, then submit your proof.',
			// Zelle.
			'Titular'                                        => 'Account holder',
			'Email'                                          => 'Email',
			'Telefono'                                       => 'Phone',
			'Numero de confirmacion Zelle'                   => 'Zelle confirmation number',
			'Nombre de quien envia'                          => 'Sender name',
			'Nombre de la persona que envio el pago'         => 'Name of the person who sent the payment',
			// Bank.
			'Nombre del Banco'                               => 'Bank name',
			'Numero de Cuenta'                               => 'Account number',
			'Beneficiario'                                   => 'Beneficiary',
			'Fecha de transferencia'                         => 'Transfer date',
			'Numero de referencia'                           => 'Reference number',
			'Cedula de identidad emisor'                     => 'Sender ID',
			'Codigo de telefono'                             => 'Phone code',
			'Numero de telefono'                             => 'Phone number',
			'Banco emisor'                                   => 'Sender bank',
			'Banco receptor'                                 => 'Receiver bank',
			'Numero de cuenta bancaria'                      => 'Bank account number',
			'Monto transferido'                              => 'Transferred amount',
			// Pago Movil.
			'Codigo del Banco'                               => 'Bank code',
			'Cedula-RIF'                                     => 'ID-RIF',
			'Telefono del Cliente'                           => 'Customer phone',
			'Tipo de documento'                              => 'Document type',
			'Cedula o RIF del Cliente'                       => 'Customer ID or RIF',
			'Codigo del Banco del Cliente'                   => 'Customer bank code',
			'Numero de la Referencia Bancaria'               => 'Bank reference number',
			// Binance.
			'ID de transaccion / Order ID'                   => 'Transaction ID / Order ID',
			'Tu Pay ID o email Binance'                      => 'Your Binance Pay ID or email',
			'Monto enviado'                                  => 'Amount sent',
			'Red / moneda'                                   => 'Network / coin',
			'Email / usuario'                                => 'Email / username',
			'Monto'                                          => 'Amount',
			// Recargo + BCV.
			'Comision %s'                                    => '%s fee',
			'Este metodo aplica un recargo fijo de %s.'      => 'This method adds a fixed %s surcharge.',
			'Total a pagar en Bs.'                           => 'Total to pay in Bs.',
			'Total a pagar en Bs.:'                          => 'Total to pay in Bs.:',
			'Total en Bs.'                                   => 'Total in Bs.',
			'Monto a pagar en Bs.'                           => 'Amount to pay in Bs.',
			'Tasa BCV (%s)'                                  => 'BCV rate (%s)',
			'Tasa BCV (%1$s) del dia: %2$s'                  => 'BCV rate (%1$s) today: %2$s',
			'(incluida en el total)'                         => '(included in the total)',
		);
	} else {
		// Spanish with proper accents / punctuation.
		$cache[ $lang ] = array(
			'Ya casi, %s!'                                   => 'Ya casi, %s!',
			'Ya casi!'                                       => 'Ya casi!',
			'Envia tu pago para completar tu pedido'         => 'Envía tu pago para completar tu pedido',
			'Envia el pago'                                  => 'Envía el pago',
			'Accion requerida'                               => 'Acción requerida',
			'Envia %1$s via %2$s'                            => 'Envía %1$s vía %2$s',
			'Que pasa despues?'                              => '¿Qué pasa después?',
			'Telefono'                                       => 'Teléfono',
			'Numero de confirmacion Zelle'                   => 'Número de confirmación Zelle',
			'Nombre de quien envia'                          => 'Nombre de quién envía',
			'Nombre de la persona que envio el pago'         => 'Nombre de la persona que envió el pago',
			'JPG, PNG, WEBP o PDF. Maximo 5 MB.'             => 'JPG, PNG, WEBP o PDF. Máximo 5 MB.',
			'Numero de Cuenta'                               => 'Número de Cuenta',
			'Numero de referencia'                           => 'Número de referencia',
			'Cedula de identidad emisor'                     => 'Cédula de identidad emisor',
			'Codigo de telefono'                             => 'Código de teléfono',
			'Numero de telefono'                             => 'Número de teléfono',
			'Numero de cuenta bancaria'                      => 'Número de cuenta bancaria',
			'Codigo del Banco'                               => 'Código del Banco',
			'Cedula-RIF'                                     => 'Cédula-RIF',
			'Telefono del Cliente'                           => 'Teléfono del Cliente',
			'Cedula o RIF del Cliente'                       => 'Cédula o RIF del Cliente',
			'Codigo del Banco del Cliente'                   => 'Código del Banco del Cliente',
			'Numero de la Referencia Bancaria'               => 'Número de la Referencia Bancaria',
			'ID de transaccion / Order ID'                   => 'ID de transacción / Order ID',
			'Realiza el pago con los siguientes datos y luego registra tu comprobante.' => 'Realiza el pago con los siguientes datos y luego registra tu comprobante.',
			'Incluir tu numero de pedido en la nota nos ayuda a procesar tu pago mas rapido.' => 'Incluir tu número de pedido en la nota nos ayuda a procesar tu pago más rápido.',
			'Una vez recibido tu pago, procesaremos tu pedido y te enviaremos un correo de confirmacion con la informacion de seguimiento.' => 'Una vez recibido tu pago, procesaremos tu pedido y te enviaremos un correo de confirmación con la información de seguimiento.',
			// Recargo + BCV.
			'Comision %s'                                    => 'Comisión %s',
			'Este metodo aplica un recargo fijo de %s.'      => 'Este método aplica un recargo fijo de %s.',
			'Tasa BCV (%1$s) del dia: %2$s'                  => 'Tasa BCV (%1$s) del día: %2$s',
		);
	}
	return $cache[ $lang ];
}
