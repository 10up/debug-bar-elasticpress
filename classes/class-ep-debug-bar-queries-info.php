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
class QueriesInfo extends \ElasticPress\StatusReport\Report {

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
			$query = $this->format_log_entry( $query, 'query' );

			list( $error, $solution ) = $this->analyze_log( $query );
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

	/**
	 * Given a query, return a formatted log entry
	 *
	 * @param array  $query The given query
	 * @param string $type  The query type
	 * @return array
	 */
	protected function format_log_entry( array $query, string $type ) : array {
		global $wp;

		$query_time = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ?
			( $query['time_finish'] - $query['time_start'] ) * 1000 :
			false;

		// If the body is too big, trim it down to avoid storing a too big log entry
		$body = ! empty( $query['args']['body'] ) ? $query['args']['body'] : '';
		if ( strlen( $body ) > 200 * KB_IN_BYTES ) {
			$body = substr( $body, 0, 1000 ) . ' (trimmed)';
		} else {
			$json_body = json_decode( $body, true );
			// Bulk indexes are not "valid" JSON, for example.
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$body = wp_json_encode( $json_body );
			}
		}

		$request_id = ( ! empty( $query['args']['headers'] ) && ! empty( $query['args']['headers']['X-ElasticPress-Request-ID'] ) ) ?
			$query['args']['headers']['X-ElasticPress-Request-ID'] :
			null;

		$status = wp_remote_retrieve_response_code( $query['request'] );
		$result = json_decode( wp_remote_retrieve_body( $query['request'] ), true );

		$formatted_log = [
			'wp_url'      => home_url( add_query_arg( [ $_GET ], $wp->request ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'es_req'      => $query['args']['method'] . ' ' . $query['url'],
			'request_id'  => $request_id ?? '',
			'timestamp'   => current_time( 'timestamp' ),
			'query_time'  => $query_time,
			'wp_args'     => $query['query_args'] ?? [],
			'status_code' => $status,
			'body'        => $body,
			'result'      => $result,
		];

		return $formatted_log;
	}

	/**
	 * Given a log, try to find the error and its solution
	 *
	 * @param array $log The log
	 * @return array The error in index 0, solution in index 1
	 */
	public function analyze_log( $log ) {
		$error = '';

		if ( ! empty( $log['result']['error'] ) && ! empty( $log['result']['error']['root_cause'][0]['reason'] ) ) {
			$error = $log['result']['error']['root_cause'][0]['reason'];
		}

		if ( ! empty( $log['result']['errors'] ) && ! empty( $log['result']['items'] ) && ! empty( $log['result']['items'][0]['index']['error']['reason'] ) ) {
			$error = $log['result']['items'][0]['index']['error']['reason'];
		}

		$solution = ( ! empty( $error ) ) ?
			$this->maybe_suggest_solution_for_es( $error ) :
			'';

		return [ $error, $solution ];
	}

	/**
	 * Given an Elasticsearch error, try to suggest a solution
	 *
	 * @param string $error The error
	 * @return string
	 */
	protected function maybe_suggest_solution_for_es( $error ) {
		$sync_url = Utils\get_sync_url();

		if ( preg_match( '/no such index \[(.*?)\]/', $error, $matches ) ) {
			return sprintf(
				/* translators: 1. Index name; 2. Sync Page URL */
				__( 'It seems the %1$s index is missing. <a href="%2$s">Delete all data and sync</a> to fix the issue.', 'debug-bar-elasticpress' ),
				'<code>' . $matches[1] . '</code>',
				$sync_url
			);
		}

		if ( preg_match( '/No mapping found for \[(.*?)\] in order to sort on/', $error, $matches ) ) {
			return sprintf(
				/* translators: 1. Index name; 2. Sync Page URL */
				__( 'The field %1$s was not found. Make sure it is added to the list of indexed fields and run <a href="%2$s">a new sync</a> to fix the issue.', 'debug-bar-elasticpress' ),
				'<code>' . $matches[1] . '</code>',
				$sync_url
			);
		}

		/* translators: 1. Field name; 2. Sync Page URL */
		$field_type_solution = __( 'It seems you saved a post without doing a full sync first because <code>%1$s</code> is missing the correct mapping type. <a href="%2$s">Delete all data and sync</a> to fix the issue.', 'debug-bar-elasticpress' );

		if ( preg_match( '/Fielddata is disabled on text fields by default. Set fielddata=true on \[(.*?)\]/', $error, $matches ) ) {
			return sprintf( $field_type_solution, $matches[1], $sync_url );
		}

		if ( preg_match( '/field \[(.*?)\] is of type \[(.*?)\], but only numeric types are supported./', $error, $matches ) ) {
			return sprintf( $field_type_solution, $matches[1], $sync_url );
		}

		if ( preg_match( '/Alternatively, set fielddata=true on \[(.*?)\] in order to load field data by uninverting the inverted index./', $error, $matches ) ) {
			return sprintf( $field_type_solution, $matches[1], $sync_url );
		}

		if ( preg_match( '/Limit of total fields \[(.*?)\] in index \[(.*?)\] has been exceeded/', $error, $matches ) ) {
			return sprintf(
				/* translators: Elasticsearch or ElasticPress.io; 2. Link to article; 3. Link to article */
				__( 'Your website content has more public custom fields than %1$s is able to store. Check our articles about <a href="%2$s">Elasticsearch field limitations</a> and <a href="%3$s">how to index just the custom fields you need</a> and sync again.', 'debug-bar-elasticpress' ),
				Utils\is_epio() ? __( 'ElasticPress.io', 'debug-bar-elasticpress' ) : __( 'Elasticsearch', 'debug-bar-elasticpress' ),
				'https://elasticpress.zendesk.com/hc/en-us/articles/360051401212-I-get-the-error-Limit-of-total-fields-in-index-has-been-exceeded-',
				'https://elasticpress.zendesk.com/hc/en-us/articles/360052019111'
			);
		}

		// field limit

		if ( Utils\is_epio() ) {
			return sprintf(
				/* translators: ElasticPress.io My Account URL */
				__( 'We did not recognize this error. Please open an ElasticPress.io <a href="%s">support ticket</a> so we can troubleshoot further.', 'debug-bar-elasticpress' ),
				'https://www.elasticpress.io/my-account/'
			);
		}

		return sprintf(
			/* translators: New GitHub issue URL */
			__( 'We did not recognize this error. Please consider opening a <a href="%s">GitHub Issue</a> so we can add it to our list of supported errors. ', 'debug-bar-elasticpress' ),
			'https://github.com/10up/ElasticPress/issues/new/choose'
		);
	}
}
