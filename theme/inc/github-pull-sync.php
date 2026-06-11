<?php
/**
 * WordPress-initiated GitHub Markdown content synchronization.
 *
 * Instead of GitHub Actions pushing content to WordPress, WordPress pulls
 * Markdown files from GitHub. This avoids hosting WAF restrictions on inbound
 * POST requests.
 *
 * @package plainmark
 * @since 0.4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the pull sync settings page.
 */
function plainmark_add_github_pull_sync_page() {
	add_management_page(
		__( 'GitHub Pull Sync', 'plainmark' ),
		__( 'GitHub Pull Sync', 'plainmark' ),
		'manage_options',
		'plainmark-github-pull-sync',
		'plainmark_render_github_pull_sync_page'
	);
}
add_action( 'admin_menu', 'plainmark_add_github_pull_sync_page' );

/**
 * Default pull sync settings.
 *
 * @return array{repository:string,branch:string,paths:string,token:string}
 */
function plainmark_get_github_pull_sync_settings() {
	return array(
		'repository' => (string) get_option( 'plainmark_github_pull_repository', 'masakiShito/plainmark' ),
		'branch'     => (string) get_option( 'plainmark_github_pull_branch', 'main' ),
		'paths'      => (string) get_option( 'plainmark_github_pull_paths', "content/posts\ncontent/works" ),
		'token'      => (string) get_option( 'plainmark_github_pull_token', '' ),
	);
}

/**
 * Normalize configured content directories.
 *
 * @param string $paths Raw paths.
 * @return array<int,string>
 */
function plainmark_normalize_github_pull_paths( $paths ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $paths );
	$items = array();

	foreach ( $lines as $line ) {
		$path = trim( $line );
		$path = trim( $path, '/' );
		if ( '' !== $path ) {
			$items[] = $path;
		}
	}

	return array_values( array_unique( $items ) );
}

/**
 * Determine if a repository path should be synchronized.
 *
 * @param string        $path  Repository path.
 * @param array<string> $roots Allowed roots.
 * @return bool
 */
function plainmark_is_github_markdown_sync_target( $path, $roots ) {
	if ( ! preg_match( '/\.md$/i', $path ) ) {
		return false;
	}

	foreach ( $roots as $root ) {
		if ( $path === $root || 0 === strpos( $path, $root . '/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Request GitHub API.
 *
 * @param string $url   GitHub API URL.
 * @param string $token Optional token.
 * @return array|WP_Error
 */
function plainmark_github_pull_request_json( $url, $token = '' ) {
	$headers = array(
		'Accept'               => 'application/vnd.github+json',
		'User-Agent'           => 'plainmark-github-pull-sync',
		'X-GitHub-Api-Version' => '2022-11-28',
	);

	if ( '' !== $token ) {
		$headers['Authorization'] = 'Bearer ' . $token;
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => $headers,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = (string) wp_remote_retrieve_body( $response );

	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'plainmark_github_pull_http_error',
			sprintf( 'GitHub API returned HTTP %1$d: %2$s', $status, $body )
		);
	}

	$decoded = json_decode( $body, true );
	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'plainmark_github_pull_invalid_json', __( 'GitHub API returned invalid JSON.', 'plainmark' ) );
	}

	return $decoded;
}

/**
 * Import a single Markdown file fetched from GitHub.
 *
 * @param string $markdown Markdown content.
 * @param string $path     Repository path.
 * @param string $sha      Git blob SHA.
 * @return array|WP_Error
 */
function plainmark_import_github_markdown_blob( $markdown, $path, $sha ) {
	if ( ! function_exists( 'plainmark_parse_md_content' ) || ! function_exists( 'plainmark_import_single_md' ) ) {
		return new WP_Error( 'plainmark_importer_unavailable', __( 'Markdown importer is unavailable.', 'plainmark' ) );
	}

	$parsed = plainmark_parse_md_content( $markdown );
	$result = plainmark_import_single_md( $markdown, true );

	if ( ! $result || empty( $result['id'] ) ) {
		return new WP_Error( 'plainmark_import_failed', sprintf( 'Failed to import %s.', $path ) );
	}

	$post_id   = absint( $result['id'] );
	$post_type = get_post_type( $post_id );

	if ( $parsed && ! empty( $parsed['front_matter'] ) && function_exists( 'plainmark_apply_content_bridge_front_matter' ) ) {
		plainmark_apply_content_bridge_front_matter( $post_id, $parsed['front_matter'], $post_type );
	}

	update_post_meta( $post_id, '_plainmark_github_path', $path );
	update_post_meta( $post_id, '_plainmark_github_sha', $sha );
	update_post_meta( $post_id, '_plainmark_github_synced_at', current_time( 'mysql' ) );

	return array(
		'id'     => $post_id,
		'action' => sanitize_key( $result['action'] ?? 'updated' ),
		'path'   => $path,
	);
}

/**
 * Run GitHub pull synchronization.
 *
 * @return array{success:int,created:int,updated:int,skipped:int,errors:array<int,string>,items:array<int,array>}
 */
function plainmark_run_github_pull_sync() {
	$settings = plainmark_get_github_pull_sync_settings();
	$repo     = trim( $settings['repository'] );
	$branch   = trim( $settings['branch'] );
	$roots    = plainmark_normalize_github_pull_paths( $settings['paths'] );
	$token    = trim( $settings['token'] );
	$result   = array(
		'success' => 0,
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'errors'  => array(),
		'items'   => array(),
	);

	if ( ! preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ) {
		$result['errors'][] = __( 'Repository must be in owner/name format.', 'plainmark' );
		return $result;
	}

	if ( '' === $branch ) {
		$result['errors'][] = __( 'Branch is required.', 'plainmark' );
		return $result;
	}

	if ( empty( $roots ) ) {
		$result['errors'][] = __( 'At least one content path is required.', 'plainmark' );
		return $result;
	}

	$tree_url = sprintf(
		'https://api.github.com/repos/%1$s/git/trees/%2$s?recursive=1',
		rawurlencode( $repo ),
		rawurlencode( $branch )
	);
	$tree_url = str_replace( '%2F', '/', $tree_url );
	$tree     = plainmark_github_pull_request_json( $tree_url, $token );

	if ( is_wp_error( $tree ) ) {
		$result['errors'][] = $tree->get_error_message();
		return $result;
	}

	if ( empty( $tree['tree'] ) || ! is_array( $tree['tree'] ) ) {
		$result['errors'][] = __( 'No files were found in the GitHub tree.', 'plainmark' );
		return $result;
	}

	foreach ( $tree['tree'] as $item ) {
		$path = isset( $item['path'] ) ? (string) $item['path'] : '';
		$type = isset( $item['type'] ) ? (string) $item['type'] : '';
		$sha  = isset( $item['sha'] ) ? (string) $item['sha'] : '';

		if ( 'blob' !== $type || '' === $sha || ! plainmark_is_github_markdown_sync_target( $path, $roots ) ) {
			continue;
		}

		$existing = get_posts(
			array(
				'post_type'      => array( 'post', 'portfolio' ),
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_plainmark_github_sha',
				'meta_value'     => $sha,
			)
		);

		if ( ! empty( $existing ) ) {
			$result['skipped']++;
			continue;
		}

		$blob_url = sprintf(
			'https://api.github.com/repos/%1$s/git/blobs/%2$s',
			rawurlencode( $repo ),
			rawurlencode( $sha )
		);
		$blob_url = str_replace( '%2F', '/', $blob_url );
		$blob     = plainmark_github_pull_request_json( $blob_url, $token );

		if ( is_wp_error( $blob ) ) {
			$result['errors'][] = $path . ': ' . $blob->get_error_message();
			continue;
		}

		if ( empty( $blob['content'] ) || 'base64' !== ( $blob['encoding'] ?? '' ) ) {
			$result['errors'][] = sprintf( '%s: invalid GitHub blob response.', $path );
			continue;
		}

		$markdown = base64_decode( preg_replace( '/\s+/', '', (string) $blob['content'] ), true );
		if ( false === $markdown ) {
			$result['errors'][] = sprintf( '%s: failed to decode content.', $path );
			continue;
		}

		$imported = plainmark_import_github_markdown_blob( $markdown, $path, $sha );
		if ( is_wp_error( $imported ) ) {
			$result['errors'][] = $path . ': ' . $imported->get_error_message();
			continue;
		}

		$result['success']++;
		$result['items'][] = $imported;
		if ( 'created' === $imported['action'] ) {
			$result['created']++;
		} else {
			$result['updated']++;
		}
	}

	return $result;
}

/**
 * Save pull sync settings.
 */
function plainmark_handle_github_pull_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage this setting.', 'plainmark' ) );
	}

	check_admin_referer( 'plainmark_save_github_pull_settings' );

	$repository = isset( $_POST['repository'] ) ? sanitize_text_field( wp_unslash( $_POST['repository'] ) ) : 'masakiShito/plainmark';
	$branch     = isset( $_POST['branch'] ) ? sanitize_text_field( wp_unslash( $_POST['branch'] ) ) : 'main';
	$paths      = isset( $_POST['paths'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paths'] ) ) : "content/posts\ncontent/works";
	$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

	update_option( 'plainmark_github_pull_repository', $repository, false );
	update_option( 'plainmark_github_pull_branch', $branch, false );
	update_option( 'plainmark_github_pull_paths', $paths, false );
	if ( '' !== $token ) {
		update_option( 'plainmark_github_pull_token', $token, false );
	}

	wp_safe_redirect( admin_url( 'tools.php?page=plainmark-github-pull-sync&settings-updated=1' ) );
	exit;
}
add_action( 'admin_post_plainmark_save_github_pull_settings', 'plainmark_handle_github_pull_settings_save' );

/**
 * Run pull sync from admin.
 */
function plainmark_handle_github_pull_sync_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run synchronization.', 'plainmark' ) );
	}

	check_admin_referer( 'plainmark_run_github_pull_sync' );

	$result = plainmark_run_github_pull_sync();
	set_transient( 'plainmark_github_pull_sync_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS * 10 );

	wp_safe_redirect( admin_url( 'tools.php?page=plainmark-github-pull-sync&synced=1' ) );
	exit;
}
add_action( 'admin_post_plainmark_run_github_pull_sync', 'plainmark_handle_github_pull_sync_run' );

/**
 * Render the pull sync settings page.
 */
function plainmark_render_github_pull_sync_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = plainmark_get_github_pull_sync_settings();
	$result   = get_transient( 'plainmark_github_pull_sync_result_' . get_current_user_id() );
	if ( false !== $result ) {
		delete_transient( 'plainmark_github_pull_sync_result_' . get_current_user_id() );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GitHub Pull Sync', 'plainmark' ); ?></h1>
		<p><?php esc_html_e( 'WordPressからGitHubへMarkdownを取得し、投稿とWorksへ同期します。外部からWordPressへPOSTしないため、ロリポップのWAF制限を回避しやすい方式です。', 'plainmark' ); ?></p>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '設定を保存しました。', 'plainmark' ); ?></p></div>
		<?php endif; ?>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice <?php echo empty( $result['errors'] ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible">
				<p>
					<?php
					printf(
						esc_html__( '同期完了: 成功 %1$d件 / 作成 %2$d件 / 更新 %3$d件 / スキップ %4$d件', 'plainmark' ),
						(int) $result['success'],
						(int) $result['created'],
						(int) $result['updated'],
						(int) $result['skipped']
					);
					?>
				</p>
				<?php if ( ! empty( $result['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $result['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( '同期設定', 'plainmark' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'plainmark_save_github_pull_settings' ); ?>
			<input type="hidden" name="action" value="plainmark_save_github_pull_settings">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="plainmark-repository"><?php esc_html_e( 'Repository', 'plainmark' ); ?></label></th>
					<td><input id="plainmark-repository" class="regular-text" name="repository" value="<?php echo esc_attr( $settings['repository'] ); ?>" placeholder="masakiShito/plainmark"></td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-branch"><?php esc_html_e( 'Branch', 'plainmark' ); ?></label></th>
					<td><input id="plainmark-branch" class="regular-text" name="branch" value="<?php echo esc_attr( $settings['branch'] ); ?>" placeholder="main"></td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-paths"><?php esc_html_e( 'Content paths', 'plainmark' ); ?></label></th>
					<td>
						<textarea id="plainmark-paths" class="large-text code" name="paths" rows="4"><?php echo esc_textarea( $settings['paths'] ); ?></textarea>
						<p class="description"><?php esc_html_e( '1行に1つずつ、Markdownを置くディレクトリを指定します。', 'plainmark' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-token"><?php esc_html_e( 'GitHub token', 'plainmark' ); ?></label></th>
					<td>
						<input id="plainmark-token" class="regular-text" type="password" name="token" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $settings['token'] ? __( '保存済み。変更する場合のみ入力', 'plainmark' ) : __( 'Public repoなら空でOK', 'plainmark' ) ); ?>">
						<p class="description"><?php esc_html_e( '公開リポジトリでは不要です。非公開リポジトリやAPI制限回避が必要な場合のみFine-grained tokenを保存してください。', 'plainmark' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( '設定を保存', 'plainmark' ) ); ?>
		</form>

		<hr>

		<h2><?php esc_html_e( '手動同期', 'plainmark' ); ?></h2>
		<p><?php esc_html_e( 'GitHub上のMarkdownを取得して、同じslugの投稿を更新します。存在しない場合は新規作成します。', 'plainmark' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'plainmark_run_github_pull_sync' ); ?>
			<input type="hidden" name="action" value="plainmark_run_github_pull_sync">
			<?php submit_button( __( 'GitHubから同期', 'plainmark' ), 'primary large' ); ?>
		</form>

		<h2><?php esc_html_e( 'front matter例', 'plainmark' ); ?></h2>
		<pre><code>verified_status: "verified"
verified_date: "2026-06-10"
verified_env: "Node.js 24 / TypeScript 5.9"
review_date: "2026-09-10"
related_works:
  - "face-photo-sorter"

# Works側
related_posts:
  - "typescript-guide"</code></pre>
	</div>
	<?php
}
