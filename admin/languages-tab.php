<?php
/**
 * Admin — Languages tab.
 *
 * Shows the translations WordPress has actually installed for this plugin, read
 * from WP_LANG_DIR. It deliberately does NOT look inside the plugin folder:
 * wordpress.org serves translations from translate.wordpress.org and asks that
 * .po/.mo files stay out of the package, so the plugin's own languages folder
 * holds only the .pot template on a real install. Counting that folder would
 * report a number that has nothing to do with what the site can display.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Locales installed for this text domain, from WordPress's language directory.
 *
 * @return array<int,string>
 */
function lynbro_danadresse_installed_locales() {
	$dir = WP_LANG_DIR . '/plugins';
	if ( ! is_dir( $dir ) ) {
		return array();
	}

	$found = glob( $dir . '/' . LYNBRO_DANADRESSE_SLUG . '-*.mo' );
	if ( ! is_array( $found ) ) {
		return array();
	}

	$locales = array();
	foreach ( $found as $file ) {
		$name      = basename( $file, '.mo' );
		$locales[] = substr( $name, strlen( LYNBRO_DANADRESSE_SLUG . '-' ) );
	}
	sort( $locales );
	return $locales;
}

/**
 * Render the Languages tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_languages() {
	$locales = lynbro_danadresse_installed_locales();
	$current = get_user_locale();
	$site    = get_locale();
	// Whether strings really are translated right now. Inferring this from one
	// directory would be wrong — WordPress loads a .mo from WP_LANG_DIR or from
	// the plugin's own folder, and only it knows which one won.
	$active  = 'en_US' === $current || is_textdomain_loaded( 'lynbro-danadresse' );
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Languages', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'Everything the plugin shows — these settings screens and the suggestion list your customers see — follows the language WordPress is running in. There is nothing to set up here.', 'lynbro-danadresse' ); ?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Site language', 'lynbro-danadresse' ); ?></th>
				<td><code><?php echo esc_html( $site ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Your language', 'lynbro-danadresse' ); ?></th>
				<td>
					<code><?php echo esc_html( $current ); ?></code>
					<?php if ( $active ) : ?>
						<span class="lynbro-badge installed"><?php esc_html_e( 'Translated', 'lynbro-danadresse' ); ?></span>
					<?php else : ?>
						<span class="lynbro-badge soon"><?php esc_html_e( 'Falls back to English', 'lynbro-danadresse' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>

	<div class="lynbro-section">
		<h2>
			<?php
			printf(
				/* translators: %s: number of translations installed on this site. */
				esc_html__( 'Installed on this site (%s)', 'lynbro-danadresse' ),
				esc_html( number_format_i18n( count( $locales ) ) )
			);
			?>
		</h2>
		<p class="desc">
			<?php esc_html_e( 'WordPress downloads translations from translate.wordpress.org and keeps them up to date for you — they are not shipped inside the plugin. Whatever is missing simply falls back to English.', 'lynbro-danadresse' ); ?>
		</p>

		<?php if ( $locales ) : ?>
			<p class="lynbro-danadresse-locales">
				<?php foreach ( $locales as $locale ) : ?>
					<code class="<?php echo $locale === $current ? 'is-active' : ''; ?>"><?php echo esc_html( $locale ); ?></code>
				<?php endforeach; ?>
			</p>
		<?php else : ?>
			<p class="lynbro-muted">
				<?php esc_html_e( 'None yet. WordPress fetches them automatically once translations for your language are available; you can also trigger it from Dashboard → Updates.', 'lynbro-danadresse' ); ?>
			</p>
		<?php endif; ?>

		<p class="lynbro-muted">
			<?php esc_html_e( 'Spotted a wording that could be better in your language? Translations are community-maintained — you can improve them directly on translate.wordpress.org.', 'lynbro-danadresse' ); ?>
			<a href="https://translate.wordpress.org/projects/wp-plugins/lynbro-danadresse/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the translation project', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>
	<?php
}
