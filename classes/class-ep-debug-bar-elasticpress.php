<?php

class EP_Debug_Bar_ElasticPress extends Debug_Bar_Panel {

	/**
	 * Panel menu title
	 */
	public $title;

	/**
	 * Dummy construct method
	 */
	public function __construct() { }

	/**
	 * Initial debug bar stuff
	 */
	public function setup() {
		$this->title( esc_html__( 'ElasticPress', 'debug-bar' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
	}

	/**
	 * Enqueue scripts for front end and admin
	 */
	public function enqueue_scripts_styles() {
		wp_enqueue_script( 'debug-bar-elasticpress', plugins_url( '../assets/js/main.js' , __FILE__ ), array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'debug-bar-elasticpress', plugins_url( '../assets/css/main.css' , __FILE__ ), array(), '1.0' );
	}

	/**
	 * Get class instance
	 *
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
	 * Show the menu item in Debug Bar.
	 */
	public function prerender() {
		$this->set_visible( true );
	}

	/**
	 * Show the contents of the panel
	 */
	public function render() {
		if ( ! function_exists( 'ep_get_query_log' ) ) {
			esc_html_e( 'ElasticPress not activated or not at least version 1.8.', 'debug-bar' );
			return;
		}

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			esc_html_e( 'Please enable WP_DEBUG to start logging ElasticPress queries.', 'debug-bar' );
			return;
		}

		$queries = ep_get_query_log();
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}

		?>

		<h2><?php printf( __( '<span>Total ElasticPress Queries:</span> %d', 'debug-bar' ), count( $queries ) ); ?></h2>
		<h2><?php printf( __( '<span>Total Blocking ElasticPress Query Time:</span> %d ms', 'debug-bar' ), (int) ( $total_query_time * 1000 ) ); ?></h2><?php

		if ( empty( $queries ) ) :

			?><ol class="wpd-queries">
				<li><?php esc_html_e( 'No queries to show', 'debug-bar' ); ?></li>
			</ol><?php

		else :

			?><ol class="wpd-queries ep-queries-debug"><?php

				foreach ( $queries as $query ) :
					$query_time = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ? $query['time_finish'] - $query['time_start'] : false;

					$result = wp_remote_retrieve_body( $query['request'] );
					$response = wp_remote_retrieve_response_code( $query['request'] );

					$class = $response < 200 || $response >= 300 ? 'ep-query-failed' : '';

					?><li class="ep-query-debug hide-query-body hide-query-results <?php echo sanitize_html_class( $class ); ?>">
						<div class="ep-query-host">
							<strong><?php esc_html_e( 'Host:', 'debug-bar' ); ?></strong>
							<?php echo esc_html( $query['host'] ); ?>
						</div>

						<div class="ep-query-time"><?php
							if ( ! empty( $query_time ) ) :
								printf( __( '<strong>Time Taken:</strong> %d ms', 'debug-bar' ), ( $query_time * 1000 ) );
							else :
								_e( '<strong>Time Taken:</strong> -', 'debug-bar' );
							endif;
						?></div>

						<div class="ep-query-url">
							<strong><?php esc_html_e( 'URL:', 'debug-bar' ); ?></strong>
							<?php echo esc_url( $query['url'] ); ?>
						</div>

						<div class="ep-query-method">
							<strong><?php esc_html_e( 'Method:', 'debug-bar' ); ?></strong>
							<?php echo esc_html( $query['args']['method'] ); ?>
						</div>

						<div clsas="ep-query-body">
							<strong><?php esc_html_e( 'Query Body:', 'debug-bar' ); ?> <div class="query-body-toggle dashicons"></div></strong>
							<pre class="query-body"><?php echo esc_html( stripslashes( json_encode( json_decode( $query['args']['body'], true ), JSON_PRETTY_PRINT ) ) ); ?></pre>
						</div>

						<div class="ep-query-response-code">
							<?php printf( __( '<strong>Query Response Code:</strong> HTTP %d', 'debug-bar' ), (int) $response ); ?>
						</div>

						<div class="ep-query-result">
							<strong><?php esc_html_e( 'Query Result:', 'debug-bar' ); ?> <div class="query-result-toggle dashicons"></div></strong>
							<pre class="query-results"><?php echo esc_html( stripslashes( json_encode( json_decode( $result, true ), JSON_PRETTY_PRINT ) ) ); ?></pre>
						</div>
					</li><?php

				endforeach;

			?></ol><?php

		endif;
	}

}
