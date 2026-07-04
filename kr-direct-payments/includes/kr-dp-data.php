<?php
/**
 * Shared data and sanitizers for KR Direct Payments.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a CSS color (hex, rgb, rgba, hsl or named). Strips anything that
 * could break out of a CSS declaration to prevent injection.
 *
 * @param string $value    Raw value.
 * @param string $fallback Fallback when empty/invalid.
 * @return string
 */
function kr_dp_sanitize_color( $value, $fallback = '' ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		return $fallback;
	}
	// Allow only characters valid inside a CSS color value.
	$clean = preg_replace( '/[^a-zA-Z0-9#%.,()\s\/-]/', '', $value );
	$clean = trim( (string) $clean );
	return '' === $clean ? $fallback : $clean;
}

/**
 * Clamp an integer between min and max.
 *
 * @param mixed $value Value.
 * @param int   $min   Min.
 * @param int   $max   Max.
 * @param int   $default Default.
 * @return int
 */
function kr_dp_clamp_int( $value, $min, $max, $default ) {
	if ( '' === $value || null === $value ) {
		$value = $default;
	}
	$value = (int) $value;
	return max( $min, min( $max, $value ) );
}

/**
 * Venezuelan banks list (code => name). Codes are the official 4-digit
 * banking codes used in transfers and Pago Movil.
 *
 * @return array<string,string>
 */
function kr_dp_venezuela_banks() {
	return array(
		'0102' => '0102 - Banco de Venezuela',
		'0104' => '0104 - Banco Venezolano de Credito',
		'0105' => '0105 - Banco Mercantil',
		'0108' => '0108 - Banco Provincial (BBVA)',
		'0114' => '0114 - Bancaribe',
		'0115' => '0115 - Banco Exterior',
		'0128' => '0128 - Banco Caroni',
		'0134' => '0134 - Banesco',
		'0137' => '0137 - Banco Sofitasa',
		'0138' => '0138 - Banco Plaza',
		'0146' => '0146 - Bangente',
		'0151' => '0151 - BFC Banco Fondo Comun',
		'0156' => '0156 - 100% Banco',
		'0157' => '0157 - DelSur Banco Universal',
		'0163' => '0163 - Banco del Tesoro',
		'0166' => '0166 - Banco Agricola de Venezuela',
		'0168' => '0168 - Bancrecer',
		'0169' => '0169 - Mi Banco',
		'0171' => '0171 - Banco Activo',
		'0172' => '0172 - Bancamiga',
		'0174' => '0174 - Banplus',
		'0175' => '0175 - Banco Bicentenario',
		'0177' => '0177 - Banco de la FANB (BANFANB)',
		'0178' => '0178 - N58 Banco Digital',
		'0191' => '0191 - Banco Nacional de Credito (BNC)',
	);
}

/**
 * Phone operator codes used for Pago Movil in Venezuela.
 *
 * @return array<string,string>
 */
function kr_dp_venezuela_phone_codes() {
	return array(
		'0412' => '0412',
		'0414' => '0414',
		'0416' => '0416',
		'0424' => '0424',
		'0426' => '0426',
	);
}

/**
 * Document-type prefixes used in Venezuela (V, E, J, G, P).
 *
 * @return array<string,string>
 */
function kr_dp_document_types() {
	return array(
		'V' => 'V',
		'E' => 'E',
		'J' => 'J',
		'G' => 'G',
		'P' => 'P',
	);
}
