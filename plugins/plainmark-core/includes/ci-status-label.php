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
		'passing' => __( 'CI Passed', 'plainmark' ),
		'failing' => __( 'CI Failed', 'plainmark' ),
		'error'   => __( 'CI Error', 'plainmark' ),
		'skipped' => __( 'CI Skipped', 'plainmark' ),
		'unknown' => __( 'CI Unknown', 'plainmark' ),
	);

	return $labels[ $status ] ?? '';
}
