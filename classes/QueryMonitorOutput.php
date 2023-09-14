<?php
/**
 * QueryMonitorOutput class file.
 *
 * @since 3.1.0
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

defined( 'ABSPATH' ) || exit;

/**
 * QueryMonitorOutput class.
 */
class QueryMonitorOutput extends \QM_Output_Html {
	/**
	 * Common panel instance
	 *
	 * @var CommonPanel
	 */
	protected $common_panel;

	/**
	 * Class constructor
	 *
	 * @param QueryMonitorCollector $collector Our QM Collector
	 */
	public function __construct( QueryMonitorCollector $collector ) {
		parent::__construct( $collector );

		$this->common_panel = new CommonPanel();

		add_filter( 'qm/output/menus', [ $this, 'admin_menu' ] );
	}

	/**
	 * Panel title
	 *
	 * @return string
	 */
	public function name() : string {
		return $this->common_panel->get_title();
	}

	/**
	 * Echoes the output
	 *
	 * @return void
	 */
	public function output() {
		?>
		<div class="qm qm-non-tabular qm-debug-bar qm-panel-show" id="<?php echo esc_attr( $this->collector->id() ); ?>">
			<?php $this->render_summary(); ?>
			<?php $this->common_panel->render(); ?>
		</div>
		<?php
	}

	/**
	 * Render the summary in Query Monitor format
	 *
	 * @return void
	 */
	protected function render_summary() {
		$queries          = \ElasticPress\Elasticsearch::factory()->get_query_log();
		$total_query_time = 0;

		foreach ( $queries as $query ) {
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$total_query_time += ( $query['time_finish'] - $query['time_start'] );
			}
		}
		?>
		<div class="qm-boxed">
			<section>
				<h3>
					<?php esc_html_e( 'Total ElasticPress Queries:', 'debug-bar-elasticpress' ); ?>
				</h3>
				<p><?php echo count( $queries ); ?></p>
			</section>
			<section>
				<h3>
					<?php esc_html_e( 'Total Blocking ElasticPress Query Time:', 'debug-bar-elasticpress' ); ?>
				</h3>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: time spent */
							__( '%d ms', 'debug-bar-elasticpress' ),
							(int) ( $total_query_time * 1000 )
						)
					);
					?>
				</p>
			</section>
		</div>
		<?php
	}
}
