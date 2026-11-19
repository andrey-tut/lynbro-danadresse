<?php
/**
 * Admin — Log tab.
 *
 * A short list of the most recent failures, so a shop owner can answer "why did
 * autocomplete stop working?" without opening a support ticket. Addresses that
 * visitors typed are never recorded here — see includes/log.php.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the Log tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_log() {
	$entries = lynbro_danadresse_log_entries();
	$kinds   = array(
		'autocomplete' => __( 'Autocomplete', 'lynbro-danadresse' ),
		'datavask'     => __( 'Address validation', 'lynbro-danadresse' ),
		'test'         => __( 'Test lookup', 'lynbro-danadresse' ),
		'enrich'       => __( 'Address details', 'lynbro-danadresse' ),
		'lookup'       => __( 'Lookup', 'lynbro-danadresse' ),
	);
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Recent problems', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'The last few times a lookup failed, and why. Successful lookups are not listed — you can see those as numbers on the Statistics screen.', 'lynbro-danadresse' ); ?>
		</p>

		<?php if ( empty( $entries ) ) : ?>
			<p class="lynbro-muted"><?php esc_html_e( 'Nothing to report — no failed lookups have been recorded.', 'lynbro-danadresse' ); ?></p>
		<?php else : ?>
			<table class="widefat striped lynbro-danadresse-log">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'When', 'lynbro-danadresse' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Lookup', 'lynbro-danadresse' ); ?></th>
						<th scope="col"><?php esc_html_e( 'What happened', 'lynbro-danadresse' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Code', 'lynbro-danadresse' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entries as $entry ) : ?>
						<?php
						$time   = isset( $entry['time'] ) ? (int) $entry['time'] : 0;
						$kind   = isset( $entry['kind'] ) ? (string) $entry['kind'] : '';
						$status = isset( $entry['status'] ) ? (int) $entry['status'] : 0;
						$code   = isset( $entry['code'] ) ? (string) $entry['code'] : '';
						?>
						<tr>
							<td>
								<?php
								echo esc_html(
									$time
										? sprintf(
											/* translators: %s: human time difference, e.g. "2 hours". */
											__( '%s ago', 'lynbro-danadresse' ),
											human_time_diff( $time, time() )
										)
										: '—'
								);
								?>
							</td>
							<td><?php echo esc_html( isset( $kinds[ $kind ] ) ? $kinds[ $kind ] : $kind ); ?></td>
							<td><?php echo esc_html( lynbro_danadresse_log_label( $code, $status ) ); ?></td>
							<td><code><?php echo esc_html( '' !== $code ? $code : (string) $status ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="lynbro-muted">
				<?php esc_html_e( 'Only the technical details are kept: time, which kind of lookup, and the error code. The address text is never stored.', 'lynbro-danadresse' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
