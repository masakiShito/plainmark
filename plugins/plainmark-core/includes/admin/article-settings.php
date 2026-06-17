<?php
/**
 * Article settings meta box
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register series meta fields for REST API (Block Editor sidebar).
 */
function plainmark_register_series_meta() {
	$post_types = plainmark_get_article_settings_post_types();

	foreach ( $post_types as $post_type ) {
		register_post_meta(
			$post_type,
			'_plainmark_series_name',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			$post_type,
			'_plainmark_series_order',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'plainmark_register_series_meta' );

/**
 * Register REST API endpoint to get all series names.
 */
function plainmark_register_series_rest_route() {
	register_rest_route(
		'plainmark/v1',
		'/series',
		array(
			'methods'             => 'GET',
			'callback'            => 'plainmark_get_all_series_names',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'rest_api_init', 'plainmark_register_series_rest_route' );

/**
 * Get all unique series names.
 *
 * @return WP_REST_Response List of series names.
 */
function plainmark_get_all_series_names() {
	global $wpdb;

	$series_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_value
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value != ''
			AND p.post_status IN ('publish', 'draft', 'pending', 'private')
			ORDER BY meta_value ASC",
			'_plainmark_series_name'
		)
	);

	return rest_ensure_response( $series_names ? $series_names : array() );
}

if ( ! defined( 'PLAINMARK_ARTICLE_SETTINGS_NONCE_ACTION' ) ) {
    define( 'PLAINMARK_ARTICLE_SETTINGS_NONCE_ACTION', 'plainmark_save_article_settings' );
}

if ( ! defined( 'PLAINMARK_ARTICLE_SETTINGS_NONCE_NAME' ) ) {
    define( 'PLAINMARK_ARTICLE_SETTINGS_NONCE_NAME', 'plainmark_article_settings_nonce' );
}

/**
 * Get post types supported by article settings.
 *
 * @return array<int, string>
 */
if ( ! function_exists( 'plainmark_get_article_settings_post_types' ) ) {
    function plainmark_get_article_settings_post_types() {
        return apply_filters( 'plainmark_article_settings_post_types', array( 'post' ) );
    }
}

/**
 * Get allowed article type options.
 *
 * @return array<string, string>
 */
if ( ! function_exists( 'plainmark_get_article_type_options' ) ) {
    function plainmark_get_article_type_options() {
        return array(
            'tech_note'      => __( '技術メモ', 'plainmark' ),
            'tutorial'       => __( 'チュートリアル', 'plainmark' ),
            'error_solution' => __( 'エラー解決', 'plainmark' ),
            'learning_log'   => __( '学習ログ', 'plainmark' ),
            'review'         => __( 'レビュー', 'plainmark' ),
            'portfolio'      => __( 'ポートフォリオ', 'plainmark' ),
        );
    }
}

/**
 * Get allowed difficulty options.
 *
 * @return array<string, string>
 */
if ( ! function_exists( 'plainmark_get_difficulty_options' ) ) {
    function plainmark_get_difficulty_options() {
        return array(
            'beginner'     => __( 'Beginner', 'plainmark' ),
            'intermediate' => __( 'Intermediate', 'plainmark' ),
            'advanced'     => __( 'Advanced', 'plainmark' ),
        );
    }
}

/**
 * Get article metadata with normalized defaults.
 *
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return array<string, string|bool>
 */
if ( ! function_exists( 'plainmark_get_article_meta' ) ) {
	function plainmark_get_article_meta( $post_id = null ) {
		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		$defaults = array(
			'article_type'       => '',
			'article_type_label' => '',
			'difficulty'         => '',
			'difficulty_label'   => '',
			'target_reader'      => '',
			'prerequisites'      => '',
			'github_url'         => '',
			'official_docs_url'  => '',
			'show_toc'           => true,
			'show_code_copy'     => true,
			'series_name'        => '',
			'series_order'       => '',
		);

		if ( ! $post_id ) {
			return $defaults;
		}

		$article_type       = (string) get_post_meta( $post_id, '_plainmark_article_type', true );
		$difficulty         = (string) get_post_meta( $post_id, '_plainmark_difficulty', true );
		$article_types      = plainmark_get_article_type_options();
		$difficulty_options = plainmark_get_difficulty_options();

		return array(
			'article_type'       => $article_type,
			'article_type_label' => isset( $article_types[ $article_type ] ) ? $article_types[ $article_type ] : '',
			'difficulty'         => $difficulty,
			'difficulty_label'   => isset( $difficulty_options[ $difficulty ] ) ? $difficulty_options[ $difficulty ] : '',
			'target_reader'      => (string) get_post_meta( $post_id, '_plainmark_target_reader', true ),
			'prerequisites'      => (string) get_post_meta( $post_id, '_plainmark_prerequisites', true ),
			'github_url'         => (string) get_post_meta( $post_id, '_plainmark_github_url', true ),
			'official_docs_url'  => (string) get_post_meta( $post_id, '_plainmark_official_docs_url', true ),
			'show_toc'           => metadata_exists( 'post', $post_id, '_plainmark_show_toc' )
				? '1' === get_post_meta( $post_id, '_plainmark_show_toc', true )
				: true,
			'show_code_copy'     => metadata_exists( 'post', $post_id, '_plainmark_show_code_copy' )
				? '1' === get_post_meta( $post_id, '_plainmark_show_code_copy', true )
				: true,
			'series_name'        => (string) get_post_meta( $post_id, '_plainmark_series_name', true ),
			'series_order'       => (string) get_post_meta( $post_id, '_plainmark_series_order', true ),
		);
	}
}

/**
 * Register the article settings meta box.
 */
if ( ! function_exists( 'plainmark_add_article_settings_meta_box' ) ) {
    function plainmark_add_article_settings_meta_box() {
        foreach ( plainmark_get_article_settings_post_types() as $post_type ) {
            add_meta_box(
                'plainmark-article-settings',
                __( 'plainmark Article Settings', 'plainmark' ),
                'plainmark_render_article_settings_meta_box',
                $post_type,
                'normal',
                'high'
            );
        }
    }
}
add_action( 'add_meta_boxes', 'plainmark_add_article_settings_meta_box' );

/**
 * Render the article settings meta box.
 *
 * @param WP_Post $post Current post object.
 */
if ( ! function_exists( 'plainmark_render_article_settings_meta_box' ) ) {
    function plainmark_render_article_settings_meta_box( $post ) {
        $article_meta       = plainmark_get_article_meta( $post->ID );
        $article_types      = plainmark_get_article_type_options();
        $difficulty_options = plainmark_get_difficulty_options();

        wp_nonce_field( PLAINMARK_ARTICLE_SETTINGS_NONCE_ACTION, PLAINMARK_ARTICLE_SETTINGS_NONCE_NAME );
        ?>
        <style>
            .plainmark-article-settings__field { margin: 0 0 20px; }
            .plainmark-article-settings__field:last-child { margin-bottom: 0; }
            .plainmark-article-settings__label { display: block; margin-bottom: 6px; font-weight: 600; }
            .plainmark-article-settings__control { width: 100%; max-width: 100%; }
            .plainmark-article-settings__description { margin: 6px 0 0; color: #646970; font-size: 12px; }
            .plainmark-article-settings__check { display: flex; align-items: flex-start; gap: 8px; }
            .plainmark-article-settings__check input { margin-top: 2px; }
        </style>

        <div class="plainmark-article-settings">
            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-article-type">
                    <?php esc_html_e( 'Article Type', 'plainmark' ); ?>
                </label>
                <select class="plainmark-article-settings__control" id="plainmark-article-type" name="plainmark_article_type">
                    <option value=""><?php esc_html_e( 'Select an article type', 'plainmark' ); ?></option>
                    <?php foreach ( $article_types as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $article_meta['article_type'], $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="plainmark-article-settings__description">
                    <?php esc_html_e( 'Choose the format that best describes this article.', 'plainmark' ); ?>
                </p>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-difficulty">
                    <?php esc_html_e( 'Difficulty', 'plainmark' ); ?>
                </label>
                <select class="plainmark-article-settings__control" id="plainmark-difficulty" name="plainmark_difficulty">
                    <option value=""><?php esc_html_e( 'Select a difficulty', 'plainmark' ); ?></option>
                    <?php foreach ( $difficulty_options as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $article_meta['difficulty'], $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-target-reader">
                    <?php esc_html_e( 'Target Reader', 'plainmark' ); ?>
                </label>
                <input
                    class="plainmark-article-settings__control"
                    id="plainmark-target-reader"
                    name="plainmark_target_reader"
                    type="text"
                    value="<?php echo esc_attr( $article_meta['target_reader'] ); ?>"
                    placeholder="<?php echo esc_attr__( 'React初心者、WordPressテーマ開発者', 'plainmark' ); ?>"
                >
                <p class="plainmark-article-settings__description">
                    <?php esc_html_e( 'Describe the readers this article is intended for.', 'plainmark' ); ?>
                </p>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-prerequisites">
                    <?php esc_html_e( 'Prerequisites', 'plainmark' ); ?>
                </label>
                <textarea
                    class="plainmark-article-settings__control"
                    id="plainmark-prerequisites"
                    name="plainmark_prerequisites"
                    rows="4"
                    placeholder="<?php echo esc_attr__( 'HTML/CSSの基礎、PHPの基本、WordPressの基本操作', 'plainmark' ); ?>"
                ><?php echo esc_textarea( $article_meta['prerequisites'] ); ?></textarea>
                <p class="plainmark-article-settings__description">
                    <?php esc_html_e( 'List the knowledge or setup readers should have beforehand.', 'plainmark' ); ?>
                </p>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-github-url">
                    <?php esc_html_e( 'GitHub URL', 'plainmark' ); ?>
                </label>
                <input
                    class="plainmark-article-settings__control"
                    id="plainmark-github-url"
                    name="plainmark_github_url"
                    type="url"
                    value="<?php echo esc_attr( $article_meta['github_url'] ); ?>"
                    placeholder="https://github.com/"
                >
                <p class="plainmark-article-settings__description">
                    <?php esc_html_e( 'Add a related repository or source code URL.', 'plainmark' ); ?>
                </p>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__label" for="plainmark-official-docs-url">
                    <?php esc_html_e( 'Official Docs URL', 'plainmark' ); ?>
                </label>
                <input
                    class="plainmark-article-settings__control"
                    id="plainmark-official-docs-url"
                    name="plainmark_official_docs_url"
                    type="url"
                    value="<?php echo esc_attr( $article_meta['official_docs_url'] ); ?>"
                    placeholder="https://"
                >
                <p class="plainmark-article-settings__description">
                    <?php esc_html_e( 'Add an official documentation or reference URL.', 'plainmark' ); ?>
                </p>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__check" for="plainmark-show-toc">
                    <input
                        id="plainmark-show-toc"
                        name="plainmark_show_toc"
                        type="checkbox"
                        value="1"
                        <?php checked( $article_meta['show_toc'] ); ?>
                    >
                    <span>
                        <strong><?php esc_html_e( 'Show TOC', 'plainmark' ); ?></strong><br>
                        <span class="plainmark-article-settings__description">
                            <?php esc_html_e( 'Display a table of contents on the article page.', 'plainmark' ); ?>
                        </span>
                    </span>
                </label>
            </div>

            <div class="plainmark-article-settings__field">
                <label class="plainmark-article-settings__check" for="plainmark-show-code-copy">
                    <input
                        id="plainmark-show-code-copy"
                        name="plainmark_show_code_copy"
                        type="checkbox"
                        value="1"
                        <?php checked( $article_meta['show_code_copy'] ); ?>
                    >
                    <span>
                        <strong><?php esc_html_e( 'Show Code Copy Button', 'plainmark' ); ?></strong><br>
                        <span class="plainmark-article-settings__description">
                            <?php esc_html_e( 'Display copy controls for code blocks on the article page.', 'plainmark' ); ?>
                        </span>
                    </span>
                </label>
            </div>

        </div>
        <?php
    }
}

/**
 * Save article settings metadata.
 *
 * @param int $post_id Post ID.
 */
if ( ! function_exists( 'plainmark_save_article_settings_meta' ) ) {
    function plainmark_save_article_settings_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST[ PLAINMARK_ARTICLE_SETTINGS_NONCE_NAME ] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST[ PLAINMARK_ARTICLE_SETTINGS_NONCE_NAME ] ) );
        if ( ! wp_verify_nonce( $nonce, PLAINMARK_ARTICLE_SETTINGS_NONCE_ACTION ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! in_array( get_post_type( $post_id ), plainmark_get_article_settings_post_types(), true ) ) {
            return;
        }

        $article_types      = plainmark_get_article_type_options();
        $difficulty_options = plainmark_get_difficulty_options();
        $article_type       = isset( $_POST['plainmark_article_type'] )
            ? sanitize_text_field( wp_unslash( $_POST['plainmark_article_type'] ) )
            : '';
        $difficulty         = isset( $_POST['plainmark_difficulty'] )
            ? sanitize_text_field( wp_unslash( $_POST['plainmark_difficulty'] ) )
            : '';

        if ( ! array_key_exists( $article_type, $article_types ) ) {
            $article_type = '';
        }

        if ( ! array_key_exists( $difficulty, $difficulty_options ) ) {
            $difficulty = '';
        }

        update_post_meta( $post_id, '_plainmark_article_type', $article_type );
        update_post_meta( $post_id, '_plainmark_difficulty', $difficulty );
        update_post_meta(
            $post_id,
            '_plainmark_target_reader',
            isset( $_POST['plainmark_target_reader'] )
                ? sanitize_text_field( wp_unslash( $_POST['plainmark_target_reader'] ) )
                : ''
        );
        update_post_meta(
            $post_id,
            '_plainmark_prerequisites',
            isset( $_POST['plainmark_prerequisites'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['plainmark_prerequisites'] ) )
                : ''
        );
        update_post_meta(
            $post_id,
            '_plainmark_github_url',
            isset( $_POST['plainmark_github_url'] )
                ? esc_url_raw( wp_unslash( $_POST['plainmark_github_url'] ) )
                : ''
        );
        update_post_meta(
            $post_id,
            '_plainmark_official_docs_url',
            isset( $_POST['plainmark_official_docs_url'] )
                ? esc_url_raw( wp_unslash( $_POST['plainmark_official_docs_url'] ) )
                : ''
        );
        update_post_meta( $post_id, '_plainmark_show_toc', isset( $_POST['plainmark_show_toc'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_plainmark_show_code_copy', isset( $_POST['plainmark_show_code_copy'] ) ? '1' : '0' );
        // Series settings are saved via REST API (Block Editor sidebar).
    }
}
add_action( 'save_post', 'plainmark_save_article_settings_meta' );

/**
 * Generate table of contents from headings.
 *
 * @param string $content Post content.
 * @return string TOC HTML list or empty string if no headings.
 */
if ( ! function_exists( 'plainmark_get_toc' ) ) {
	function plainmark_get_toc( $content ) {
		if ( ! $content ) {
			return '';
		}

		// Match h2 and h3 headings.
		$pattern = '/<h([23])[^>]*>(.+?)<\/h[23]>/i';
		if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			return '';
		}

		$toc    = '<ol class="article-toc__list">';
		$in_sub = false;

		foreach ( $matches as $match ) {
			$level = (int) $match[1];
			$text  = wp_strip_all_tags( $match[2] );
			$id    = sanitize_title( $text );

			if ( 2 === $level ) {
				if ( $in_sub ) {
					$toc   .= '</ol></li>';
					$in_sub = false;
				}
				$toc .= sprintf(
					'<li class="article-toc__item article-toc__item--h2"><a class="article-toc__link" href="#%s">%s</a>',
					esc_attr( $id ),
					esc_html( $text )
				);
			} elseif ( 3 === $level ) {
				if ( ! $in_sub ) {
					$toc   .= '<ol class="article-toc__sublist">';
					$in_sub = true;
				}
				$toc .= sprintf(
					'<li class="article-toc__item article-toc__item--h3"><a class="article-toc__link" href="#%s">%s</a></li>',
					esc_attr( $id ),
					esc_html( $text )
				);
			}
		}

		if ( $in_sub ) {
			$toc .= '</ol></li>';
		}

		$toc .= '</ol>';

		return $toc;
	}
}

/**
 * Add IDs to headings in post content.
 *
 * @param string $content Post content.
 * @return string Content with heading IDs.
 */
if ( ! function_exists( 'plainmark_add_heading_ids' ) ) {
	function plainmark_add_heading_ids( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$pattern = '/<h([23])([^>]*)>(.+?)<\/h([23])>/i';

		$content = preg_replace_callback( $pattern, function ( $match ) {
			$level    = $match[1];
			$attrs    = $match[2];
			$text     = $match[3];
			$id       = sanitize_title( wp_strip_all_tags( $text ) );

			// If ID already exists, keep it.
			if ( preg_match( '/\bid\s*=\s*["\']/', $attrs ) ) {
				return $match[0];
			}

			return sprintf( '<h%s id="%s"%s>%s</h%s>', $level, esc_attr( $id ), $attrs, $text, $level );
		}, $content );

		return $content;
	}
}
add_filter( 'the_content', 'plainmark_add_heading_ids', 5 );
