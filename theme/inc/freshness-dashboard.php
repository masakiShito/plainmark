<?php
/**
 * Freshness management dashboard.
 *
 * Provides:
 * - Admin dashboard widget showing stale/watch articles.
 * - wp_cron email reminders for upcoming review dates.
 * - Reader "is this still accurate?" feedback on the frontend.
 *
 * @package plainmark
 * @since 0.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register cached freshness metadata.
 */
function plainmark_register_freshness_cache_meta() {
	register_post_meta(
		'post',
		'_plainmark_freshness_score',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'post',
		'_plainmark_freshness_rank',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => static function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'plainmark_register_freshness_cache_meta' );

/**
 * Cache the Freshness score when an article is saved.
 *
 * @param int $post_id Post ID.
 */
function plainmark_cache_freshness_score( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! function_exists( 'plainmark_get_freshness_score' ) ) {
		return;
	}

	$freshness = plainmark_get_freshness_score( $post_id );

	update_post_meta( $post_id, '_plainmark_freshness_score', (int) $freshness['score'] );
	update_post_meta( $post_id, '_plainmark_freshness_rank', sanitize_key( $freshness['rank'] ) );
}
add_action( 'save_post', 'plainmark_cache_freshness_score' );

/**
 * Register the Freshness dashboard widget.
 */
function plainmark_register_freshness_widget() {
	wp_add_dashboard_widget(
		'plainmark_freshness_widget',
		__( '記事の鮮度チェック', 'plainmark' ),
		'plainmark_render_freshness_widget'
	);
}
add_action( 'wp_dashboard_setup', 'plainmark_register_freshness_widget' );

/**
 * Enqueue Freshness widget admin styles.
 */
function plainmark_enqueue_freshness_widget_styles() {
	$css = PLAINMARK_DIR . '/assets/css/admin-freshness-widget.css';

	wp_enqueue_style(
		'plainmark-freshness-widget',
		PLAINMARK_URI . '/assets/css/admin-freshness-widget.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : PLAINMARK_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'plainmark_enqueue_freshness_widget_styles' );

/**
 * Count published posts by cached freshness rank.
 *
 * @param string $rank Freshness rank.
 * @return int
 */
function plainmark_count_posts_by_freshness_rank( $rank ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(1)
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_status = 'publish'
			AND p.post_type = 'post'",
			'_plainmark_freshness_rank',
			$rank
		)
	);
}

/**
 * Get the average cached freshness score for published posts.
 *
 * @return int
 */
function plainmark_get_average_freshness_score() {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ROUND(AVG(pm.meta_value))
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND p.post_status = 'publish'
			AND p.post_type = 'post'",
			'_plainmark_freshness_score'
		)
	);
}

/**
 * Build dashboard list items from cached freshness post IDs.
 *
 * @param int[] $post_ids Post IDs.
 * @return array<int,array{id:int,score:int,reasons:array,reports:array}>
 */
function plainmark_build_freshness_widget_items( $post_ids ) {
	$items = array();

	foreach ( $post_ids as $post_id ) {
		$freshness = function_exists( 'plainmark_get_freshness_score' ) ? plainmark_get_freshness_score( $post_id ) : array( 'reasons' => array() );
		$score     = get_post_meta( $post_id, '_plainmark_freshness_score', true );

		$items[] = array(
			'id'      => $post_id,
			'score'   => '' === $score ? (int) ( $freshness['score'] ?? 0 ) : (int) $score,
			'reasons' => $freshness['reasons'] ?? array(),
			'reports' => plainmark_get_freshness_reports( $post_id ),
		);
	}

	return $items;
}

/**
 * Render the Freshness dashboard widget.
 */
function plainmark_render_freshness_widget() {
	$stale_posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_plainmark_freshness_rank',
					'value' => 'stale',
				),
			),
			'meta_key'       => '_plainmark_freshness_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	$watch_posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_plainmark_freshness_rank',
					'value' => 'watch',
				),
			),
			'meta_key'       => '_plainmark_freshness_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	$total       = (int) wp_count_posts( 'post' )->publish;
	$stale_count = plainmark_count_posts_by_freshness_rank( 'stale' );
	$watch_count = plainmark_count_posts_by_freshness_rank( 'watch' );
	$healthy     = max( 0, $total - $stale_count - $watch_count );
	$avg         = plainmark_get_average_freshness_score();
	$stale       = plainmark_build_freshness_widget_items( $stale_posts );
	$watch       = plainmark_build_freshness_widget_items( $watch_posts );
	?>
	<div class="plainmark-freshness-widget">
		<div class="plainmark-fw-summary">
			<div class="is-stale">
				<strong><?php echo esc_html( (string) $stale_count ); ?></strong>
				<span><?php esc_html_e( '要対応', 'plainmark' ); ?></span>
			</div>
			<div class="is-watch">
				<strong><?php echo esc_html( (string) $watch_count ); ?></strong>
				<span><?php esc_html_e( '注意', 'plainmark' ); ?></span>
			</div>
			<div class="is-healthy">
				<strong><?php echo esc_html( (string) $healthy ); ?></strong>
				<span><?php esc_html_e( '良好', 'plainmark' ); ?></span>
			</div>
		</div>

		<p><?php printf( esc_html__( 'サイト平均 Freshness: %d / 100', 'plainmark' ), esc_html( (string) $avg ) ); ?></p>

		<?php plainmark_render_freshness_widget_list( __( '要対応（Freshness < 55）', 'plainmark' ), $stale, 'stale', 10 ); ?>
		<?php plainmark_render_freshness_widget_list( __( '注意（Freshness 55-79）', 'plainmark' ), $watch, 'watch', 5 ); ?>

		<?php if ( empty( $stale ) && empty( $watch ) ) : ?>
			<p style="color: #1a6b2a;"><?php esc_html_e( 'すべての記事が良好な状態です。', 'plainmark' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render a freshness widget list.
 *
 * @param string $title List title.
 * @param array  $items List items.
 * @param string $rank Freshness rank.
 * @param int    $limit Max items.
 */
function plainmark_render_freshness_widget_list( $title, $items, $rank, $limit ) {
	if ( empty( $items ) ) {
		return;
	}
	?>
	<h4 style="margin: 12px 0 4px;"><?php echo esc_html( $title ); ?></h4>
	<ul class="plainmark-fw-list">
		<?php foreach ( array_slice( $items, 0, $limit ) as $item ) : ?>
			<li>
				<div>
					<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( get_the_title( $item['id'] ) ); ?></a>
					<?php if ( ! empty( $item['reasons'] ) ) : ?>
						<span class="plainmark-fw-reason"><?php echo esc_html( $item['reasons'][0] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['reports']['outdated'] ) ) : ?>
						<span class="plainmark-fw-reports"><?php printf( esc_html__( '読者報告: 古い情報 %d 件', 'plainmark' ), esc_html( (string) $item['reports']['outdated'] ) ); ?></span>
					<?php endif; ?>
				</div>
				<span class="plainmark-fw-score is-<?php echo esc_attr( $rank ); ?>"><?php echo esc_html( (string) $item['score'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Schedule the daily freshness check cron event.
 */
function plainmark_schedule_freshness_cron() {
	if ( ! wp_next_scheduled( 'plainmark_daily_freshness_check' ) ) {
		wp_schedule_event( time(), 'daily', 'plainmark_daily_freshness_check' );
	}
}
add_action( 'init', 'plainmark_schedule_freshness_cron' );

/**
 * Run the daily freshness check.
 */
function plainmark_run_freshness_check() {
	$now      = current_datetime();
	$today    = $now->format( 'Y-m-d' );
	$week_out = $now->modify( '+7 days' )->format( 'Y-m-d' );

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_plainmark_review_date',
					'value'   => array( $today, $week_out ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		)
	);

	if ( empty( $posts ) ) {
		return;
	}

	$last_sent = get_option( 'plainmark_freshness_last_notified', '' );
	if ( $last_sent === $today ) {
		return;
	}

	$lines = array();
	foreach ( $posts as $post ) {
		$review_date = get_post_meta( $post->ID, '_plainmark_review_date', true );
		$freshness   = plainmark_get_freshness_score( $post->ID );
		$lines[]     = sprintf(
			'- [%s] (Freshness: %d) - レビュー期限: %s - %s',
			$post->post_title,
			$freshness['score'],
			$review_date,
			get_edit_post_link( $post->ID, 'raw' )
		);
	}

	$subject = sprintf(
		/* translators: %d: number of articles */
		__( '[plainmark] %d件の記事がレビュー期限に近づいています', 'plainmark' ),
		count( $posts )
	);

	$body  = __( '以下の記事のレビュー期限が7日以内です。内容が最新か確認してください。', 'plainmark' ) . "\n\n";
	$body .= implode( "\n", $lines );
	$body .= "\n\n" . __( 'このメールは plainmark テーマの Freshness System から自動送信されています。', 'plainmark' );

	wp_mail( get_option( 'admin_email' ), $subject, $body );
	update_option( 'plainmark_freshness_last_notified', $today, false );
}
add_action( 'plainmark_daily_freshness_check', 'plainmark_run_freshness_check' );

/**
 * Clean up cron on theme deactivation.
 */
function plainmark_deactivate_freshness_cron() {
	wp_clear_scheduled_hook( 'plainmark_daily_freshness_check' );
}
add_action( 'switch_theme', 'plainmark_deactivate_freshness_cron' );

/**
 * Append a freshness feedback section to stale/watch/review-needed posts.
 *
 * @param string $content Post content.
 * @return string
 */
function plainmark_append_freshness_feedback( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id   = get_the_ID();
	$data      = plainmark_get_verification_data( $post_id );
	$freshness = plainmark_get_freshness_score( $post_id );

	if ( 'verified' === $data['status'] && $freshness['score'] >= 80 ) {
		return $content;
	}

	$nonce = wp_create_nonce( 'plainmark_freshness_report' );

	$html  = '<aside class="freshness-feedback" data-post-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
	$html .= '<p class="freshness-feedback__question">' . esc_html__( 'この記事の情報は最新ですか？', 'plainmark' ) . '</p>';
	$html .= '<div class="freshness-feedback__buttons">';
	$html .= '<button type="button" class="freshness-feedback__button" data-freshness-report="accurate">' . esc_html__( '最新です', 'plainmark' ) . '</button>';
	$html .= '<button type="button" class="freshness-feedback__button freshness-feedback__button--outdated" data-freshness-report="outdated">' . esc_html__( '古い情報がある', 'plainmark' ) . '</button>';
	$html .= '</div>';
	$html .= '<div class="freshness-feedback__thanks" hidden>' . esc_html__( 'フィードバックありがとうございます。', 'plainmark' ) . '</div>';
	$html .= '</aside>';

	return $content . $html;
}
add_filter( 'the_content', 'plainmark_append_freshness_feedback', 35 );

/**
 * Enqueue inline assets for the freshness feedback UI.
 */
function plainmark_enqueue_freshness_feedback_assets() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$css = '.freshness-feedback{margin:2rem 0;padding:1.5rem;border:1px solid var(--color-border-light);border-radius:var(--border-radius-md,12px);text-align:center}.freshness-feedback__question{font-size:var(--font-size-sm,.875rem);color:var(--color-text-secondary);margin:0 0 .75rem}.freshness-feedback__buttons{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}.freshness-feedback__button{padding:.5rem 1rem;border:1px solid var(--color-border-default);border-radius:var(--border-radius-md,12px);background:transparent;cursor:pointer;font-size:var(--font-size-sm,.875rem);transition:background var(--transition-duration,160ms) var(--transition-easing,ease)}.freshness-feedback__button:hover{background:var(--color-bg-secondary)}.freshness-feedback__button--outdated:hover{border-color:#c0392b;color:#c0392b}.freshness-feedback__button:disabled{opacity:.5;cursor:default}.freshness-feedback__thanks{font-size:var(--font-size-sm,.875rem);color:var(--color-text-secondary)}';

	wp_register_style( 'plainmark-freshness-feedback', false, array(), PLAINMARK_VERSION );
	wp_enqueue_style( 'plainmark-freshness-feedback' );
	wp_add_inline_style( 'plainmark-freshness-feedback', $css );

	$script = "document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.freshness-feedback').forEach(function(container){var postId=container.getAttribute('data-post-id');var nonce=container.getAttribute('data-nonce');var buttons=container.querySelectorAll('[data-freshness-report]');var thanks=container.querySelector('.freshness-feedback__thanks');buttons.forEach(function(button){button.addEventListener('click',function(){var report=button.getAttribute('data-freshness-report');buttons.forEach(function(item){item.disabled=true;});var body=new FormData();body.append('action','plainmark_freshness_report');body.append('post_id',postId||'');body.append('report',report||'');body.append('nonce',nonce||'');fetch('" . esc_js( admin_url( 'admin-ajax.php' ) ) . "',{method:'POST',body:body}).then(function(response){return response.json();}).then(function(data){if(data&&data.success&&thanks){container.querySelectorAll('.freshness-feedback__question,.freshness-feedback__buttons').forEach(function(el){el.hidden=true;});thanks.hidden=false;}else{buttons.forEach(function(item){item.disabled=false;});}}).catch(function(){buttons.forEach(function(item){item.disabled=false;});});});});});});";

	wp_register_script( 'plainmark-freshness-feedback', '', array(), PLAINMARK_VERSION, true );
	wp_enqueue_script( 'plainmark-freshness-feedback' );
	wp_add_inline_script( 'plainmark-freshness-feedback', $script );
}
add_action( 'wp_enqueue_scripts', 'plainmark_enqueue_freshness_feedback_assets', 40 );

/**
 * Handle freshness report AJAX.
 */
function plainmark_handle_freshness_report() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'plainmark_freshness_report' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$report  = isset( $_POST['report'] ) ? sanitize_key( wp_unslash( $_POST['report'] ) ) : '';

	if ( ! $post_id || ! in_array( $report, array( 'accurate', 'outdated' ), true ) ) {
		wp_send_json_error( 'Invalid data' );
	}

	$meta_key = '_plainmark_freshness_report_' . $report;
	$count    = (int) get_post_meta( $post_id, $meta_key, true );
	update_post_meta( $post_id, $meta_key, $count + 1 );

	if ( 'outdated' === $report ) {
		$outdated_count = (int) get_post_meta( $post_id, '_plainmark_freshness_report_outdated', true );
		$status         = get_post_meta( $post_id, '_plainmark_verified_status', true );

		if ( $outdated_count >= 3 && 'verified' === $status ) {
			update_post_meta( $post_id, '_plainmark_verified_status', 'unverified' );
			update_post_meta( $post_id, '_plainmark_review_date', current_time( 'Y-m-d' ) );
			plainmark_cache_freshness_score( $post_id );
		}
	}

	wp_send_json_success( array( 'message' => 'Report recorded' ) );
}
add_action( 'wp_ajax_plainmark_freshness_report', 'plainmark_handle_freshness_report' );
add_action( 'wp_ajax_nopriv_plainmark_freshness_report', 'plainmark_handle_freshness_report' );

/**
 * Get freshness report counts.
 *
 * @param int $post_id Post ID.
 * @return array{accurate:int,outdated:int}
 */
function plainmark_get_freshness_reports( $post_id ) {
	return array(
		'accurate' => (int) get_post_meta( $post_id, '_plainmark_freshness_report_accurate', true ),
		'outdated' => (int) get_post_meta( $post_id, '_plainmark_freshness_report_outdated', true ),
	);
}