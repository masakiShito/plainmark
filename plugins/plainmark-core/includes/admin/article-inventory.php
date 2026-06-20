<?php
/**
 * Article inventory admin page.
 *
 * @package plainmark
 * @since 0.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the Article Inventory page under Posts.
 */
function plainmark_add_article_inventory_page() {
	add_submenu_page(
		'edit.php',
		__( 'Article Inventory', 'plainmark' ),
		__( 'Article Inventory', 'plainmark' ),
		'edit_posts',
		'plainmark-article-inventory',
		'plainmark_render_article_inventory_page'
	);
}
add_action( 'admin_menu', 'plainmark_add_article_inventory_page' );

/**
 * Handle manual dismissal of a reader-feedback review flag.
 */
function plainmark_handle_dismiss_review_flag() {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( '権限がありません。', 'plainmark' ) );
	}

	check_admin_referer( 'plainmark_dismiss_review_flag_' . $post_id );

	delete_post_meta( $post_id, '_plainmark_freshness_review_flagged' );
	delete_post_meta( $post_id, '_plainmark_freshness_review_flagged_at' );

	if ( function_exists( 'plainmark_cache_freshness_score' ) ) {
		plainmark_cache_freshness_score( $post_id );
	}

	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_plainmark_dismiss_review_flag', 'plainmark_handle_dismiss_review_flag' );

/**
 * Get allowed verification status filters.
 *
 * @return array<string,string>
 */
function plainmark_get_article_inventory_verification_options() {
	return array(
		'verified'   => __( '動作確認済み', 'plainmark' ),
		'unverified' => __( '未検証', 'plainmark' ),
		'review_due' => __( '再確認が必要', 'plainmark' ),
		'deprecated' => __( '非推奨', 'plainmark' ),
	);
}

/**
 * Get allowed post status filters.
 *
 * @return array<string,string>
 */
function plainmark_get_article_inventory_post_status_options() {
	return array(
		'publish' => __( '公開済み', 'plainmark' ),
		'draft'   => __( '下書き', 'plainmark' ),
		'pending' => __( 'レビュー待ち', 'plainmark' ),
		'private' => __( '非公開', 'plainmark' ),
		'future'  => __( '予約済み', 'plainmark' ),
	);
}

/**
 * Read and sanitize current inventory filters.
 *
 * @return array<string,string|int>
 */
function plainmark_get_article_inventory_filters() {
	$article_types        = function_exists( 'plainmark_get_article_type_options' ) ? plainmark_get_article_type_options() : array();
	$difficulty_options   = function_exists( 'plainmark_get_difficulty_options' ) ? plainmark_get_difficulty_options() : array();
	$verification_options = plainmark_get_article_inventory_verification_options();
	$post_status_options  = plainmark_get_article_inventory_post_status_options();

	$article_type = isset( $_GET['article_type'] ) ? sanitize_key( wp_unslash( $_GET['article_type'] ) ) : '';
	$difficulty   = isset( $_GET['difficulty'] ) ? sanitize_key( wp_unslash( $_GET['difficulty'] ) ) : '';
	$verified     = isset( $_GET['verified_status'] ) ? sanitize_key( wp_unslash( $_GET['verified_status'] ) ) : '';
	$post_status  = isset( $_GET['post_status_filter'] ) ? sanitize_key( wp_unslash( $_GET['post_status_filter'] ) ) : '';
	$s            = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

	if ( ! array_key_exists( $article_type, $article_types ) ) {
		$article_type = '';
	}

	if ( ! array_key_exists( $difficulty, $difficulty_options ) ) {
		$difficulty = '';
	}

	if ( ! array_key_exists( $verified, $verification_options ) ) {
		$verified = '';
	}

	if ( ! array_key_exists( $post_status, $post_status_options ) ) {
		$post_status = '';
	}

	return array(
		'category'        => isset( $_GET['category'] ) ? absint( $_GET['category'] ) : 0,
		'technology'      => isset( $_GET['technology'] ) ? absint( $_GET['technology'] ) : 0,
		'article_type'    => $article_type,
		'difficulty'      => $difficulty,
		'verified_status' => $verified,
		'post_status'     => $post_status,
		's'               => $s,
		'paged'           => max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ),
	);
}

/**
 * Build query args for the inventory table.
 *
 * @param array<string,string|int> $filters Current filters.
 * @return array<string,mixed>
 */
function plainmark_get_article_inventory_query_args( $filters ) {
	$args = array(
		'post_type'      => 'post',
		'post_status'    => $filters['post_status'] ? $filters['post_status'] : array_keys( plainmark_get_article_inventory_post_status_options() ),
		'posts_per_page' => 25,
		'paged'          => $filters['paged'],
		'orderby'        => 'modified',
		'order'          => 'DESC',
	);

	if ( '' !== $filters['s'] ) {
		$args['s'] = $filters['s'];
	}

	$tax_query = array();
	if ( $filters['category'] ) {
		$tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => array( $filters['category'] ),
		);
	}
	if ( $filters['technology'] ) {
		$tax_query[] = array(
			'taxonomy' => 'technology',
			'field'    => 'term_id',
			'terms'    => array( $filters['technology'] ),
		);
	}
	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}

	$meta_query = array();
	if ( $filters['article_type'] ) {
		$meta_query[] = array(
			'key'   => '_plainmark_article_type',
			'value' => $filters['article_type'],
		);
	}
	if ( $filters['difficulty'] ) {
		$meta_query[] = array(
			'key'   => '_plainmark_difficulty',
			'value' => $filters['difficulty'],
		);
	}
	if ( $filters['verified_status'] ) {
		if ( 'review_due' === $filters['verified_status'] ) {
			$meta_query[] = array(
				'key'     => '_plainmark_review_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '<',
				'type'    => 'DATE',
			);
			$meta_query[] = array(
				'key'     => '_plainmark_verified_status',
				'value'   => 'deprecated',
				'compare' => '!=',
			);
		} else {
			$meta_query[] = array(
				'key'   => '_plainmark_verified_status',
				'value' => $filters['verified_status'],
			);
		}
	}
	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	return $args;
}

/**
 * Get inventory summary metrics.
 *
 * @return array<string,int>
 */
function plainmark_get_article_inventory_summary() {
	$post_ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array_keys( plainmark_get_article_inventory_post_status_options() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$summary = array(
		'total'              => count( $post_ids ),
		'unverified'         => 0,
		'review_due'         => 0,
		'missing_category'   => 0,
		'missing_technology' => 0,
		'missing_difficulty' => 0,
		'missing_type'       => 0,
	);

	foreach ( $post_ids as $post_id ) {
		$article_meta = function_exists( 'plainmark_get_article_meta' ) ? plainmark_get_article_meta( $post_id ) : array();
		$verification = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( $post_id ) : array( 'status' => 'unverified' );

		if ( 'unverified' === ( $verification['status'] ?? 'unverified' ) ) {
			$summary['unverified']++;
		}
		if ( 'review_due' === ( $verification['status'] ?? '' ) ) {
			$summary['review_due']++;
		}
		if ( ! has_term( '', 'category', $post_id ) ) {
			$summary['missing_category']++;
		}
		if ( ! has_term( '', 'technology', $post_id ) ) {
			$summary['missing_technology']++;
		}
		if ( empty( $article_meta['difficulty'] ) ) {
			$summary['missing_difficulty']++;
		}
		if ( empty( $article_meta['article_type'] ) ) {
			$summary['missing_type']++;
		}
	}

	return $summary;
}

/**
 * Render a metric card.
 *
 * @param string $label Metric label.
 * @param int    $value Metric value.
 * @param string $tone  Visual tone.
 */
function plainmark_render_article_inventory_metric( $label, $value, $tone = 'default' ) {
	?>
	<div class="plainmark-inventory-metric plainmark-inventory-metric--<?php echo esc_attr( $tone ); ?>">
		<strong><?php echo esc_html( (string) $value ); ?></strong>
		<span><?php echo esc_html( $label ); ?></span>
	</div>
	<?php
}

/**
 * Render term names as compact text.
 *
 * @param int    $post_id Post ID.
 * @param string $taxonomy Taxonomy name.
 */
function plainmark_render_article_inventory_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		echo '<span class="plainmark-inventory-empty">未設定</span>';
		return;
	}

	$names = wp_list_pluck( $terms, 'name' );
	echo esc_html( implode( ', ', $names ) );
}

/**
 * Render a compact status pill.
 *
 * @param string $label Pill label.
 * @param string $tone  Visual tone.
 */
function plainmark_render_article_inventory_pill( $label, $tone = 'default' ) {
	?>
	<span class="plainmark-inventory-pill plainmark-inventory-pill--<?php echo esc_attr( $tone ); ?>"><?php echo esc_html( $label ); ?></span>
	<?php
}

/**
 * Render the Article Inventory page.
 */
function plainmark_render_article_inventory_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'plainmark' ) );
	}

	$filters              = plainmark_get_article_inventory_filters();
	$query                = new WP_Query( plainmark_get_article_inventory_query_args( $filters ) );
	$summary              = plainmark_get_article_inventory_summary();
	$article_types        = function_exists( 'plainmark_get_article_type_options' ) ? plainmark_get_article_type_options() : array();
	$difficulty_options   = function_exists( 'plainmark_get_difficulty_options' ) ? plainmark_get_difficulty_options() : array();
	$verification_options = plainmark_get_article_inventory_verification_options();
	$post_status_options  = plainmark_get_article_inventory_post_status_options();
	$categories           = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$technologies         = get_terms( array( 'taxonomy' => 'technology', 'hide_empty' => false ) );
	?>
	<div class="wrap plainmark-inventory">
		<h1><?php esc_html_e( 'Article Inventory', 'plainmark' ); ?></h1>
		<p class="plainmark-inventory-lead"><?php esc_html_e( '記事のカテゴリ、技術スタック、難易度、検証状態をまとめて棚卸しできます。', 'plainmark' ); ?></p>

		<style>
			.plainmark-inventory-lead { margin-top: 4px; color: #646970; }
			.plainmark-inventory-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(132px, 1fr)); gap: 12px; margin: 20px 0; }
			.plainmark-inventory-metric { padding: 16px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; }
			.plainmark-inventory-metric strong { display: block; margin-bottom: 4px; color: #1d2327; font-size: 26px; line-height: 1; }
			.plainmark-inventory-metric span { color: #646970; font-size: 12px; font-weight: 600; }
			.plainmark-inventory-metric--warning { border-color: #f0c33c; background: #fff8e5; }
			.plainmark-inventory-metric--danger { border-color: #d63638; background: #fcf0f1; }
			.plainmark-inventory-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end; margin: 18px 0 20px; padding: 16px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; }
			.plainmark-inventory-filters label { display: grid; gap: 5px; color: #1d2327; font-weight: 600; }
			.plainmark-inventory-filters select, .plainmark-inventory-filters input[type="search"] { width: 100%; max-width: 100%; }
			.plainmark-inventory-actions { display: flex; gap: 8px; align-items: center; }
			.plainmark-inventory-table-wrap { overflow-x: auto; border: 1px solid #c3c4c7; background: #fff; }
			.plainmark-inventory-table { min-width: 1180px; margin: 0; border: 0; }
			.plainmark-inventory-table th, .plainmark-inventory-table td { vertical-align: top; }
			.plainmark-inventory-title { max-width: 280px; }
			.plainmark-inventory-title strong { display: block; margin-bottom: 4px; }
			.plainmark-inventory-title code { font-size: 11px; }
			.plainmark-inventory-muted { color: #646970; font-size: 12px; }
			.plainmark-inventory-empty { color: #b32d2e; font-weight: 600; }
			.plainmark-inventory-pill { display: inline-flex; align-items: center; margin: 0 4px 4px 0; padding: 3px 8px; border-radius: 999px; background: #f0f0f1; color: #1d2327; font-size: 11px; font-weight: 700; line-height: 1.4; white-space: nowrap; }
			.plainmark-inventory-pill--success { background: #edfaef; color: #1a6b2a; }
			.plainmark-inventory-pill--warning { background: #fff8e5; color: #7a5600; }
			.plainmark-inventory-pill--danger { background: #fcf0f1; color: #8b1a1a; }
			.plainmark-inventory-pagination { display: flex; justify-content: flex-end; margin-top: 16px; }
		</style>

		<div class="plainmark-inventory-summary">
			<?php plainmark_render_article_inventory_metric( __( '総記事数', 'plainmark' ), $summary['total'] ); ?>
			<?php plainmark_render_article_inventory_metric( __( '未検証', 'plainmark' ), $summary['unverified'], $summary['unverified'] ? 'warning' : 'default' ); ?>
			<?php plainmark_render_article_inventory_metric( __( 'レビュー期限切れ', 'plainmark' ), $summary['review_due'], $summary['review_due'] ? 'danger' : 'default' ); ?>
			<?php plainmark_render_article_inventory_metric( __( 'カテゴリなし', 'plainmark' ), $summary['missing_category'], $summary['missing_category'] ? 'warning' : 'default' ); ?>
			<?php plainmark_render_article_inventory_metric( __( '技術スタックなし', 'plainmark' ), $summary['missing_technology'], $summary['missing_technology'] ? 'warning' : 'default' ); ?>
			<?php plainmark_render_article_inventory_metric( __( '難易度なし', 'plainmark' ), $summary['missing_difficulty'], $summary['missing_difficulty'] ? 'warning' : 'default' ); ?>
		</div>

		<form class="plainmark-inventory-filters" method="get">
			<input type="hidden" name="page" value="plainmark-article-inventory">
			<label>
				<span><?php esc_html_e( 'キーワード', 'plainmark' ); ?></span>
				<input type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" placeholder="<?php echo esc_attr__( 'タイトル・本文を検索', 'plainmark' ); ?>">
			</label>
			<label>
				<span><?php esc_html_e( 'カテゴリ', 'plainmark' ); ?></span>
				<select name="category">
					<option value="0"><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php if ( ! is_wp_error( $categories ) ) : ?>
						<?php foreach ( $categories as $term ) : ?>
							<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $filters['category'], $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Technology', 'plainmark' ); ?></span>
				<select name="technology">
					<option value="0"><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php if ( ! is_wp_error( $technologies ) ) : ?>
						<?php foreach ( $technologies as $term ) : ?>
							<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $filters['technology'], $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( '記事タイプ', 'plainmark' ); ?></span>
				<select name="article_type">
					<option value=""><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php foreach ( $article_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['article_type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( '難易度', 'plainmark' ); ?></span>
				<select name="difficulty">
					<option value=""><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php foreach ( $difficulty_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['difficulty'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( '検証状態', 'plainmark' ); ?></span>
				<select name="verified_status">
					<option value=""><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php foreach ( $verification_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['verified_status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( '公開状態', 'plainmark' ); ?></span>
				<select name="post_status_filter">
					<option value=""><?php esc_html_e( 'すべて', 'plainmark' ); ?></option>
					<?php foreach ( $post_status_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['post_status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="plainmark-inventory-actions">
				<button class="button button-primary" type="submit"><?php esc_html_e( '絞り込み', 'plainmark' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?page=plainmark-article-inventory' ) ); ?>"><?php esc_html_e( 'リセット', 'plainmark' ); ?></a>
			</div>
		</form>

		<div class="plainmark-inventory-table-wrap">
			<table class="widefat striped plainmark-inventory-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'タイトル', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'カテゴリ', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Technology', 'plainmark' ); ?></th>
						<th><?php esc_html_e( '記事タイプ', 'plainmark' ); ?></th>
						<th><?php esc_html_e( '難易度', 'plainmark' ); ?></th>
						<th><?php esc_html_e( '検証', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Freshness', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'シリーズ', 'plainmark' ); ?></th>
						<th><?php esc_html_e( 'Source', 'plainmark' ); ?></th>
						<th><?php esc_html_e( '更新日', 'plainmark' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $query->have_posts() ) : ?>
						<?php while ( $query->have_posts() ) : ?>
							<?php
							$query->the_post();
							$post_id        = get_the_ID();
							$article_meta   = function_exists( 'plainmark_get_article_meta' ) ? plainmark_get_article_meta( $post_id ) : array();
							$verification   = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( $post_id ) : array( 'status' => 'unverified', 'date' => '', 'review' => '' );
							$freshness      = function_exists( 'plainmark_get_freshness_score' ) ? plainmark_get_freshness_score( $post_id ) : null;
							$status         = $verification['status'] ?? 'unverified';
							$status_tone    = 'verified' === $status ? 'success' : ( 'unverified' === $status ? 'warning' : 'danger' );
							$freshness_tone = $freshness && 'fresh' === $freshness['rank'] ? 'success' : ( $freshness && 'watch' === $freshness['rank'] ? 'warning' : 'danger' );
							$source_path    = (string) get_post_meta( $post_id, '_plainmark_github_path', true );
							?>
							<tr>
								<td class="plainmark-inventory-title">
									<strong><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php the_title(); ?></a></strong>
									<span class="plainmark-inventory-muted"><?php echo esc_html( get_post_status_object( get_post_status( $post_id ) )->label ?? get_post_status( $post_id ) ); ?> / ID: <?php echo esc_html( (string) $post_id ); ?></span>
								</td>
								<td><?php plainmark_render_article_inventory_terms( $post_id, 'category' ); ?></td>
								<td><?php plainmark_render_article_inventory_terms( $post_id, 'technology' ); ?></td>
								<td>
									<?php echo ! empty( $article_meta['article_type_label'] ) ? esc_html( $article_meta['article_type_label'] ) : '<span class="plainmark-inventory-empty">未設定</span>'; ?>
								</td>
								<td>
									<?php echo ! empty( $article_meta['difficulty_label'] ) ? esc_html( $article_meta['difficulty_label'] ) : '<span class="plainmark-inventory-empty">未設定</span>'; ?>
								</td>
								<td>
									<?php plainmark_render_article_inventory_pill( function_exists( 'plainmark_get_verification_label' ) ? plainmark_get_verification_label( $status ) : $status, $status_tone ); ?>
									<?php if ( ! empty( $verification['date'] ) ) : ?>
										<div class="plainmark-inventory-muted"><?php printf( esc_html__( '最終確認: %s', 'plainmark' ), esc_html( $verification['date'] ) ); ?></div>
									<?php endif; ?>
									<?php if ( ! empty( $verification['review'] ) ) : ?>
										<div class="plainmark-inventory-muted"><?php printf( esc_html__( 'レビュー: %s', 'plainmark' ), esc_html( $verification['review'] ) ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $freshness ) : ?>
										<?php plainmark_render_article_inventory_pill( (string) $freshness['score'], $freshness_tone ); ?>
										<?php if ( ! empty( $freshness['reasons'] ) ) : ?>
											<div class="plainmark-inventory-muted"><?php echo esc_html( $freshness['reasons'][0] ); ?></div>
										<?php endif; ?>
									<?php else : ?>
										<span class="plainmark-inventory-empty">未計算</span>
									<?php endif; ?>
									<?php if ( get_post_meta( $post_id, '_plainmark_freshness_review_flagged', true ) ) : ?>
										<div class="plainmark-inventory-muted">
											<?php plainmark_render_article_inventory_pill( esc_html__( 'レビュー要', 'plainmark' ), 'danger' ); ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
												<input type="hidden" name="action" value="plainmark_dismiss_review_flag" />
												<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post_id ); ?>" />
												<?php wp_nonce_field( 'plainmark_dismiss_review_flag_' . $post_id ); ?>
												<button type="submit" class="button-link"><?php esc_html_e( '解除', 'plainmark' ); ?></button>
											</form>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty( $article_meta['series_name'] ) ) : ?>
										<?php echo esc_html( $article_meta['series_name'] ); ?>
										<?php if ( ! empty( $article_meta['series_order'] ) ) : ?>
											<span class="plainmark-inventory-muted">#<?php echo esc_html( $article_meta['series_order'] ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										<span class="plainmark-inventory-muted">-</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $source_path ) : ?>
										<code><?php echo esc_html( $source_path ); ?></code>
									<?php else : ?>
										<span class="plainmark-inventory-muted">WordPress</span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( get_the_modified_date( 'Y-m-d', $post_id ) ); ?>
								</td>
							</tr>
						<?php endwhile; ?>
					<?php else : ?>
						<tr><td colspan="10"><?php esc_html_e( '条件に一致する記事はありません。', 'plainmark' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $query->max_num_pages > 1 ) : ?>
			<div class="plainmark-inventory-pagination tablenav">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $filters['paged'],
								'total'     => $query->max_num_pages,
								'prev_text' => '&lsaquo;',
								'next_text' => '&rsaquo;',
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
}
