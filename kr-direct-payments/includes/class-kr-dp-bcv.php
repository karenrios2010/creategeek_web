<?php
/**
 * BCV exchange-rate fetcher for KR Direct Payments.
 *
 * Scrapes the official published rates (EUR and USD) from bcv.org.ve,
 * caches them in a transient and keeps the last known value as fallback
 * so the checkout never hits the BCV site on every page load.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KR_DP_BCV {

	const TRANSIENT      = 'kr_dp_bcv_rates';
	const FAIL_TRANSIENT = 'kr_dp_bcv_fetch_failed';
	const LAST_OPTION    = 'kr_dp_bcv_last_rates';
	const SOURCE_URL     = 'https://www.bcv.org.ve/';

	/**
	 * Get the cached rates array: array( 'euro' => float, 'dolar' => float, 'date' => 'Y-m-d H:i:s' ).
	 *
	 * @return array
	 */
	public static function get_rates() {
		$rates = get_transient( self::TRANSIENT );
		if ( is_array( $rates ) && ( ! empty( $rates['euro'] ) || ! empty( $rates['dolar'] ) ) ) {
			return $rates;
		}

		// Cooldown after a failed fetch: fall back to the last known rate.
		if ( get_transient( self::FAIL_TRANSIENT ) ) {
			$last = get_option( self::LAST_OPTION );
			return is_array( $last ) ? $last : array();
		}

		$rates = self::fetch();
		if ( $rates ) {
			set_transient( self::TRANSIENT, $rates, (int) apply_filters( 'kr_dp_bcv_cache_seconds', 3 * HOUR_IN_SECONDS ) );
			update_option( self::LAST_OPTION, $rates, false );
			return $rates;
		}

		set_transient( self::FAIL_TRANSIENT, 1, 15 * MINUTE_IN_SECONDS );
		$last = get_option( self::LAST_OPTION );
		return is_array( $last ) ? $last : array();
	}

	/**
	 * Get a single rate (Bs per unit of the given currency).
	 *
	 * @param string $source 'euro' or 'dolar'.
	 * @return float 0 when unavailable.
	 */
	public static function get_rate( $source = 'euro' ) {
		$source = in_array( $source, array( 'euro', 'dolar' ), true ) ? $source : 'euro';
		$rates  = self::get_rates();
		$rate   = isset( $rates[ $source ] ) ? (float) $rates[ $source ] : 0.0;
		return (float) apply_filters( 'kr_dp_bcv_rate', $rate, $source, $rates );
	}

	/**
	 * Date the current rates were fetched (site timezone) or ''.
	 *
	 * @return string
	 */
	public static function get_rate_date() {
		$rates = self::get_rates();
		return isset( $rates['date'] ) ? (string) $rates['date'] : '';
	}

	/**
	 * Fetch and parse the BCV home page.
	 *
	 * @return array|false
	 */
	protected static function fetch() {
		$args = array(
			'timeout'     => 15,
			'redirection' => 3,
			'user-agent'  => 'Mozilla/5.0 (X11; Linux x86_64) KR-Direct-Payments/' . KR_DP_VERSION,
		);

		$response = wp_remote_get( self::SOURCE_URL, $args );

		// La cadena de certificados del BCV suele fallar la verificacion SSL;
		// se reintenta sin verificar solo como ultimo recurso (filtrable).
		if ( is_wp_error( $response ) && apply_filters( 'kr_dp_bcv_allow_insecure', true ) ) {
			$args['sslverify'] = false;
			$response          = wp_remote_get( self::SOURCE_URL, $args );
		}

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$html  = wp_remote_retrieve_body( $response );
		$rates = array();

		foreach ( array( 'euro', 'dolar' ) as $currency ) {
			// Ej.: <div id="euro" ...> ... <strong> 106,34910000 </strong>
			if ( preg_match( '/id="' . $currency . '"[^>]*>.*?<strong>\s*([0-9][0-9.,]*)\s*<\/strong>/is', $html, $m ) ) {
				$value = str_replace( '.', '', $m[1] );   // separador de miles
				$value = str_replace( ',', '.', $value ); // decimal venezolano
				if ( (float) $value > 0 ) {
					$rates[ $currency ] = round( (float) $value, 8 );
				}
			}
		}

		if ( empty( $rates['euro'] ) && empty( $rates['dolar'] ) ) {
			return false;
		}

		$rates['date'] = current_time( 'mysql' );
		return $rates;
	}
}

/**
 * Format an amount in bolivares: Bs. 1.234,56.
 *
 * @param float $amount Amount.
 * @return string
 */
function kr_dp_format_bs( $amount ) {
	return 'Bs. ' . number_format( (float) $amount, 2, ',', '.' );
}
