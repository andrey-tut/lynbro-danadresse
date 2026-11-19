<?php
/**
 * WooCommerce integration.
 *
 * Front end: the widget script attaches itself to the classic checkout,
 * Checkout Block and My Account address fields (selectors live in
 * public/autocomplete.js). This file adds the server-side pieces: optional
 * address validation (datavask) at checkout and the validation badge on
 * orders in the admin.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compose the one-line address that gets validated.
 *
 * Deliberately drops any floor/door part: validation answers "does this
 * building exist at this postcode", and a unit suffix only gets in the way.
 * Measured against the live service — the same address scores 100 (kategori A)
 * without the unit and 18–23 (kategori C) with it, whichever way it is written:
 *
 *   "Nyhavn 17, 1051 København K"           → A / 100
 *   "Nyhavn 17, kl. tv, 1051 København K"   → C / 20
 *
 * Since the checkout field legitimately holds "Nyhavn 17, kl. tv" — that is
 * what our own autofill writes — keeping the suffix would flag perfectly valid
 * deliveries as unrecognised, and would block them outright for shops that
 * turn on "block checkout when the address is not recognised".
 *
 * @param string $address_1 Street + number, possibly with a floor/door suffix.
 * @param string $postcode  Postal code.
 * @param string $city      City.
 * @return string
 */
function lynbro_danadresse_compose_betegnelse( $address_1, $postcode, $city ) {
	$street = trim( (string) $address_1 );

	// Everything before the first comma is street + house number; anything
	// after it is the unit (", kl. tv", ", 1. th", ", st.").
	$comma = strpos( $street, ',' );
	if ( false !== $comma ) {
		$street = trim( substr( $street, 0, $comma ) );
	}

	$tail = trim( trim( (string) $postcode ) . ' ' . trim( (string) $city ) );
	if ( '' === $street ) {
		return $tail;
	}
	return ( '' !== $tail ) ? $street . ', ' . $tail : $street;
}

/**
 * Classic checkout: wash the shipping (or billing) address when enabled.
 *
 * Kategori A = exact match, B = close match, C = not recognised. By default
 * this is informational only (saved on the order); optionally C blocks
 * checkout with a friendly error.
 *
 * @param array<string,mixed> $data   Posted checkout data.
 * @param WP_Error            $errors Checkout validation errors.
 * @return void
 */
function lynbro_danadresse_checkout_validate( $data, $errors ) {
	if ( ! lynbro_danadresse_get_option( 'enable_datavask' ) ) {
		return;
	}

	$use_shipping = ! empty( $data['ship_to_different_address'] );
	$prefix       = $use_shipping ? 'shipping' : 'billing';

	$country = isset( $data[ $prefix . '_country' ] ) ? (string) $data[ $prefix . '_country' ] : '';
	if ( 'DK' !== strtoupper( $country ) ) {
		return;
	}

	$betegnelse = lynbro_danadresse_compose_betegnelse(
		isset( $data[ $prefix . '_address_1' ] ) ? (string) $data[ $prefix . '_address_1' ] : '',
		isset( $data[ $prefix . '_postcode' ] ) ? (string) $data[ $prefix . '_postcode' ] : '',
		isset( $data[ $prefix . '_city' ] ) ? (string) $data[ $prefix . '_city' ] : ''
	);

	if ( '' === trim( $betegnelse, ' ,' ) ) {
		return;
	}

	$washed = lynbro_danadresse_datavask( $betegnelse );
	if ( ! $washed['success'] ) {
		return; // Service unavailable — never block checkout on our own outage.
	}

	// Stash for the order-created hook below (per-request only).
	$GLOBALS['lynbro_danadresse_last_wash'] = $washed;

	if ( 'C' === $washed['kategori'] && lynbro_danadresse_get_option( 'datavask_block_c' ) && is_wp_error( $errors ) ) {
		$errors->add(
			'lynbro_danadresse_unknown_address',
			__( 'We could not recognise this Danish address. Please check the street, number and postal code.', 'lynbro-danadresse' )
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'lynbro_danadresse_checkout_validate', 10, 2 );

/**
 * Persist the wash result on the order (classic checkout).
 *
 * @param WC_Order $order Order object.
 * @return void
 */
function lynbro_danadresse_save_order_meta( $order ) {
	if ( empty( $GLOBALS['lynbro_danadresse_last_wash'] ) || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}

	$washed = $GLOBALS['lynbro_danadresse_last_wash'];

	$order->update_meta_data( '_danadresse_kategori', $washed['kategori'] );
	if ( ! empty( $washed['adresse']['id'] ) ) {
		$order->update_meta_data( '_danadresse_adresse_id', $washed['adresse']['id'] );
	}
}
add_action( 'woocommerce_checkout_create_order', 'lynbro_danadresse_save_order_meta', 10, 1 );

/**
 * Store API (Checkout Block): wash the address when the order is processed.
 *
 * @param WC_Order $order Order object.
 * @return void
 */
function lynbro_danadresse_store_api_order( $order ) {
	if ( ! lynbro_danadresse_get_option( 'enable_datavask' ) || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}

	$address = $order->get_shipping_address_1() ? array(
		'address_1' => $order->get_shipping_address_1(),
		'postcode'  => $order->get_shipping_postcode(),
		'city'      => $order->get_shipping_city(),
		'country'   => $order->get_shipping_country(),
	) : array(
		'address_1' => $order->get_billing_address_1(),
		'postcode'  => $order->get_billing_postcode(),
		'city'      => $order->get_billing_city(),
		'country'   => $order->get_billing_country(),
	);

	if ( 'DK' !== strtoupper( (string) $address['country'] ) ) {
		return;
	}

	$washed = lynbro_danadresse_datavask(
		lynbro_danadresse_compose_betegnelse( $address['address_1'], $address['postcode'], $address['city'] )
	);
	if ( ! $washed['success'] ) {
		return;
	}

	$order->update_meta_data( '_danadresse_kategori', $washed['kategori'] );
	if ( ! empty( $washed['adresse']['id'] ) ) {
		$order->update_meta_data( '_danadresse_adresse_id', $washed['adresse']['id'] );
	}
	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'lynbro_danadresse_store_api_order', 10, 1 );

/**
 * Admin: show the validation badge next to the billing address on the order screen.
 *
 * @param WC_Order $order Order object.
 * @return void
 */
function lynbro_danadresse_admin_order_badge( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return;
	}

	$kategori = (string) $order->get_meta( '_danadresse_kategori' );
	if ( '' === $kategori ) {
		return;
	}

	$labels = array(
		'A' => __( 'Address verified (exact match)', 'lynbro-danadresse' ),
		'B' => __( 'Address verified (close match)', 'lynbro-danadresse' ),
		'C' => __( 'Address not recognised', 'lynbro-danadresse' ),
	);
	$label  = isset( $labels[ $kategori ] ) ? $labels[ $kategori ] : $kategori;
	$class  = ( 'C' === $kategori ) ? 'danadresse-badge is-c' : 'danadresse-badge';

	printf(
		'<p class="%1$s"><strong>Danadresse:</strong> %2$s</p>',
		esc_attr( $class ),
		esc_html( $label )
	);
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'lynbro_danadresse_admin_order_badge', 10, 1 );
