<?php
/**
 * New Debug Bar Panel class file.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * @package DebugBarElasticPress
 */

defined( 'ABSPATH' ) || exit;

/**
 * New Debug Bar Panel class.
 */
class EP_Debug_Bar_ElasticPress extends Debug_Bar_Panel {


	/**
	 * Panel menu title
	 *
	 * @var string|null
	 */
	public $title;

	/**
	 * Initial debug bar stuff
	 */
	public function init() {
		$this->title( esc_html__( 'ElasticPress', 'debug-bar-elasticpress' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
	}

	/**
	 * Enqueue scripts for front end and admin
	 */
	public function enqueue_scripts_styles() {
		wp_enqueue_script( 'debug-bar-elasticpress', plugins_url( '../assets/js/main.js', __FILE__ ), array( 'wp-dom-ready' ), EP_DEBUG_VERSION, true );
		wp_enqueue_style( 'debug-bar-elasticpress', plugins_url( '../assets/css/main.css', __FILE__ ), array(), EP_DEBUG_VERSION );
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
		if ( ! defined( 'EP_VERSION' ) ) {
			esc_html_e( 'ElasticPress not activated.', 'debug-bar-elasticpress' );
			return;
		}

		if ( function_exists( 'ep_get_query_log' ) ) {
			$queries = ep_get_query_log();
		} else {
			if ( class_exists( '\ElasticPress\Elasticsearch' ) ) {
				$queries = \ElasticPress\Elasticsearch::factory()->get_query_log();
			} else {
				esc_html_e( 'ElasticPress not at least version 1.8.', 'debug-bar-elasticpress' );
				return;
			}
		}
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}

		?>

		<h2>
			<?php
			echo wp_kses_post(
				/* translators: queries count. */
				sprintf( __( '<span>Total ElasticPress Queries:</span> %d', 'debug-bar-elasticpress' ), count( $queries ) )
			);
			?>
		</h2>
		<h2>
			<?php
			echo wp_kses_post(
				/* translators: blocking query time. */
				sprintf( __( '<span>Total Blocking ElasticPress Query Time:</span> %d ms', 'debug-bar-elasticpress' ), (int) ( $total_query_time * 1000 ) )
			);
			?>
		</h2>
		<?php if ( empty( $queries ) ) : ?>
			<ol class="wpd-queries">
				<li><?php esc_html_e( 'No queries to show', 'debug-bar-elasticpress' ); ?></li>
			</ol>
		<?php else : ?>
			<ol class="wpd-queries ep-queries-debug">
				<?php
				foreach ( $queries as $query ) {
					EP_Debug_Bar_Query_Output::render_query( $query );
				}
				?>
			</ol>
			<?php
		endif;
	}
}
