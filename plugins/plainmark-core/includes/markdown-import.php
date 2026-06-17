<?php
/**
 * Markdown Import functionality.
 *
 * Imports Markdown files with YAML front matter as posts or portfolio items.
 *
 * @package plainmark
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------
// Admin Menu
// -------------------------------------------------------------------------

/**
 * Add Markdown Import page under Tools menu.
 */
function plainmark_add_import_menu() {
	add_management_page(
		__( 'Markdown Import', 'plainmark' ),
		__( 'Markdown Import', 'plainmark' ),
		'manage_options',
		'plainmark-md-import',
		'plainmark_render_import_page'
	);
}
add_action( 'admin_menu', 'plainmark_add_import_menu' );

// -------------------------------------------------------------------------
// Import Page Render
// -------------------------------------------------------------------------

/**
 * Render the import page.
 */
function plainmark_render_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'plainmark' ) );
	}

	// Handle form submission.
	$import_results = array();
	if ( isset( $_POST['plainmark_import_action'] ) && 'import' === $_POST['plainmark_import_action'] ) {
		$import_results = plainmark_process_import();
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Markdown Import', 'plainmark' ); ?></h1>

		<?php if ( ! empty( $import_results ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $import_results['status'] ); ?> is-dismissible">
				<p><?php echo esc_html( $import_results['message'] ); ?></p>
				<?php if ( ! empty( $import_results['posts'] ) ) : ?>
					<ul>
						<?php foreach ( $import_results['posts'] as $post_info ) : ?>
							<li>
								<a href="<?php echo esc_url( get_edit_post_link( $post_info['id'] ) ); ?>">
									<?php echo esc_html( $post_info['title'] ); ?>
								</a>
								(<?php echo esc_html( $post_info['action'] ); ?>)
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<style>
			.plainmark-dropzone {
				border: 2px dashed #c3c4c7;
				border-radius: 8px;
				padding: 40px;
				text-align: center;
				background: #f6f7f7;
				margin: 20px 0;
				transition: all 0.2s ease;
			}
			.plainmark-dropzone.drag-over {
				border-color: #2271b1;
				background: #f0f6fc;
			}
			.plainmark-dropzone p {
				margin: 10px 0;
				color: #50575e;
			}
			.plainmark-dropzone .dashicons {
				font-size: 48px;
				width: 48px;
				height: 48px;
				color: #c3c4c7;
			}
			.plainmark-file-list {
				margin: 20px 0;
			}
			.plainmark-file-item {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 15px;
				margin-bottom: 10px;
			}
			.plainmark-file-item h3 {
				margin: 0 0 10px;
				font-size: 16px;
			}
			.plainmark-file-meta {
				display: flex;
				flex-wrap: wrap;
				gap: 15px;
				font-size: 13px;
				color: #50575e;
			}
			.plainmark-file-meta span {
				display: flex;
				align-items: center;
				gap: 5px;
			}
			.plainmark-preview-content {
				max-height: 150px;
				overflow-y: auto;
				background: #f6f7f7;
				padding: 10px;
				border-radius: 4px;
				margin-top: 10px;
				font-family: monospace;
				font-size: 12px;
				white-space: pre-wrap;
			}
			.plainmark-duplicate-warning {
				background: #fcf0f1;
				border-left: 4px solid #d63638;
				padding: 8px 12px;
				margin-top: 10px;
			}
			.plainmark-actions {
				margin-top: 20px;
			}
			.plainmark-hidden {
				display: none;
			}
		</style>

		<form method="post" enctype="multipart/form-data" id="plainmark-import-form">
			<?php wp_nonce_field( 'plainmark_md_import', 'plainmark_import_nonce' ); ?>
			<input type="hidden" name="plainmark_import_action" value="import">
			<input type="hidden" name="plainmark_file_data" id="plainmark-file-data" value="">

			<div class="plainmark-dropzone" id="plainmark-dropzone">
				<span class="dashicons dashicons-upload"></span>
				<p><strong><?php esc_html_e( 'ファイルをドラッグ&ドロップ', 'plainmark' ); ?></strong></p>
				<p><?php esc_html_e( 'または', 'plainmark' ); ?></p>
				<input type="file" id="plainmark-file-input" accept=".md" multiple style="display:none;">
				<button type="button" class="button" id="plainmark-select-files">
					<?php esc_html_e( 'ファイルを選択', 'plainmark' ); ?>
				</button>
				<p><small><?php esc_html_e( '.md ファイルのみ、最大1MBまで', 'plainmark' ); ?></small></p>
			</div>

			<div class="plainmark-file-list plainmark-hidden" id="plainmark-file-list"></div>

			<div class="plainmark-actions plainmark-hidden" id="plainmark-actions">
				<p>
					<label>
						<input type="checkbox" name="plainmark_overwrite" value="1">
						<?php esc_html_e( '同じslugの既存記事を上書きする', 'plainmark' ); ?>
					</label>
				</p>
				<button type="submit" class="button button-primary" id="plainmark-import-btn">
					<?php esc_html_e( 'インポート実行', 'plainmark' ); ?>
				</button>
				<button type="button" class="button" id="plainmark-clear-btn">
					<?php esc_html_e( 'クリア', 'plainmark' ); ?>
				</button>
			</div>
		</form>

		<script>
		(function() {
			const dropzone = document.getElementById('plainmark-dropzone');
			const fileInput = document.getElementById('plainmark-file-input');
			const selectBtn = document.getElementById('plainmark-select-files');
			const fileList = document.getElementById('plainmark-file-list');
			const actions = document.getElementById('plainmark-actions');
			const fileDataInput = document.getElementById('plainmark-file-data');
			const clearBtn = document.getElementById('plainmark-clear-btn');

			let filesData = [];

			// Click to select.
			selectBtn.addEventListener('click', () => fileInput.click());
			fileInput.addEventListener('change', handleFiles);

			// Drag and drop.
			dropzone.addEventListener('dragover', (e) => {
				e.preventDefault();
				dropzone.classList.add('drag-over');
			});
			dropzone.addEventListener('dragleave', () => {
				dropzone.classList.remove('drag-over');
			});
			dropzone.addEventListener('drop', (e) => {
				e.preventDefault();
				dropzone.classList.remove('drag-over');
				handleFiles({ target: { files: e.dataTransfer.files } });
			});

			// Clear button.
			clearBtn.addEventListener('click', () => {
				filesData = [];
				fileList.innerHTML = '';
				fileList.classList.add('plainmark-hidden');
				actions.classList.add('plainmark-hidden');
				fileDataInput.value = '';
			});

			function handleFiles(e) {
				const files = Array.from(e.target.files);

				files.forEach(file => {
					if (!file.name.endsWith('.md')) {
						alert('<?php echo esc_js( __( '.md ファイルのみアップロードできます。', 'plainmark' ) ); ?>');
						return;
					}
					if (file.size > 1024 * 1024) {
						alert('<?php echo esc_js( __( 'ファイルサイズは1MB以下にしてください。', 'plainmark' ) ); ?>');
						return;
					}

					const reader = new FileReader();
					reader.onload = (event) => {
						const content = event.target.result;
						const parsed = parseMarkdown(content);
						parsed.filename = file.name;
						filesData.push(parsed);
						renderFileList();
					};
					reader.readAsText(file);
				});
			}

			function parseMarkdown(content) {
				const result = {
					frontMatter: {},
					content: '',
					raw: content
				};

				// Extract front matter.
				const fmMatch = content.match(/^---\n([\s\S]*?)\n---\n?([\s\S]*)$/);
				if (fmMatch) {
					result.frontMatter = parseYaml(fmMatch[1]);
					result.content = fmMatch[2].trim();
				} else {
					result.content = content.trim();
				}

				return result;
			}

			function parseYaml(yaml) {
				const result = {};
				const lines = yaml.split('\n');
				let currentKey = null;
				let isArray = false;

				lines.forEach(line => {
					// Array item.
					if (/^\s+-\s+/.test(line)) {
						const value = line.replace(/^\s+-\s+/, '').replace(/^["']|["']$/g, '');
						if (currentKey && isArray) {
							result[currentKey].push(value);
						}
						return;
					}

					// Key: value.
					const kvMatch = line.match(/^([a-z_]+):\s*(.*)$/i);
					if (kvMatch) {
						currentKey = kvMatch[1];
						let value = kvMatch[2].trim().replace(/^["']|["']$/g, '');

						if (value === '') {
							// Start of array.
							result[currentKey] = [];
							isArray = true;
						} else {
							result[currentKey] = value;
							isArray = false;
						}
					}
				});

				return result;
			}

			function renderFileList() {
				fileList.innerHTML = '';

				filesData.forEach((file, index) => {
					const fm = file.frontMatter;
					const title = fm.title || file.filename;
					const slug = fm.slug || '';
					const postType = fm.post_type || 'post';
					const status = fm.status || 'draft';
					const categories = Array.isArray(fm.categories) ? fm.categories.join(', ') : '';

					const item = document.createElement('div');
					item.className = 'plainmark-file-item';
					item.innerHTML = `
						<h3>${escapeHtml(title)}</h3>
						<div class="plainmark-file-meta">
							<span><strong>Slug:</strong> ${escapeHtml(slug)}</span>
							<span><strong>Type:</strong> ${escapeHtml(postType)}</span>
							<span><strong>Status:</strong> ${escapeHtml(status)}</span>
							${categories ? `<span><strong>Categories:</strong> ${escapeHtml(categories)}</span>` : ''}
						</div>
						<div class="plainmark-preview-content">${escapeHtml(file.content.substring(0, 500))}${file.content.length > 500 ? '...' : ''}</div>
						<button type="button" class="button button-link-delete" style="margin-top:10px" onclick="removeFile(${index})">
							<?php esc_html_e( '削除', 'plainmark' ); ?>
						</button>
					`;

					// Check for duplicate slug.
					if (slug) {
						checkDuplicateSlug(slug, postType, item);
					}

					fileList.appendChild(item);
				});

				fileList.classList.remove('plainmark-hidden');
				actions.classList.remove('plainmark-hidden');
				fileDataInput.value = JSON.stringify(filesData.map(f => f.raw));
			}

			window.removeFile = function(index) {
				filesData.splice(index, 1);
				renderFileList();
				if (filesData.length === 0) {
					fileList.classList.add('plainmark-hidden');
					actions.classList.add('plainmark-hidden');
				}
			};

			function checkDuplicateSlug(slug, postType, container) {
				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: `action=plainmark_check_slug&slug=${encodeURIComponent(slug)}&post_type=${encodeURIComponent(postType)}&nonce=<?php echo wp_create_nonce( 'plainmark_check_slug' ); ?>`
				})
				.then(res => res.json())
				.then(data => {
					if (data.exists) {
						const warning = document.createElement('div');
						warning.className = 'plainmark-duplicate-warning';
						warning.innerHTML = `<?php esc_html_e( '同じslugの記事が既に存在します。上書きオプションを有効にすると更新されます。', 'plainmark' ); ?>`;
						container.appendChild(warning);
					}
				});
			}

			function escapeHtml(text) {
				const div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
			}
		})();
		</script>
	</div>
	<?php
}

// -------------------------------------------------------------------------
// AJAX: Check Duplicate Slug
// -------------------------------------------------------------------------

/**
 * AJAX handler to check if slug exists.
 */
function plainmark_ajax_check_slug() {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'plainmark_check_slug' ) ) {
		wp_send_json_error();
	}

	$slug      = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
	$post_type = sanitize_key( wp_unslash( $_POST['post_type'] ?? 'post' ) );

	$existing = get_page_by_path( $slug, OBJECT, $post_type );

	wp_send_json( array( 'exists' => (bool) $existing ) );
}
add_action( 'wp_ajax_plainmark_check_slug', 'plainmark_ajax_check_slug' );

// -------------------------------------------------------------------------
// Import Processing
// -------------------------------------------------------------------------

/**
 * Process the import form submission.
 *
 * @return array Import results.
 */
function plainmark_process_import() {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plainmark_import_nonce'] ?? '' ) ), 'plainmark_md_import' ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Security check failed.', 'plainmark' ),
			'posts'   => array(),
		);
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Permission denied.', 'plainmark' ),
			'posts'   => array(),
		);
	}

	$file_data = isset( $_POST['plainmark_file_data'] ) ? wp_unslash( $_POST['plainmark_file_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$overwrite = ! empty( $_POST['plainmark_overwrite'] );

	if ( empty( $file_data ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'No files to import.', 'plainmark' ),
			'posts'   => array(),
		);
	}

	$files_raw = json_decode( $file_data, true );
	if ( ! is_array( $files_raw ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Invalid file data.', 'plainmark' ),
			'posts'   => array(),
		);
	}

	$imported_posts = array();

	foreach ( $files_raw as $raw_content ) {
		$result = plainmark_import_single_md( $raw_content, $overwrite );
		if ( $result ) {
			$imported_posts[] = $result;
		}
	}

	$count = count( $imported_posts );

	return array(
		'status'  => $count > 0 ? 'success' : 'warning',
		'message' => sprintf(
			/* translators: %d: number of imported posts */
			_n( '%d件の記事をインポートしました。', '%d件の記事をインポートしました。', $count, 'plainmark' ),
			$count
		),
		'posts'   => $imported_posts,
	);
}

/**
 * Import a single Markdown file.
 *
 * @param string $content   Raw Markdown content.
 * @param bool   $overwrite Whether to overwrite existing posts.
 * @return array|null Post info or null on failure.
 */
function plainmark_import_single_md( $content, $overwrite ) {
	// Parse front matter.
	$parsed = plainmark_parse_md_content( $content );
	if ( ! $parsed ) {
		return null;
	}

	$front_matter = $parsed['front_matter'];
	$body         = $parsed['body'];

	// Basic post data.
	$post_type   = isset( $front_matter['post_type'] ) && in_array( $front_matter['post_type'], array( 'post', 'portfolio' ), true )
		? $front_matter['post_type']
		: 'post';
	$post_status = isset( $front_matter['status'] ) && in_array( $front_matter['status'], array( 'publish', 'draft', 'pending', 'private' ), true )
		? $front_matter['status']
		: 'draft';
	$post_title  = isset( $front_matter['title'] ) ? sanitize_text_field( $front_matter['title'] ) : __( 'Untitled', 'plainmark' );
	$post_slug   = isset( $front_matter['slug'] ) ? sanitize_title( $front_matter['slug'] ) : sanitize_title( $post_title );

	// Check for existing post.
	$existing = get_page_by_path( $post_slug, OBJECT, $post_type );
	$action   = 'created';

	if ( $existing ) {
		if ( ! $overwrite ) {
			return null; // Skip.
		}
		$post_id = $existing->ID;
		$action  = 'updated';
	} else {
		$post_id = 0;
	}

	// Convert Markdown to HTML.
	$post_content = plainmark_markdown_to_html( $body );
	$post_content = wp_kses_post( $post_content );

	// Prepare post data.
	$post_data = array(
		'ID'           => $post_id,
		'post_type'    => $post_type,
		'post_status'  => $post_status,
		'post_title'   => $post_title,
		'post_name'    => $post_slug,
		'post_content' => $post_content,
	);

	// Date.
	if ( ! empty( $front_matter['date'] ) ) {
		$post_data['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $front_matter['date'] ) );
	}

	// Excerpt.
	if ( ! empty( $front_matter['excerpt'] ) ) {
		$post_data['post_excerpt'] = sanitize_textarea_field( $front_matter['excerpt'] );
	}

	// Insert or update post.
	if ( $post_id ) {
		$result = wp_update_post( $post_data, true );
	} else {
		$result = wp_insert_post( $post_data, true );
	}

	if ( is_wp_error( $result ) ) {
		return null;
	}

	$post_id = $result;

	// Set taxonomies.
	plainmark_set_import_taxonomies( $post_id, $front_matter );

	// Set meta fields.
	plainmark_set_import_meta( $post_id, $front_matter, $post_type );

	// Featured image.
	if ( ! empty( $front_matter['featured_image'] ) ) {
		plainmark_set_featured_image( $post_id, $front_matter['featured_image'] );
	}

	return array(
		'id'     => $post_id,
		'title'  => $post_title,
		'action' => $action,
	);
}

/**
 * Parse Markdown content with front matter.
 *
 * @param string $content Raw content.
 * @return array|null Parsed data or null.
 */
function plainmark_parse_md_content( $content ) {
	// Match front matter.
	if ( ! preg_match( '/^---\n([\s\S]*?)\n---\n?([\s\S]*)$/', $content, $matches ) ) {
		return array(
			'front_matter' => array(),
			'body'         => $content,
		);
	}

	$yaml_content = $matches[1];
	$body         = trim( $matches[2] );

	// Parse YAML.
	$front_matter = plainmark_parse_yaml( $yaml_content );

	return array(
		'front_matter' => $front_matter,
		'body'         => $body,
	);
}

/**
 * Simple YAML parser for front matter.
 *
 * @param string $yaml YAML content.
 * @return array Parsed data.
 */
function plainmark_parse_yaml( $yaml ) {
	$result      = array();
	$lines       = explode( "\n", $yaml );
	$current_key = null;
	$is_array    = false;

	foreach ( $lines as $line ) {
		// Skip empty lines.
		if ( '' === trim( $line ) ) {
			continue;
		}

		// Array item.
		if ( preg_match( '/^\s+-\s+"?([^"]*)"?$/', $line, $arr_match ) ) {
			if ( $current_key && $is_array ) {
				$result[ $current_key ][] = $arr_match[1];
			}
			continue;
		}

		// Key: value.
		if ( preg_match( '/^([a-z_]+):\s*(.*)$/i', $line, $kv_match ) ) {
			$key   = $kv_match[1];
			$value = trim( $kv_match[2] );

			// Remove surrounding quotes.
			$value = preg_replace( '/^["\'](.*)["\']$/', '$1', $value );

			if ( '' === $value ) {
				// Start of array.
				$result[ $key ] = array();
				$current_key    = $key;
				$is_array       = true;
			} else {
				// Handle boolean.
				if ( 'true' === strtolower( $value ) ) {
					$value = true;
				} elseif ( 'false' === strtolower( $value ) ) {
					$value = false;
				}

				$result[ $key ] = $value;
				$current_key    = $key;
				$is_array       = false;
			}
		}
	}

	return $result;
}

/**
 * Set taxonomies for imported post.
 *
 * @param int   $post_id      Post ID.
 * @param array $front_matter Front matter data.
 */
function plainmark_set_import_taxonomies( $post_id, $front_matter ) {
	// Categories.
	if ( ! empty( $front_matter['categories'] ) && is_array( $front_matter['categories'] ) ) {
		$cat_ids = array();
		foreach ( $front_matter['categories'] as $cat_name ) {
			$cat = get_category_by_slug( sanitize_title( $cat_name ) );
			if ( $cat ) {
				$cat_ids[] = $cat->term_id;
			} else {
				$new_cat = wp_insert_category( array( 'cat_name' => sanitize_text_field( $cat_name ) ) );
				if ( $new_cat && ! is_wp_error( $new_cat ) ) {
					$cat_ids[] = $new_cat;
				}
			}
		}
		if ( $cat_ids ) {
			wp_set_post_categories( $post_id, $cat_ids );
		}
	}

	// Tags.
	if ( ! empty( $front_matter['tags'] ) && is_array( $front_matter['tags'] ) ) {
		wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $front_matter['tags'] ) );
	}

	// Technologies.
	if ( ! empty( $front_matter['technologies'] ) && is_array( $front_matter['technologies'] ) ) {
		$tech_terms = array_map( 'sanitize_text_field', $front_matter['technologies'] );
		wp_set_object_terms( $post_id, $tech_terms, 'technology' );
	}
}

/**
 * Set meta fields for imported post.
 *
 * @param int    $post_id      Post ID.
 * @param array  $front_matter Front matter data.
 * @param string $post_type    Post type.
 */
function plainmark_set_import_meta( $post_id, $front_matter, $post_type ) {
	if ( 'post' === $post_type ) {
		$meta_map = array(
			'article_type'      => '_plainmark_article_type',
			'difficulty'        => '_plainmark_difficulty',
			'target_reader'     => '_plainmark_target_reader',
			'prerequisites'     => '_plainmark_prerequisites',
			'github_url'        => '_plainmark_github_url',
			'official_docs_url' => '_plainmark_official_docs_url',
			'show_toc'          => '_plainmark_show_toc',
			'show_code_copy'    => '_plainmark_show_code_copy',
			'series_name'       => '_plainmark_series_name',
			'series_order'      => '_plainmark_series_order',
		);

		foreach ( $meta_map as $fm_key => $meta_key ) {
			if ( isset( $front_matter[ $fm_key ] ) ) {
				$value = $front_matter[ $fm_key ];

				// Sanitize based on type.
				if ( in_array( $fm_key, array( 'show_toc', 'show_code_copy' ), true ) ) {
					$value = $value ? '1' : '0';
				} elseif ( 'series_order' === $fm_key ) {
					$value = absint( $value );
				} elseif ( in_array( $fm_key, array( 'github_url', 'official_docs_url' ), true ) ) {
					$value = esc_url_raw( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	if ( 'portfolio' === $post_type ) {
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
			if ( isset( $front_matter[ $field ] ) ) {
				$value = $front_matter[ $field ];

				if ( in_array( $field, array( 'work_github_url', 'work_demo_url' ), true ) ) {
					$value = esc_url_raw( $value );
				} else {
					$value = sanitize_textarea_field( $value );
				}

				update_post_meta( $post_id, $field, $value );
			}
		}
	}
}

/**
 * Set featured image from URL.
 *
 * @param int    $post_id   Post ID.
 * @param string $image_url Image URL.
 */
function plainmark_set_featured_image( $post_id, $image_url ) {
	// Skip local paths or empty URLs.
	if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
		return;
	}

	// Skip if already has thumbnail.
	if ( has_post_thumbnail( $post_id ) ) {
		return;
	}

	// Need these for media_sideload_image.
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $image_url, $post_id, '', 'id' );

	if ( ! is_wp_error( $attachment_id ) ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}
}

// -------------------------------------------------------------------------
// Markdown to HTML Conversion
// -------------------------------------------------------------------------

/**
 * Convert Markdown to HTML.
 *
 * @param string $markdown Markdown content.
 * @return string HTML content.
 */
function plainmark_markdown_to_html( $markdown ) {
	// Normalize line endings.
	$markdown = str_replace( array( "\r\n", "\r" ), "\n", $markdown );

	// Process code blocks first (to protect them from other conversions).
	$code_blocks = array();
	$markdown    = preg_replace_callback(
		'/```([a-zA-Z0-9-]*)\n([\s\S]*?)```/m',
		function ( $matches ) use ( &$code_blocks ) {
			$placeholder                  = '%%CODEBLOCK' . count( $code_blocks ) . '%%';
			$language                     = $matches[1] ? ' class="language-' . esc_attr( $matches[1] ) . '"' : '';
			$code                         = esc_html( $matches[2] );
			$code_blocks[ $placeholder ] = '<pre><code' . $language . '>' . $code . '</code></pre>';
			return $placeholder;
		},
		$markdown
	);

	// Inline code.
	$markdown = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $markdown );

	// Headings.
	$markdown = preg_replace( '/^###### (.+)$/m', '<h6>$1</h6>', $markdown );
	$markdown = preg_replace( '/^##### (.+)$/m', '<h5>$1</h5>', $markdown );
	$markdown = preg_replace( '/^#### (.+)$/m', '<h4>$1</h4>', $markdown );
	$markdown = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $markdown );
	$markdown = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $markdown );
	$markdown = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $markdown );

	// Bold and italic.
	$markdown = preg_replace( '/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $markdown );
	$markdown = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown );
	$markdown = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $markdown );

	// Links.
	$markdown = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $markdown );

	// Images.
	$markdown = preg_replace( '/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $markdown );

	// Blockquotes.
	$markdown = preg_replace_callback(
		'/(?:^> .+$\n?)+/m',
		function ( $matches ) {
			$content = preg_replace( '/^> /m', '', $matches[0] );
			return '<blockquote><p>' . trim( $content ) . '</p></blockquote>' . "\n";
		},
		$markdown
	);

	// Unordered lists.
	$markdown = preg_replace_callback(
		'/(?:^[-*] .+$\n?)+/m',
		function ( $matches ) {
			$items = preg_split( '/^[-*] /m', $matches[0], -1, PREG_SPLIT_NO_EMPTY );
			$html  = '<ul>';
			foreach ( $items as $item ) {
				$html .= '<li>' . trim( $item ) . '</li>';
			}
			$html .= '</ul>';
			return $html . "\n";
		},
		$markdown
	);

	// Ordered lists.
	$markdown = preg_replace_callback(
		'/(?:^\d+\. .+$\n?)+/m',
		function ( $matches ) {
			$items = preg_split( '/^\d+\. /m', $matches[0], -1, PREG_SPLIT_NO_EMPTY );
			$html  = '<ol>';
			foreach ( $items as $item ) {
				$html .= '<li>' . trim( $item ) . '</li>';
			}
			$html .= '</ol>';
			return $html . "\n";
		},
		$markdown
	);

	// Horizontal rules.
	$markdown = preg_replace( '/^(?:---|\*\*\*|___)$/m', '<hr>', $markdown );

	// Paragraphs (lines separated by blank lines).
	$blocks = preg_split( '/\n\n+/', $markdown );
	$html   = '';

	foreach ( $blocks as $block ) {
		$block = trim( $block );
		if ( '' === $block ) {
			continue;
		}

		// Skip if already wrapped in HTML tags.
		if ( preg_match( '/^<(h[1-6]|ul|ol|blockquote|pre|hr|p|\[)/', $block ) ) {
			$html .= $block . "\n\n";
		} elseif ( preg_match( '/^%%CODEBLOCK\d+%%$/', $block ) ) {
			$html .= $block . "\n\n";
		} elseif ( preg_match( '/^\[/', $block ) ) {
			// Shortcode - don't wrap.
			$html .= $block . "\n\n";
		} else {
			// Wrap in paragraph.
			$block = str_replace( "\n", '<br>', $block );
			$html .= '<p>' . $block . '</p>' . "\n\n";
		}
	}

	// Restore code blocks.
	foreach ( $code_blocks as $placeholder => $code ) {
		$html = str_replace( $placeholder, $code, $html );
	}

	return trim( $html );
}
