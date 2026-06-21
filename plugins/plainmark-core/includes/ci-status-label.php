<?php
/**
 * CI status labels.
 *
 * @package plainmark-core
 * @since 0.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Human-readable label for a CI status.
 *
 * @param string $status CI status.
 * @return string
 */
function plainmark_get_ci_status_label( $status ) {
	$labels = array(
		'passing' => __( 'CI 成功', 'plainmark' ),
		'failing' => __( 'CI 失敗', 'plainmark' ),
		'error'   => __( 'CI エラー', 'plainmark' ),
		'skipped' => __( 'CI スキップ', 'plainmark' ),
		'unknown' => __( 'CI 未実行', 'plainmark' ),
	);

	return $labels[ $status ] ?? '';
}
