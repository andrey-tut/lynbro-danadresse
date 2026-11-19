<?php
/**
 * Integration registry.
 *
 * A single source of truth for the plugins the address autofill can attach to.
 * The front-end engine (public/autocomplete.js) has a matching adapter per id;
 * this registry drives the admin toggles and the "detected on this site" hints,
 * and it decides which adapters are shipped to the browser.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * The known integrations.
 *
 * Each entry:
 *   id        Adapter id (matches ADAPTERS[].id in autocomplete.js).
 *   label     Human name.
 *   detect    Callback returning true when the target plugin is active.
 *   composite Whether the plugin exposes separate postcode/city sub-fields
 *             (informational — shown in the admin as "fills postcode & city").
 *
 * @return array<int,array<string,mixed>>
 */
function lynbro_danadresse_integrations() {
	$registry = array(
		array(
			'id'        => 'woocommerce',
			'label'     => __( 'WooCommerce checkout & account', 'lynbro-danadresse' ),
			'note'      => __( 'Classic checkout, the Checkout Block and My Account — fills street, postcode and city.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return class_exists( 'WooCommerce' );
			},
		),
		array(
			'id'        => 'wpforms',
			'label'     => __( 'WPForms', 'lynbro-danadresse' ),
			'note'      => __( 'The WPForms Address field — fills address line, postcode and city.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return function_exists( 'wpforms' ) || defined( 'WPFORMS_VERSION' );
			},
		),
		array(
			'id'        => 'gravityforms',
			'label'     => __( 'Gravity Forms', 'lynbro-danadresse' ),
			'note'      => __( 'The Gravity Forms Address field — fills street, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return class_exists( 'GFForms' );
			},
		),
		array(
			'id'        => 'fluentform',
			'label'     => __( 'Fluent Forms', 'lynbro-danadresse' ),
			'note'      => __( 'The Fluent Forms Address field — fills address line, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return defined( 'FLUENTFORM_VERSION' ) || function_exists( 'wpFluentForm' );
			},
		),
		array(
			'id'        => 'formidable',
			'label'     => __( 'Formidable Forms', 'lynbro-danadresse' ),
			'note'      => __( 'The Formidable Address field — fills line, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return class_exists( 'FrmHooksController' ) || defined( 'FrmAppHelper::plugin_version' );
			},
		),
		array(
			'id'        => 'edd',
			'label'     => __( 'Easy Digital Downloads', 'lynbro-danadresse' ),
			'note'      => __( 'The EDD checkout billing address — fills street, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return class_exists( 'Easy_Digital_Downloads' );
			},
		),
		array(
			'id'        => 'givewp',
			'label'     => __( 'GiveWP donations', 'lynbro-danadresse' ),
			'note'      => __( 'The GiveWP donation billing address — fills street, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return function_exists( 'give' ) || defined( 'GIVE_VERSION' );
			},
		),
		array(
			'id'        => 'forminator',
			'label'     => __( 'Forminator', 'lynbro-danadresse' ),
			'note'      => __( 'The Forminator Address field — fills address line, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return defined( 'FORMINATOR_VERSION' ) || class_exists( 'Forminator' );
			},
		),
		array(
			'id'        => 'everestforms',
			'label'     => __( 'Everest Forms', 'lynbro-danadresse' ),
			'note'      => __( 'The Everest Forms Address field — fills address line, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return defined( 'EVF_VERSION' ) || function_exists( 'evf' );
			},
		),
		array(
			'id'        => 'pmpro',
			'label'     => __( 'Paid Memberships Pro', 'lynbro-danadresse' ),
			'note'      => __( 'The membership checkout billing address — fills street, city and ZIP.', 'lynbro-danadresse' ),
			'composite' => true,
			'detect'    => static function () {
				return defined( 'PMPRO_VERSION' ) || function_exists( 'pmpro_getOption' );
			},
		),
		array(
			'id'        => 'ultimatemember',
			'label'     => __( 'Ultimate Member', 'lynbro-danadresse' ),
			'note'      => __( 'The Ultimate Member profile/register address field.', 'lynbro-danadresse' ),
			'composite' => false,
			'detect'    => static function () {
				return function_exists( 'UM' ) || class_exists( 'UM' );
			},
		),
		array(
			'id'        => 'contactform7',
			'label'     => __( 'Contact Form 7', 'lynbro-danadresse' ),
			'note'      => __( 'Auto-detects an address text field, or target it with a CSS selector below.', 'lynbro-danadresse' ),
			'composite' => false,
			'detect'    => static function () {
				return defined( 'WPCF7_VERSION' );
			},
		),
		array(
			'id'        => 'ninjaforms',
			'label'     => __( 'Ninja Forms', 'lynbro-danadresse' ),
			'note'      => __( 'Auto-detects an address text field, or target it with a CSS selector below.', 'lynbro-danadresse' ),
			'composite' => false,
			'detect'    => static function () {
				return class_exists( 'Ninja_Forms' );
			},
		),
		array(
			'id'        => 'elementor',
			'label'     => __( 'Elementor Forms', 'lynbro-danadresse' ),
			'note'      => __( 'Auto-detects an address text field, or target it with a CSS selector below.', 'lynbro-danadresse' ),
			'composite' => false,
			'detect'    => static function () {
				return defined( 'ELEMENTOR_PRO_VERSION' ) || defined( 'ELEMENTOR_VERSION' );
			},
		),
	);

	/**
	 * Filter the integration registry (e.g. to register a custom adapter).
	 *
	 * @param array<int,array<string,mixed>> $registry Integration entries.
	 */
	return apply_filters( 'lynbro_danadresse_integrations', $registry );
}

/**
 * The enabled/disabled map for integrations, merged over defaults (all on).
 *
 * @return array<string,bool>
 */
function lynbro_danadresse_integration_state() {
	$saved = (array) lynbro_danadresse_get_option( 'integrations', array() );
	$state = array();
	foreach ( lynbro_danadresse_integrations() as $it ) {
		$id = $it['id'];
		// Default: enabled — an integration the user has never seen (added in a
		// later version) should just work. Once the key exists, its stored value
		// decides; the sanitizer writes 0/1, so compare loosely rather than
		// against `false`, or unchecking a box would have no effect at all.
		$state[ $id ] = ! array_key_exists( $id, $saved ) || ! empty( $saved[ $id ] );
	}
	return $state;
}

/**
 * Whether WooCommerce autofill should run.
 *
 * The Integrations screen is the single source of truth. `enable_woo` is the
 * pre-registry option and survives only as a fallback for sites that were set
 * up before the toggles existed — reading it as the gate meant the checkbox on
 * screen could say "Enabled" while autofill was in fact off.
 *
 * @return bool
 */
function lynbro_danadresse_woo_enabled() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	// Read the saved map, not the computed state: the state map always contains
	// every registry id, so checking it would make the legacy branch unreachable.
	$saved = (array) lynbro_danadresse_get_option( 'integrations', array() );
	if ( array_key_exists( 'woocommerce', $saved ) ) {
		return ! empty( $saved['woocommerce'] );
	}

	// Installed before the toggles existed: the old standalone flag still decides,
	// so a site that had deliberately turned WooCommerce off stays off.
	return ! empty( lynbro_danadresse_get_option( 'enable_woo', 1 ) );
}

/**
 * Whether a target plugin is active on this site (best-effort detection).
 *
 * @param array<string,mixed> $integration One registry entry.
 * @return bool
 */
function lynbro_danadresse_integration_detected( $integration ) {
	if ( empty( $integration['detect'] ) || ! is_callable( $integration['detect'] ) ) {
		return false;
	}
	return (bool) call_user_func( $integration['detect'] );
}
