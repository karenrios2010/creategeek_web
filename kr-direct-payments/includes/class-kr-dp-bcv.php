<?php
/**
 * BCV exchange-rate fetcher for KR Direct Payments.
 *
 * Scrapes the official published rates (EUR and USD) from bcv.org.ve.
 *
 * Speed model: front-end requests NEVER wait for the BCV site. A WP-Cron
 * task refreshes the rate in the background on a configurable interval
 * (default 30 min); visitors always read the cached/last-known value.
 * The only synchronous fetch happens once, on a fresh install with no
 * stored rate yet.
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
	 * Refresh interval in seconds, from the admin setting (minutes).
	 * Clamped to 5 min - 24 h. Default 30 min. Filterable.
	 *
	 * @return int
	 */
	public static function cache_seconds() {
		$minutes = (int) get_option( 'kr_dp_bcv_cache_minutes', 30 );
		if ( $minutes < 5 || $minutes > 1440 ) {
			$minutes = 30;
		}
		return (int) apply_filters( 'kr_dp_bcv_cache_seconds', $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Get the cached rates array: array( 'euro' => float, 'dolar' => float, 'date' => 'Y-m-d H:i:s' ).
	 *
	 * Never blocks the visitor when a previous rate exists: an expired
	 * cache returns the last known value and queues a background refresh.
	 *
	 * @return array
	 */
	public static function get_rates() {
		$rates = get_transient( self::TRANSIENT );
		if ( is_array( $rates ) && ( ! empty( $rates['euro'] ) || ! empty( $rates['dolar'] ) ) ) {
			return $rates;
		}

		$last = get_option( self::LAST_OPTION );
		if ( is_array( $last ) && ( ! empty( $last['euro'] ) || ! empty( $last['dolar'] ) ) ) {
			// Stale-while-revalidate: responder al instante con la ultima
			// tasa y actualizar en segundo plano sin bloquear la pagina.
			self::queue_background_refresh();
			return $last;
		}

		// Primera vez (sin tasa guardada): unica consulta sincrona.
		if ( get_transient( self::FAIL_TRANSIENT ) ) {
			return array();
		}
		$rates = self::refresh();
		return is_array( $rates ) ? $rates : array();
	}

	/**
	 * Force-fetch from bcv.org.ve and persist. Used by the WP-Cron task.
	 *
	 * @return array|false
	 */
	public static function refresh() {
		$rates = self::fetch();
		if ( $rates ) {
			set_transient( self::TRANSIENT, $rates, self::cache_seconds() );
			update_option( self::LAST_OPTION, $rates, false );
			delete_transient( self::FAIL_TRANSIENT );
			return $rates;
		}
		set_transient( self::FAIL_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * Queue a one-off async refresh (fires via WP-Cron on this same
	 * request's shutdown loopback, without delaying the visitor).
	 */
	protected static function queue_background_refresh() {
		if ( get_transient( self::FAIL_TRANSIENT ) ) {
			return;
		}
		if ( ! wp_next_scheduled( 'kr_dp_bcv_refresh_single' ) ) {
			wp_schedule_single_event( time(), 'kr_dp_bcv_refresh_single' );
		}
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
			'timeout'     => 8,
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
