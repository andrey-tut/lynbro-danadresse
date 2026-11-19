<?php
/**
 * Error log for the address service.
 *
 * Records only failures, and only technical facts about them: when, which kind
 * of lookup, the HTTP status and the service's error code. The address text a
 * visitor typed is deliberately never stored — it is personal data, it is not
 * needed to diagnose anything, and keeping it would turn a support aid into a
 * privacy liability.
 *
 * Successful lookups are not logged either: they would mean a database write on
 * every keystroke. Their volume is already visible as daily counters on the
 * Statistics screen.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option holding the rolling error log.
 */
if ( ! defined( 'LYNBRO_DANADRESSE_LOG_OPTION' ) ) {
	define( 'LYNBRO_DANADRESSE_LOG_OPTION', 'lynbro_danadresse_log' );
}

/**
 * How many entries to keep.
 */
if ( ! defined( 'LYNBRO_DANADRESSE_LOG_MAX' ) ) {
	define( 'LYNBRO_DANADRESSE_LOG_MAX', 30 );
}

/**
 * Append one failure to the log.
 *
 * @param string $kind     'autocomplete', 'datavask' or 'test'.
 * @param int    $status   HTTP status (0 when the host was unreachable).
 * @param string $api_code Machine-readable code from the service, if any.
 * @return void
 */
function lynbro_danadresse_log_error( $kind, $status, $api_code ) {
	$log = get_option( LYNBRO_DANADRESSE_LOG_OPTION, array() );
	$log = is_array( $log ) ? $log : array();

	array_unshift(
		$log,
		array(
			'time'   => time(),
			'kind'   => sanitize_key( $kind ),
			'status' => (int) $status,
			'code'   => sanitize_text_field( (string) $api_code ),
		)
	);

	if ( count( $log ) > LYNBRO_DANADRESSE_LOG_MAX ) {
		$log = array_slice( $log, 0, LYNBRO_DANADRESSE_LOG_MAX );
	}

	update_option( LYNBRO_DANADRESSE_LOG_OPTION, $log, false );
}

/**
 * Read the log, newest first.
 *
 * @return array<int,array<string,mixed>>
 */
function lynbro_danadresse_log_entries() {
	$log = get_option( LYNBRO_DANADRESSE_LOG_OPTION, array() );
	return is_array( $log ) ? $log : array();
}

/**
 * Human label for a logged error code.
 *
 * @param string $code   Service error code.
 * @param int    $status HTTP status.
 * @return string
 */
function lynbro_danadresse_log_label( $code, $status ) {
	$map = array(
		'missing_api_key'       => __( 'No API key configured', 'lynbro-danadresse' ),
		'invalid_api_key'       => __( 'API key rejected', 'lynbro-danadresse' ),
		'key_blocked'           => __( 'Key blocked — unlock it in the danadresse.dk dashboard', 'lynbro-danadresse' ),
		'account_suspended'     => __( 'Account suspended', 'lynbro-danadresse' ),
		'sandbox_scope'         => __( 'Not available on a test key', 'lynbro-danadresse' ),
		'rate_limit_per_minute' => __( 'Rate limit reached (per minute)', 'lynbro-danadresse' ),
		'rate_limit_exceeded'   => __( 'Quota reached', 'lynbro-danadresse' ),
		'network_error'         => __( 'Could not reach the service', 'lynbro-danadresse' ),
		'route_not_found'       => __( 'Unknown endpoint', 'lynbro-danadresse' ),
		'not_found'             => __( 'Address not found', 'lynbro-danadresse' ),
	);

	if ( isset( $map[ $code ] ) ) {
		return $map[ $code ];
	}
	if ( $status >= 500 ) {
		return __( 'Service temporarily unavailable', 'lynbro-danadresse' );
	}
	return __( 'Unexpected error', 'lynbro-danadresse' );
}
