/**
 * Danadresse — "Danish address field" block (editor side, no build step).
 * The front end is server-rendered via render_callback.
 */
(function (wp) {
	'use strict';

	if (!wp || !wp.blocks) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;

	wp.blocks.registerBlockType('lynbro-danadresse/address-field', {
		title: __('Danish address field', 'lynbro-danadresse'),
		description: __('A text field with typo-tolerant Danish address autocomplete.', 'lynbro-danadresse'),
		category: 'widgets',
		icon: 'location-alt',
		attributes: {
			name: { type: 'string', default: 'lynbro_danadresse_address' },
			label: { type: 'string', default: '' },
			placeholder: { type: 'string', default: '' },
			required: { type: 'boolean', default: false }
		},
		edit: function (props) {
			var attrs = props.attributes;
			var blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Field settings', 'lynbro-danadresse') },
						el(TextControl, {
							label: __('Field name', 'lynbro-danadresse'),
							value: attrs.name,
							onChange: function (v) { props.setAttributes({ name: v }); }
						}),
						el(TextControl, {
							label: __('Label', 'lynbro-danadresse'),
							value: attrs.label,
							onChange: function (v) { props.setAttributes({ label: v }); }
						}),
						el(TextControl, {
							label: __('Placeholder', 'lynbro-danadresse'),
							value: attrs.placeholder,
							onChange: function (v) { props.setAttributes({ placeholder: v }); }
						}),
						el(ToggleControl, {
							label: __('Required', 'lynbro-danadresse'),
							checked: !!attrs.required,
							onChange: function (v) { props.setAttributes({ required: !!v }); }
						})
					)
				),
				el('label', { className: 'danadresse-label', style: { display: 'block', fontWeight: 600, marginBottom: '4px' } },
					attrs.label || __('Address', 'lynbro-danadresse')),
				el('input', {
					type: 'text',
					disabled: true,
					placeholder: attrs.placeholder || __('Start typing a Danish address…', 'lynbro-danadresse'),
					style: { width: '100%', maxWidth: '480px' }
				})
			);
		},
		save: function () {
			return null; // Dynamic block — rendered in PHP.
		}
	});
})(window.wp);
