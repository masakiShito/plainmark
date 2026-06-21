<?php
/**
 * Freshness badge for article cards and single posts.
 *
 * @package plainmark
 * @since 0.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Freshness badge HTML.
 *
 * @param int $post_id Post ID.
 * @return string HTML string.
 */
function plainmark_render_freshness_badge( $post_id = 0 ) {
	if ( ! function_exists( 'plainmark_get_freshness_score' ) ) {
		return '';
	}

	$post_id = $post_id ? absint( $post_id ) : get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	$raw_score = get_post_meta( $post_id, '_plainmark_freshness_score', true );
	$score     = '' === $raw_score ? 0 : (int) $raw_score;
	$rank      = (string) get_post_meta( $post_id, '_plainmark_freshness_rank', true );

	if ( '' === $rank ) {
		$data  = plainmark_get_freshness_score( $post_id );
		$score = (int) $data['score'];
		$rank  = (string) $data['rank'];
	}

	if ( '' === $rank ) {
		return '';
	}

	$labels = array(
		'fresh' => __( 'Fresh', 'plainmark' ),
		'watch' => __( 'Watch', 'plainmark' ),
		'stale' => __( 'Stale', 'plainmark' ),
	);

	$icons = array(
		'fresh' => '✓',
		'watch' => '△',
		'stale' => '!',
	);

	$label = $labels[ $rank ] ?? $rank;
	$icon  = $icons[ $rank ] ?? '';

	$verified_date = (string) get_post_meta( $post_id, '_plainmark_verified_date', true );
	$verified_env  = (string) get_post_meta( $post_id, '_plainmark_verified_env', true );
	$tooltip       = '';

	if ( $verified_date ) {
		$verified_month = date_i18n( 'Y-m', strtotime( $verified_date ) );
		$tooltip        = sprintf(
			/* translators: %s: date. */
			esc_attr__( '最終確認: %s', 'plainmark' ),
			esc_attr( $verified_month )
		);

		if ( $verified_env ) {
			$tooltip .= ' / ' . esc_attr( $verified_env );
		}
	}

	return sprintf(
		'<span class="freshness-badge freshness-badge--%1$s" aria-label="%2$s"%3$s><span class="freshness-badge__icon" aria-hidden="true">%4$s</span><span class="freshness-badge__label">%5$s</span></span>',
		esc_attr( $rank ),
		esc_attr(
			sprintf(
				/* translators: 1: rank label, 2: score. */
				__( 'Freshness: %1$s (%2$d/100)', 'plainmark' ),
				$label,
				$score
			)
		),
		$tooltip ? ' title="' . $tooltip . '"' : '',
		esc_html( $icon ),
		esc_html( $label )
	);
}

/**
 * Render the CI verification badge HTML.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function plainmark_render_ci_badge( $post_id = 0 ) {
	if ( ! function_exists( 'plainmark_get_ci_data' ) ) {
		return '';
	}

	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$ci      = plainmark_get_ci_data( $post_id );
	$status  = $ci['status'];
	$display = array(
		'passing' =>
			array(
				'icon'  => '✓',
				'label' => __( 'CI 検証済み', 'plainmark' ),
			),
		'failing' =>
			array(
				'icon'  => 'x',
				'label' => __( 'CI 失敗', 'plainmark' ),
			),
		'error'   =>
			array(
				'icon'  => '!',
				'label' => __( 'CI エラー', 'plainmark' ),
			),
	);

	if ( ! isset( $display[ $status ] ) ) {
		return '';
	}

	$tooltip = '';
	if ( 'passing' === $status && $ci['checked_at'] ) {
		/* translators: %s: CI checked timestamp. */
		$tooltip_format = esc_attr__( '最終成功: %s', 'plainmark' );
		$tooltip        = sprintf( $tooltip_format, esc_attr( $ci['checked_at'] ) );
	}

	$inner = sprintf(
		'<span class="ci-badge__icon" aria-hidden="true">%1$s</span><span class="ci-badge__label">%2$s</span>',
		esc_html( $display[ $status ]['icon'] ),
		esc_html( $display[ $status ]['label'] )
	);

	$classes = 'ci-badge ci-badge--' . sanitize_html_class( $status );

	if ( $ci['run_url'] ) {
		return sprintf(
			'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer"%3$s>%4$s</a>',
			esc_attr( $classes ),
			esc_url( $ci['run_url'] ),
			$tooltip ? ' title="' . $tooltip . '"' : '',
			$inner
		);
	}

	return sprintf(
		'<span class="%1$s"%2$s>%3$s</span>',
		esc_attr( $classes ),
		$tooltip ? ' title="' . $tooltip . '"' : '',
		$inner
	);
}
