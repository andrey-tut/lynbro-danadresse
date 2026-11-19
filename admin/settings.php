<?php
/**
 * Admin settings page (Lynbro → Danadresse).
 *
 * Tabs: Settings · Integrations · Status · Statistics · Log · Languages ·
 * Tools · Feedback · About. Settings API handles nonces/sanitization; the
 * Status tab includes a live test lookup so the owner can verify the API key
 * without leaving the admin.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the setting.
 *
 * @return void
 */
function lynbro_danadresse_register_settings() {
	register_setting(
		'lynbro_danadresse_group',
		LYNBRO_DANADRESSE_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lynbro_danadresse_sanitize_options',
			'default'           => lynbro_danadresse_default_options(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'lynbro_danadresse_register_settings' );

/**
 * Attach the page under the shared Lynbro menu (registered by the shell).
 *
 * @return void
 */
function lynbro_danadresse_add_settings_page() {
	Lynbro_Admin_Menu::add(
		array(
			'slug'       => 'lynbro-danadresse',
			'page_title' => __( 'Danadresse', 'lynbro-danadresse' ),
			'menu_title' => __( 'Danadresse', 'lynbro-danadresse' ),
			'capability' => 'manage_options',
			'callback'   => 'lynbro_danadresse_render_settings_page',
		)
	);
}
add_action( 'admin_menu', 'lynbro_danadresse_add_settings_page' );

/**
 * Enqueue admin styles and the small test-lookup script, on our page only.
 *
 * @param string $hook Admin page hook suffix.
 * @return void
 */
function lynbro_danadresse_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'lynbro-danadresse' ) ) {
		return;
	}

	wp_enqueue_style(
		'danadresse-admin',
		LYNBRO_DANADRESSE_URL . 'admin/admin.css',
		array(),
		LYNBRO_DANADRESSE_VERSION
	);

	wp_enqueue_script(
		'danadresse-admin',
		LYNBRO_DANADRESSE_URL . 'public/admin.js',
		array(),
		LYNBRO_DANADRESSE_VERSION,
		true
	);
	wp_localize_script(
		'danadresse-admin',
		'lynbroDanadresseAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'lynbro_danadresse_admin' ),
			'labels'  => array(
				'empty'   => __( 'Type an address first.', 'lynbro-danadresse' ),
				'working' => __( 'Looking up…', 'lynbro-danadresse' ),
				'none'    => __( 'No suggestions returned.', 'lynbro-danadresse' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'lynbro_danadresse_admin_assets' );

/**
 * Current tab helper.
 *
 * @return string
 */
function lynbro_danadresse_current_tab() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch.
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
	$tabs = array( 'settings', 'integrations', 'status', 'statistics', 'log', 'languages', 'tools', 'feedback', 'about' );
	return in_array( $tab, $tabs, true ) ? $tab : 'settings';
}

/**
 * Render the page.
 *
 * @return void
 */
function lynbro_danadresse_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lynbro-danadresse' ) );
	}

	$tab  = lynbro_danadresse_current_tab();
	$base = admin_url( 'admin.php?page=lynbro-danadresse' );
	$tabs = array(
		'settings'     => __( 'Settings', 'lynbro-danadresse' ),
		'integrations' => __( 'Integrations', 'lynbro-danadresse' ),
		'status'       => __( 'Status', 'lynbro-danadresse' ),
		'statistics'   => __( 'Statistics', 'lynbro-danadresse' ),
		'log'          => __( 'Log', 'lynbro-danadresse' ),
		'languages'    => __( 'Languages', 'lynbro-danadresse' ),
		'tools'        => __( 'Tools', 'lynbro-danadresse' ),
		'feedback'     => __( 'Feedback', 'lynbro-danadresse' ),
		'about'        => __( 'About', 'lynbro-danadresse' ),
	);
	?>
	<div class="wrap lynbro-wrap">
		<div class="lynbro-hero">
			<div>
				<h1><?php esc_html_e( 'Lynbro Danadresse Autofill', 'lynbro-danadresse' ); ?></h1>
				<p><?php esc_html_e( 'Typo-tolerant Danish address autocomplete & validation that autofills WooCommerce and every major form builder — powered by danadresse.dk.', 'lynbro-danadresse' ); ?></p>
			</div>
		</div>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'tab', $key, $base ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php
		switch ( $tab ) {
			case 'integrations':
				lynbro_danadresse_render_tab_integrations();
				break;
			case 'status':
				lynbro_danadresse_render_tab_status();
				break;
			case 'statistics':
				lynbro_danadresse_render_tab_statistics();
				break;
			case 'log':
				lynbro_danadresse_render_tab_log();
				break;
			case 'languages':
				lynbro_danadresse_render_tab_languages();
				break;
			case 'feedback':
				lynbro_danadresse_render_tab_feedback();
				break;
			case 'about':
				lynbro_danadresse_render_tab_about();
				break;
			case 'tools':
				if ( function_exists( 'lynbro_render_tools_section' ) ) {
					lynbro_render_tools_section(
						array( LYNBRO_DANADRESSE_OPTION, LYNBRO_DANADRESSE_STATS_OPTION, LYNBRO_DANADRESSE_LOG_OPTION, 'lynbro_danadresse_version', 'lynbro_danadresse_last_error', 'lynbro_danadresse_installed_at' ),
						'lynbro-danadresse'
					);
				}
				break;
			default:
				lynbro_danadresse_render_tab_settings();
		}

		// Rendered here, on our own screen, rather than through admin_notices —
		// see includes/onboarding.php.
		if ( function_exists( 'lynbro_danadresse_render_review_prompt' ) ) {
			lynbro_danadresse_render_review_prompt();
		}
		?>
	</div>
	<?php
}

/**
 * Settings tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_settings() {
	$options = lynbro_danadresse_get_options();
	$opt     = LYNBRO_DANADRESSE_OPTION;
	$has_key = '' !== (string) $options['api_key'];
	$masked  = $has_key ? str_repeat( '•', 12 ) : '';
	?>
	<?php if ( ! $has_key ) : ?>
		<div class="lynbro-section lynbro-danadresse-getstarted">
			<h2><?php esc_html_e( 'Get started in 30 seconds', 'lynbro-danadresse' ); ?></h2>
			<p class="desc">
				<?php esc_html_e( 'Address autofill needs a free danadresse.dk API key — 2,000 lookups every month, no card required. Create your key, then paste it in the field below and save.', 'lynbro-danadresse' ); ?>
			</p>
			<p class="lynbro-tools-row">
				<a class="button button-primary" href="https://danadresse.dk/dashboard/signup" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a free API key →', 'lynbro-danadresse' ); ?></a>
				<a class="button" href="https://danadresse.dk/dashboard/keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'I already have an account', 'lynbro-danadresse' ); ?></a>
			</p>
			<p class="lynbro-muted"><?php esc_html_e( 'Need more than 2,000 lookups a month? Larger plans are available on danadresse.dk — the plugin itself is always free.', 'lynbro-danadresse' ); ?></p>
		</div>
	<?php endif; ?>
	<form action="options.php" method="post">
		<?php settings_fields( 'lynbro_danadresse_group' ); ?>
		<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[_tab]" value="settings" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="danadresse-key"><?php esc_html_e( 'API key', 'lynbro-danadresse' ); ?></label></th>
				<td>
					<input type="password" id="danadresse-key" class="regular-text"
						name="<?php echo esc_attr( $opt ); ?>[api_key]"
						value="<?php echo esc_attr( $masked ); ?>"
						autocomplete="new-password" />
					<p class="description">
						<?php esc_html_e( 'Get a free API key (2,000 lookups/month) from your danadresse.dk dashboard. The key is stored on your server and never sent to visitors’ browsers.', 'lynbro-danadresse' ); ?>
						<a href="https://danadresse.dk/dashboard/keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open danadresse.dk dashboard', 'lynbro-danadresse' ); ?></a>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="danadresse-tier"><?php esc_html_e( 'Suggestion detail', 'lynbro-danadresse' ); ?></label></th>
				<td>
					<select id="danadresse-tier" name="<?php echo esc_attr( $opt ); ?>[tier]">
						<option value="adresse" <?php selected( $options['tier'], 'adresse' ); ?>><?php esc_html_e( 'Full address incl. floor/door (recommended for delivery)', 'lynbro-danadresse' ); ?></option>
						<option value="adgangsadresse" <?php selected( $options['tier'], 'adgangsadresse' ); ?>><?php esc_html_e( 'Street entrance only (no floor/door)', 'lynbro-danadresse' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Typo tolerance', 'lynbro-danadresse' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[fuzzy]" value="1" <?php checked( ! empty( $options['fuzzy'] ) ); ?> />
						<?php esc_html_e( 'Fuzzy matching (also folds oe↔ø, aa↔å, ae↔æ)', 'lynbro-danadresse' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="danadresse-max"><?php esc_html_e( 'Max suggestions', 'lynbro-danadresse' ); ?></label></th>
				<td>
					<input type="number" id="danadresse-max" min="3" max="20" step="1" class="small-text"
						name="<?php echo esc_attr( $opt ); ?>[max_items]"
						value="<?php echo esc_attr( (string) $options['max_items'] ); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="danadresse-min"><?php esc_html_e( 'Minimum characters', 'lynbro-danadresse' ); ?></label></th>
				<td>
					<input type="number" id="danadresse-min" min="1" max="6" step="1" class="small-text"
						name="<?php echo esc_attr( $opt ); ?>[min_chars]"
						value="<?php echo esc_attr( (string) $options['min_chars'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Lookups start after this many typed characters.', 'lynbro-danadresse' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
	<?php
}

/**
 * Integrations tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_integrations() {
	$options = lynbro_danadresse_get_options();
	$opt     = LYNBRO_DANADRESSE_OPTION;
	$state   = lynbro_danadresse_integration_state();
	?>
	<form action="options.php" method="post">
		<?php settings_fields( 'lynbro_danadresse_group' ); ?>
		<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[_tab]" value="integrations" />

		<div class="lynbro-section">
			<h2><?php esc_html_e( 'Where autofill runs', 'lynbro-danadresse' ); ?></h2>
			<p class="desc"><?php esc_html_e( 'Turn address autofill on for the plugins you use. Plugins detected as active on this site are marked. Composite address fields (WooCommerce, WPForms, Gravity, Fluent, Formidable, EDD, GiveWP, Forminator, Everest Forms, Paid Memberships Pro) also fill postcode and city automatically.', 'lynbro-danadresse' ); ?></p>
			<div class="lynbro-grid">
				<?php foreach ( lynbro_danadresse_integrations() as $it ) : ?>
					<?php
					$id       = $it['id'];
					$detected = lynbro_danadresse_integration_detected( $it );
					$on       = ! empty( $state[ $id ] );
					?>
					<div class="lynbro-card">
						<div class="head">
							<div>
								<h3><?php echo esc_html( $it['label'] ); ?></h3>
								<?php if ( $detected ) : ?>
									<span class="lynbro-badge installed"><?php esc_html_e( 'Active on this site', 'lynbro-danadresse' ); ?></span>
								<?php else : ?>
									<span class="lynbro-badge soon"><?php esc_html_e( 'Not detected', 'lynbro-danadresse' ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<p><?php echo esc_html( $it['note'] ); ?></p>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[integrations][<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( $on ); ?> />
							<?php esc_html_e( 'Enabled', 'lynbro-danadresse' ); ?>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-detect', 'lynbro-danadresse' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[auto_detect]" value="1" <?php checked( ! empty( $options['auto_detect'] ) ); ?> />
						<?php esc_html_e( 'Also attach to any text field that looks like an address (by label, name or placeholder)', 'lynbro-danadresse' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Catches address fields in themes and form plugins without a dedicated adapter. Turn off if it attaches to a field it should not.', 'lynbro-danadresse' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Denmark only', 'lynbro-danadresse' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[dk_only]" value="1" <?php checked( ! empty( $options['dk_only'] ) ); ?> />
						<?php esc_html_e( 'Only suggest when the selected country is Denmark (or the form has no country field)', 'lynbro-danadresse' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Address validation', 'lynbro-danadresse' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_datavask]" value="1" <?php checked( ! empty( $options['enable_datavask'] ) ); ?> />
						<?php esc_html_e( 'Validate Danish delivery addresses at WooCommerce checkout (result saved on the order)', 'lynbro-danadresse' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[datavask_block_c]" value="1" <?php checked( ! empty( $options['datavask_block_c'] ) ); ?> />
						<?php esc_html_e( 'Block checkout when the address is not recognised at all', 'lynbro-danadresse' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Validation never blocks checkout when the address service is unreachable.', 'lynbro-danadresse' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="danadresse-selectors"><?php esc_html_e( 'Custom CSS selectors', 'lynbro-danadresse' ); ?></label></th>
				<td>
					<textarea id="danadresse-selectors" class="large-text code" rows="4"
						name="<?php echo esc_attr( $opt ); ?>[selectors]"
						placeholder="<?php echo esc_attr( '#my-address-input' . "\n" . '.wpcf7 input[name="your-address"]' ); ?>"><?php echo esc_textarea( (string) $options['selectors'] ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'One CSS selector per line for any address field not covered above (e.g. a single Contact Form 7 or Elementor field). Inputs with a data-lynbro-danadresse attribute or the lynbro-danadresse-field class attach automatically.', 'lynbro-danadresse' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Shortcode & block', 'lynbro-danadresse' ); ?></th>
				<td>
					<code>[lynbro_danadresse_field name="address" label="Address" required="1"]</code>
					<p class="description"><?php esc_html_e( 'Or add the “Danish address field” block in the editor.', 'lynbro-danadresse' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
	<?php
}

/**
 * Status tab: key check, test lookup, 30-day usage.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_status() {
	$options = lynbro_danadresse_get_options();
	$has_key = '' !== (string) $options['api_key'];
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Service status', 'lynbro-danadresse' ); ?></h2>
		<p>
			<?php if ( $has_key ) : ?>
				<span class="lynbro-badge installed"><?php esc_html_e( 'API key configured', 'lynbro-danadresse' ); ?></span>
			<?php else : ?>
				<span class="lynbro-badge soon"><?php esc_html_e( 'No API key', 'lynbro-danadresse' ); ?></span>
				<a href="https://danadresse.dk/dashboard/signup" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a free key at danadresse.dk', 'lynbro-danadresse' ); ?></a>
			<?php endif; ?>
		</p>
		<p class="lynbro-muted"><?php esc_html_e( 'Quota and plan details live in your danadresse.dk dashboard.', 'lynbro-danadresse' ); ?>
			<a href="https://danadresse.dk/dashboard/usage" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open usage', 'lynbro-danadresse' ); ?></a>
		</p>

		<?php if ( 'key_blocked' === get_option( 'lynbro_danadresse_last_error', '' ) ) : ?>
			<div class="notice notice-warning inline lynbro-danadresse-notice">
				<p><strong><?php esc_html_e( 'This API key is blocked', 'lynbro-danadresse' ); ?></strong></p>
				<p><?php esc_html_e( 'The key was used from more devices than your plan allows. Only the account owner can unlock it, from the danadresse.dk dashboard — for security it cannot be done from here.', 'lynbro-danadresse' ); ?></p>
				<p>
					<a class="button button-primary" href="https://danadresse.dk/dashboard/keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Unlock in danadresse.dk dashboard', 'lynbro-danadresse' ); ?></a>
				</p>
				<p class="lynbro-muted"><?php esc_html_e( 'In the dashboard: Indstillinger → nøgler → “Skift enhed / Lås op”. Lookups resume on the next request.', 'lynbro-danadresse' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Test lookup', 'lynbro-danadresse' ); ?></h2>
		<p class="desc"><?php esc_html_e( 'Runs a live request through your API key — a quick way to confirm everything works.', 'lynbro-danadresse' ); ?></p>
		<p>
			<input type="text" id="danadresse-test-q" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Rådhuspladsen 1', 'lynbro-danadresse' ); ?>" />
			<button type="button" class="button button-primary" id="danadresse-test-ac"><?php esc_html_e( 'Autocomplete', 'lynbro-danadresse' ); ?></button>
			<button type="button" class="button" id="danadresse-test-dv"><?php esc_html_e( 'Validate (datavask)', 'lynbro-danadresse' ); ?></button>
		</p>
		<div id="danadresse-test-out" class="lynbro-muted" aria-live="polite"></div>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Usage', 'lynbro-danadresse' ); ?></h2>
		<p class="desc"><?php esc_html_e( 'Day-by-day lookup counts recorded on this site are on the Statistics screen; anything that failed is on the Log screen.', 'lynbro-danadresse' ); ?></p>
		<p class="lynbro-tools-row">
			<a class="button" href="<?php echo esc_url( add_query_arg( 'tab', 'statistics', admin_url( 'admin.php?page=lynbro-danadresse' ) ) ); ?>"><?php esc_html_e( 'Statistics', 'lynbro-danadresse' ); ?></a>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'tab', 'log', admin_url( 'admin.php?page=lynbro-danadresse' ) ) ); ?>"><?php esc_html_e( 'Log', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Declare which options this screen owns, at load time.
 *
 * The Tools export/reset handlers run on admin-post.php, where this settings
 * page never renders — so the declaration cannot wait for render. It also must
 * not travel in the request: every Lynbro plugin shares the option prefix, so a
 * request-supplied list would let one plugin's Tools form read or delete
 * another's settings.
 *
 * @param array<int,string> $keys Already-declared option names.
 * @param string            $page Page slug being acted on.
 * @return array<int,string>
 */
function lynbro_danadresse_tools_option_keys( $keys, $page ) {
	if ( 'lynbro-danadresse' !== $page ) {
		return $keys;
	}
	return array_merge( (array) $keys, array( LYNBRO_DANADRESSE_OPTION, LYNBRO_DANADRESSE_STATS_OPTION, LYNBRO_DANADRESSE_LOG_OPTION, 'lynbro_danadresse_version', 'lynbro_danadresse_last_error', 'lynbro_danadresse_installed_at' ) );
}
add_filter( 'lynbro_tools_option_keys', 'lynbro_danadresse_tools_option_keys', 10, 2 );
