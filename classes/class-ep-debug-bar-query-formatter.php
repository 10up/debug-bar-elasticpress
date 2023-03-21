<?php
/**
 * Queries report class
 *
 * @since 3.0.0
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

use \ElasticPress\QueryLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Queries report class
 *
 * @package DebugBarElasticPress
 */
class QueryFormatter extends QueryLogger {
	/**
	 * Format queries to display
	 *
	 * @param array $queries Queries to be displayed
	 * @return array
	 */
	public function format_queries_for_display( $queries ) : array {
		$formatted_queries = [];

		$labels = [
			'wp_url'      => esc_html__( 'Page URL', 'elasticpress' ),
			'es_req'      => esc_html__( 'Elasticsearch Request', 'elasticpress' ),
			'request_id'  => esc_html__( 'Request ID', 'elasticpress' ),
			'timestamp'   => esc_html__( 'Time', 'elasticpress' ),
			'query_time'  => esc_html__( 'Time Spent (ms)', 'elasticpress' ),
			'wp_args'     => esc_html__( 'WP Query Args', 'elasticpress' ),
			'status_code' => esc_html__( 'HTTP Status Code', 'elasticpress' ),
			'body'        => esc_html__( 'Query Body', 'elasticpress' ),
			'result'      => esc_html__( 'Query Result', 'elasticpress' ),
		];

		$failed_queries_obj = new \ElasticPress\StatusReport\FailedQueries( $this );

		foreach ( $queries as $query ) {
			$query                    = $this->format_log_entry( $query, 'query' );
			list( $error, $solution ) = $failed_queries_obj->analyze_log( $query );

			$fields = [
				'error'                => [
					'label' => __( 'Error', 'elasticpress' ),
					'value' => $error,
				],
				'recommended_solution' => [
					'label' => __( 'Recommended Solution', 'elasticpress' ),
					'value' => $solution,
				],
			];

			foreach ( $query as $field => $value ) {
				// Already outputted in the title
				if ( in_array( $field, [ 'wp_url', 'timestamp' ], true ) ) {
					continue;
				}

				$fields[ $field ] = [
					'label' => $labels[ $field ] ?? $field,
					'value' => $value,
				];
			}

			$formatted_queries[] = [
				'title'  => sprintf( '%s (%s)', $query['wp_url'], date_i18n( 'Y-m-d H:i:s', $query['timestamp'] ) ),
				'fields' => $fields,
			];
		}

		return $formatted_queries;
	}
}
