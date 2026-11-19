<?php
/**
 * The one thing this plugin asks of you, asked on its own screen.
 *
 * Guideline 11 says a plugin must not hijack the dashboard. The cheapest way to
 * comply is to own no global hook at all: there is no `admin_notices` here, no
 * redirect after activation, nothing that appears on a screen belonging to
 * WordPress or to somebody else's plugin. The review request is rendered by our
 * own settings page, once, after a week of actual use, and can be turned off for
 * good.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * How long the plugin must have been installed before the request may appear.
 */
if ( ! defined( 'LYNBRO_DANADRESSE_REVIEW_DELAY' ) ) {
	define( 'LYNBRO_DANADRESSE_REVIEW_DELAY', 7 * DAY_IN_SECONDS );
}

/**
 * Remember when the plugin was first activated. Called from activation.
 *
 * @return void
 */
function lynbro_danadresse_record_install_time() {
	if ( ! get_option( 'lynbro_danadresse_installed_at' ) ) {
		add_option( 'lynbro_danadresse_installed_at', time(), '', false );
	}
}

/**
 * Whether the review request has earned the right to be shown.
 *
 * @return bool
 */
function lynbro_danadresse_should_ask_for_review() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( get_user_meta( get_current_user_id(), 'lynbro_danadresse_review_dismissed', true ) ) {
		return false;
	}

	$installed = (int) get_option( 'lynbro_danadresse_installed_at', 0 );
	if ( ! $installed ) {
		// Upgraded from a version that did not record this — start the clock now.
		lynbro_danadresse_record_install_time();
		return false;
	}
	if ( ( time() - $installed ) < LYNBRO_DANADRESSE_REVIEW_DELAY ) {
		return false;
	}

	// Only ask people for whom the plugin has actually done something.
	$stats = get_option( LYNBRO_DANADRESSE_STATS_OPTION, array() );
	return is_array( $stats ) && ! empty( $stats );
}

/**
 * Render the review request. Called from our settings page, nowhere else.
 *
 * @return void
 */
function lynbro_danadresse_render_review_prompt() {
	if ( ! lynbro_danadresse_should_ask_for_review() ) {
		return;
	}

	$dismiss = wp_nonce_url(
		add_query_arg( 'lynbro_danadresse_dismiss_review', '1' ),
		'lynbro_danadresse_dismiss_review'
	);
	?>
	<div class="lynbro-section lynbro-danadresse-review">
		<p>
			<?php esc_html_e( 'Has Lynbro Danadresse been useful on your site? A few words on WordPress.org help other Danish shops find it.', 'lynbro-danadresse' ); ?>
		</p>
		<p class="lynbro-tools-row">
			<a class="button button-primary" href="https://wordpress.org/support/plugin/lynbro-danadresse/reviews/#new-post" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Write a review', 'lynbro-danadresse' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'No thanks', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Handle "No thanks" — hide the request for this user, permanently.
 *
 * @return void
 */
function lynbro_danadresse_dismiss_review() {
	if ( ! isset( $_GET['lynbro_danadresse_dismiss_review'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked immediately below.
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'lynbro_danadresse_dismiss_review' );

	update_user_meta( get_current_user_id(), 'lynbro_danadresse_review_dismissed', 1 );

	wp_safe_redirect( remove_query_arg( array( 'lynbro_danadresse_dismiss_review', '_wpnonce' ) ) );
	exit;
}
add_action( 'admin_init', 'lynbro_danadresse_dismiss_review' );
