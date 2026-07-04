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
	 * Live connectivity check for the admin status line: clears the failure
	 * cooldown and forces a synchronous fetch when no fresh rate is cached.
	 *
	 * @return array Same shape as get_rates().
	 */
	public static function check_now() {
		delete_transient( self::FAIL_TRANSIENT );
		$rates = get_transient( self::TRANSIENT );
		if ( ! is_array( $rates ) || ( empty( $rates['euro'] ) && empty( $rates['dolar'] ) ) ) {
			self::refresh();
		}
		return self::get_rates();
	}

	/**
	 * Query the sources in order, MERGING results until both EUR and USD
	 * are filled (a source that only publishes USD, like dolarapi, no
	 * longer stops the search for the euro rate). The BCV site blocks many
	 * datacenter IP ranges, so mirrors of the official rate act as
	 * fallbacks. Filterable via 'kr_dp_bcv_source_order'.
	 *
	 * @return array|false
	 */
	protected static function fetch() {
		$order = (array) apply_filters( 'kr_dp_bcv_source_order', array( 'bcv', 'pydolarve', 'dolarapi', 'erapi' ) );

		$rates = array();
		$used  = array();

		foreach ( $order as $source_id ) {
			if ( ! empty( $rates['euro'] ) && ! empty( $rates['dolar'] ) ) {
				break;
			}
			$result = false;
			switch ( $source_id ) {
				case 'bcv':
					$result = self::fetch_bcv_html();
					break;
				case 'pydolarve':
					$result = self::fetch_pydolarve();
					break;
				case 'dolarapi':
					$result = self::fetch_dolarapi();
					break;
				case 'erapi':
					$result = self::fetch_erapi( $rates );
					break;
			}
			if ( ! is_array( $result ) ) {
				continue;
			}
			foreach ( array( 'euro', 'dolar' ) as $slot ) {
				if ( empty( $rates[ $slot ] ) && ! empty( $result[ $slot ] ) ) {
					$rates[ $slot ] = $result[ $slot ];
					$used[ $slot ]  = $source_id;
				}
			}
		}

		if ( empty( $rates['euro'] ) && empty( $rates['dolar'] ) ) {
			return false;
		}

		$rates['source'] = implode( '+', array_unique( array_values( $used ) ) );
		$rates['date']   = current_time( 'mysql' );
		return $rates;
	}

	/**
	 * Shared HTTP GET with a browser-like user agent and short timeout.
	 *
	 * @param string $url      URL.
	 * @param bool   $insecure_retry Retry without SSL verification on error.
	 * @return string Body or ''.
	 */
	protected static function http_get( $url, $insecure_retry = false ) {
		$args = array(
			'timeout'     => 8,
			'redirection' => 3,
			'user-agent'  => 'Mozilla/5.0 (X11; Linux x86_64) KR-Direct-Payments/' . KR_DP_VERSION,
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) && $insecure_retry && apply_filters( 'kr_dp_bcv_allow_insecure', true ) ) {
			$args['sslverify'] = false;
			$response          = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}
		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Source 1: official BCV home page (EUR + USD).
	 *
	 * @return array|false
	 */
	protected static function fetch_bcv_html() {
		// La cadena de certificados del BCV suele fallar la verificacion SSL,
		// por eso se permite el reintento sin verificar solo en esta fuente.
		$html = self::http_get( self::SOURCE_URL, true );
		if ( '' === $html ) {
			return false;
		}

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
		return $rates ? $rates : false;
	}

	/**
	 * Source 2: pydolarve.org mirror of the official BCV page (EUR + USD).
	 * Parses defensively: accepts {"monitors":{"eur":{"price":X},...}} or a
	 * flat {"eur":{"price":X},"usd":{...}} shape.
	 *
	 * @return array|false
	 */
	protected static function fetch_pydolarve() {
		$urls = array(
			'https://pydolarve.org/api/v1/dollar?page=bcv',
			'https://pydolarve.org/api/v2/dollar?page=bcv',
		);

		foreach ( $urls as $url ) {
			$body = self::http_get( $url );
			if ( '' === $body ) {
				continue;
			}
			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$monitors = isset( $data['monitors'] ) && is_array( $data['monitors'] ) ? $data['monitors'] : $data;

			$rates = array();
			foreach ( array( 'eur' => 'euro', 'usd' => 'dolar' ) as $key => $slot ) {
				if ( isset( $monitors[ $key ]['price'] ) && (float) $monitors[ $key ]['price'] > 0 ) {
					$rates[ $slot ] = round( (float) $monitors[ $key ]['price'], 8 );
				}
			}
			if ( $rates ) {
				return $rates;
			}
		}
		return false;
	}

	/**
	 * Source 3: ve.dolarapi.com official rate (USD only).
	 *
	 * @return array|false
	 */
	protected static function fetch_dolarapi() {
		$body = self::http_get( 'https://ve.dolarapi.com/v1/dolares/oficial' );
		if ( '' === $body ) {
			return false;
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return false;
		}
		$price = 0.0;
		foreach ( array( 'promedio', 'venta', 'compra', 'price' ) as $key ) {
			if ( isset( $data[ $key ] ) && (float) $data[ $key ] > 0 ) {
				$price = (float) $data[ $key ];
				break;
			}
		}
		return $price > 0 ? array( 'dolar' => round( $price, 8 ) ) : false;
	}

	/**
	 * Source 4 (last resort): open.er-api.com, a global FX API whose VES
	 * value tracks the official rate. Used to fill whichever currency the
	 * previous sources could not provide (typically EUR). Only requests
	 * the missing currencies.
	 *
	 * @param array $have Rates already collected.
	 * @return array|false
	 */
	protected static function fetch_erapi( $have = array() ) {
		$rates = array();
		foreach ( array( 'EUR' => 'euro', 'USD' => 'dolar' ) as $currency => $slot ) {
			if ( ! empty( $have[ $slot ] ) ) {
				continue;
			}
			$body = self::http_get( 'https://open.er-api.com/v6/latest/' . $currency );
			if ( '' === $body ) {
				continue;
			}
			$data = json_decode( $body, true );
			if ( isset( $data['rates']['VES'] ) && (float) $data['rates']['VES'] > 0 ) {
				$rates[ $slot ] = round( (float) $data['rates']['VES'], 8 );
			}
		}
		return $rates ? $rates : false;
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
