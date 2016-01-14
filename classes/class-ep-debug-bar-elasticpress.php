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
		$queries = ep_get_query_log();
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}

		?>

		<h2><?php printf( __( '<span>Total ElasticPress Queries:</span> %d', 'debug-bar' ), count( $queries ) ); ?></h2>
		<h2><?php printf( __( '<span>Total Blocking ElasticPress Query Time:</span> %d ms', 'debug-bar' ), (int) ( $total_query_time * 1000 ) ); ?></h2>

		<?php if ( empty( $queries ) ) : ?>
			<ol class="wpd-queries">
				<li><?php esc_html_e( 'No queries to show', 'debug-bar' ); ?></li>
			</ol>
		<?php else : ?>
			<ol class="wpd-queries ep-queries-debug">
				<?php foreach ( $queries as $query ) :
					$query_time = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ? $query['time_finish'] - $query['time_start'] : false;
					$response = wp_remote_retrieve_response_code( $query['request'] );

					if ( $response >= 200 && $response < 300 ) {
						$result = wp_remote_retrieve_body( $query['request'] );
					}
					?>
					<li class="ep-query-debug hide-query-body hide-query-results">
						<strong><?php esc_html_e( 'Host:', 'debug-bar'); ?></strong> <?php echo esc_html( $query['host'] ); ?><br>
						<?php if ( ! empty( $query_time ) ) : ?>
							<?php printf( __( '<strong>Time Taken:</strong> %d ms', 'debug-bar'), ( $query_time * 1000 ) ); ?><br>
						<?php else : ?>
							<?php _e( '<strong>Time Taken:</strong> -', 'debug-bar'); ?><br>
						<?php endif; ?>
						<strong><?php esc_html_e( 'URL:', 'debug-bar'); ?></strong> <?php echo esc_url( $query['url'] ); ?><br>
						<strong><?php esc_html_e( 'Method:', 'debug-bar'); ?></strong> <?php echo esc_html( $query['args']['method'] ); ?><br>
						<strong><?php esc_html_e( 'Query Body:', 'debug-bar'); ?> <div class="query-body-toggle dashicons"></div></strong> <pre class="query-body"><?php echo json_encode( json_decode( $query['args']['body'], true ), JSON_PRETTY_PRINT ); ?></pre><br>
						<?php printf( __( '<strong>Query Response Code:</strong> HTTP %d', 'debug-bar'), (int) $response ); ?><br>
						<?php if ( 200 <= $response && $response < 300 ) : ?>
							<strong><?php esc_html_e( 'Query Result:', 'debug-bar'); ?> <div class="query-result-toggle dashicons"></div></strong> <pre class="query-results"><?php echo json_encode( json_decode( $result, true ), JSON_PRETTY_PRINT ); ?></pre><br>
						<?php endif; ?>

					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php
	}
}