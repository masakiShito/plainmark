<?php
/**
 * Verified articles, Blog/Works relations and GitHub-managed content.
 *
 * @package plainmark
 * @since 0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register metadata used by the content bridge. */
function plainmark_register_content_bridge_meta() {
	$definitions = array(
		'_plainmark_verified_status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
		'_plainmark_verified_date'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_verified_env'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
		'_plainmark_review_date'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_ci_status'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
		'_plainmark_ci_checked_at'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_ci_run_url'       => array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ),
		'_plainmark_tested_path'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_test_command'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_related_works'    => array( 'type' => 'array', 'sanitize_callback' => 'plainmark_sanitize_id_array' ),
		'_plainmark_related_posts'    => array( 'type' => 'array', 'sanitize_callback' => 'plainmark_sanitize_id_array' ),
		'_plainmark_github_path'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_github_sha'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
		'_plainmark_github_synced_at' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
	);

	$post_only_keys = array(
		'_plainmark_verified_status',
		'_plainmark_verified_date',
		'_plainmark_verified_env',
		'_plainmark_review_date',
		'_plainmark_ci_status',
		'_plainmark_ci_checked_at',
		'_plainmark_ci_run_url',
		'_plainmark_tested_path',
		'_plainmark_test_command',
		'_plainmark_related_works',
	);

	foreach ( array( 'post', 'portfolio' ) as $post_type ) {
		foreach ( $definitions as $key => $definition ) {
			if ( 'post' !== $post_type && in_array( $key, $post_only_keys, true ) ) {
				continue;
			}
			if ( 'portfolio' !== $post_type && '_plainmark_related_posts' === $key ) {
				continue;
			}

			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $definition['type'],
					'single'            => true,
					'show_in_rest'      => 'array' === $definition['type']
						? array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) )
						: true,
					'sanitize_callback' => $definition['sanitize_callback'],
					'auth_callback'     => static function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'plainmark_register_content_bridge_meta' );

/** Enqueue frontend styles. */
function plainmark_enqueue_content_bridge_assets() {
	$css = PLAINMARK_DIR . '/assets/css/content-bridge.css';
	wp_enqueue_style(
		'plainmark-content-bridge',
		PLAINMARK_URI . '/assets/css/content-bridge.css',
		array( 'plainmark-style' ),
		file_exists( $css ) ? (string) filemtime( $css ) : PLAINMARK_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'plainmark_enqueue_content_bridge_assets', 20 );

/** Sanitize post ID arrays. */
function plainmark_sanitize_id_array( $value ) {
	return array_values( array_unique( array_filter( array_map( 'absint', is_array( $value ) ? $value : array() ) ) ) );
}

/** Get verification data for a post. */
function plainmark_get_verification_data( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$status  = get_post_meta( $post_id, '_plainmark_verified_status', true );
	$date    = get_post_meta( $post_id, '_plainmark_verified_date', true );
	$review  = get_post_meta( $post_id, '_plainmark_review_date', true );

	if ( $review && strtotime( $review ) < current_datetime()->getTimestamp() && 'deprecated' !== $status ) {
		$status = 'review_due';
	}

	return array(
		'status' => $status ?: 'unverified',
		'date'   => $date,
		'env'    => get_post_meta( $post_id, '_plainmark_verified_env', true ),
		'review' => $review,
	);
}

/**
 * Get CI verification data for an article.
 *
 * @param int $post_id Post ID.
 * @return array{status:string,checked_at:string,run_url:string,tested_path:string,test_command:string}
 */
function plainmark_get_ci_data( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$status  = (string) get_post_meta( $post_id, '_plainmark_ci_status', true );

	return array(
		'status'       => $status ?: 'unknown',
		'checked_at'   => (string) get_post_meta( $post_id, '_plainmark_ci_checked_at', true ),
		'run_url'      => (string) get_post_meta( $post_id, '_plainmark_ci_run_url', true ),
		'tested_path'  => (string) get_post_meta( $post_id, '_plainmark_tested_path', true ),
		'test_command' => (string) get_post_meta( $post_id, '_plainmark_test_command', true ),
	);
}

/** Get the public label for a verification status. */
function plainmark_get_verification_label( $status ) {
	$labels = array(
		'verified'   => __( '動作確認済み', 'plainmark' ),
		'unverified' => __( '未検証', 'plainmark' ),
		'review_due' => __( '再確認が必要', 'plainmark' ),
		'deprecated' => __( '非推奨', 'plainmark' ),
	);
	return $labels[ $status ] ?? $labels['unverified'];
}

/** Add verification panel (with freshness score) before article body. */
function plainmark_add_verification_card( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$data      = plainmark_get_verification_data();
	$freshness = function_exists( 'plainmark_get_freshness_score' ) ? plainmark_get_freshness_score() : null;

	if ( 'unverified' === $data['status'] && ! $data['env'] && ! $data['date'] && ! $freshness ) {
		return $content;
	}

	$html  = '<aside class="article-verification article-verification--' . esc_attr( $data['status'] ) . '">';
	$html .= '<div class="article-verification__header">';
	$html .= '<div class="article-verification__status">';
	$html .= '<span aria-hidden="true">' . ( 'verified' === $data['status'] ? '✓' : '!' ) . '</span>';
	$html .= '<strong>' . esc_html( plainmark_get_verification_label( $data['status'] ) ) . '</strong>';
	$html .= '</div>';

	if ( $freshness ) {
		$html .= '<span class="article-verification__freshness article-verification__freshness--' . esc_attr( $freshness['rank'] ) . '">';
		$html .= '<span>' . esc_html__( 'Freshness', 'plainmark' ) . '</span> ';
		$html .= '<strong>' . esc_html( (string) $freshness['score'] ) . '</strong>';
		$html .= '</span>';
	}

	$html .= '</div>';
	$html .= '<div class="article-verification__details">';
	if ( $data['date'] ) {
		$html .= '<span>' . sprintf( esc_html__( '最終確認: %s', 'plainmark' ), esc_html( $data['date'] ) ) . '</span>';
	}
	if ( $data['env'] ) {
		$html .= '<span>' . nl2br( esc_html( $data['env'] ) ) . '</span>';
	}
	if ( $data['review'] ) {
		$html .= '<span>' . sprintf( esc_html__( 'レビュー: %s', 'plainmark' ), esc_html( $data['review'] ) ) . '</span>';
	}
	if ( $freshness && ! empty( $freshness['reasons'] ) ) {
		foreach ( array_slice( $freshness['reasons'], 0, 2 ) as $reason ) {
			$html .= '<span class="article-verification__reason">' . esc_html( $reason ) . '</span>';
		}
	}
	$html .= '</div></aside>';

	return $html . $content;
}
add_filter( 'the_content', 'plainmark_add_verification_card', 11 );

/** Return related content IDs, including reverse links. */
function plainmark_get_related_content_ids( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array();
	}

	$meta_key  = 'post' === $post->post_type ? '_plainmark_related_works' : '_plainmark_related_posts';
	$other_key = 'post' === $post->post_type ? '_plainmark_related_posts' : '_plainmark_related_works';
	$other     = 'post' === $post->post_type ? 'portfolio' : 'post';
	$related   = plainmark_sanitize_id_array( get_post_meta( $post_id, $meta_key, true ) );
	$reverse   = get_posts(
		array(
			'post_type'      => $other,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => $other_key,
					'value'   => sprintf( 'i:%d;', absint( $post_id ) ),
					'compare' => 'LIKE',
				),
			),
		)
	);

	// LIKE はシリアライズ値の部分一致なので、別の数値の一部に誤マッチしないようPHP側で厳密に確認する.
	if ( ! empty( $reverse ) ) {
		$reverse = array_values(
			array_filter(
				$reverse,
				static function ( $candidate_id ) use ( $post_id, $other_key ) {
					$stored = plainmark_sanitize_id_array( get_post_meta( $candidate_id, $other_key, true ) );
					return in_array( absint( $post_id ), $stored, true );
				}
			)
		);
	}

	return array_values( array_unique( array_merge( $related, $reverse ) ) );
}

/** Append related Blog/Works cards. */
function plainmark_append_related_content( $content ) {
	if ( ! is_singular( array( 'post', 'portfolio' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$ids = plainmark_get_related_content_ids( get_the_ID() );
	if ( empty( $ids ) ) {
		return $content;
	}

	$is_post = 'post' === get_post_type();
	$html    = '<section class="content-bridge"><div class="content-bridge__heading"><p>' . ( $is_post ? 'FROM KNOWLEDGE TO PRODUCT' : 'FROM PRODUCT TO KNOWLEDGE' ) . '</p><h2>';
	$html   .= $is_post ? esc_html__( 'この知識を使ったWorks', 'plainmark' ) : esc_html__( 'この制作物に関連する記事', 'plainmark' );
	$html   .= '</h2></div><div class="content-bridge__grid">';

	foreach ( $ids as $id ) {
		$item = get_post( $id );
		if ( ! $item || 'publish' !== $item->post_status ) {
			continue;
		}
		$html .= '<a class="content-bridge__card" href="' . esc_url( get_permalink( $id ) ) . '">';
		$html .= '<span>' . esc_html( 'portfolio' === $item->post_type ? 'WORK' : 'ARTICLE' ) . '</span>';
		$html .= '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>';
		$html .= '<p>' . esc_html( wp_trim_words( get_the_excerpt( $id ), 24, '…' ) ) . '</p>';
		$html .= '<strong>' . esc_html__( '詳しく見る', 'plainmark' ) . ' →</strong></a>';
	}
	$html .= '</div></section>';

	return $content . $html;
}
add_filter( 'the_content', 'plainmark_append_related_content', 30 );

/** Resolve front-matter references by post ID or slug. */
function plainmark_resolve_content_references( $references, $post_type ) {
	$resolved = array();
	foreach ( is_array( $references ) ? $references : array() as $reference ) {
		if ( is_numeric( $reference ) ) {
			$post = get_post( absint( $reference ) );
		} else {
			$post = get_page_by_path( sanitize_title( $reference ), OBJECT, $post_type );
		}
		if ( $post && $post_type === $post->post_type ) {
			$resolved[] = $post->ID;
		}
	}
	return plainmark_sanitize_id_array( $resolved );
}

/** Apply verification and relation front matter after Markdown import. */
function plainmark_apply_content_bridge_front_matter( $post_id, $front_matter, $post_type ) {
	if ( 'post' === $post_type ) {
		$map = array(
			'verified_status' => '_plainmark_verified_status',
			'verified_date'   => '_plainmark_verified_date',
			'verified_env'    => '_plainmark_verified_env',
			'review_date'     => '_plainmark_review_date',
		);
		foreach ( $map as $front_key => $meta_key ) {
			if ( isset( $front_matter[ $front_key ] ) ) {
				$value = 'verified_env' === $front_key ? sanitize_textarea_field( $front_matter[ $front_key ] ) : sanitize_text_field( $front_matter[ $front_key ] );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		$ci_status_allowed = array( 'passing', 'failing', 'error', 'skipped', 'unknown' );

		if ( isset( $front_matter['ci_status'] ) ) {
			$ci_status = sanitize_key( (string) $front_matter['ci_status'] );
			if ( ! in_array( $ci_status, $ci_status_allowed, true ) ) {
				$ci_status = 'unknown';
			}
			update_post_meta( $post_id, '_plainmark_ci_status', $ci_status );
		}

		if ( isset( $front_matter['ci_checked_at'] ) ) {
			update_post_meta( $post_id, '_plainmark_ci_checked_at', sanitize_text_field( (string) $front_matter['ci_checked_at'] ) );
		}

		if ( isset( $front_matter['ci_run_url'] ) ) {
			update_post_meta( $post_id, '_plainmark_ci_run_url', esc_url_raw( (string) $front_matter['ci_run_url'] ) );
		}

		if ( isset( $front_matter['tested_path'] ) ) {
			update_post_meta( $post_id, '_plainmark_tested_path', sanitize_text_field( (string) $front_matter['tested_path'] ) );
		}

		if ( isset( $front_matter['test_command'] ) ) {
			update_post_meta( $post_id, '_plainmark_test_command', sanitize_text_field( (string) $front_matter['test_command'] ) );
		}

		if ( ! empty( $front_matter['related_works'] ) ) {
			update_post_meta( $post_id, '_plainmark_related_works', plainmark_resolve_content_references( $front_matter['related_works'], 'portfolio' ) );
		}
	}

	if ( 'portfolio' === $post_type && ! empty( $front_matter['related_posts'] ) ) {
		update_post_meta( $post_id, '_plainmark_related_posts', plainmark_resolve_content_references( $front_matter['related_posts'], 'post' ) );
	}
}

/** Register authenticated GitHub sync REST endpoint. */
function plainmark_register_github_sync_route() {
	register_rest_route(
		'plainmark/v1',
		'/github-sync',
		array(
			'methods'             => 'POST',
			'callback'            => 'plainmark_handle_github_sync',
			'permission_callback' => 'plainmark_authorize_github_sync',
		)
	);
}
add_action( 'rest_api_init', 'plainmark_register_github_sync_route' );

/** Validate GitHub sync secret. */
function plainmark_authorize_github_sync( WP_REST_Request $request ) {
	$configured = defined( 'PLAINMARK_GITHUB_SYNC_SECRET' ) ? PLAINMARK_GITHUB_SYNC_SECRET : get_option( 'plainmark_github_sync_secret', '' );
	$provided   = $request->get_header( 'x-plainmark-secret' );
	return $configured && $provided && hash_equals( (string) $configured, (string) $provided );
}

/**
 * Import Markdown content and update GitHub sync metadata.
 *
 * Shared logic for all GitHub sync endpoints (REST, AJAX, pull).
 *
 * @param string $markdown Raw Markdown content.
 * @param string $path     Repository file path.
 * @param string $sha      Git blob SHA.
 * @return array{id:int,action:string,path:string}|WP_Error
 */
function plainmark_sync_markdown( $markdown, $path, $sha ) {
	if ( '' === trim( $markdown ) ) {
		return new WP_Error( 'plainmark_empty_content', __( 'Markdown content is required.', 'plainmark' ), array( 'status' => 400 ) );
	}

	if ( ! function_exists( 'plainmark_parse_md_content' ) || ! function_exists( 'plainmark_import_single_md' ) ) {
		return new WP_Error( 'plainmark_importer_unavailable', __( 'Markdown importer is unavailable.', 'plainmark' ), array( 'status' => 500 ) );
	}

	$parsed = plainmark_parse_md_content( $markdown );
	$result = plainmark_import_single_md( $markdown, true );

	if ( ! $result || empty( $result['id'] ) ) {
		return new WP_Error( 'plainmark_import_failed', __( 'Markdown import failed.', 'plainmark' ), array( 'status' => 422 ) );
	}

	$post_id   = absint( $result['id'] );
	$post_type = get_post_type( $post_id );

	if ( $parsed && ! empty( $parsed['front_matter'] ) && function_exists( 'plainmark_apply_content_bridge_front_matter' ) ) {
		plainmark_apply_content_bridge_front_matter( $post_id, $parsed['front_matter'], $post_type );
	}

	update_post_meta( $post_id, '_plainmark_github_path', sanitize_text_field( $path ) );
	update_post_meta( $post_id, '_plainmark_github_sha', sanitize_text_field( $sha ) );
	update_post_meta( $post_id, '_plainmark_github_synced_at', current_time( 'mysql' ) );

	return array(
		'id'     => $post_id,
		'action' => sanitize_key( $result['action'] ?? 'updated' ),
		'path'   => $path,
	);
}

/** Import Markdown sent by GitHub Actions. */
function plainmark_handle_github_sync( WP_REST_Request $request ) {
	$markdown = (string) $request->get_param( 'content' );
	$path     = sanitize_text_field( (string) $request->get_param( 'path' ) );
	$sha      = sanitize_text_field( (string) $request->get_param( 'sha' ) );

	$result = plainmark_sync_markdown( $markdown, $path, $sha );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response(
		array(
			'success' => true,
			'post_id' => $result['id'],
			'action'  => $result['action'],
			'path'    => $result['path'],
		)
	);
}

/** Add GitHub sync settings page. */
function plainmark_add_github_content_page() {
	add_management_page( __( 'GitHub Content', 'plainmark' ), __( 'GitHub Content', 'plainmark' ), 'manage_options', 'plainmark-github-content', 'plainmark_render_github_content_page' );
}
add_action( 'admin_menu', 'plainmark_add_github_content_page' );

/** Render GitHub sync setup instructions and secret generator. */
function plainmark_render_github_content_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['plainmark_generate_sync_secret'] ) && check_admin_referer( 'plainmark_generate_sync_secret' ) ) {
		update_option( 'plainmark_github_sync_secret', wp_generate_password( 48, false, false ), false );
	}
	$secret = get_option( 'plainmark_github_sync_secret', '' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GitHub Content', 'plainmark' ); ?></h1>
		<p><?php esc_html_e( 'content/posts と content/works のMarkdownを、GitHub ActionsからWordPressへ同期できます。', 'plainmark' ); ?></p>
		<form method="post"><?php wp_nonce_field( 'plainmark_generate_sync_secret' ); ?><button class="button button-primary" name="plainmark_generate_sync_secret" value="1"><?php esc_html_e( '同期シークレットを生成・更新', 'plainmark' ); ?></button></form>
		<?php if ( $secret ) : ?>
			<h2><?php esc_html_e( 'GitHub Repository secrets', 'plainmark' ); ?></h2>
			<table class="widefat striped"><tbody>
				<tr><th>PLAINMARK_SYNC_URL</th><td><code><?php echo esc_html( rest_url( 'plainmark/v1/github-sync' ) ); ?></code></td></tr>
				<tr><th>PLAINMARK_SYNC_SECRET</th><td><code><?php echo esc_html( $secret ); ?></code></td></tr>
			</tbody></table>
			<p><strong><?php esc_html_e( '注意:', 'plainmark' ); ?></strong> <?php esc_html_e( 'シークレットはGitHubのRepository secretsに保存してください。', 'plainmark' ); ?></p>
		<?php endif; ?>
		<h2><?php esc_html_e( '追加front matter', 'plainmark' ); ?></h2>
		<pre><code>verified_status: "verified"
verified_date: "2026-06-10"
verified_env: "Node.js 24 / TypeScript 5.9"
review_date: "2026-09-10"
ci_status: "passing"
ci_checked_at: "2026-06-20T09:30:00Z"
ci_run_url: "https://github.com/&lt;owner&gt;/&lt;repo&gt;/actions/runs/123456"
tested_path: "examples/react-state"
test_command: "npm test"
related_works:
  - "face-photo-sorter"

# Works側
related_posts:
  - "typescript-guide"</code></pre>
	</div>
	<?php
}

/** Add verification and GitHub columns to post lists. */
function plainmark_content_bridge_columns( $columns ) {
	$columns['plainmark_verified'] = __( '検証', 'plainmark' );
	$columns['plainmark_source']   = __( 'Source', 'plainmark' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'plainmark_content_bridge_columns' );
add_filter( 'manage_portfolio_posts_columns', 'plainmark_content_bridge_columns' );

/** Render content bridge list columns. */
function plainmark_render_content_bridge_column( $column, $post_id ) {
	if ( 'plainmark_verified' === $column ) {
		if ( 'post' !== get_post_type( $post_id ) ) {
			echo '—';
			return;
		}
		$data = plainmark_get_verification_data( $post_id );
		echo esc_html( plainmark_get_verification_label( $data['status'] ) );
	}
	if ( 'plainmark_source' === $column ) {
		$path = get_post_meta( $post_id, '_plainmark_github_path', true );
		echo $path ? '<code>' . esc_html( $path ) . '</code>' : 'WordPress';
	}
}
add_action( 'manage_post_posts_custom_column', 'plainmark_render_content_bridge_column', 10, 2 );
add_action( 'manage_portfolio_posts_custom_column', 'plainmark_render_content_bridge_column', 10, 2 );
