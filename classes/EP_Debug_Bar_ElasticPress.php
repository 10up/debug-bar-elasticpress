<?php
/**
 * New Debug Bar Panel class file.
 *
 * This class can not be in a namespace or Debug Bar won't be able to generate a correct HTML ID for its panel.
 * Also, Query Monitor has a special CSS rule just for this plugin with this class name.
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * @package DebugBarElasticPress
 */

defined( 'ABSPATH' ) || exit;

/**
 * New Debug Bar Panel class.
 */
class EP_Debug_Bar_ElasticPress extends \Debug_Bar_Panel {

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
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script( 'debug-bar-elasticpress', EP_DEBUG_URL . 'assets/js/main.js', array( 'wp-dom-ready', 'clipboard' ), EP_DEBUG_VERSION, true );
		wp_enqueue_style( 'debug-bar-elasticpress', EP_DEBUG_URL . 'assets/css/main.css', array(), EP_DEBUG_VERSION );
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
		$queries          = \ElasticPress\Elasticsearch::factory()->get_query_log();
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

		<?php if ( function_exists( '\ElasticPress\Utils\is_indexing' ) && \ElasticPress\Utils\is_indexing() ) : ?>
			<div class="ep-debug-bar-warning">
				<?php esc_html_e( 'ElasticPress is currently indexing.', 'debug-bar-elasticpress' ); ?>
			</div>
		<?php endif; ?>

		<?php
		$debug_bar_output = new \DebugBarElasticPress\QueryOutput( $queries );
		$debug_bar_output->render_buttons();
		$debug_bar_output->render_queries();
	}
}
