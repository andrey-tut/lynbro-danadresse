<?php
/**
 * Admin — Feedback tab.
 *
 * Links out only. The plugin deliberately does not post feedback anywhere by
 * itself: that would add a second external service to disclose, for something a
 * link does just as well. Every button here opens a page in a new tab and sends
 * nothing from this site.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the Feedback tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_feedback() {
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Is it working for you?', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'This plugin is free and built in Denmark. If it saved you some typing — or some bounced parcels — a short review helps other Danish shops find it.', 'lynbro-danadresse' ); ?>
		</p>
		<p class="lynbro-tools-row">
			<a class="button button-primary" href="https://wordpress.org/support/plugin/lynbro-danadresse/reviews/#new-post" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Leave a review on WordPress.org', 'lynbro-danadresse' ); ?>
			</a>
		</p>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Something missing or broken?', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'Feature requests and bug reports are read by the people who build the plugin. Pick whichever fits — nothing is sent from your site by opening these.', 'lynbro-danadresse' ); ?>
		</p>
		<p class="lynbro-tools-row">
			<a class="button" href="https://plugins.lynbro.dk/roadmap" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Suggest a feature (public roadmap)', 'lynbro-danadresse' ); ?>
			</a>
			<a class="button" href="https://wordpress.org/support/plugin/lynbro-danadresse/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Ask for help in the support forum', 'lynbro-danadresse' ); ?>
			</a>
		</p>
		<p class="lynbro-muted">
			<?php esc_html_e( 'For anything about your API key, quota or billing, use your danadresse.dk dashboard — that is a separate service with your account details.', 'lynbro-danadresse' ); ?>
		</p>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Which form builder should we support next?', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'Fourteen integrations ship today. If the form plugin you use is not one of them, tell us on the roadmap — adding one is usually a small job once we can see its address field.', 'lynbro-danadresse' ); ?>
		</p>
	</div>
	<?php
}
