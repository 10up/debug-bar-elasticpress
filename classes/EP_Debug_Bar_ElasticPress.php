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
	 * Common panel instance
	 *
	 * @var \DebugBarElasticPress\CommonPanel
	 */
	protected $common_panel;

	/**
	 * Initial debug bar stuff
	 */
	public function init() {
		$this->title( esc_html__( 'ElasticPress', 'debug-bar-elasticpress' ) );

		$this->common_panel = new \DebugBarElasticPress\CommonPanel();
		$this->common_panel->enqueue_scripts_styles();
	}

	/**
	 * Enqueue scripts for front end and admin
	 */
	public function enqueue_scripts_styles() {
		_deprecated_function( __METHOD__, '3.1.0', 'DebugBarElasticPress\EP_Panel::enqueue_scripts_styles()' );
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

		<?php
		$this->common_panel->render();
	}
}
