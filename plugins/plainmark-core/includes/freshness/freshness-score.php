<?php
/**
 * Freshness Score engine.
 *
 * @package plainmark-core
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Freshness scoring weights. All magic numbers live here and are filterable.
 *
 * @return array<string,mixed>
 */
function plainmark_get_freshness_weights() {
	$defaults = array(
		'status_deprecated' => 55,
		'status_unverified' => 25,
		'age_over_year'     => 35,
		'age_over_half'     => 18,
		'age_over_quarter'  => 8,
		'age_days_year'     => 365,
		'age_days_half'     => 180,
		'age_days_quarter'  => 90,
		'no_verified_date'  => 15,
		'review_overdue'    => 25,
		'no_dependencies'   => 5,
		'dep_outdated_each' => 8,
		'dep_outdated_cap'  => 25,
		'ci_failing'        => 30,
		'ci_error'          => 10,
		'review_flagged'    => 10,
		'rank_fresh_min'    => 80,
		'rank_watch_min'    => 55,
	);

	return wp_parse_args(
		(array) apply_filters( 'plainmark_freshness_weights', $defaults ),
		$defaults
	);
}

/**
 * Calculate article freshness score.
 *
 * @param int $post_id Post ID.
 * @return array{score:int,rank:string,reasons:array}
 */
function plainmark_get_freshness_score( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$data    = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( $post_id ) : array();
	$score   = 100;
	$reasons = array();
	$status  = $data['status'] ?? 'unverified';
	$now     = current_datetime()->getTimestamp();
	$w       = plainmark_get_freshness_weights();

	if ( 'verified' !== $status ) {
		$score    -= 'deprecated' === $status ? (int) $w['status_deprecated'] : (int) $w['status_unverified'];
		$reasons[] = 'deprecated' === $status ? __( '非推奨の記事です。', 'plainmark' ) : __( '動作確認が未完了です。', 'plainmark' );
	}

	$verified_date = ! empty( $data['date'] ) ? strtotime( $data['date'] ) : false;
	if ( $verified_date ) {
		$days = floor( ( $now - $verified_date ) / DAY_IN_SECONDS );
		if ( $days > (int) $w['age_days_year'] ) {
			$score    -= (int) $w['age_over_year'];
			$reasons[] = __( '最終確認から1年以上経過しています。', 'plainmark' );
		} elseif ( $days > (int) $w['age_days_half'] ) {
			$score    -= (int) $w['age_over_half'];
			$reasons[] = __( '最終確認から半年以上経過しています。', 'plainmark' );
		} elseif ( $days > (int) $w['age_days_quarter'] ) {
			$score    -= (int) $w['age_over_quarter'];
			$reasons[] = __( '最終確認から3か月以上経過しています。', 'plainmark' );
		}
	} else {
		$score    -= (int) $w['no_verified_date'];
		$reasons[] = __( '最終確認日が未設定です。', 'plainmark' );
	}

	if ( ! empty( $data['review'] ) && strtotime( $data['review'] ) < $now ) {
		$score    -= (int) $w['review_overdue'];
		$reasons[] = __( 'レビュー期限を過ぎています。', 'plainmark' );
	}

	$dependencies = trim( (string) get_post_meta( $post_id, '_plainmark_dependencies', true ) );
	if ( '' === $dependencies ) {
		$score    -= (int) $w['no_dependencies'];
		$reasons[] = __( '依存ライブラリ情報が未設定です。', 'plainmark' );
	} else {
		$outdated_count = (int) get_post_meta( $post_id, '_plainmark_dep_outdated_count', true );
		if ( $outdated_count > 0 ) {
			$penalty   = min( (int) $w['dep_outdated_cap'], $outdated_count * (int) $w['dep_outdated_each'] );
			$score    -= $penalty;
			$reasons[] = sprintf(
				/* translators: %d: number of outdated packages */
				_n(
					'%d 件の依存パッケージのメジャーバージョンが古くなっています。',
					'%d 件の依存パッケージのメジャーバージョンが古くなっています。',
					$outdated_count,
					'plainmark'
				),
				$outdated_count
			);
		}
	}

	$ci_status = (string) get_post_meta( $post_id, '_plainmark_ci_status', true );
	if ( 'failing' === $ci_status ) {
		$score    -= (int) $w['ci_failing'];
		$reasons[] = __( 'CIが失敗しています(コードが動作しない可能性があります)。', 'plainmark' );
	} elseif ( 'error' === $ci_status ) {
		$score    -= (int) $w['ci_error'];
		$reasons[] = __( 'CI実行がエラーで終了しました。', 'plainmark' );
	}

	if ( get_post_meta( $post_id, '_plainmark_freshness_review_flagged', true ) ) {
		$score    -= (int) $w['review_flagged'];
		$reasons[] = __( '読者から情報が古い可能性が報告されています。', 'plainmark' );
	}

	$score = max( 0, min( 100, $score ) );
	$rank  = $score >= (int) $w['rank_fresh_min'] ? 'fresh' : ( $score >= (int) $w['rank_watch_min'] ? 'watch' : 'stale' );

	return array(
		'score'   => $score,
		'rank'    => $rank,
		'reasons' => $reasons,
	);
}
