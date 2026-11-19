<?php
/**
 * Plugin Name:       Lynbro Danadresse
 * Plugin URI:        https://plugins.lynbro.dk/lynbro-danadresse
 * Description:       Typo-tolerant Danish address autocomplete and validation (DAWA-compatible) that autofills WooCommerce and every major form builder — powered by danadresse.dk.
 * Version:           0.4.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Lynbro
 * Author URI:        https://lynbro.dk
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lynbro-danadresse
 * Domain Path:       /languages
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

define( 'LYNBRO_DANADRESSE_VERSION', '0.4.5' );
define( 'LYNBRO_DANADRESSE_FILE', __FILE__ );
define( 'LYNBRO_DANADRESSE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LYNBRO_DANADRESSE_URL', plugin_dir_url( __FILE__ ) );
define( 'LYNBRO_DANADRESSE_SLUG', 'lynbro-danadresse' );

/**
 * Base URL of the danadresse.dk API (filterable for self-hosted mirrors).
 */
define( 'LYNBRO_DANADRESSE_API_BASE', 'https://api.danadresse.dk' );

// The shared Lynbro admin shell. Required at file scope, not inside the
// bootstrap below: every vendored copy has to announce itself before
// plugins_loaded priority 1, which is where the newest one is chosen.
require_once LYNBRO_DANADRESSE_DIR . 'includes/lynbro-core/lynbro-shell.php';

/**
 * Declare compatibility with WooCommerce HPOS and Cart/Checkout Blocks.
 * Runs only when WooCommerce is present; the plugin itself does not
 * require WooCommerce.
 *
 * @return void
 */
function lynbro_danadresse_declare_wc_compatibility() {
	if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}
add_action( 'before_woocommerce_init', 'lynbro_danadresse_declare_wc_compatibility' );

/**
 * Activation: seed default options.
 *
 * @return void
 */
function lynbro_danadresse_activate() {
	require_once LYNBRO_DANADRESSE_DIR . 'includes/options.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/onboarding.php';

	add_option( 'lynbro_danadresse_version', LYNBRO_DANADRESSE_VERSION );
	lynbro_danadresse_record_install_time();

	if ( false === get_option( LYNBRO_DANADRESSE_OPTION, false ) ) {
		add_option( LYNBRO_DANADRESSE_OPTION, lynbro_danadresse_default_options() );
	}
}
register_activation_hook( __FILE__, 'lynbro_danadresse_activate' );

/**
 * Add a "Settings" link to the plugin row (gentle onboarding, like the flagship).
 *
 * @param array<int,string> $links Existing action links.
 * @return array<int,string>
 */
function lynbro_danadresse_action_links( $links ) {
	$settings = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=lynbro-danadresse' ) ),
		esc_html__( 'Settings', 'lynbro-danadresse' )
	);
	array_unshift( $links, $settings );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lynbro_danadresse_action_links' );

/**
 * Bootstrap once all plugins are loaded.
 *
 * @return void
 */
function lynbro_danadresse_init() {
	require_once LYNBRO_DANADRESSE_DIR . 'includes/options.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/integrations.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/log.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/api.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/ajax.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/assets.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/forms.php';
	require_once LYNBRO_DANADRESSE_DIR . 'includes/block.php';

	if ( class_exists( 'WooCommerce' ) ) {
		require_once LYNBRO_DANADRESSE_DIR . 'includes/woocommerce.php';
	}

	if ( is_admin() ) {
		require_once LYNBRO_DANADRESSE_DIR . 'includes/onboarding.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/settings.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/statistics-tab.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/log-tab.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/languages-tab.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/feedback-tab.php';
		require_once LYNBRO_DANADRESSE_DIR . 'admin/about-tab.php';
	}

	/**
	 * Fires after Danadresse has loaded all of its modules.
	 */
	do_action( 'lynbro_danadresse_loaded' );
}
add_action( 'plugins_loaded', 'lynbro_danadresse_init' );
