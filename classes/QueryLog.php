<?php
/**
 * Query Log class file.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

use ElasticPress\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Query Log class.
 */
class QueryLog {

	/**
	 * Setup the logging page
	 *
	 * @since 1.3
	 */
	public function setup() {
		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) { // Must be network admin in multisite.
			add_action( 'network_admin_menu', array( $this, 'action_admin_menu' ), 11 );
		} else {
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ), 11 );
		}

		add_action( 'ep_remote_request', array( $this, 'log_query' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_init', array( $this, 'maybe_clear_log' ) );
		add_action( 'init', array( $this, 'maybe_disable' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		/**
		 * Handle query storage as JSON strings.
		 *
		 * @see json_encode_query_log()
		 */
		add_filter( 'pre_update_site_option_ep_query_log', array( $this, 'json_encode_query_log' ) );
		add_filter( 'pre_update_option_ep_query_log', array( $this, 'json_encode_query_log' ) );
		add_filter( 'option_ep_query_log', array( $this, 'json_decode_query_log' ) );
		add_filter( 'site_option_ep_query_log', array( $this, 'json_decode_query_log' ) );

		add_filter( 'ep_query_request_args', [ $this, 'maybe_add_request_query_type' ], 10, 7 );
		add_filter( 'ep_pre_request_args', [ $this, 'maybe_add_request_type' ], 10, 4 );
		add_filter( 'ep_pre_request_args', [ $this, 'maybe_add_request_context' ] );
	}

	/**
	 * Save logging settings
	 *
	 * @since 1.3
	 */
	public function action_admin_init() {
		// Save options for multisite
		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK && isset( $_POST['ep_enable_logging'] ) ) {
			check_admin_referer( 'ep-debug-options' );

			update_site_option( 'ep_enable_logging', $this->sanitize_enable_logging( $_POST['ep_enable_logging'] ) );
			update_site_option( 'ep_query_log_by_status', sanitize_text_field( $_POST['ep_query_log_by_status'] ) );
			if ( ! empty( $_POST['ep_query_log_by_context'] ) ) {
				update_site_option( 'ep_query_log_by_context', array_map( 'sanitize_text_field', $_POST['ep_query_log_by_context'] ) );
			} else {
				update_site_option( 'ep_query_log_by_context', [] );
			}
		} else {
			register_setting(
				'ep-debug',
				'ep_enable_logging',
				[ 'sanitize_callback' => [ $this, 'sanitize_enable_logging' ] ]
			);
			register_setting(
				'ep-debug',
				'ep_query_log_by_status',
				[ 'sanitize_callback' => 'sanitize_text_field' ]
			);
			register_setting(
				'ep-debug',
				'ep_query_log_by_context',
				[
					'sanitize_callback' => function ( $value ) {
						return ! empty( $value ) ? array_map( 'sanitize_text_field', $value ) : [];
					},
				]
			);
		}
	}

	/**
	 * Clear query log
	 *
	 * @since 1.3
	 */
	public function maybe_clear_log() {
		if ( empty( $_GET['ep_clear_query_log'] ) || ! wp_verify_nonce( $_GET['ep_clear_query_log'], 'ep_clear_query_log' ) ) {
			return;
		}

		Utils\delete_option( 'ep_query_log' );

		wp_safe_redirect( remove_query_arg( 'ep_clear_query_log' ) );
		exit();
	}

	/**
	 * Add options page
	 *
	 * @since 1.3
	 * @return void
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'elasticpress',
			esc_html__( 'Query Log', 'debug-bar-elasticpress' ),
			esc_html__( 'Query Log', 'debug-bar-elasticpress' ),
			'manage_options',
			'ep-query-log',
			array( $this, 'screen_options' )
		);
	}

	/**
	 * Only log delete index error if not 2xx AND not 404
	 *
	 * @param  array $query Remote request arguments
	 * @since  1.3
	 * @return bool
	 */
	public function maybe_log_delete_index( $query ) {
		$response_code = wp_remote_retrieve_response_code( $query['request'] );

		return ( ( $response_code < 200 || $response_code > 299 ) && 404 !== $response_code );
	}

	/**
	 * Log all non-200 requests
	 *
	 * @param  array $query Remote request arguments
	 * @since  1.3
	 * @return bool
	 */
	public function is_query_error( $query ) {
		if ( is_wp_error( $query['request'] ) ) {
			return true;
		}

		$response_code = wp_remote_retrieve_response_code( $query['request'] );

		return ( $response_code < 200 || $response_code > 299 );
	}

	/**
	 * Check the request body, as usually bulk indexing does not return a status error.
	 *
	 * @since 2.1.0
	 * @param array $query Remote request arguments
	 * @return boolean
	 */
	public function is_bulk_index_error( $query ) {
		if ( $this->is_query_error( $query ) ) {
			return true;
		}

		$request_body = json_decode( wp_remote_retrieve_body( $query['request'] ), true );
		return ! empty( $request_body['errors'] );
	}

	/**
	 * Conditionally save a query to the log which is stored in options. This is a big performance hit so be careful.
	 *
	 * @param array  $query Remote request arguments
	 * @param string $type  Request type
	 * @since 1.3
	 */
	public function log_query( $query, $type ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! $this->should_log_by_context() ) {
			return;
		}

		if ( ! $this->should_log_by_status( $query, $type ) ) {
			return;
		}

		$log = Utils\get_option( 'ep_query_log', [] );

		$log[] = array(
			'query' => $query,
			'type'  => $type,
		);

		// Storing this log would exceed the limit
		if ( mb_strlen( maybe_serialize( $log ) ) > $this->get_logging_storage_limit() ) {
			return;
		}

		Utils\update_option( 'ep_query_log', $log );
	}

	/**
	 * Output query log page
	 *
	 * @since 1.3
	 */
	public function screen_options() {
		$log        = Utils\get_option( 'ep_query_log', array() );
		$enabled    = Utils\get_option( 'ep_enable_logging' );
		$by_status  = Utils\get_option( 'ep_query_log_by_status', 'failed' );
		$by_context = Utils\get_option( 'ep_query_log_by_context', [] );

		if ( is_array( $log ) ) {
			$log = array_reverse( $log );
		}

		$action = 'options.php';

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$action = '';
		}

		$is_time_limit = ! empty( $enabled ) && ! in_array( $enabled, [ '0', 0, '-1', -1 ], true );
		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'ElasticPress Query Log', 'debug-bar-elasticpress' ); ?></h2>

			<form action="<?php echo esc_url( $action ); ?>" method="post">
				<?php settings_fields( 'ep-debug' ); ?>
				<?php settings_errors(); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="ep_enable_logging">
									<?php esc_html_e( 'Enable or disable query logging:', 'debug-bar-elasticpress' ); ?>
								</label>
							</th>
							<td>
								<select name="ep_enable_logging" id="ep_enable_logging">
									<option value="0"><?php esc_html_e( 'Disable', 'debug-bar-elasticpress' ); ?></option>
									<option <?php selected( $is_time_limit ); ?> value="time_limit"><?php esc_html_e( 'Enable for 5 minutes', 'debug-bar-elasticpress' ); ?></option>
									<option <?php selected( '-1', $enabled ); ?> value="-1"><?php esc_html_e( 'Keep enabled', 'debug-bar-elasticpress' ); ?></option>
								</select>
								<br>
								<span class="description">
									<?php
									echo wp_kses_post( __( 'Note that query logging can have <strong>severe</strong> performance implications on your website.', 'debug-bar-elasticpress' ) );
									if ( $is_time_limit ) {
										echo ' ' . wp_kses_post(
											sprintf(
												/* translators: date */
												__( 'Logging queries until <strong>%s</strong>.', 'debug-bar-elasticpress' ),
												wp_date( 'Y-m-d H:i:s', $enabled )
											)
										);
									}
									?>
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ep_query_log_by_status"><?php esc_html_e( 'Log by status:', 'debug-bar-elasticpress' ); ?></label></th>
							<td>
								<select name="ep_query_log_by_status" id="ep_query_log_by_status">
									<option <?php selected( 'failed', $by_status ); ?> value="failed"><?php esc_html_e( 'Only failed queries', 'debug-bar-elasticpress' ); ?></option>
									<option <?php selected( 'all', $by_status ); ?> value="all"><?php esc_html_e( 'All queries', 'debug-bar-elasticpress' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Log by context:', 'debug-bar-elasticpress' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ep_query_log_by_context[]" value="public" <?php checked( empty( $by_context ) || in_array( 'public', $by_context, true ) ); ?>>
									<?php esc_html_e( 'Public', 'debug-bar-elasticpress' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="ep_query_log_by_context[]" value="admin" <?php checked( empty( $by_context ) || in_array( 'admin', $by_context, true ) ); ?>>
									<?php esc_html_e( 'Admin', 'debug-bar-elasticpress' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="ep_query_log_by_context[]" value="ajax" <?php checked( empty( $by_context ) || in_array( 'ajax', $by_context, true ) ); ?>>
									<?php esc_html_e( 'AJAX', 'debug-bar-elasticpress' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="ep_query_log_by_context[]" value="rest" <?php checked( empty( $by_context ) || in_array( 'rest', $by_context, true ) ); ?>>
									<?php esc_html_e( 'REST API', 'debug-bar-elasticpress' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: Current limit */
							__( 'Please note that logs are stored until the storage limit is reached. The current limit is: <strong>%s</strong>', 'debug-bar-elasticpress' ),
							size_format( $this->get_logging_storage_limit() )
						)
					);
					?>
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'debug-bar-elasticpress' ); ?>">

					<?php if ( ! empty( $log ) ) : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'ep_clear_query_log' => wp_create_nonce( 'ep_clear_query_log' ) ) ) ); ?>"><?php esc_html_e( 'Empty Log', 'debug-bar-elasticpress' ); ?></a>
					<?php endif; ?>
				</p>
			</form>

			<?php
			$queries = array_map(
				function( $query ) {
					return $query['query'];
				},
				$log
			);

			$debug_bar_output = new QueryOutput( $queries );
			$debug_bar_output->render_buttons();
			$debug_bar_output->render_queries( [ 'display_context' => true ] );
			?>
		</div>
		<?php
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 *
	 * @since  1.3
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Store the queries as JSON objects.
	 *
	 * This is necessary because otherwise, WP will run it thought `maybe_unserialize()` and break it.
	 *
	 * @param mixed $value The ep_query_log option value.
	 * @return string
	 */
	public function json_encode_query_log( $value ) {
		return wp_json_encode( $value );
	}

	/**
	 * Decode the queries back to an associative array.
	 *
	 * @param string $value A JSON string.
	 * @return array
	 */
	public function json_decode_query_log( $value ) {
		return ( is_string( $value ) ) ? json_decode( $value, true ) : $value;
	}

	/**
	 * Conditionally add the request type to the request args
	 *
	 * @since 3.1.0
	 * @param array       $args       Request args
	 * @param string      $path       Site URL to retrieve
	 * @param array       $query_args The query args originally passed to WP_Query.
	 * @param string|null $type       Type of request, used for debugging.
	 * @return array New request args
	 */
	public function maybe_add_request_type( array $args, string $path, array $query_args, $type ) : array {
		if ( ! empty( $args['ep_query_type'] ) ) {
			return $args;
		}

		if ( ! empty( $type ) ) {
			$args['ep_query_type'] = $type;

			if ( 'get' === $type ) {
				$args['ep_query_type'] = esc_html__( 'Raw ES document', 'debug-bar-elasticpress' );
			}
		}

		if ( '_nodes/plugins' === $path ) {
			$args['ep_query_type'] = esc_html__( 'Elasticsearch check', 'debug-bar-elasticpress' );
		}

		return $args;
	}

	/**
	 * Conditionally add the context of the query
	 *
	 * @param array $args Request args
	 * @return array
	 */
	public function maybe_add_request_context( array $args ) : array {
		$args['ep_context'] = $this->get_current_context();

		return $args;
	}

	/**
	 * Get the current context
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function get_current_context() : string {
		$context = 'public';

		if ( is_admin() ) {
			$context = 'admin';
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$context = 'ajax';
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$context = 'rest';
		}

		return $context;
	}

	/**
	 * Conditionally add the request type to the request args (for query requests)
	 *
	 * @since 3.1.0
	 * @param array  $request_args Request arguments
	 * @param string $path         Request path
	 * @param string $index        Index name
	 * @param string $type         Index type
	 * @param array  $query        Prepared Elasticsearch query
	 * @param array  $query_args   Query arguments
	 * @param mixed  $query_object Could be WP_Query, WP_User_Query, etc.
	 * @return array New request arguments
	 */
	public function maybe_add_request_query_type( array $request_args, string $path, string $index, string $type, array $query, array $query_args, $query_object ) : array {
		$request_args['ep_query_type'] = $this->determine_request_query_type( $request_args, $path, $index, $type, $query, $query_args, $query_object );
		return $request_args;
	}

	/**
	 * Enqueue assets if we are in the correct admin screen
	 *
	 * @since 3.1.0
	 */
	public function admin_enqueue_scripts() {
		$current_screen = get_current_screen();

		if ( ! isset( $current_screen->id ) || 'elasticpress_page_ep-query-log' !== $current_screen->id ) {
			return;
		}

		( new CommonPanel() )->enqueue_scripts_styles();
	}

	/**
	 * Conditionally add the request type to the request args (for query requests)
	 *
	 * @since 3.1.0
	 * @param array  $request_args Request arguments
	 * @param string $path         Request path
	 * @param string $index        Index name
	 * @param string $type         Index type
	 * @param array  $query        Prepared Elasticsearch query
	 * @param array  $query_args   Query arguments
	 * @param mixed  $query_object Could be WP_Query, WP_User_Query, etc.
	 * @return string Request type
	 */
	protected function determine_request_query_type( array $request_args, string $path, string $index, string $type, array $query, array $query_args, $query_object ) : string {
		if ( $query_object instanceof \WP_Query && $query_object->is_main_query() ) {
			return esc_html__( 'Main query', 'debug-bar-elasticpress' );
		}

		if ( empty( $query['query'] ) && ! empty( $query['aggs'] ) ) {
			return esc_html__( 'Possible values for EP filter', 'debug-bar-elasticpress' );
		}

		$search_term = $query_args['s'] ?? '';
		if ( '' !== $search_term ) {
			$type = 'Search';
			if ( apply_filters( 'ep_autosuggest_query_placeholder', 'ep_autosuggest_placeholder' ) === $search_term ) {
				return esc_html__( 'Autosuggest template', 'debug-bar-elasticpress' );
			}

			return esc_html__( 'Search', 'debug-bar-elasticpress' );
		}

		return $type;
	}

	/**
	 * Whether logging is enabled or not
	 *
	 * @since 3.1.0
	 * @return boolean
	 */
	protected function is_enabled() : bool {
		$enabled = Utils\get_option( 'ep_enable_logging' );

		return ! empty( $enabled );
	}

	/**
	 * Whether the current context should or not be logged
	 *
	 * @since 3.1.0
	 * @return boolean
	 */
	protected function should_log_by_context() {
		$by_context = Utils\get_option( 'ep_query_log_by_context', [] );

		return empty( $by_context ) || in_array( $this->get_current_context(), $by_context, true );
	}

	/**
	 * Whether a query (and a type) should be logged or not
	 *
	 * @since 3.1.0
	 * @param array  $query Remote request arguments
	 * @param string $type  Query type
	 * @return boolean
	 */
	protected function should_log_by_status( array $query, $type ) : bool {
		$by_status = Utils\get_option( 'ep_query_log_by_status', 'failed' );

		if ( 'all' === $by_status ) {
			return true;
		}

		/**
		 * This filter allows you to map query types to callables. If the callable returns true,
		 * that query will be logged.
		 *
		 * @var   array
		 * @since 1.3
		 * @since 2.1.0 Added `bulk_index`
		 */
		$allowed_log_types = apply_filters(
			'ep_debug_bar_allowed_log_types',
			array(
				'put_mapping'          => array( $this, 'is_query_error' ),
				'delete_network_alias' => array( $this, 'is_query_error' ),
				'create_network_alias' => array( $this, 'is_query_error' ),
				'bulk_index'           => array( $this, 'is_bulk_index_error' ),
				'bulk_index_posts'     => array( $this, 'is_query_error' ),
				'delete_index'         => array( $this, 'maybe_log_delete_index' ),
				'create_pipeline'      => array( $this, 'is_query_error' ),
				'get_pipeline'         => array( $this, 'is_query_error' ),
				'query'                => array( $this, 'is_query_error' ),
			),
			$query,
			$type
		);

		if ( ! isset( $allowed_log_types[ $type ] ) ) {
			return false;
		}

		return call_user_func( $allowed_log_types[ $type ], $query );
	}

	/**
	 * Return the size limit for stored logs
	 *
	 * @since 3.1.0
	 * @return integer
	 */
	protected function get_logging_storage_limit() : int {
		/**
		 * Filter the log size limit
		 *
		 * @since  3.1.0
		 * @hook ep_debug_bar_log_size_limit
		 * @param  {int} $number Log size limit
		 * @return {int} New limit
		 */
		return apply_filters( 'ep_debug_bar_log_size_limit', MB_IN_BYTES );
	}

	/**
	 * Conditionally disable logging based on period
	 *
	 * @since 3.1.0
	 */
	public function maybe_disable() {
		$enabled = Utils\get_option( 'ep_enable_logging' );

		$is_time_limit = ! empty( $enabled ) && ! in_array( $enabled, [ '0', 0, '-1' ], true );
		if ( ! $is_time_limit || $enabled > wp_date( 'U' ) ) {
			return;
		}

		Utils\update_option( 'ep_enable_logging', 0 );
	}

	/**
	 * Sanitize the ep_enable_logging option, conditionally setting it as a time limit
	 *
	 * @since 3.1.0
	 * @param mixed $value Value sent
	 * @return mixed
	 */
	public function sanitize_enable_logging( $value ) {
		$value = sanitize_text_field( $_POST['ep_enable_logging'] );

		if ( 'time_limit' === $value ) {
			$value = wp_date( 'U', strtotime( '+5 minutes' ) );
		} else {
			$value = ! empty( $value ) ? -1 : 0;
		}

		return $value;
	}
}
