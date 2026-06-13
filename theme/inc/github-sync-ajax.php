<?php
/**
 * Lolipop-compatible GitHub content synchronization endpoint.
 *
 * Uses admin-ajax.php and Base64-encoded Markdown so hosting WAF rules do not
 * reject raw JSON requests containing source code.
 *
 * @package plainmark
 * @since 0.4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read the configured synchronization secret.
 *
 * @return string
 */
function plainmark_get_github_sync_secret() {
	return defined( 'PLAINMARK_GITHUB_SYNC_SECRET' )
		? (string) PLAINMARK_GITHUB_SYNC_SECRET
		: (string) get_option( 'plainmark_github_sync_secret', '' );
}

/**
 * Handle GitHub Actions synchronization through admin-ajax.php.
 */
function plainmark_handle_github_sync_ajax() {
	$configured_secret = plainmark_get_github_sync_secret();
	$provided_secret   = isset( $_POST['secret'] )
		? sanitize_text_field( wp_unslash( $_POST['secret'] ) )
		: '';

	if ( '' === $configured_secret || '' === $provided_secret || ! hash_equals( $configured_secret, $provided_secret ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid synchronization secret.', 'plainmark' ) ),
			403
		);
	}

	$content_base64 = isset( $_POST['content_base64'] )
		? preg_replace( '/\s+/', '', wp_unslash( $_POST['content_base64'] ) )
		: '';
	$markdown       = base64_decode( $content_base64, true );
	$path           = isset( $_POST['path'] )
		? sanitize_text_field( wp_unslash( $_POST['path'] ) )
		: '';
	$sha            = isset( $_POST['sha'] )
		? sanitize_text_field( wp_unslash( $_POST['sha'] ) )
		: '';

	if ( false === $markdown ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid Base64 content.', 'plainmark' ) ),
			400
		);
	}

	$result = plainmark_sync_markdown( $markdown, $path, $sha );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), $result->get_error_data()['status'] ?? 500 );
	}

	wp_send_json_success(
		array(
			'post_id' => $result['id'],
			'action'  => $result['action'],
			'path'    => $result['path'],
		)
	);
}
add_action( 'wp_ajax_plainmark_github_sync', 'plainmark_handle_github_sync_ajax' );
add_action( 'wp_ajax_nopriv_plainmark_github_sync', 'plainmark_handle_github_sync_ajax' );

/**
 * Show the hosting-compatible endpoint on the GitHub Content settings page.
 */
function plainmark_render_github_sync_ajax_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'tools_page_plainmark-github-content' !== $screen->id ) {
		return;
	}
	?>
	<div class="notice notice-info inline">
		<p><strong><?php esc_html_e( 'ロリポップ対応の同期URL', 'plainmark' ); ?></strong></p>
		<p><code><?php echo esc_html( admin_url( 'admin-ajax.php' ) ); ?></code></p>
		<p><?php esc_html_e( 'GitHub ActionsはこのURLへ自動変換して送信します。既存のPLAINMARK_SYNC_URLはそのままでも利用できます。', 'plainmark' ); ?></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'plainmark_render_github_sync_ajax_notice' );
