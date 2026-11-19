<?php
/**
 * Admin — About tab.
 *
 * What the plugin does, how it handles data, and where everything lives. Also
 * repeats the external-services disclosure from the readme, so an administrator
 * can see it without leaving WordPress.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the About tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_about() {
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'About Lynbro Danadresse Autofill', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'Danish customers type their address once and pick it from a list. The street, postcode and city land in the right fields, spelled the way the official register spells them — so parcels stop bouncing on a mistyped street name.', 'lynbro-danadresse' ); ?>
		</p>
		<p class="desc">
			<?php esc_html_e( 'It works on the WooCommerce checkout and on the address field of every major form builder, through adapters that know each plugin\'s markup. Nothing needs to be wired up by hand.', 'lynbro-danadresse' ); ?>
		</p>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'How your data is handled', 'lynbro-danadresse' ); ?></h2>
		<ul class="lynbro-danadresse-list">
			<li><?php esc_html_e( 'Your API key stays on your server. It is stored as a password field, never printed back into a page, and never sent to a visitor\'s browser.', 'lynbro-danadresse' ); ?></li>
			<li><?php esc_html_e( 'Lookups are proxied through your own site, so no visitor IP address is passed on to the address service.', 'lynbro-danadresse' ); ?></li>
			<li><?php esc_html_e( 'Responses are cached only for as long as the service says they stay fresh, and never longer.', 'lynbro-danadresse' ); ?></li>
			<li><?php esc_html_e( 'The problem log keeps error codes only. Addresses your customers type are never written to your database by this plugin.', 'lynbro-danadresse' ); ?></li>
			<li><?php esc_html_e( 'No address data is bundled or downloaded in bulk — every suggestion is a live lookup.', 'lynbro-danadresse' ); ?></li>
		</ul>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'External service', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'This plugin talks to one service: the danadresse.dk address API (api.danadresse.dk), operated by LynBro ApS, Denmark. It receives the address text being typed — plus, when validation is on, the delivery address of an order — together with your API key, sent from your server. Without a key configured, the plugin makes no external requests at all.', 'lynbro-danadresse' ); ?>
		</p>
		<p class="lynbro-tools-row">
			<a class="button" href="https://danadresse.dk/terms" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Terms', 'lynbro-danadresse' ); ?></a>
			<a class="button" href="https://danadresse.dk/privacy" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Version & links', 'lynbro-danadresse' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Version', 'lynbro-danadresse' ); ?></th>
				<td><code><?php echo esc_html( LYNBRO_DANADRESSE_VERSION ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Address data', 'lynbro-danadresse' ); ?></th>
				<td><?php esc_html_e( 'Danish national address register (DAWA-compatible), via danadresse.dk', 'lynbro-danadresse' ); ?></td>
			</tr>
		</table>
		<p class="lynbro-tools-row">
			<a class="button button-primary" href="https://plugins.lynbro.dk/lynbro-danadresse" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Plugin home page', 'lynbro-danadresse' ); ?></a>
			<a class="button" href="https://danadresse.dk" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'danadresse.dk', 'lynbro-danadresse' ); ?></a>
			<a class="button" href="https://lynbro.dk" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Lynbro', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>
	<?php
}
