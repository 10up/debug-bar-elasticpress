<?php
/**
 * Query Log class file.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

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

			update_site_option( 'ep_enable_logging', (int) $_POST['ep_enable_logging'] );
		} else {
			register_setting( 'ep-debug', 'ep_enable_logging', 'intval' );
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

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			delete_site_option( 'ep_query_log' );
		} else {
			delete_option( 'ep_query_log' );
		}

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
		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$enabled = get_site_option( 'ep_enable_logging' );
		} else {
			$enabled = get_option( 'ep_enable_logging' );
		}

		if ( empty( $enabled ) ) {
			return;
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

		if ( isset( $allowed_log_types[ $type ] ) ) {
			$do_log = call_user_func( $allowed_log_types[ $type ], $query );

			if ( ! $do_log ) {
				return;
			}
		} else {
			return;
		}

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$log = get_site_option( 'ep_query_log', array() );
		} else {
			$log = get_option( 'ep_query_log', array() );
		}

		$log[] = array(
			'query' => $query,
			'type'  => $type,
		);

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			update_site_option( 'ep_query_log', $log );
		} else {
			update_option( 'ep_query_log', $log );
		}
	}

	/**
	 * Output query log page
	 *
	 * @since 1.3
	 */
	public function screen_options() {
		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$log     = get_site_option( 'ep_query_log', array() );
			$enabled = get_site_option( 'ep_enable_logging' );
		} else {
			$log     = get_option( 'ep_query_log', array() );
			$enabled = get_option( 'ep_enable_logging' );
		}

		if ( is_array( $log ) ) {
			$log = array_reverse( $log );
		}

		$action = 'options.php';

		if ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) {
			$action = '';
		}
		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'ElasticPress Query Log', 'debug-bar-elasticpress' ); ?></h2>

			<form action="<?php echo esc_url( $action ); ?>" method="post">
				<?php settings_fields( 'ep-debug' ); ?>
				<?php settings_errors(); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="ep_enable_logging"><?php esc_html_e( 'Enable or disable query logging:', 'debug-bar-elasticpress' ); ?></label></th>
							<td>
								<select name="ep_enable_logging" id="ep_enable_logging">
									<option value="0"><?php esc_html_e( 'Disable', 'debug-bar-elasticpress' ); ?></option>
									<option <?php selected( 1, $enabled ); ?> value="1"><?php esc_html_e( 'Enable', 'debug-bar-elasticpress' ); ?></option>
								</select>
								<br>
								<span class="description"><?php echo wp_kses_post( __( 'Note that query logging can have <strong>severe</strong> performance implications on your website. We generally recommend only enabling logging during dashboard indexing and disabling after.', 'debug-bar-elasticpress' ) ); ?></span>
							</td>
						</tr>
					</tbody>
				</table>

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
		$args['ep_context'] = 'public';

		if ( is_admin() ) {
			$args['ep_context'] = 'admin';
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$args['ep_context'] = 'ajax';
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$args['ep_context'] = 'rest';
		}

		return $args;
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

		get_common_panel()->enqueue_scripts_styles();
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
}
