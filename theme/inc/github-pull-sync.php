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
 * Parse legacy owner/name repository setting.
 *
 * @param string $repository Repository in owner/name format.
 * @return array{owner:string,name:string}
 */
function plainmark_parse_github_repository_setting( $repository ) {
	$repository = trim( (string) $repository );
	$parts      = explode( '/', $repository, 2 );

	return array(
		'owner' => isset( $parts[0] ) ? trim( $parts[0] ) : '',
		'name'  => isset( $parts[1] ) ? trim( $parts[1] ) : '',
	);
}

/**
 * Build repository full name from owner and repository name.
 *
 * @param string $owner GitHub owner.
 * @param string $name  GitHub repository name.
 * @return string
 */
function plainmark_build_github_repository_full_name( $owner, $name ) {
	$owner = trim( (string) $owner );
	$name  = trim( (string) $name );

	if ( '' === $owner || '' === $name ) {
		return '';
	}

	return $owner . '/' . $name;
}

/**
 * Build encoded GitHub repository path for API URLs.
 *
 * @param string $owner GitHub owner.
 * @param string $name  GitHub repository name.
 * @return string
 */
function plainmark_build_github_api_repo_path( $owner, $name ) {
	return rawurlencode( $owner ) . '/' . rawurlencode( $name );
}

/**
 * Default pull sync settings.
 *
 * @return array{owner:string,repository_name:string,repository:string,branch:string,posts_path:string,works_path:string,paths:string,token:string}
 */
function plainmark_get_github_pull_sync_settings() {
	$legacy_repository = (string) get_option( 'plainmark_github_pull_repository', '' );
	$legacy_parts      = plainmark_parse_github_repository_setting( $legacy_repository );

	$owner           = (string) get_option( 'plainmark_github_pull_owner', $legacy_parts['owner'] ?: 'masakiShito' );
	$repository_name = (string) get_option( 'plainmark_github_pull_repository_name', $legacy_parts['name'] ?: 'plainmark-content' );
	$repository      = plainmark_build_github_repository_full_name( $owner, $repository_name );
	$posts_path      = (string) get_option( 'plainmark_github_pull_posts_path', 'posts' );
	$works_path      = (string) get_option( 'plainmark_github_pull_works_path', 'works' );
	$paths           = (string) get_option( 'plainmark_github_pull_paths', trim( $posts_path . "\n" . $works_path ) );

	return array(
		'owner'           => $owner,
		'repository_name' => $repository_name,
		'repository'      => $repository,
		'branch'          => (string) get_option( 'plainmark_github_pull_branch', 'main' ),
		'posts_path'      => $posts_path,
		'works_path'      => $works_path,
		'paths'           => $paths,
		'token'           => (string) get_option( 'plainmark_github_pull_token', '' ),
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
	if ( ! function_exists( 'plainmark_sync_markdown' ) ) {
		return new WP_Error( 'plainmark_sync_unavailable', __( 'Sync function is unavailable.', 'plainmark' ) );
	}

	$result = plainmark_sync_markdown( $markdown, $path, $sha );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( $result->get_error_code(), sprintf( 'Failed to import %s: %s', $path, $result->get_error_message() ) );
	}

	return $result;
}

/**
 * Run GitHub pull synchronization.
 *
 * @return array{success:int,created:int,updated:int,skipped:int,errors:array<int,string>,items:array<int,array>}
 */
function plainmark_run_github_pull_sync() {
	$settings        = plainmark_get_github_pull_sync_settings();
	$owner           = trim( $settings['owner'] );
	$repository_name = trim( $settings['repository_name'] );
	$repo            = trim( $settings['repository'] );
	$branch          = trim( $settings['branch'] );
	$roots           = plainmark_normalize_github_pull_paths( $settings['paths'] );
	$token           = trim( $settings['token'] );
	$result          = array(
		'success' => 0,
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'errors'  => array(),
		'items'   => array(),
	);

	if ( ! preg_match( '/^[A-Za-z0-9-]+$/', $owner ) ) {
		$result['errors'][] = __( 'Owner must be a valid GitHub owner name.', 'plainmark' );
		return $result;
	}

	if ( ! preg_match( '/^[A-Za-z0-9_.-]+$/', $repository_name ) ) {
		$result['errors'][] = __( 'Repository name must be a valid GitHub repository name.', 'plainmark' );
		return $result;
	}

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

	$repo_api_path = plainmark_build_github_api_repo_path( $owner, $repository_name );
	$tree_url      = sprintf(
		'https://api.github.com/repos/%1$s/git/trees/%2$s?recursive=1',
		$repo_api_path,
		rawurlencode( $branch )
	);
	$tree          = plainmark_github_pull_request_json( $tree_url, $token );

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
			$repo_api_path,
			rawurlencode( $sha )
		);
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

	$owner           = isset( $_POST['owner'] ) ? sanitize_text_field( wp_unslash( $_POST['owner'] ) ) : 'masakiShito';
	$repository_name = isset( $_POST['repository_name'] ) ? sanitize_text_field( wp_unslash( $_POST['repository_name'] ) ) : 'plainmark-content';
	$branch          = isset( $_POST['branch'] ) ? sanitize_text_field( wp_unslash( $_POST['branch'] ) ) : 'main';
	$posts_path      = isset( $_POST['posts_path'] ) ? sanitize_text_field( wp_unslash( $_POST['posts_path'] ) ) : 'posts';
	$works_path      = isset( $_POST['works_path'] ) ? sanitize_text_field( wp_unslash( $_POST['works_path'] ) ) : 'works';
	$token           = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$owner           = preg_replace( '/[^A-Za-z0-9-]/', '', $owner );
	$repository_name = preg_replace( '/[^A-Za-z0-9_.-]/', '', $repository_name );
	$posts_path      = trim( preg_replace( '/[^A-Za-z0-9_\.\/-]/', '', $posts_path ), '/' );
	$works_path      = trim( preg_replace( '/[^A-Za-z0-9_\.\/-]/', '', $works_path ), '/' );
	$paths           = trim( $posts_path . "\n" . $works_path );
	$repository      = plainmark_build_github_repository_full_name( $owner, $repository_name );

	update_option( 'plainmark_github_pull_owner', $owner, false );
	update_option( 'plainmark_github_pull_repository_name', $repository_name, false );
	update_option( 'plainmark_github_pull_repository', $repository, false );
	update_option( 'plainmark_github_pull_branch', $branch, false );
	update_option( 'plainmark_github_pull_posts_path', $posts_path, false );
	update_option( 'plainmark_github_pull_works_path', $works_path, false );
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
		<p><?php esc_html_e( 'WordPressから指定したGitHubリポジトリのMarkdownを取得し、投稿とWorksへ同期します。テーマ本体とは別のコンテンツ管理リポジトリを指定できます。', 'plainmark' ); ?></p>

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
					<th scope="row"><label for="plainmark-owner"><?php esc_html_e( 'Owner', 'plainmark' ); ?></label></th>
					<td>
						<input id="plainmark-owner" class="regular-text" name="owner" value="<?php echo esc_attr( $settings['owner'] ); ?>" placeholder="masakiShito">
						<p class="description"><?php esc_html_e( 'GitHubユーザー名またはOrganization名を指定します。', 'plainmark' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-repository-name"><?php esc_html_e( 'Repository name', 'plainmark' ); ?></label></th>
					<td>
						<input id="plainmark-repository-name" class="regular-text" name="repository_name" value="<?php echo esc_attr( $settings['repository_name'] ); ?>" placeholder="plainmark-content">
						<p class="description"><?php esc_html_e( '記事・WorksのMarkdownを置くコンテンツ管理リポジトリ名を指定します。', 'plainmark' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Repository', 'plainmark' ); ?></th>
					<td><code><?php echo esc_html( $settings['repository'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-branch"><?php esc_html_e( 'Branch', 'plainmark' ); ?></label></th>
					<td><input id="plainmark-branch" class="regular-text" name="branch" value="<?php echo esc_attr( $settings['branch'] ); ?>" placeholder="main"></td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-posts-path"><?php esc_html_e( 'Posts path', 'plainmark' ); ?></label></th>
					<td>
						<input id="plainmark-posts-path" class="regular-text" name="posts_path" value="<?php echo esc_attr( $settings['posts_path'] ); ?>" placeholder="posts">
						<p class="description"><?php esc_html_e( '記事Markdownを置くディレクトリです。例: posts または content/posts', 'plainmark' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="plainmark-works-path"><?php esc_html_e( 'Works path', 'plainmark' ); ?></label></th>
					<td>
						<input id="plainmark-works-path" class="regular-text" name="works_path" value="<?php echo esc_attr( $settings['works_path'] ); ?>" placeholder="works">
						<p class="description"><?php esc_html_e( 'Works Markdownを置くディレクトリです。例: works または content/works', 'plainmark' ); ?></p>
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
		<p>
			<?php
			printf(
				esc_html__( '現在の同期元: %1$s / branch: %2$s / paths: %3$s', 'plainmark' ),
				esc_html( $settings['repository'] ),
				esc_html( $settings['branch'] ),
				esc_html( str_replace( "\n", ', ', $settings['paths'] ) )
			);
			?>
		</p>
		<p><?php esc_html_e( 'GitHub上のMarkdownを取得して、同じslugの投稿を更新します。存在しない場合は新規作成します。', 'plainmark' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'plainmark_run_github_pull_sync' ); ?>
			<input type="hidden" name="action" value="plainmark_run_github_pull_sync">
			<?php submit_button( __( 'GitHubから同期', 'plainmark' ), 'primary large' ); ?>
		</form>

		<h2><?php esc_html_e( 'コンテンツリポジトリ構成例', 'plainmark' ); ?></h2>
		<pre><code>plainmark-content/
  posts/
    react-state-snapshot.md
  works/
    plainmark.md

# posts側front matter例
verified_status: "verified"
verified_date: "2026-06-10"
verified_env: "Node.js 24 / TypeScript 5.9"
review_date: "2026-09-10"
related_works:
  - "face-photo-sorter"

# works側front matter例
related_posts:
  - "typescript-guide"</code></pre>
	</div>
	<?php
}
