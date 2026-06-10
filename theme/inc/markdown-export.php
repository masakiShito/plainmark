<?php
/**
 * Markdown Export functionality.
 *
 * Exports posts and portfolio items as Markdown files with YAML front matter.
 *
 * @package plainmark
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------
// Row Actions
// -------------------------------------------------------------------------

/**
 * Add MD Export link to post row actions.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post object.
 * @return array Modified actions.
 */
function plainmark_md_export_row_action( $actions, $post ) {
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}

	if ( ! in_array( $post->post_type, array( 'post', 'portfolio' ), true ) ) {
		return $actions;
	}

	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'action'  => 'plainmark_md_export',
				'post_id' => $post->ID,
			),
			admin_url( 'admin.php' )
		),
		'plainmark_md_export_' . $post->ID
	);

	$actions['md_export'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $export_url ),
		esc_html__( 'MD Export', 'plainmark' )
	);

	return $actions;
}
add_filter( 'post_row_actions', 'plainmark_md_export_row_action', 10, 2 );
add_filter( 'page_row_actions', 'plainmark_md_export_row_action', 10, 2 );

// -------------------------------------------------------------------------
// Bulk Actions
// -------------------------------------------------------------------------

/**
 * Add MD Export to bulk actions dropdown.
 *
 * @param array $actions Bulk actions.
 * @return array Modified actions.
 */
function plainmark_md_export_bulk_action( $actions ) {
	$actions['plainmark_md_bulk_export'] = __( 'Markdownエクスポート', 'plainmark' );
	return $actions;
}
add_filter( 'bulk_actions-edit-post', 'plainmark_md_export_bulk_action' );
add_filter( 'bulk_actions-edit-portfolio', 'plainmark_md_export_bulk_action' );

/**
 * Handle bulk export action.
 *
 * @param string $redirect_url Redirect URL.
 * @param string $action       Action name.
 * @param array  $post_ids     Selected post IDs.
 * @return string Redirect URL.
 */
function plainmark_handle_bulk_export( $redirect_url, $action, $post_ids ) {
	if ( 'plainmark_md_bulk_export' !== $action ) {
		return $redirect_url;
	}

	if ( empty( $post_ids ) ) {
		return $redirect_url;
	}

	// Store post IDs in transient for download.
	$transient_key = 'plainmark_bulk_export_' . get_current_user_id();
	set_transient( $transient_key, $post_ids, 300 ); // 5 minutes.

	return add_query_arg(
		array(
			'action' => 'plainmark_md_bulk_download',
			'nonce'  => wp_create_nonce( 'plainmark_md_bulk_download' ),
		),
		admin_url( 'admin.php' )
	);
}
add_filter( 'handle_bulk_actions-edit-post', 'plainmark_handle_bulk_export', 10, 3 );
add_filter( 'handle_bulk_actions-edit-portfolio', 'plainmark_handle_bulk_export', 10, 3 );

// -------------------------------------------------------------------------
// Download Handlers
// -------------------------------------------------------------------------

/**
 * Handle export download requests.
 */
function plainmark_handle_export_download() {
	// Single export.
	if ( isset( $_GET['action'] ) && 'plainmark_md_export' === $_GET['action'] ) {
		plainmark_download_single_md();
	}

	// Bulk export.
	if ( isset( $_GET['action'] ) && 'plainmark_md_bulk_download' === $_GET['action'] ) {
		plainmark_download_bulk_md();
	}
}
add_action( 'admin_init', 'plainmark_handle_export_download' );

/**
 * Download single post as Markdown.
 */
function plainmark_download_single_md() {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

	if ( ! $post_id ) {
		wp_die( esc_html__( 'Invalid post ID.', 'plainmark' ) );
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'plainmark_md_export_' . $post_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'plainmark' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'Permission denied.', 'plainmark' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		wp_die( esc_html__( 'Post not found.', 'plainmark' ) );
	}

	$markdown = plainmark_generate_markdown( $post );
	$filename = plainmark_generate_md_filename( $post );

	header( 'Content-Type: text/markdown; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $markdown ) );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );

	echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Download multiple posts as ZIP.
 */
function plainmark_download_bulk_md() {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), 'plainmark_md_bulk_download' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'plainmark' ) );
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'plainmark' ) );
	}

	$transient_key = 'plainmark_bulk_export_' . get_current_user_id();
	$post_ids      = get_transient( $transient_key );
	delete_transient( $transient_key );

	if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
		wp_die( esc_html__( 'No posts selected or session expired.', 'plainmark' ) );
	}

	// Check ZipArchive availability.
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die(
			esc_html__( 'ZipArchiveが利用できません。単体エクスポートをご利用ください。', 'plainmark' ),
			esc_html__( 'Error', 'plainmark' ),
			array( 'back_link' => true )
		);
	}

	// Create temporary ZIP file.
	$upload_dir = wp_upload_dir();
	$zip_path   = $upload_dir['basedir'] . '/plainmark-export-' . time() . '.zip';
	$zip        = new ZipArchive();

	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		wp_die( esc_html__( 'Failed to create ZIP file.', 'plainmark' ) );
	}

	foreach ( $post_ids as $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
			continue;
		}

		$markdown = plainmark_generate_markdown( $post );
		$filename = plainmark_generate_md_filename( $post );
		$zip->addFromString( $filename, $markdown );
	}

	$zip->close();

	// Send ZIP file.
	$zip_filename = 'plainmark-export-' . gmdate( 'Y-m-d' ) . '.zip';

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $zip_filename . '"' );
	header( 'Content-Length: ' . filesize( $zip_path ) );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );

	readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	unlink( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	exit;
}

// -------------------------------------------------------------------------
// Markdown Generation
// -------------------------------------------------------------------------

/**
 * Generate Markdown filename.
 *
 * @param WP_Post $post Post object.
 * @return string Filename.
 */
function plainmark_generate_md_filename( $post ) {
	$date = get_the_date( 'Y-m-d', $post );
	$slug = sanitize_file_name( $post->post_name );
	return $date . '_' . $slug . '.md';
}

/**
 * Generate Markdown content with front matter.
 *
 * @param WP_Post $post Post object.
 * @return string Markdown content.
 */
function plainmark_generate_markdown( $post ) {
	$front_matter = plainmark_generate_front_matter( $post );
	$content      = plainmark_html_to_markdown( $post->post_content );

	return "---\n" . $front_matter . "---\n\n" . $content;
}

/**
 * Generate YAML front matter.
 *
 * @param WP_Post $post Post object.
 * @return string YAML front matter.
 */
function plainmark_generate_front_matter( $post ) {
	$yaml = '';

	// Basic fields.
	$yaml .= plainmark_yaml_line( 'title', $post->post_title );
	$yaml .= plainmark_yaml_line( 'slug', $post->post_name );
	$yaml .= plainmark_yaml_line( 'date', get_the_date( 'Y-m-d', $post ) );
	$yaml .= plainmark_yaml_line( 'modified', get_the_modified_date( 'Y-m-d', $post ) );
	$yaml .= plainmark_yaml_line( 'status', $post->post_status );
	$yaml .= plainmark_yaml_line( 'post_type', $post->post_type );

	// Categories.
	$categories = get_the_category( $post->ID );
	if ( $categories ) {
		$yaml .= plainmark_yaml_array( 'categories', wp_list_pluck( $categories, 'name' ) );
	}

	// Tags.
	$tags = get_the_tags( $post->ID );
	if ( $tags ) {
		$yaml .= plainmark_yaml_array( 'tags', wp_list_pluck( $tags, 'name' ) );
	}

	// Technologies taxonomy.
	$technologies = get_the_terms( $post->ID, 'technology' );
	if ( $technologies && ! is_wp_error( $technologies ) ) {
		$yaml .= plainmark_yaml_array( 'technologies', wp_list_pluck( $technologies, 'name' ) );
	}

	// Post meta fields.
	if ( 'post' === $post->post_type ) {
		$meta_fields = array(
			'article_type'     => '_plainmark_article_type',
			'difficulty'       => '_plainmark_difficulty',
			'target_reader'    => '_plainmark_target_reader',
			'prerequisites'    => '_plainmark_prerequisites',
			'github_url'       => '_plainmark_github_url',
			'official_docs_url' => '_plainmark_official_docs_url',
			'show_toc'         => '_plainmark_show_toc',
			'show_code_copy'   => '_plainmark_show_code_copy',
			'series_name'      => '_plainmark_series_name',
			'series_order'     => '_plainmark_series_order',
		);

		foreach ( $meta_fields as $key => $meta_key ) {
			$value = get_post_meta( $post->ID, $meta_key, true );
			if ( '' !== $value ) {
				// Boolean fields.
				if ( in_array( $key, array( 'show_toc', 'show_code_copy' ), true ) ) {
					$yaml .= $key . ': ' . ( $value ? 'true' : 'false' ) . "\n";
				} elseif ( 'series_order' === $key ) {
					$yaml .= $key . ': ' . intval( $value ) . "\n";
				} else {
					$yaml .= plainmark_yaml_line( $key, $value );
				}
			}
		}
	}

	// Portfolio meta fields.
	if ( 'portfolio' === $post->post_type ) {
		$portfolio_fields = array(
			'work_summary',
			'work_problem',
			'work_solution',
			'work_architecture',
			'work_features',
			'work_learnings',
			'work_next_steps',
			'work_role',
			'work_period',
			'work_github_url',
			'work_demo_url',
		);

		foreach ( $portfolio_fields as $field ) {
			$value = get_post_meta( $post->ID, $field, true );
			if ( '' !== $value ) {
				$yaml .= plainmark_yaml_line( $field, $value );
			}
		}
	}

	// Featured image.
	if ( has_post_thumbnail( $post->ID ) ) {
		$image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( $image_url ) {
			$yaml .= plainmark_yaml_line( 'featured_image', $image_url );
		}
	}

	// Excerpt.
	if ( $post->post_excerpt ) {
		$yaml .= plainmark_yaml_line( 'excerpt', $post->post_excerpt );
	}

	return $yaml;
}

/**
 * Generate a YAML key-value line.
 *
 * @param string $key   Key.
 * @param string $value Value.
 * @return string YAML line.
 */
function plainmark_yaml_line( $key, $value ) {
	if ( '' === $value ) {
		return '';
	}

	// Escape quotes and handle multiline.
	$value = str_replace( '"', '\\"', $value );

	// If value contains newlines or special chars, use quotes.
	if ( preg_match( '/[\n:#{}\[\]&*!|>\'"%@`]/', $value ) || '' === trim( $value ) ) {
		return $key . ': "' . $value . "\"\n";
	}

	return $key . ': "' . $value . "\"\n";
}

/**
 * Generate a YAML array.
 *
 * @param string $key   Key.
 * @param array  $items Array items.
 * @return string YAML array.
 */
function plainmark_yaml_array( $key, $items ) {
	if ( empty( $items ) ) {
		return '';
	}

	$yaml = $key . ":\n";
	foreach ( $items as $item ) {
		$yaml .= '  - "' . str_replace( '"', '\\"', $item ) . "\"\n";
	}

	return $yaml;
}

// -------------------------------------------------------------------------
// HTML to Markdown Conversion
// -------------------------------------------------------------------------

/**
 * Convert HTML content to Markdown.
 *
 * @param string $html HTML content.
 * @return string Markdown content.
 */
function plainmark_html_to_markdown( $html ) {
	// Remove Gutenberg block comments.
	$html = preg_replace( '/<!--\s*\/?wp:[^>]*-->/s', '', $html );

	// Normalize line breaks.
	$html = str_replace( array( "\r\n", "\r" ), "\n", $html );

	// Process code blocks first (before other conversions).
	$html = plainmark_convert_code_blocks( $html );

	// Convert headings.
	$html = preg_replace( '/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $html );
	$html = preg_replace( '/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $html );
	$html = preg_replace( '/<h4[^>]*>(.*?)<\/h4>/is', "\n#### $1\n", $html );
	$html = preg_replace( '/<h5[^>]*>(.*?)<\/h5>/is', "\n##### $1\n", $html );
	$html = preg_replace( '/<h6[^>]*>(.*?)<\/h6>/is', "\n###### $1\n", $html );

	// Convert inline elements.
	$html = preg_replace( '/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $html );
	$html = preg_replace( '/<b[^>]*>(.*?)<\/b>/is', '**$1**', $html );
	$html = preg_replace( '/<em[^>]*>(.*?)<\/em>/is', '*$1*', $html );
	$html = preg_replace( '/<i[^>]*>(.*?)<\/i>/is', '*$1*', $html );
	$html = preg_replace( '/<code[^>]*>(.*?)<\/code>/is', '`$1`', $html );

	// Convert links.
	$html = preg_replace_callback(
		'/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is',
		function ( $matches ) {
			return '[' . $matches[2] . '](' . $matches[1] . ')';
		},
		$html
	);

	// Convert images.
	$html = preg_replace_callback(
		'/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*\/?>/is',
		function ( $matches ) {
			return '![' . $matches[2] . '](' . $matches[1] . ')';
		},
		$html
	);
	$html = preg_replace_callback(
		'/<img[^>]*alt=["\']([^"\']*)["\'][^>]*src=["\']([^"\']*)["\'][^>]*\/?>/is',
		function ( $matches ) {
			return '![' . $matches[1] . '](' . $matches[2] . ')';
		},
		$html
	);
	// Images without alt.
	$html = preg_replace_callback(
		'/<img[^>]*src=["\']([^"\']*)["\'][^>]*\/?>/is',
		function ( $matches ) {
			return '![](' . $matches[1] . ')';
		},
		$html
	);

	// Convert lists.
	$html = plainmark_convert_lists( $html );

	// Convert blockquotes.
	$html = preg_replace_callback(
		'/<blockquote[^>]*>(.*?)<\/blockquote>/is',
		function ( $matches ) {
			$content = strip_tags( $matches[1] );
			$content = trim( $content );
			$lines   = explode( "\n", $content );
			$quoted  = array_map(
				function ( $line ) {
					return '> ' . trim( $line );
				},
				$lines
			);
			return "\n" . implode( "\n", $quoted ) . "\n";
		},
		$html
	);

	// Convert paragraphs.
	$html = preg_replace( '/<p[^>]*>(.*?)<\/p>/is', "\n$1\n", $html );

	// Convert line breaks.
	$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );

	// Remove remaining HTML tags (except shortcodes).
	$html = preg_replace( '/<\/?(?![\[\]])[^>]+>/i', '', $html );

	// Clean up multiple newlines.
	$html = preg_replace( '/\n{3,}/', "\n\n", $html );

	// Decode HTML entities.
	$html = html_entity_decode( $html, ENT_QUOTES, 'UTF-8' );

	return trim( $html );
}

/**
 * Convert HTML code blocks to Markdown fenced code blocks.
 *
 * @param string $html HTML content.
 * @return string Content with converted code blocks.
 */
function plainmark_convert_code_blocks( $html ) {
	// Match <pre><code> blocks.
	return preg_replace_callback(
		'/<pre[^>]*>\s*<code[^>]*(?:class=["\'][^"\']*language-([a-zA-Z0-9-]+)[^"\']*["\'])?[^>]*>(.*?)<\/code>\s*<\/pre>/is',
		function ( $matches ) {
			$language = $matches[1] ?? '';
			$code     = $matches[2];

			// Decode HTML entities in code.
			$code = html_entity_decode( $code, ENT_QUOTES, 'UTF-8' );
			$code = trim( $code );

			return "\n```" . $language . "\n" . $code . "\n```\n";
		},
		$html
	);
}

/**
 * Convert HTML lists to Markdown.
 *
 * @param string $html HTML content.
 * @return string Content with converted lists.
 */
function plainmark_convert_lists( $html ) {
	// Convert unordered lists.
	$html = preg_replace_callback(
		'/<ul[^>]*>(.*?)<\/ul>/is',
		function ( $matches ) {
			$items = preg_replace( '/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $matches[1] );
			$items = strip_tags( $items );
			return "\n" . trim( $items ) . "\n";
		},
		$html
	);

	// Convert ordered lists.
	$html = preg_replace_callback(
		'/<ol[^>]*>(.*?)<\/ol>/is',
		function ( $matches ) {
			$counter = 0;
			$items   = preg_replace_callback(
				'/<li[^>]*>(.*?)<\/li>/is',
				function ( $li_matches ) use ( &$counter ) {
					$counter++;
					return $counter . '. ' . strip_tags( $li_matches[1] ) . "\n";
				},
				$matches[1]
			);
			return "\n" . trim( $items ) . "\n";
		},
		$html
	);

	return $html;
}
