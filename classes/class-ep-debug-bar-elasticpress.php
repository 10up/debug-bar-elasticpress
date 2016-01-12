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
	}

	/**
	 * Get class instance
	 * 
	 * @return object
	 */
	public function factory() {
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
		$queries = EP_API::factory()->queries;
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}

		?>

		<h2><?php printf( __( '<span>Total ElasticPress Queries:</span> %d', 'debug-bar' ), count( EP_API::factory()->queries ) ); ?></h2>
		<h2><?php printf( __( '<span>Total Blocking ElasticPress Query Time:</span> %d ms', 'debug-bar' ), (int) ( $total_query_time * 1000 ) ); ?></h2>

		<?php if ( empty( $queries ) ) : ?>
			<p><?php esc_html_e( 'No queries to show', 'debug-bar' ); ?></p>
		<?php else : ?>
			<ol class="wpd-queries">
				<?php foreach ( $queries as $query ) : $query_time = ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) ? $query['time_finish'] - $query['time_start'] : false; ?>
					<li>
						<strong><?php esc_html_e( 'Host:', 'debug-bar'); ?></strong> <?php echo esc_html( $query['host'] ); ?><br>
						<?php if ( ! empty( $query_time ) ) : ?>
							<?php printf( __( '<strong>Time Taken:</strong> %d ms', 'debug-bar'), ( $query_time * 1000 ) ); ?><br>
						<?php else : ?>
							<?php _e( '<strong>Time Taken:</strong> -', 'debug-bar'); ?><br>
						<?php endif; ?>
						<strong><?php esc_html_e( 'URL:', 'debug-bar'); ?></strong> <?php echo esc_url( $query['url'] ); ?><br>
						<strong><?php esc_html_e( 'Method:', 'debug-bar'); ?></strong> <?php echo esc_html( $query['args']['method'] ); ?><br>
						<strong><?php esc_html_e( 'Query Body:', 'debug-bar'); ?></strong> <pre><?php echo json_encode( json_decode( $query['args']['body'], true ), JSON_PRETTY_PRINT ); ?></pre><br>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php
	}
}