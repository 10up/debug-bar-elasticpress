<?php
/**
 * CommonPanel class file.
 *
 * @since 3.1.0
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

defined( 'ABSPATH' ) || exit;

/**
 * CommonPanel class.
 */
class CommonPanel {
	/**
	 * Return the panel title
	 *
	 * @return string
	 */
	public function get_title(): string {
		$queries_count = count( \ElasticPress\Elasticsearch::factory()->get_query_log() );

		if ( $queries_count ) {
			return sprintf(
				/* translators: %d: number of queries */
				esc_html__( 'ElasticPress (%d)', 'debug-bar-elasticpress' ),
				$queries_count
			);
		}

		return esc_html__( 'ElasticPress', 'debug-bar-elasticpress' );
	}

	/**
	 * Enqueue scripts for front end and admin
	 */
	public function enqueue_scripts_styles() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script( 'debug-bar-elasticpress', EP_DEBUG_URL . 'assets/js/main.js', array( 'wp-dom-ready', 'clipboard' ), EP_DEBUG_VERSION, true );
		wp_enqueue_style( 'debug-bar-elasticpress', EP_DEBUG_URL . 'assets/css/main.css', array(), EP_DEBUG_VERSION );
	}

	/**
	 * Show the contents of the panel
	 */
	public function render() {
		$queries = \ElasticPress\Elasticsearch::factory()->get_query_log();

		if ( function_exists( '\ElasticPress\Utils\is_indexing' ) && \ElasticPress\Utils\is_indexing() ) {
			?>
			<div class="ep-debug-bar-warning">
				<?php esc_html_e( 'ElasticPress is currently indexing.', 'debug-bar-elasticpress' ); ?>
			</div>
			<?php
		}

		$debug_bar_output = new \DebugBarElasticPress\QueryOutput( $queries );
		$debug_bar_output->render_buttons();
		$debug_bar_output->render_additional_buttons();
		$debug_bar_output->render_queries();
	}
}
