<?php
/**
 * GitHub Works Sync admin page.
 *
 * MVP features:
 * - Save a GitHub username.
 * - Sync public repositories.
 * - Create portfolio draft posts from selected repositories.
 *
 * @package plainmark
 * @since 0.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLAINMARK_GITHUB_WORKS_USERNAME_OPTION = 'plainmark_github_works_username';
const PLAINMARK_GITHUB_WORKS_REPOS_OPTION    = 'plainmark_github_works_repositories';
const PLAINMARK_GITHUB_WORKS_LINKS_OPTION    = 'plainmark_github_works_repo_links';

/** Register GitHub Works Sync admin page. */
function plainmark_register_github_works_sync_page() {
	add_submenu_page(
		'edit.php?post_type=portfolio',
		__( 'GitHub Works Sync', 'plainmark' ),
		__( 'GitHub Works Sync', 'plainmark' ),
		'edit_posts',
		'plainmark-github-works-sync',
		'plainmark_render_github_works_sync_page'
	);
}
add_action( 'admin_menu', 'plainmark_register_github_works_sync_page' );

/** Handle sync and draft creation actions. */
function plainmark_handle_github_works_sync_actions() {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$action = isset( $_POST['plainmark_github_works_action'] ) ? sanitize_key( wp_unslash( $_POST['plainmark_github_works_action'] ) ) : '';
	if ( ! $action ) {
		return;
	}

	check_admin_referer( 'plainmark_github_works_sync' );

	$redirect = add_query_arg(
		array( 'post_type' => 'portfolio', 'page' => 'plainmark-github-works-sync' ),
		admin_url( 'edit.php' )
	);

	if ( 'sync' === $action ) {
		$username = isset( $_POST['plainmark_github_username'] ) ? plainmark_sanitize_github_username( wp_unslash( $_POST['plainmark_github_username'] ) ) : '';

		if ( '' === $username ) {
			wp_safe_redirect( add_query_arg( 'plainmark_github_works_notice', 'empty_username', $redirect ) );
			exit;
		}

		$result = plainmark_fetch_github_public_repositories( $username );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'plainmark_github_works_notice' => 'sync_error',
						'plainmark_github_works_error'  => rawurlencode( $result->get_error_message() ),
					),
					$redirect
				)
			);
			exit;
		}

		update_option( PLAINMARK_GITHUB_WORKS_USERNAME_OPTION, $username );
		update_option( PLAINMARK_GITHUB_WORKS_REPOS_OPTION, $result );

		wp_safe_redirect( add_query_arg( 'plainmark_github_works_notice', 'synced', $redirect ) );
		exit;
	}

	if ( 'create_work' === $action ) {
		$repo_id = isset( $_POST['plainmark_github_repo_id'] ) ? absint( $_POST['plainmark_github_repo_id'] ) : 0;
		$result  = plainmark_create_work_from_github_repository( $repo_id );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'plainmark_github_works_notice' => 'create_error',
						'plainmark_github_works_error'  => rawurlencode( $result->get_error_message() ),
					),
					$redirect
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'plainmark_github_works_notice' => 'created',
					'plainmark_created_work'        => absint( $result ),
				),
				$redirect
			)
		);
		exit;
	}
}
add_action( 'admin_init', 'plainmark_handle_github_works_sync_actions' );

/**
 * Sanitize GitHub username.
 *
 * @param string $username Raw username.
 * @return string
 */
function plainmark_sanitize_github_username( $username ) {
	$username = sanitize_text_field( $username );
	$username = preg_replace( '/[^A-Za-z0-9-]/', '', $username );
	$username = trim( (string) $username, '-' );

	return $username;
}

/**
 * Fetch public GitHub repositories.
 *
 * @param string $username GitHub username.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function plainmark_fetch_github_public_repositories( $username ) {
	$url      = sprintf( 'https://api.github.com/users/%s/repos?per_page=100&sort=updated', rawurlencode( $username ) );
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'plainmark-github-works-sync',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( 200 !== $code ) {
		return new WP_Error( 'github_sync_failed', sprintf( __( 'GitHub API error: HTTP %d', 'plainmark' ), $code ) );
	}

	$data = json_decode( $body, true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'github_sync_invalid_json', __( 'GitHub API response could not be parsed.', 'plainmark' ) );
	}

	$repos = array();
	foreach ( $data as $repo ) {
		if ( empty( $repo['id'] ) || empty( $repo['name'] ) || ! empty( $repo['private'] ) ) {
			continue;
		}

		$topics = array();
		if ( ! empty( $repo['topics'] ) && is_array( $repo['topics'] ) ) {
			$topics = array_values( array_filter( array_map( 'sanitize_text_field', $repo['topics'] ) ) );
		}

		$repos[ absint( $repo['id'] ) ] = array(
			'id'          => absint( $repo['id'] ),
			'name'        => sanitize_text_field( $repo['name'] ),
			'full_name'   => isset( $repo['full_name'] ) ? sanitize_text_field( $repo['full_name'] ) : '',
			'description' => isset( $repo['description'] ) ? sanitize_text_field( (string) $repo['description'] ) : '',
			'html_url'    => isset( $repo['html_url'] ) ? esc_url_raw( $repo['html_url'] ) : '',
			'homepage'    => isset( $repo['homepage'] ) ? esc_url_raw( (string) $repo['homepage'] ) : '',
			'language'    => isset( $repo['language'] ) ? sanitize_text_field( (string) $repo['language'] ) : '',
			'topics'      => $topics,
			'pushed_at'   => isset( $repo['pushed_at'] ) ? sanitize_text_field( (string) $repo['pushed_at'] ) : '',
			'updated_at'  => isset( $repo['updated_at'] ) ? sanitize_text_field( (string) $repo['updated_at'] ) : '',
			'stars'       => isset( $repo['stargazers_count'] ) ? absint( $repo['stargazers_count'] ) : 0,
			'archived'    => ! empty( $repo['archived'] ),
			'fork'        => ! empty( $repo['fork'] ),
		);
	}

	return $repos;
}

/**
 * Create a portfolio draft from a stored GitHub repository.
 *
 * @param int $repo_id GitHub repository ID.
 * @return int|WP_Error Created post ID or error.
 */
function plainmark_create_work_from_github_repository( $repo_id ) {
	$repos = get_option( PLAINMARK_GITHUB_WORKS_REPOS_OPTION, array() );
	$links = get_option( PLAINMARK_GITHUB_WORKS_LINKS_OPTION, array() );

	if ( ! is_array( $repos ) || empty( $repos[ $repo_id ] ) ) {
		return new WP_Error( 'repo_not_found', __( 'Selected repository was not found. Please sync repositories again.', 'plainmark' ) );
	}

	if ( ! empty( $links[ $repo_id ] ) && get_post( absint( $links[ $repo_id ] ) ) ) {
		return absint( $links[ $repo_id ] );
	}

	$repo    = $repos[ $repo_id ];
	$title   = $repo['name'];
	$summary = $repo['description'] ?: sprintf( __( '%s のGitHubリポジトリから作成したWorks下書きです。', 'plainmark' ), $title );
	$content = plainmark_build_work_draft_content_from_repo( $repo );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'portfolio',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_excerpt' => $summary,
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, 'work_summary', $summary );
	update_post_meta( $post_id, 'work_problem', __( 'このプロジェクトで解決したかった課題を追記してください。', 'plainmark' ) );
	update_post_meta( $post_id, 'work_solution', __( 'どのように解決したかを追記してください。', 'plainmark' ) );
	update_post_meta( $post_id, 'work_architecture', plainmark_build_work_architecture_from_repo( $repo ) );
	update_post_meta( $post_id, 'work_features', __( '主な機能を箇条書きで追記してください。', 'plainmark' ) );
	update_post_meta( $post_id, 'work_learnings', __( '実装や運用で学んだことを追記してください。', 'plainmark' ) );
	update_post_meta( $post_id, 'work_next_steps', __( '今後の改善予定を追記してください。', 'plainmark' ) );
	update_post_meta( $post_id, 'work_role', __( '企画 / 設計 / 実装', 'plainmark' ) );
	update_post_meta( $post_id, 'work_period', plainmark_format_github_repo_date( $repo['pushed_at'] ?? '' ) );
	update_post_meta( $post_id, 'work_github_url', $repo['html_url'] ?? '' );
	update_post_meta( $post_id, 'work_demo_url', $repo['homepage'] ?? '' );
	update_post_meta( $post_id, '_plainmark_github_repo_id', (string) $repo['id'] );
	update_post_meta( $post_id, '_plainmark_github_repo_full_name', $repo['full_name'] ?? '' );
	update_post_meta( $post_id, '_plainmark_github_repo_pushed_at', $repo['pushed_at'] ?? '' );
	update_post_meta( $post_id, '_plainmark_github_repo_stars', (string) ( $repo['stars'] ?? 0 ) );

	$terms = plainmark_get_repository_technology_terms( $repo );
	if ( $terms ) {
		wp_set_object_terms( $post_id, $terms, 'technology', false );
	}

	$links[ $repo_id ] = $post_id;
	update_option( PLAINMARK_GITHUB_WORKS_LINKS_OPTION, $links );

	return $post_id;
}

/**
 * Build Work body from repository metadata.
 *
 * @param array<string, mixed> $repo Repository data.
 * @return string
 */
function plainmark_build_work_draft_content_from_repo( $repo ) {
	$lines = array(
		'## 概要',
		'',
		$repo['description'] ?: 'このWorksは、GitHubリポジトリから自動生成された下書きです。概要を追記してください。',
		'',
		'## 課題',
		'',
		'<!-- なぜ作ったか、どんな課題を解決したかったかを書く -->',
		'',
		'## 実装内容',
		'',
		'<!-- 何を実装したかを書く -->',
		'',
		'## 技術構成',
		'',
		plainmark_build_work_architecture_from_repo( $repo ),
		'',
		'## 工夫した点',
		'',
		'<!-- 設計・実装で工夫した点を書く -->',
		'',
		'## 今後の改善',
		'',
		'<!-- 追加したい機能や改善予定を書く -->',
	);

	return implode( "\n", $lines );
}

/**
 * Build architecture summary from repository metadata.
 *
 * @param array<string, mixed> $repo Repository data.
 * @return string
 */
function plainmark_build_work_architecture_from_repo( $repo ) {
	$items = array();

	if ( ! empty( $repo['language'] ) ) {
		$items[] = sprintf( 'Main language: %s', $repo['language'] );
	}
	if ( ! empty( $repo['topics'] ) && is_array( $repo['topics'] ) ) {
		$items[] = 'Topics: ' . implode( ', ', $repo['topics'] );
	}
	if ( ! empty( $repo['pushed_at'] ) ) {
		$items[] = 'Last pushed: ' . $repo['pushed_at'];
	}

	return $items ? implode( "\n", $items ) : __( '技術構成を追記してください。', 'plainmark' );
}

/**
 * Convert repo metadata to technology terms.
 *
 * @param array<string, mixed> $repo Repository data.
 * @return array<int, string>
 */
function plainmark_get_repository_technology_terms( $repo ) {
	$terms = array();

	if ( ! empty( $repo['language'] ) ) {
		$terms[] = $repo['language'];
	}

	if ( ! empty( $repo['topics'] ) && is_array( $repo['topics'] ) ) {
		foreach ( $repo['topics'] as $topic ) {
			$terms[] = $topic;
		}
	}

	$terms = array_map( 'sanitize_text_field', $terms );
	$terms = array_filter( array_unique( $terms ) );

	return array_values( $terms );
}

/**
 * Format a GitHub date for the Work period field.
 *
 * @param string $date GitHub date.
 * @return string
 */
function plainmark_format_github_repo_date( $date ) {
	$timestamp = $date ? strtotime( $date ) : false;
	if ( ! $timestamp ) {
		return '';
	}

	return wp_date( 'Y.m', $timestamp );
}

/** Render admin page. */
function plainmark_render_github_works_sync_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'plainmark' ) );
	}

	$username = get_option( PLAINMARK_GITHUB_WORKS_USERNAME_OPTION, '' );
	$repos    = get_option( PLAINMARK_GITHUB_WORKS_REPOS_OPTION, array() );
	$links    = get_option( PLAINMARK_GITHUB_WORKS_LINKS_OPTION, array() );
	$notice   = isset( $_GET['plainmark_github_works_notice'] ) ? sanitize_key( wp_unslash( $_GET['plainmark_github_works_notice'] ) ) : '';
	$error    = isset( $_GET['plainmark_github_works_error'] ) ? sanitize_text_field( wp_unslash( $_GET['plainmark_github_works_error'] ) ) : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GitHub Works Sync', 'plainmark' ); ?></h1>
		<p><?php esc_html_e( 'GitHubのpublicリポジトリを同期し、選択したリポジトリからPortfolio下書きを作成します。', 'plainmark' ); ?></p>

		<?php plainmark_render_github_works_sync_notice( $notice, $error ); ?>

		<div class="card" style="max-width: 920px;">
			<h2><?php esc_html_e( 'Repository Sync', 'plainmark' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'plainmark_github_works_sync' ); ?>
				<input type="hidden" name="plainmark_github_works_action" value="sync">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="plainmark_github_username"><?php esc_html_e( 'GitHub username', 'plainmark' ); ?></label></th>
						<td>
							<input name="plainmark_github_username" id="plainmark_github_username" type="text" class="regular-text" value="<?php echo esc_attr( $username ); ?>" placeholder="masakiShito">
							<p class="description"><?php esc_html_e( 'MVPではpublic repositoryのみ同期します。private repositoryやtoken認証は未対応です。', 'plainmark' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'GitHubから同期', 'plainmark' ) ); ?>
			</form>
		</div>

		<?php if ( is_array( $repos ) && $repos ) : ?>
			<h2><?php esc_html_e( 'Synced Repositories', 'plainmark' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Repository', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Language', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Topics', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Works', 'plainmark' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $repos as $repo_id => $repo ) : ?>
						<?php
						$linked_post_id = ! empty( $links[ $repo_id ] ) ? absint( $links[ $repo_id ] ) : 0;
						$linked_post    = $linked_post_id ? get_post( $linked_post_id ) : null;
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $repo['name'] ); ?></strong>
								<?php if ( ! empty( $repo['fork'] ) ) : ?>
									<span class="dashicons dashicons-networking" title="fork"></span>
								<?php endif; ?>
								<?php if ( ! empty( $repo['archived'] ) ) : ?>
									<span class="dashicons dashicons-archive" title="archived"></span>
								<?php endif; ?>
								<p class="description"><?php echo esc_html( $repo['description'] ?: __( 'No description', 'plainmark' ) ); ?></p>
								<?php if ( ! empty( $repo['html_url'] ) ) : ?>
									<a href="<?php echo esc_url( $repo['html_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open GitHub', 'plainmark' ); ?></a>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $repo['language'] ?: '-' ); ?></td>
							<td><?php echo esc_html( ! empty( $repo['topics'] ) ? implode( ', ', $repo['topics'] ) : '-' ); ?></td>
							<td><?php echo esc_html( plainmark_format_github_repo_date( $repo['pushed_at'] ?? '' ) ?: '-' ); ?></td>
							<td>
								<?php if ( $linked_post ) : ?>
									<a class="button" href="<?php echo esc_url( get_edit_post_link( $linked_post_id, '' ) ); ?>"><?php esc_html_e( 'Worksを編集', 'plainmark' ); ?></a>
								<?php else : ?>
									<form method="post">
										<?php wp_nonce_field( 'plainmark_github_works_sync' ); ?>
										<input type="hidden" name="plainmark_github_works_action" value="create_work">
										<input type="hidden" name="plainmark_github_repo_id" value="<?php echo esc_attr( (string) $repo_id ); ?>">
										<?php submit_button( __( 'Works下書きを作成', 'plainmark' ), 'secondary small', 'submit', false ); ?>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render admin notices.
 *
 * @param string $notice Notice key.
 * @param string $error Error text.
 */
function plainmark_render_github_works_sync_notice( $notice, $error = '' ) {
	if ( ! $notice ) {
		return;
	}

	$message = '';
	$type    = 'success';

	if ( 'synced' === $notice ) {
		$message = __( 'GitHub repositories synced.', 'plainmark' );
	} elseif ( 'created' === $notice ) {
		$created = isset( $_GET['plainmark_created_work'] ) ? absint( $_GET['plainmark_created_work'] ) : 0;
		$message = __( 'Works draft created.', 'plainmark' );
		if ( $created ) {
			$message .= ' <a href="' . esc_url( get_edit_post_link( $created, '' ) ) . '">' . esc_html__( 'Edit draft', 'plainmark' ) . '</a>';
		}
	} elseif ( 'empty_username' === $notice ) {
		$type    = 'error';
		$message = __( 'GitHub username is required.', 'plainmark' );
	} elseif ( 'sync_error' === $notice || 'create_error' === $notice ) {
		$type    = 'error';
		$message = $error ? rawurldecode( $error ) : __( 'GitHub Works Sync failed.', 'plainmark' );
	}

	if ( ! $message ) {
		return;
	}

	echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
}
