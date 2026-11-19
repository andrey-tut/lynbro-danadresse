<?php
/**
 * Server-side client for the danadresse.dk API.
 *
 * The API key is only ever used here, on the server — it is never exposed to
 * the browser. Requests are cached in transients and counted in a local
 * 30-day usage log so the site owner can watch their quota.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the API base URL.
 *
 * @return string
 */
function lynbro_danadresse_api_base() {
	/**
	 * Filter the danadresse API base URL (e.g. for a self-hosted mirror).
	 *
	 * @param string $base Base URL without trailing slash.
	 */
	return untrailingslashit( (string) apply_filters( 'lynbro_danadresse_api_base', LYNBRO_DANADRESSE_API_BASE ) );
}

/**
 * A stable device id for this WordPress site.
 *
 * The service counts how many distinct devices use one key (the allowance
 * depends on the plan). Without this header it derives the identity from the
 * request's User-Agent/Accept headers — which legitimately differ between an
 * admin screen, a REST request and WP-Cron, so a single site would burn
 * several device slots and eventually hit 403 key_blocked. One stable string
 * makes the whole installation count as exactly one device.
 *
 * Derived from home_url() so it survives plugin updates and cache clears, and
 * changes only if the site itself moves to another domain.
 *
 * @return string 64-char hex hash.
 */
function lynbro_danadresse_device_id() {
	/**
	 * Filter the device id sent to the address service.
	 *
	 * Useful when several sites must share one key deliberately, or when a
	 * site is reachable under more than one domain.
	 *
	 * @param string $device_id Default: sha256 of the site home URL.
	 */
	return (string) apply_filters(
		'lynbro_danadresse_device_id',
		hash( 'sha256', 'lynbro-danadresse|' . home_url( '/' ) )
	);
}

/**
 * Which kind of lookup a request path belongs to, for the error log.
 *
 * @param string $path Request path.
 * @return string 'autocomplete', 'datavask', 'enrich' or 'lookup'.
 */
function lynbro_danadresse_path_kind( $path ) {
	if ( false !== strpos( $path, 'autocomplete' ) ) {
		return 'autocomplete';
	}
	if ( false !== strpos( $path, 'datavask' ) ) {
		return 'datavask';
	}
	if ( false !== strpos( $path, 'enrich' ) ) {
		return 'enrich';
	}
	return 'lookup';
}

/**
 * Perform a GET request against the API with the stored key.
 *
 * @param string               $path    Path starting with '/'.
 * @param array<string,mixed>  $query   Query args.
 * @param int                  $timeout Timeout in seconds.
 * @param array<string,string> $headers Extra request headers.
 * @return array{success:bool,code:int,body:mixed,error:string,api_code:string,max_age:?int}
 */
function lynbro_danadresse_api_get( $path, $query = array(), $timeout = 5, $headers = array() ) {
	$key = (string) lynbro_danadresse_get_option( 'api_key', '' );

	if ( '' === $key ) {
		return array(
			'success'  => false,
			'code'     => 0,
			'body'     => null,
			'error'    => __( 'No API key configured. Add your free danadresse.dk key in the plugin settings.', 'lynbro-danadresse' ),
			'api_code' => 'missing_api_key',
			'max_age'  => null,
		);
	}

	// Quota spent: stay quiet until the reported reset instead of collecting 429s.
	if ( lynbro_danadresse_is_paused() ) {
		return array(
			'success'  => false,
			'code'     => 429,
			'body'     => null,
			'error'    => __( 'The address service quota was reached. Lookups will resume automatically.', 'lynbro-danadresse' ),
			'api_code' => 'rate_limit_exceeded',
			'max_age'  => null,
		);
	}

	$url = add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $query ) ), lynbro_danadresse_api_base() . $path );

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => $timeout,
			'user-agent' => 'danadresse-wp/' . LYNBRO_DANADRESSE_VERSION . '; ' . home_url( '/' ),
			'headers'    => array_merge(
				array(
					'X-Api-Key'   => $key,
					// Stable per-site device id — makes this installation count as
					// one device rather than one per request type. Must stay
					// identical across admin, REST and cron.
					'X-Device-Id' => lynbro_danadresse_device_id(),
					'Accept'      => 'application/json',
				),
				$headers
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		lynbro_danadresse_stats_bump( 'errors' );
		lynbro_danadresse_log_error( lynbro_danadresse_path_kind( $path ), 0, 'network_error' );
		return array(
			'success'  => false,
			'code'     => 0,
			'body'     => null,
			'error'    => __( 'Could not reach the address service. Please try again.', 'lynbro-danadresse' ),
			'api_code' => 'network_error',
			'max_age'  => null,
		);
	}

	$code    = (int) wp_remote_retrieve_response_code( $response );
	$body    = json_decode( wp_remote_retrieve_body( $response ), true );
	$max_age = lynbro_danadresse_parse_max_age( wp_remote_retrieve_header( $response, 'cache-control' ) );

	lynbro_danadresse_note_rate_limit( $response );

	// Machine-readable error code. The service exposes it at the top level on the
	// DAWA-compatible (RFC 7807) paths and inside "error" on /api/v1.
	$api_code = '';
	if ( is_array( $body ) ) {
		if ( isset( $body['code'] ) && is_string( $body['code'] ) ) {
			$api_code = $body['code'];
		} elseif ( isset( $body['error']['code'] ) && is_string( $body['error']['code'] ) ) {
			$api_code = $body['error']['code'];
		}
	}

	if ( $code < 200 || $code >= 300 ) {
		lynbro_danadresse_stats_bump( 'errors' );
		lynbro_danadresse_remember_error( $api_code, $code );
		lynbro_danadresse_log_error( lynbro_danadresse_path_kind( $path ), $code, $api_code );

		switch ( $api_code ) {
			case 'key_blocked':
				$message = __( 'The address service blocked this API key: it was used from more devices than your plan allows. Unlock it in your danadresse.dk dashboard under Indstillinger → nøgler, then try again.', 'lynbro-danadresse' );
				break;
			case 'missing_api_key':
			case 'invalid_api_key':
				$message = __( 'The address service rejected the API key. Check the key in the plugin settings.', 'lynbro-danadresse' );
				break;
			case 'account_suspended':
				$message = __( 'Your danadresse.dk account is suspended. Please check your account at danadresse.dk.', 'lynbro-danadresse' );
				break;
			case 'sandbox_scope':
				$message = __( 'This feature is not available on a sandbox (test) key. Use a live key from your danadresse.dk dashboard.', 'lynbro-danadresse' );
				break;
			case 'rate_limit_per_minute':
			case 'rate_limit_exceeded':
				$message = __( 'The address service rate limit was reached. Lookups will resume automatically.', 'lynbro-danadresse' );
				break;
			default:
				if ( 401 === $code || 403 === $code ) {
					$message = __( 'The address service rejected the API key. Check the key in the plugin settings.', 'lynbro-danadresse' );
				} elseif ( 429 === $code ) {
					$message = __( 'The address service quota was reached. Lookups will resume automatically.', 'lynbro-danadresse' );
				} elseif ( $code >= 500 ) {
					$message = __( 'The address service is temporarily unavailable. Lookups will resume automatically.', 'lynbro-danadresse' );
				} else {
					$message = __( 'The address service returned an error.', 'lynbro-danadresse' );
				}
		}

		return array(
			'success'  => false,
			'code'     => $code,
			'body'     => $body,
			'error'    => $message,
			'api_code' => $api_code,
			'max_age'  => $max_age,
		);
	}

	// A successful call clears any remembered error state.
	lynbro_danadresse_remember_error( '', $code );

	return array(
		'success'  => true,
		'code'     => $code,
		'body'     => $body,
		'error'    => '',
		'api_code' => '',
		'max_age'  => $max_age,
	);
}

/**
 * Note the quota state the service reports and pause lookups when it runs out.
 *
 * The API sends X-RateLimit-Limit / -Remaining / -Reset on every response. When
 * the remaining budget hits zero we stop calling until the reset timestamp
 * instead of walking into 429s.
 *
 * @param array<string,mixed>|WP_Error $response wp_remote_get response.
 * @return void
 */
function lynbro_danadresse_note_rate_limit( $response ) {
	// Two windows are reported. The per-minute one is the one autocomplete
	// actually hits — it fires on every keystroke — while the monthly counter
	// still shows a huge remainder at that moment, so check the minute window
	// first. Plans without a per-minute cap send "unlimited" (or nothing).
	$windows = array(
		array( 'x-ratelimit-minute-remaining', 'x-ratelimit-minute-reset', MINUTE_IN_SECONDS ),
		array( 'x-ratelimit-remaining', 'x-ratelimit-reset', HOUR_IN_SECONDS ),
	);

	$seen = false;

	foreach ( $windows as $window ) {
		list( $remaining_header, $reset_header, $fallback ) = $window;

		$remaining = wp_remote_retrieve_header( $response, $remaining_header );
		if ( '' === $remaining || null === $remaining || ! is_numeric( $remaining ) ) {
			continue; // Header absent, or "unlimited" — nothing to enforce here.
		}

		$seen = true;

		if ( (int) $remaining > 0 ) {
			continue;
		}

		// This window is spent: hold off until it resets. Bounded so a bogus
		// timestamp can never park the plugin for good.
		$reset   = wp_remote_retrieve_header( $response, $reset_header );
		$seconds = is_numeric( $reset ) ? ( (int) $reset - time() ) : $fallback;
		$seconds = max( 10, min( DAY_IN_SECONDS, $seconds ) );
		set_transient( 'lynbro_danadresse_paused', 1, $seconds );
		return;
	}

	if ( $seen ) {
		delete_transient( 'lynbro_danadresse_paused' );
	}
}

/**
 * Whether lookups are paused because the monthly quota is spent.
 *
 * @return bool
 */
function lynbro_danadresse_is_paused() {
	return (bool) get_transient( 'lynbro_danadresse_paused' );
}

/**
 * Parse max-age (in seconds) out of a Cache-Control header.
 *
 * The plugin must never cache a response for longer than the service says it
 * stays fresh. Returns 0 for no-store/no-cache, null when the header carries no
 * usable directive (caller then applies its own conservative default).
 *
 * @param string|array<int,string> $header Cache-Control header value.
 * @return int|null
 */
function lynbro_danadresse_parse_max_age( $header ) {
	$header = is_array( $header ) ? implode( ',', $header ) : (string) $header;
	if ( '' === $header ) {
		return null;
	}
	$header = strtolower( $header );

	if ( false !== strpos( $header, 'no-store' ) || false !== strpos( $header, 'no-cache' ) ) {
		return 0;
	}
	if ( preg_match( '/max-age\s*=\s*(\d+)/', $header, $m ) ) {
		return (int) $m[1];
	}
	return null;
}

/**
 * Cache TTL for a response: our own ceiling, but never longer than the service
 * allows.
 *
 * @param int|null $max_age Value parsed from Cache-Control, if any.
 * @param int      $ceiling Our own maximum, in seconds.
 * @return int Seconds to cache; 0 means do not cache.
 */
function lynbro_danadresse_cache_ttl( $max_age, $ceiling ) {
	if ( null === $max_age ) {
		return $ceiling;
	}
	return max( 0, min( $ceiling, $max_age ) );
}

/**
 * Remember the last error code from the service so the admin screens can offer
 * the matching recovery action.
 *
 * @param string $api_code Machine-readable code ('' clears the state).
 * @param int    $status   HTTP status.
 * @return void
 */
function lynbro_danadresse_remember_error( $api_code, $status ) {
	$stored = (string) get_option( 'lynbro_danadresse_last_error', '' );

	if ( '' === $api_code ) {
		if ( '' !== $stored ) {
			delete_option( 'lynbro_danadresse_last_error' );
		}
		return;
	}
	if ( $stored !== $api_code ) {
		update_option( 'lynbro_danadresse_last_error', $api_code, false );
	}
	unset( $status );
}


/**
 * Address autocomplete (DAWA-compatible /autocomplete).
 *
 * @param string $q        Query text.
 * @param int    $caretpos Caret position within the query (drill-down hint).
 * @return array{success:bool,items:array<int,array<string,mixed>>,error:string}
 */
function lynbro_danadresse_autocomplete( $q, $caretpos = -1 ) {
	$q = trim( $q );
	if ( '' === $q ) {
		return array(
			'success' => true,
			'items'   => array(),
			'error'   => '',
		);
	}

	$options = lynbro_danadresse_get_options();

	$query = array(
		'q'        => $q,
		'type'     => $options['tier'],
		'per_side' => (int) $options['max_items'],
	);
	if ( ! empty( $options['fuzzy'] ) ) {
		$query['fuzzy'] = '';
	}
	if ( $caretpos >= 0 ) {
		$query['caretpos'] = $caretpos;
	}

	$cache_key = 'lynbro_danadresse_ac_' . md5( wp_json_encode( $query ) );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return array(
			'success' => true,
			'items'   => $cached,
			'error'   => '',
		);
	}

	$result = lynbro_danadresse_api_get( '/autocomplete', $query, 5 );
	if ( ! $result['success'] ) {
		return array(
			'success' => false,
			'items'   => array(),
			'error'   => $result['error'],
		);
	}

	$items = array();
	if ( is_array( $result['body'] ) ) {
		foreach ( $result['body'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$data  = isset( $row['data'] ) && is_array( $row['data'] ) ? $row['data'] : array();
			$items[] = array(
				'type'          => isset( $row['type'] ) ? sanitize_text_field( (string) $row['type'] ) : '',
				'tekst'         => isset( $row['tekst'] ) ? sanitize_text_field( (string) $row['tekst'] ) : '',
				'forslagstekst' => isset( $row['forslagstekst'] ) ? sanitize_text_field( (string) $row['forslagstekst'] ) : '',
				'caretpos'      => isset( $row['caretpos'] ) ? (int) $row['caretpos'] : 0,
				'data'          => array(
					'id'         => isset( $data['id'] ) ? sanitize_text_field( (string) $data['id'] ) : '',
					'vejnavn'    => isset( $data['vejnavn'] ) ? sanitize_text_field( (string) $data['vejnavn'] ) : '',
					'husnr'      => isset( $data['husnr'] ) ? sanitize_text_field( (string) $data['husnr'] ) : '',
					'etage'      => isset( $data['etage'] ) ? sanitize_text_field( (string) $data['etage'] ) : '',
					'dør'        => isset( $data['dør'] ) ? sanitize_text_field( (string) $data['dør'] ) : '',
					'postnr'     => isset( $data['postnr'] ) ? sanitize_text_field( (string) $data['postnr'] ) : '',
					'postnrnavn' => isset( $data['postnrnavn'] ) ? sanitize_text_field( (string) $data['postnrnavn'] ) : '',
				),
			);
		}
	}

	/**
	 * Filter autocomplete suggestions before they are cached and returned.
	 *
	 * @param array<int,array<string,mixed>> $items Suggestions.
	 * @param string                         $q     Query text.
	 */
	$items = (array) apply_filters( 'lynbro_danadresse_result', $items, $q );

	// Never keep a response longer than the service says it stays fresh
	// (/autocomplete currently sends max-age=60).
	$ttl = lynbro_danadresse_cache_ttl( $result['max_age'], 5 * MINUTE_IN_SECONDS );
	if ( $ttl > 0 ) {
		set_transient( $cache_key, $items, $ttl );
	}
	lynbro_danadresse_stats_bump( 'autocomplete' );

	return array(
		'success' => true,
		'items'   => $items,
		'error'   => '',
	);
}

/**
 * Address wash / validation (DAWA-compatible /datavask/adresser).
 *
 * @param string $betegnelse Full address as one line, e.g. "Rådhuspladsen 1, 1550 København V".
 * @return array{success:bool,kategori:string,adresse:array<string,string>,error:string}
 */
function lynbro_danadresse_datavask( $betegnelse ) {
	$betegnelse = trim( $betegnelse );
	if ( '' === $betegnelse ) {
		return array(
			'success'  => false,
			'kategori' => '',
			'adresse'  => array(),
			'error'    => __( 'Empty address.', 'lynbro-danadresse' ),
		);
	}

	$path = ( 'adgangsadresse' === lynbro_danadresse_get_option( 'tier' ) ) ? '/datavask/adgangsadresser' : '/datavask/adresser';

	$cache_key = 'lynbro_danadresse_dv_' . md5( $path . '|' . $betegnelse );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$result = lynbro_danadresse_api_get( $path, array( 'betegnelse' => $betegnelse ), 10 );
	if ( ! $result['success'] ) {
		return array(
			'success'  => false,
			'kategori' => '',
			'adresse'  => array(),
			'error'    => $result['error'],
		);
	}

	$body     = is_array( $result['body'] ) ? $result['body'] : array();
	$kategori = isset( $body['kategori'] ) ? strtoupper( sanitize_text_field( (string) $body['kategori'] ) ) : '';
	$adresse  = array();

	if ( isset( $body['resultater'][0]['adresse'] ) && is_array( $body['resultater'][0]['adresse'] ) ) {
		foreach ( array( 'id', 'vejnavn', 'husnr', 'etage', 'dør', 'postnr', 'postnrnavn' ) as $field ) {
			$adresse[ $field ] = isset( $body['resultater'][0]['adresse'][ $field ] )
				? sanitize_text_field( (string) $body['resultater'][0]['adresse'][ $field ] )
				: '';
		}
	}

	$washed = array(
		'success'  => true,
		'kategori' => in_array( $kategori, array( 'A', 'B', 'C' ), true ) ? $kategori : 'C',
		'adresse'  => $adresse,
		'error'    => '',
	);

	/**
	 * Fires after an address has been washed against the service.
	 *
	 * @param array<string,mixed> $washed     Wash result (kategori, adresse).
	 * @param string              $betegnelse Input address.
	 */
	do_action( 'lynbro_danadresse_washed', $washed, $betegnelse );

	$ttl = lynbro_danadresse_cache_ttl( $result['max_age'], DAY_IN_SECONDS );
	if ( $ttl > 0 ) {
		set_transient( $cache_key, $washed, $ttl );
	}
	lynbro_danadresse_stats_bump( 'datavask' );

	return $washed;
}

/**
 * Increment a local daily usage counter (kept for 30 days).
 *
 * @param string $bucket One of 'autocomplete', 'datavask', 'errors'.
 * @return void
 */
function lynbro_danadresse_stats_bump( $bucket ) {
	$stats = get_option( LYNBRO_DANADRESSE_STATS_OPTION, array() );
	$stats = is_array( $stats ) ? $stats : array();
	$today = gmdate( 'Y-m-d' );

	if ( ! isset( $stats[ $today ] ) || ! is_array( $stats[ $today ] ) ) {
		$stats[ $today ] = array();
	}
	$stats[ $today ][ $bucket ] = isset( $stats[ $today ][ $bucket ] ) ? (int) $stats[ $today ][ $bucket ] + 1 : 1;

	if ( count( $stats ) > 31 ) {
		ksort( $stats );
		$stats = array_slice( $stats, -31, null, true );
	}

	update_option( LYNBRO_DANADRESSE_STATS_OPTION, $stats, false );
}
