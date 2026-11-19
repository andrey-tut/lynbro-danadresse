<?php
/**
 * Front-end assets: the autocomplete widget script/style and its config.
 *
 * The script is only enqueued when there is something to attach to: the
 * WooCommerce integration is enabled (and WooCommerce is active), custom
 * selectors are configured, or a shortcode/block placed a field on the page.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the widget assets should load on this request.
 *
 * @return bool
 */
function lynbro_danadresse_should_enqueue() {
	if ( is_admin() ) {
		return false;
	}

	$options = lynbro_danadresse_get_options();

	if ( '' === (string) $options['api_key'] ) {
		return false;
	}

	if ( lynbro_danadresse_woo_enabled() ) {
		return true;
	}

	// Any enabled integration whose target plugin is active on this site.
	$state = lynbro_danadresse_integration_state();
	foreach ( lynbro_danadresse_integrations() as $it ) {
		if ( ! empty( $state[ $it['id'] ] ) && lynbro_danadresse_integration_detected( $it ) ) {
			return true;
		}
	}

	// Auto-detect mode scans every page for address-like fields.
	if ( ! empty( $options['auto_detect'] ) ) {
		return true;
	}

	if ( count( lynbro_danadresse_get_selectors() ) > 0 ) {
		return true;
	}

	// Shortcode / block on the current singular content.
	if ( is_singular() ) {
		$post = get_post();
		if ( $post && ( has_shortcode( (string) $post->post_content, 'lynbro_danadresse_field' ) || has_block( 'lynbro-danadresse/address-field', $post ) ) ) {
			return true;
		}
	}

	/**
	 * Filter whether the Danadresse widget assets load on this request.
	 *
	 * @param bool $load Default decision.
	 */
	return (bool) apply_filters( 'lynbro_danadresse_should_enqueue', false );
}

/**
 * Register + enqueue the public widget.
 *
 * @return void
 */
function lynbro_danadresse_enqueue_assets() {
	if ( ! lynbro_danadresse_should_enqueue() ) {
		return;
	}

	$options = lynbro_danadresse_get_options();

	wp_enqueue_style(
		'lynbro-danadresse',
		LYNBRO_DANADRESSE_URL . 'public/autocomplete.css',
		array(),
		LYNBRO_DANADRESSE_VERSION
	);

	wp_enqueue_script(
		'lynbro-danadresse',
		LYNBRO_DANADRESSE_URL . 'public/autocomplete.js',
		array(),
		LYNBRO_DANADRESSE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'lynbro-danadresse',
		'lynbroDanadresseCfg',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( LYNBRO_DANADRESSE_NONCE ),
			'minChars'  => (int) $options['min_chars'],
			'maxItems'  => (int) $options['max_items'],
			'tier'      => (string) $options['tier'],
			'dkOnly'    => (bool) $options['dk_only'],
			'woo'       => lynbro_danadresse_woo_enabled(),
			'autoDetect' => (bool) ! empty( $options['auto_detect'] ),
			'integrations' => lynbro_danadresse_integration_state(),
			'selectors' => lynbro_danadresse_get_selectors(),
			'labels'    => array(
				'noResults' => __( 'No matching address', 'lynbro-danadresse' ),
				/* translators: %d: number of address suggestions. */
				'results'   => __( '%d suggestions available', 'lynbro-danadresse' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lynbro_danadresse_enqueue_assets' );
