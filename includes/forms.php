<?php
/**
 * Generic form integrations: shortcode + auto-attach markup contract.
 *
 * Any input matching a configured CSS selector, carrying a
 * `data-danadresse` attribute or the `danadresse-field` class gets the
 * autocomplete. The shortcode below renders such an input, and the
 * Gutenberg block reuses the same renderer.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a ready-to-use address input.
 *
 * @param array<string,string> $args {
 *     Field arguments.
 *
 *     @type string $name        Input name attribute.
 *     @type string $label       Visible label ('' hides it).
 *     @type string $placeholder Placeholder text.
 *     @type string $value       Initial value.
 *     @type string $required    '1' to mark required.
 *     @type string $class       Extra CSS classes for the input.
 * }
 * @return string HTML.
 */
function lynbro_danadresse_render_field( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'name'        => 'lynbro_danadresse_address',
			'label'       => __( 'Address', 'lynbro-danadresse' ),
			'placeholder' => __( 'Start typing a Danish address…', 'lynbro-danadresse' ),
			'value'       => '',
			'required'    => '',
			'class'       => '',
		)
	);

	$id = 'danadresse-' . sanitize_html_class( sanitize_key( $args['name'] ) );

	$html = '<div class="danadresse-field-wrap">';
	if ( '' !== $args['label'] ) {
		$html .= sprintf(
			'<label class="danadresse-label" for="%1$s">%2$s</label>',
			esc_attr( $id ),
			esc_html( $args['label'] )
		);
	}
	$html .= sprintf(
		'<input type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="lynbro-danadresse-field danadresse-field %5$s" autocomplete="off" data-lynbro-danadresse="1"%6$s />',
		esc_attr( $id ),
		esc_attr( $args['name'] ),
		esc_attr( $args['value'] ),
		esc_attr( $args['placeholder'] ),
		esc_attr( $args['class'] ),
		$args['required'] ? ' required' : ''
	);
	$html .= '</div>';

	return $html;
}

/**
 * Shortcode: [lynbro_danadresse_field name="address" label="Address" placeholder="…" required="1" class=""].
 *
 * @param array<string,string>|string $atts Shortcode attributes.
 * @return string
 */
function lynbro_danadresse_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'name'        => 'lynbro_danadresse_address',
			'label'       => __( 'Address', 'lynbro-danadresse' ),
			'placeholder' => __( 'Start typing a Danish address…', 'lynbro-danadresse' ),
			'value'       => '',
			'required'    => '',
			'class'       => '',
		),
		$atts,
		'lynbro_danadresse_field'
	);

	return lynbro_danadresse_render_field(
		array(
			'name'        => sanitize_key( $atts['name'] ),
			'label'       => sanitize_text_field( $atts['label'] ),
			'placeholder' => sanitize_text_field( $atts['placeholder'] ),
			'value'       => sanitize_text_field( $atts['value'] ),
			'required'    => $atts['required'] ? '1' : '',
			'class'       => implode( ' ', array_map( 'sanitize_html_class', explode( ' ', (string) $atts['class'] ) ) ),
		)
	);
}
add_shortcode( 'lynbro_danadresse_field', 'lynbro_danadresse_shortcode' );
