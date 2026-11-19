<?php
/**
 * AJAX proxy endpoints.
 *
 * The public autocomplete endpoint proxies browser queries to the address
 * service so the API key never reaches the front end. Every request must carry
 * a valid nonce — including the logged-out one, which is what a visitor at the
 * checkout sends.
 *
 * An earlier version accepted a same-origin Referer instead when the nonce was
 * missing, to survive pages cached longer than the nonce lifetime. That was not
 * a weaker check, it was no check at all: Referer is a request header and any
 * client can set it to anything. The endpoint spends the site owner's API quota,
 * so it is now nonce-only, and a page cached past the nonce lifetime simply
 * degrades to a plain text field.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LYNBRO_DANADRESSE_NONCE' ) ) {
	define( 'LYNBRO_DANADRESSE_NONCE', 'lynbro_danadresse_front' );
}

/**
 * Best-effort per-IP rate limit using a short transient counter.
 *
 * @param string $bucket Logical bucket name.
 * @param int    $max    Max requests within the window.
 * @param int    $window Window length in seconds.
 * @return bool
 */
function lynbro_danadresse_rate_ok( $bucket, $max = 60, $window = 60 ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'lynbro_danadresse_rl_' . $bucket . '_' . md5( $ip );

	$count = (int) get_transient( $key );
	if ( $count >= $max ) {
		return false;
	}

	set_transient( $key, $count + 1, $window );
	return true;
}

/**
 * AJAX (public): address autocomplete proxy.
 *
 * @return void
 */
function lynbro_danadresse_ajax_autocomplete() {
	// Every request carries a valid nonce (dies with a 403 otherwise) — review F1,
	// 2026-08-23. A page cache can serve a nonce long after it expired; the script
	// then fetches a fresh one from lynbro_danadresse_nonce below and retries once.
	check_ajax_referer( LYNBRO_DANADRESSE_NONCE, 'nonce' );

	if ( ! lynbro_danadresse_rate_ok( 'ac', 90, 60 ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many lookups. Please wait a moment.', 'lynbro-danadresse' ) ), 429 );
	}

	$q     = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
	$caret = isset( $_POST['caretpos'] ) ? absint( $_POST['caretpos'] ) : -1;

	if ( mb_strlen( $q ) > 200 ) {
		$q = mb_substr( $q, 0, 200 );
	}

	$result = lynbro_danadresse_autocomplete( $q, $caret );

	if ( ! $result['success'] ) {
		wp_send_json_error( array( 'message' => $result['error'] ) );
	}

	wp_send_json_success( array( 'items' => $result['items'] ) );
}
add_action( 'wp_ajax_lynbro_danadresse_autocomplete', 'lynbro_danadresse_ajax_autocomplete' );
add_action( 'wp_ajax_nopriv_lynbro_danadresse_autocomplete', 'lynbro_danadresse_ajax_autocomplete' );

/**
 * AJAX (public): a fresh nonce for pages served from a page cache.
 *
 * Reads no input and returns only the token the page would have carried if it
 * had been rendered now; never cached, rate-limited like the lookup itself.
 *
 * @return void
 */
function lynbro_danadresse_ajax_nonce() {
	nocache_headers();
	if ( ! lynbro_danadresse_rate_ok( 'nonce', 30, 60 ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many lookups. Please wait a moment.', 'lynbro-danadresse' ) ), 429 );
	}
	wp_send_json_success( array( 'nonce' => wp_create_nonce( LYNBRO_DANADRESSE_NONCE ) ) );
}
add_action( 'wp_ajax_lynbro_danadresse_nonce', 'lynbro_danadresse_ajax_nonce' );
add_action( 'wp_ajax_nopriv_lynbro_danadresse_nonce', 'lynbro_danadresse_ajax_nonce' );

/**
 * AJAX (admin only): settings-page test lookup — autocomplete + datavask.
 *
 * @return void
 */
function lynbro_danadresse_ajax_admin_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'lynbro-danadresse' ) ), 403 );
	}
	check_ajax_referer( 'lynbro_danadresse_admin', 'nonce' );

	$q    = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
	$mode = ( isset( $_POST['mode'] ) && 'datavask' === $_POST['mode'] ) ? 'datavask' : 'autocomplete';

	if ( 'datavask' === $mode ) {
		$washed = lynbro_danadresse_datavask( $q );
		if ( ! $washed['success'] ) {
			wp_send_json_error( array( 'message' => $washed['error'] ) );
		}
		wp_send_json_success(
			array(
				'kategori' => $washed['kategori'],
				'adresse'  => $washed['adresse'],
			)
		);
	}

	$result = lynbro_danadresse_autocomplete( $q );
	if ( ! $result['success'] ) {
		wp_send_json_error( array( 'message' => $result['error'] ) );
	}
	wp_send_json_success( array( 'items' => $result['items'] ) );
}
add_action( 'wp_ajax_lynbro_danadresse_admin_test', 'lynbro_danadresse_ajax_admin_test' );

