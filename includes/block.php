<?php
/**
 * Gutenberg block: lynbro-danadresse/address-field (dynamic, server-rendered).
 *
 * The editor script is plain JS (no build step) registered from
 * public/block-editor.js; the front-end output reuses the shortcode renderer.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the block and its editor script.
 *
 * @return void
 */
function lynbro_danadresse_register_block() {
	wp_register_script(
		'danadresse-block-editor',
		LYNBRO_DANADRESSE_URL . 'public/block-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		LYNBRO_DANADRESSE_VERSION,
		true
	);
	register_block_type(
		'lynbro-danadresse/address-field',
		array(
			'api_version'     => 3,
			'title'           => __( 'Danish address field', 'lynbro-danadresse' ),
			'description'     => __( 'A text field with typo-tolerant Danish address autocomplete.', 'lynbro-danadresse' ),
			'category'        => 'widgets',
			'icon'            => 'location-alt',
			'editor_script'   => 'danadresse-block-editor',
			'render_callback' => 'lynbro_danadresse_render_block',
			'attributes'      => array(
				'name'        => array(
					'type'    => 'string',
					'default' => 'lynbro_danadresse_address',
				),
				'label'       => array(
					'type'    => 'string',
					'default' => '',
				),
				'placeholder' => array(
					'type'    => 'string',
					'default' => '',
				),
				'required'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);
}
add_action( 'init', 'lynbro_danadresse_register_block' );

/**
 * Server render for the block.
 *
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function lynbro_danadresse_render_block( $attributes ) {
	$attributes = is_array( $attributes ) ? $attributes : array();

	return lynbro_danadresse_render_field(
		array(
			'name'        => isset( $attributes['name'] ) ? sanitize_key( (string) $attributes['name'] ) : 'lynbro_danadresse_address',
			'label'       => isset( $attributes['label'] ) && '' !== $attributes['label']
				? sanitize_text_field( (string) $attributes['label'] )
				: __( 'Address', 'lynbro-danadresse' ),
			'placeholder' => isset( $attributes['placeholder'] ) && '' !== $attributes['placeholder']
				? sanitize_text_field( (string) $attributes['placeholder'] )
				: __( 'Start typing a Danish address…', 'lynbro-danadresse' ),
			'required'    => ! empty( $attributes['required'] ) ? '1' : '',
		)
	);
}
