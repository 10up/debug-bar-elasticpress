<?php
/**
 * Queries report class
 *
 * @since 2.2.0
 * @package DebugBarElasticPress
 */

use \ElasticPress\QueryLogger;
use \ElasticPress\Utils;
use \ElasticPress\StatusReport\Report as Report;

defined( 'ABSPATH' ) || exit;

/**
 * Queries report class
 *
 * @package DebugBarElasticPress
 */
class QueriesInfo extends QueryLogger {

	/**
	 * The array of queries
	 *
	 * @var array
	 */
	protected $queries;

	/**
	 * Class constructor
	 *
	 * @param array $queries array of queries
	 */
	public function __construct( $queries ) {
		$this->queries = $queries;
	}

	/**
	 * Return the report title
	 *
	 * @return string
	 */
	public function get_title() : string {
		return __( 'Queries info', 'debug-bar-elasticpress' );
	}

	/**
	 * Return the report fields
	 *
	 * @return array
	 */
	public function get_groups() : array {

		$labels = [
			'wp_url'      => esc_html__( 'Page URL', 'debug-bar-elasticpress' ),
			'es_req'      => esc_html__( 'Elasticsearch Request', 'debug-bar-elasticpress' ),
			'request_id'  => esc_html__( 'Request ID', 'debug-bar-elasticpress' ),
			'timestamp'   => esc_html__( 'Time', 'debug-bar-elasticpress' ),
			'query_time'  => esc_html__( 'Time Spent (ms)', 'debug-bar-elasticpress' ),
			'wp_args'     => esc_html__( 'WP Query Args', 'debug-bar-elasticpress' ),
			'status_code' => esc_html__( 'HTTP Status Code', 'debug-bar-elasticpress' ),
			'body'        => esc_html__( 'Query Body', 'debug-bar-elasticpress' ),
			'result'      => esc_html__( 'Query Result', 'debug-bar-elasticpress' ),
		];

		$groups = [];
		foreach ( $this->queries as $query ) {
			/* this filter is documented in elasticpress.php */
			$query_logger   = apply_filters( 'ep_query_logger', new QueryLogger() );
			$failed_queries = new \ElasticPress\StatusReport\FailedQueries( $query_logger );
			$query          = $this->format_log_entry( $query, 'query' );

			list( $error, $solution ) = $failed_queries->analyze_log( $query );
			if ( ! empty( $error ) ) {
				$fields = [
					'error'                => [
						'label' => __( 'Error', 'debug-bar-elasticpress' ),
						'value' => $error,
					],
					'recommended_solution' => [
						'label' => __( 'Recommended Solution', 'debug-bar-elasticpress' ),
						'value' => $solution,
					],
				];
			}

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

			$groups[] = [
				'title'  => sprintf( '%s (%s)', $query['wp_url'], date_i18n( 'Y-m-d H:i:s', $query['timestamp'] ) ),
				'fields' => $fields,
			];
		}

		return $groups;
	}
}
