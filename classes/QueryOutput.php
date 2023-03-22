<?php
/**
 * Query Output class file.
 *
 * A centralized place for the queries output markup.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

defined( 'ABSPATH' ) || exit;

/**
 * Query Output class.
 */
class QueryOutput {
	/**
	 * Queries
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $queries = [];

	/**
	 * Class constructor
	 *
	 * @since 3.0.0
	 * @param array $queries Queries
	 */
	public function __construct( $queries ) {
		$this->queries = $queries;
	}

	/**
	 * Render the download and copy&paste buttons
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function render_buttons() {
		if ( empty( $this->queries ) ) {
			return;
		}

		$copy_paste_output = $this->get_copy_paste_report();
		?>
		<div class="ep-queries-buttons-wrapper">
			<a download="debug-bar-elasticpress-report.txt" href="data:text/plain;charset=utf-8,<?php echo rawurlencode( $copy_paste_output ); ?>"  class="button button-primary" id="ep-download-requests-info">
				<?php esc_html_e( 'Download Requests Info', 'debug-bar-elasticpress' ); ?>
			</a>
			<button class="ep-copy-button button qm-button" data-request="<?php echo esc_attr( $copy_paste_output ); ?>">
				<?php esc_html_e( 'Copy Requests Info to Clipboard', 'debug-bar-elasticpress' ); ?>
			</button>
			<span class="ep-copy-button-wrapper__success" style="display: none;">
				<?php esc_html_e( 'Copied!', 'debug-bar-elasticpress' ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Render the queries
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function render_queries() {
		?>
		<div class="ep-queries-debug-container">
			<ol class="wpd-queries ep-queries-debug">
				<?php
				if ( empty( $this->queries ) ) {
					?>
					<li><?php esc_html_e( 'No queries to show', 'debug-bar-elasticpress' ); ?></li>
					<?php
				} else {
					foreach ( $this->queries as $query ) {
						$this->render_query( $query );
					}
				}
				?>
			</ol>
		</div>
		<?php
	}

	/**
	 * Render a query in a list.
	 *
	 * @param array  $query The query info.
	 * @param string $type The type of the query.
	 * @return void
	 */
	public function render_query( $query, $type = '' ) {
		$error         = '';
		$query_time    = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ? $query['time_finish'] - $query['time_start'] : false;
		$result        = wp_remote_retrieve_body( $query['request'] );
		$response      = wp_remote_retrieve_response_code( $query['request'] );
		$class         = $response < 200 || $response >= 300 ? 'ep-query-failed' : '';
		$log['result'] = json_decode( $result, true );

		if ( class_exists( '\ElasticPress\StatusReport\FailedQueries' ) && class_exists( 'ElasticPress\QueryLogger' ) ) {
			$query_logger = apply_filters( 'ep_query_logger', new \ElasticPress\QueryLogger() );
			if ( $query_logger ) {
				$failed_queries = new \ElasticPress\StatusReport\FailedQueries( $query_logger );
				$error          = $failed_queries->analyze_log( $log );
				$error          = array_filter( $error );
			}
		}

		$curl_request = 'curl -X' . strtoupper( $query['args']['method'] );

		if ( ! empty( $query['args']['headers'] ) ) {
			foreach ( $query['args']['headers'] as $key => $value ) {
				$curl_request .= " -H '$key: $value'";
			}
		}

		if ( ! empty( $query['args']['body'] ) ) {
			$curl_request .= " -d '" . wp_json_encode( json_decode( $query['args']['body'], true ) ) . "'";
		}

			$curl_request .= " '" . $query['url'] . "'";

		?>
		<li class="ep-query-debug hide-query-body hide-query-results hide-query-errors hide-query-args hide-query-headers <?php echo sanitize_html_class( $class ); ?>">

			<?php if ( ! is_wp_error( $query['request'] ) ) : ?>
				<?php if ( ! empty( $error ) ) : ?>
					<div class="ep-query-error-code ep-query-response-code">
						<?php
						echo wp_kses_post(
							/* translators: Debug bar elasticpress error message */
							sprintf( __( '<strong>Error:</strong> %s', 'debug-bar-elasticpress' ), $error[0] )
						);
						?>
					</div>
					<div class="ep-query-error-code ep-query-response-code">
						<?php
						echo wp_kses_post(
							/* translators: Debug bar elasticpress recommended solution for the error */
							sprintf( __( '<strong>Recommended Solution:</strong> %s', 'debug-bar-elasticpress' ), $error[1] )
						);
						?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="ep-query-errors">
					<strong><?php esc_html_e( 'Errors:', 'debug-bar-elasticpress' ); ?> <div class="query-errors-toggle dashicons"></div></strong>
					<pre class="query-errors"><?php echo esc_html( stripslashes( wp_json_encode( $query['request']->errors, JSON_PRETTY_PRINT ) ) ); ?></pre>
				</div>
			<?php endif; ?>

			<?php if ( $type ) : ?>
				<div class="ep-query-type">
					<strong><?php esc_html_e( 'Type:', 'debug-bar-elasticpress' ); ?></strong>
					<?php echo esc_html( $type ); ?>
				</div>
			<?php endif; ?>

			<div class="ep-query-host">
				<strong><?php esc_html_e( 'Host:', 'debug-bar-elasticpress' ); ?></strong>
				<?php echo esc_html( $query['host'] ); ?>
			</div>

			<div class="ep-query-time">
			<?php
			if ( ! empty( $query_time ) ) :
				echo wp_kses_post(
					/* translators: time spent running the query. */
					sprintf( __( '<strong>Time Taken:</strong> %d ms', 'debug-bar-elasticpress' ), ( $query_time * 1000 ) )
				);
			else :
				echo wp_kses_post(
					__( '<strong>Time Taken:</strong> -', 'debug-bar-elasticpress' )
				);
			endif;
			?>
			</div>

			<div class="ep-query-url">
				<strong><?php esc_html_e( 'URL:', 'debug-bar-elasticpress' ); ?></strong>
				<?php echo esc_url( $query['url'] ); ?>
			</div>

			<div class="ep-query-method">
				<strong><?php esc_html_e( 'Method:', 'debug-bar-elasticpress' ); ?></strong>
				<?php echo esc_html( $query['args']['method'] ); ?>
			</div>

			<?php if ( ! empty( $query['args']['headers'] ) ) : ?>
				<div class="ep-query-headers">
					<strong><?php esc_html_e( 'Headers:', 'debug-bar-elasticpress' ); ?> <div class="query-headers-toggle dashicons"></div></strong>
					<pre class="query-headers"><?php echo esc_html( var_export( $query['args']['headers'], true ) ); ?></pre>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $query['query_args'] ) ) : ?>
				<div class="ep-query-args">
					<strong><?php esc_html_e( 'Query Args:', 'debug-bar-elasticpress' ); ?> <div class="query-args-toggle dashicons"></div></strong>
					<pre class="query-args"><?php echo esc_html( var_export( $query['query_args'], true ) ); ?></pre>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $query['args']['body'] ) ) : ?>
				<div class="ep-query-body">
					<strong><?php esc_html_e( 'Query Body:', 'debug-bar-elasticpress' ); ?> <div class="query-body-toggle dashicons"></div></strong>
					<?php
					// Bulk indexes are not "valid" JSON, for example.
					$body = json_decode( $query['args']['body'], true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$body = wp_json_encode( $body, JSON_PRETTY_PRINT );
					} else {
						$body = $query['args']['body'];
					}
					?>
					<pre class="query-body"><?php echo esc_html( stripslashes( $body ) ); ?></pre>
				</div>
			<?php endif; ?>

			<?php if ( ! is_wp_error( $query['request'] ) ) : ?>
				<div class="ep-query-response-code">
					<?php
					echo wp_kses_post(
						/* translators: Query HTTP Code response */
						sprintf( __( '<strong>Query Response Code:</strong> HTTP %d', 'debug-bar-elasticpress' ), (int) $response )
					);
					?>
				</div>
				<div class="ep-query-result">
					<strong><?php esc_html_e( 'Query Result:', 'debug-bar-elasticpress' ); ?> <div class="query-result-toggle dashicons"></div></strong>
					<pre class="query-results"><?php echo esc_html( stripslashes( wp_json_encode( json_decode( $result, true ), JSON_PRETTY_PRINT ) ) ); ?></pre>
				</div>
			<?php else : ?>
				<div class="ep-query-response-code">
					<strong><?php esc_html_e( 'Query Response Code:', 'debug-bar-elasticpress' ); ?></strong> <?php esc_html_e( 'Request Error', 'debug-bar-elasticpress' ); ?>
				</div>
			<?php endif; ?>
			<a class="copy-curl" data-request="<?php echo esc_attr( addcslashes( $curl_request, '"' ) ); ?>">Copy cURL Request</a>
		</li>
		<?php
	}

	/**
	 * Return the copy & paste queries report
	 *
	 * @since 3.0.0
	 * @return string
	 */
	protected function get_copy_paste_report() : string {
		$output = sprintf(
			"## %s ##\n\n",
			__( 'Queries info', 'debug-bar-elasticpress' )
		);

		$query_formatter   = new \DebugBarElasticPress\QueryFormatter();
		$formatted_queries = $query_formatter->format_queries_for_display( $this->queries );

		foreach ( $formatted_queries as $query ) {
			$output .= "### {$query['title']} ###\n";
			foreach ( $query['fields'] as $slug => $field ) {
				$value = $field['value'] ?? '';

				$output .= "{$slug}: ";
				$output .= $this->render_value( $value );
				$output .= "\n";
			}
			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Render a value based on its type
	 *
	 * @since 3.0.0
	 * @param mixed $value The value
	 * @return string
	 */
	protected function render_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return var_export( $value, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}
}
