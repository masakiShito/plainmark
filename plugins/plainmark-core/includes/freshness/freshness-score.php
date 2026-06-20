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

	if ( 'verified' !== $status ) {
		$score    -= 'deprecated' === $status ? 55 : 25;
		$reasons[] = 'deprecated' === $status ? __( '非推奨の記事です。', 'plainmark' ) : __( '動作確認が未完了です。', 'plainmark' );
	}

	$verified_date = ! empty( $data['date'] ) ? strtotime( $data['date'] ) : false;
	if ( $verified_date ) {
		$days = floor( ( $now - $verified_date ) / DAY_IN_SECONDS );
		if ( $days > 365 ) {
			$score    -= 35;
			$reasons[] = __( '最終確認から1年以上経過しています。', 'plainmark' );
		} elseif ( $days > 180 ) {
			$score    -= 18;
			$reasons[] = __( '最終確認から半年以上経過しています。', 'plainmark' );
		} elseif ( $days > 90 ) {
			$score    -= 8;
			$reasons[] = __( '最終確認から3か月以上経過しています。', 'plainmark' );
		}
	} else {
		$score    -= 15;
		$reasons[] = __( '最終確認日が未設定です。', 'plainmark' );
	}

	if ( ! empty( $data['review'] ) && strtotime( $data['review'] ) < $now ) {
		$score    -= 25;
		$reasons[] = __( 'レビュー期限を過ぎています。', 'plainmark' );
	}

	$dependencies = trim( (string) get_post_meta( $post_id, '_plainmark_dependencies', true ) );
	if ( '' === $dependencies ) {
		$score    -= 5;
		$reasons[] = __( '依存ライブラリ情報が未設定です。', 'plainmark' );
	} else {
		$outdated_count = (int) get_post_meta( $post_id, '_plainmark_dep_outdated_count', true );
		if ( $outdated_count > 0 ) {
			$penalty   = min( 25, $outdated_count * 8 );
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

	$score = max( 0, min( 100, $score ) );
	$rank  = $score >= 80 ? 'fresh' : ( $score >= 55 ? 'watch' : 'stale' );

	return array(
		'score'   => $score,
		'rank'    => $rank,
		'reasons' => $reasons,
	);
}
