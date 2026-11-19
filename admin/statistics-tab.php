<?php
/**
 * Admin — Statistics tab.
 *
 * Shows how much the site actually uses the address service, from counters kept
 * locally on this site. Nothing here is fetched from anywhere: the numbers are
 * the plugin's own daily tallies, so the screen works even when the service is
 * unreachable.
 *
 * @package LynbroDanadresse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the Statistics tab.
 *
 * @return void
 */
function lynbro_danadresse_render_tab_statistics() {
	$stats = get_option( LYNBRO_DANADRESSE_STATS_OPTION, array() );
	$stats = is_array( $stats ) ? $stats : array();

	// Build a continuous 30-day series so quiet days show as gaps, not as
	// missing columns that silently compress the timeline.
	$days   = array();
	$totals = array(
		'autocomplete' => 0,
		'datavask'     => 0,
		'errors'       => 0,
	);

	for ( $i = 29; $i >= 0; $i-- ) {
		$date = gmdate( 'Y-m-d', time() - ( $i * DAY_IN_SECONDS ) );
		$row  = isset( $stats[ $date ] ) && is_array( $stats[ $date ] ) ? $stats[ $date ] : array();

		$day = array(
			'date'         => $date,
			'autocomplete' => isset( $row['autocomplete'] ) ? (int) $row['autocomplete'] : 0,
			'datavask'     => isset( $row['datavask'] ) ? (int) $row['datavask'] : 0,
			'errors'       => isset( $row['errors'] ) ? (int) $row['errors'] : 0,
		);

		$totals['autocomplete'] += $day['autocomplete'];
		$totals['datavask']     += $day['datavask'];
		$totals['errors']       += $day['errors'];

		$days[] = $day;
	}

	$peak = 0;
	foreach ( $days as $day ) {
		$peak = max( $peak, $day['autocomplete'] + $day['datavask'] );
	}
	$grand = $totals['autocomplete'] + $totals['datavask'];
	?>
	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Usage over the last 30 days', 'lynbro-danadresse' ); ?></h2>
		<p class="desc">
			<?php esc_html_e( 'Counted on this site, so you can see your own consumption without opening the danadresse.dk dashboard. One lookup is one request to the service; cached repeats are free and are not counted.', 'lynbro-danadresse' ); ?>
		</p>

		<div class="lynbro-danadresse-figures">
			<div class="lynbro-danadresse-figure">
				<span class="n"><?php echo esc_html( number_format_i18n( $grand ) ); ?></span>
				<span class="l"><?php esc_html_e( 'lookups total', 'lynbro-danadresse' ); ?></span>
			</div>
			<div class="lynbro-danadresse-figure">
				<span class="n"><?php echo esc_html( number_format_i18n( $totals['autocomplete'] ) ); ?></span>
				<span class="l"><?php esc_html_e( 'autocomplete', 'lynbro-danadresse' ); ?></span>
			</div>
			<div class="lynbro-danadresse-figure">
				<span class="n"><?php echo esc_html( number_format_i18n( $totals['datavask'] ) ); ?></span>
				<span class="l"><?php esc_html_e( 'validations', 'lynbro-danadresse' ); ?></span>
			</div>
			<div class="lynbro-danadresse-figure<?php echo $totals['errors'] > 0 ? ' is-warn' : ''; ?>">
				<span class="n"><?php echo esc_html( number_format_i18n( $totals['errors'] ) ); ?></span>
				<span class="l"><?php esc_html_e( 'service errors', 'lynbro-danadresse' ); ?></span>
			</div>
		</div>

		<?php if ( $grand > 0 ) : ?>
			<div class="lynbro-danadresse-chart" role="img"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: number of lookups. */ __( 'Daily lookups over the last 30 days. %s in total.', 'lynbro-danadresse' ), number_format_i18n( $grand ) ) ); ?>">
				<?php foreach ( $days as $day ) : ?>
					<?php
					$count = $day['autocomplete'] + $day['datavask'];
					// Snap to the 5% steps the stylesheet defines, so the height comes
					// from a class rule rather than an inline style attribute.
					$height = ( $peak > 0 && $count > 0 )
						? max( 5, (int) ( round( ( $count / $peak ) * 20 ) * 5 ) )
						: 2;
					$title  = sprintf(
						/* translators: 1: date, 2: autocomplete count, 3: validation count. */
						__( '%1$s — %2$s autocomplete, %3$s validations', 'lynbro-danadresse' ),
						$day['date'],
						number_format_i18n( $day['autocomplete'] ),
						number_format_i18n( $day['datavask'] )
					);
					?>
					<span class="col<?php echo $count > 0 ? ' has-data' : ''; ?>" title="<?php echo esc_attr( $title ); ?>">
						<span class="bar" data-h="<?php echo esc_attr( (string) $height ); ?>"></span>
					</span>
				<?php endforeach; ?>
			</div>
			<p class="lynbro-muted">
				<?php
				printf(
					/* translators: 1: first date in the range, 2: last date. */
					esc_html__( '%1$s to %2$s', 'lynbro-danadresse' ),
					esc_html( $days[0]['date'] ),
					esc_html( $days[ count( $days ) - 1 ]['date'] )
				);
				?>
			</p>
		<?php else : ?>
			<p class="lynbro-muted"><?php esc_html_e( 'No lookups recorded yet. Numbers appear here as soon as someone starts typing in an address field.', 'lynbro-danadresse' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="lynbro-section">
		<h2><?php esc_html_e( 'Your plan', 'lynbro-danadresse' ); ?></h2>
		<p class="desc"><?php esc_html_e( 'Quota, plan and invoices live in your danadresse.dk dashboard — this plugin only counts what it sends from this site.', 'lynbro-danadresse' ); ?></p>
		<p class="lynbro-tools-row">
			<a class="button" href="https://danadresse.dk/dashboard/usage" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open usage on danadresse.dk', 'lynbro-danadresse' ); ?></a>
		</p>
	</div>
	<?php
}
