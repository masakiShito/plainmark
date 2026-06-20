<?php
/**
 * Freshness frontend presentation.
 *
 * Provides the reader feedback UI only. Freshness scoring, caching,
 * dashboard governance, cron, and feedback data handling live in plainmark-core.
 *
 * @package plainmark
 * @since 0.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append a freshness feedback section to stale/watch/review-needed posts.
 *
 * @param string $content Post content.
 * @return string
 */
function plainmark_append_freshness_feedback( $content ) {
	if ( ! function_exists( 'plainmark_handle_freshness_report' ) || ! function_exists( 'plainmark_get_freshness_score' ) ) {
		return $content;
	}

	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id   = get_the_ID();
	$data      = function_exists( 'plainmark_get_verification_data' )
		? plainmark_get_verification_data( $post_id )
		: array( 'status' => 'unverified' );
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
	if ( ! function_exists( 'plainmark_handle_freshness_report' ) ) {
		return;
	}

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
