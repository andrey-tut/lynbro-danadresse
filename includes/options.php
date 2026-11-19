<?php
/**
 * Options: single option array, defaults and sanitization.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option name holding all plugin settings. The "lynbro" prefix keeps the
 * option compatible with the shared Lynbro Tools export/reset helpers.
 */
if ( ! defined( 'LYNBRO_DANADRESSE_OPTION' ) ) {
	define( 'LYNBRO_DANADRESSE_OPTION', 'lynbro_danadresse_options' );
}

/**
 * Option name holding the local 30-day usage counters.
 */
if ( ! defined( 'LYNBRO_DANADRESSE_STATS_OPTION' ) ) {
	define( 'LYNBRO_DANADRESSE_STATS_OPTION', 'lynbro_danadresse_stats' );
}

/**
 * Default settings.
 *
 * @return array<string,mixed>
 */
function lynbro_danadresse_default_options() {
	return array(
		'api_key'           => '',
		'tier'              => 'adresse', // 'adresse' (with floor/door) or 'adgangsadresse'.
		'fuzzy'             => 1,
		'max_items'         => 7,
		'min_chars'         => 2,
		'enable_woo'        => 1,
		'dk_only'           => 1,
		'enable_datavask'   => 0,
		'datavask_block_c'  => 0,
		'auto_detect'       => 0,
		'integrations'      => array(), // Empty = all integrations enabled by default.
		'selectors'         => '',
	);
}

/**
 * Get all options merged over defaults.
 *
 * @return array<string,mixed>
 */
function lynbro_danadresse_get_options() {
	$saved = get_option( LYNBRO_DANADRESSE_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), lynbro_danadresse_default_options() );
}

/**
 * Get a single option.
 *
 * @param string $key      Option key.
 * @param mixed  $fallback Fallback when the key is unknown.
 * @return mixed
 */
function lynbro_danadresse_get_option( $key, $fallback = null ) {
	$options = lynbro_danadresse_get_options();
	return array_key_exists( $key, $options ) ? $options[ $key ] : $fallback;
}

/**
 * Sanitize the settings array (Settings API callback).
 *
 * @param mixed $input Raw input.
 * @return array<string,mixed>
 */
function lynbro_danadresse_sanitize_options( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$current  = lynbro_danadresse_get_options();
	$defaults = lynbro_danadresse_default_options();
	$clean    = array();

	// Tab-scoped saves: each settings form marks which keys it owns so that
	// saving one tab never resets checkboxes/fields that live on another tab.
	$tab_keys = array(
		'settings'     => array( 'api_key', 'tier', 'fuzzy', 'max_items', 'min_chars' ),
		'integrations' => array( 'enable_woo', 'dk_only', 'enable_datavask', 'datavask_block_c', 'auto_detect', 'integrations', 'selectors' ),
	);
	$tab      = isset( $input['_tab'] ) && isset( $tab_keys[ $input['_tab'] ] ) ? (string) $input['_tab'] : '';

	// API key: keep the stored key when the masked placeholder comes back unchanged.
	$key = isset( $input['api_key'] ) ? trim( sanitize_text_field( $input['api_key'] ) ) : '';
	if ( '' !== $key && preg_match( '/^\x{2022}+$/u', $key ) ) {
		$key = (string) $current['api_key'];
	}
	$clean['api_key'] = ( '' === $key || preg_match( '/^[A-Za-z0-9._\-]{8,128}$/', $key ) ) ? $key : (string) $current['api_key'];

	$clean['tier']  = ( isset( $input['tier'] ) && 'adgangsadresse' === $input['tier'] ) ? 'adgangsadresse' : 'adresse';
	$clean['fuzzy'] = empty( $input['fuzzy'] ) ? 0 : 1;

	$clean['max_items'] = isset( $input['max_items'] ) ? max( 3, min( 20, absint( $input['max_items'] ) ) ) : $defaults['max_items'];
	$clean['min_chars'] = isset( $input['min_chars'] ) ? max( 1, min( 6, absint( $input['min_chars'] ) ) ) : $defaults['min_chars'];

	$clean['enable_woo']       = empty( $input['enable_woo'] ) ? 0 : 1;
	$clean['dk_only']          = empty( $input['dk_only'] ) ? 0 : 1;
	$clean['enable_datavask']  = empty( $input['enable_datavask'] ) ? 0 : 1;
	$clean['datavask_block_c'] = empty( $input['datavask_block_c'] ) ? 0 : 1;
	$clean['auto_detect']      = empty( $input['auto_detect'] ) ? 0 : 1;

	// Integration toggles: store an explicit map of known ids only.
	$clean['integrations'] = array();
	$posted_integrations   = ( isset( $input['integrations'] ) && is_array( $input['integrations'] ) ) ? $input['integrations'] : array();
	if ( function_exists( 'lynbro_danadresse_integrations' ) ) {
		foreach ( lynbro_danadresse_integrations() as $it ) {
			$id                           = $it['id'];
			$clean['integrations'][ $id ] = ! empty( $posted_integrations[ $id ] ) ? 1 : 0;
		}
	} elseif ( is_array( $current['integrations'] ) ) {
		$clean['integrations'] = $current['integrations'];
	}

	// Keep the legacy enable_woo flag in sync with the WooCommerce toggle so
	// existing checks (asset enqueue, localized config) keep working.
	if ( array_key_exists( 'woocommerce', $clean['integrations'] ) ) {
		$clean['enable_woo'] = ! empty( $clean['integrations']['woocommerce'] ) ? 1 : 0;
	} else {
		$clean['enable_woo'] = empty( $input['enable_woo'] ) ? 0 : 1;
	}

	// Custom selectors: one per line, conservative character set (no quotes/angle brackets).
	$selectors = array();
	if ( isset( $input['selectors'] ) ) {
		$lines = explode( "\n", lynbro_danadresse_unslash_string( $input['selectors'] ) );
		foreach ( $lines as $line ) {
			$line = trim( sanitize_text_field( $line ) );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^[A-Za-z0-9#.\[\]=_\s:>()+~,\'"\-\*]{1,200}$/', $line ) && false === strpos( $line, '<' ) ) {
				$selectors[] = $line;
			}
		}
	}
	$clean['selectors'] = implode( "\n", array_slice( array_unique( $selectors ), 0, 30 ) );

	// Keep the other tab's stored values when this save came from a known tab.
	if ( '' !== $tab ) {
		foreach ( $clean as $key => $value ) {
			if ( ! in_array( $key, $tab_keys[ $tab ], true ) ) {
				$clean[ $key ] = array_key_exists( $key, $current ) ? $current[ $key ] : $value;
			}
		}
	}

	return $clean;
}

/**
 * Small unslash helper that accepts non-string input safely.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function lynbro_danadresse_unslash_string( $value ) {
	return is_string( $value ) ? wp_unslash( $value ) : '';
}

/**
 * Parse the selectors option into a clean array for the front-end config.
 *
 * @return array<int,string>
 */
function lynbro_danadresse_get_selectors() {
	$raw   = (string) lynbro_danadresse_get_option( 'selectors', '' );
	$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );

	/**
	 * Filter the list of CSS selectors the autocomplete attaches to.
	 *
	 * @param array<int,string> $lines Selector list.
	 */
	return apply_filters( 'lynbro_danadresse_attach_selectors', array_values( $lines ) );
}
