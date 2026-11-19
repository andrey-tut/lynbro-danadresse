<?php
/**
 * Uninstall: remove all plugin options. Order meta is left untouched —
 * it is part of the shop's order history.
 *
 * @package LynbroDanadresse
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lynbro_danadresse_options' );
delete_option( 'lynbro_danadresse_stats' );
delete_option( 'lynbro_danadresse_log' );
delete_option( 'lynbro_danadresse_version' );
delete_option( 'lynbro_danadresse_last_error' );
delete_option( 'lynbro_danadresse_installed_at' );
delete_transient( 'lynbro_danadresse_paused' );

// Per-user dismissal of the review notice.
delete_metadata( 'user', 0, 'lynbro_danadresse_review_dismissed', '', true );
