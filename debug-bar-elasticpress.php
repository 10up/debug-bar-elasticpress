<?php
/**
 * Plugin Name:  Debug Bar ElasticPress
 * Plugin URI:   https://wordpress.org/plugins/debug-bar-elasticpress
 * Description:  Extends the debug bar plugin for ElasticPress queries.
 * Author:       10up
 * Version:      2.1.1
 * Author URI:   https://10up.com
 * Requires PHP: 7.0
 * License:      GPLv2
 * License URI:  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package DebugBarElasticPress
 */

namespace EP_DEBUG_BAR;

define( 'EP_DEBUG_VERSION', '2.1.1' );
define( 'EP_DEBUG_URL', plugin_dir_url( __FILE__ ) );
define( 'EP_DEBUG_MIN_EP_VERSION', '4.4.0' );

/**
 * Setup plugin
 *
 * @since 3.0.0
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	if ( ! defined( 'EP_VERSION' ) || version_compare( EP_VERSION, EP_DEBUG_MIN_EP_VERSION, '<' ) ) {
		add_action( 'admin_notices', $n( 'admin_notice_min_ep_version' ) );
		return;
	}

	require_once __DIR__ . '/classes/class-ep-query-log.php';
	require_once __DIR__ . '/classes/class-ep-debug-bar-query-output.php';
	require_once __DIR__ . '/classes/class-ep-debug-bar-queries-info.php';

	add_filter( 'debug_bar_panels', $n( 'add_debug_bar_panel' ) );
	add_filter( 'debug_bar_statuses', $n( 'add_debug_bar_stati' ) );
	add_filter( 'ep_formatted_args', $n( 'add_explain_args' ), 10, 2 );

	\EP_Debug_Bar_Query_Log::factory();
}

/**
 * Register panel
 *
 * @param  array $panels Debug Bar Panels
 * @return array
 */
function add_debug_bar_panel( $panels ) {
	include_once __DIR__ . '/classes/class-ep-debug-bar-elasticpress.php';
	$panels[] = new \EP_Debug_Bar_ElasticPress();
	return $panels;
}

/**
 * Register status
 *
 * @since 2.1.0
 * @param array $stati Debug Bar Stati
 * @return array
 */
function add_debug_bar_stati( $stati ) {
	$stati[] = array(
		'ep_version',
		esc_html__( 'ElasticPress Version', 'debug-bar-elasticpress' ),
		defined( 'EP_VERSION' ) ? EP_VERSION : '',
	);

	$elasticsearch_version = '';
	if (
		class_exists( '\ElasticPress\Elasticsearch' ) &&
		method_exists( \ElasticPress\Elasticsearch::factory(), 'get_elasticsearch_version' )
	) {
		$elasticsearch_version = \ElasticPress\Elasticsearch::factory()->get_elasticsearch_version();
	}
	if ( function_exists( '\ElasticPress\Utils\is_epio' ) && \ElasticPress\Utils\is_epio() ) {
		$elasticsearch_version = esc_html__( 'ElasticPress.io Managed Platform', 'debug-bar-elasticpress' );
	}
	$stati[] = array(
		'es_version',
		esc_html__( 'Elasticsearch Version', 'debug-bar-elasticpress' ),
		$elasticsearch_version,
	);
	return $stati;
}

/**
 * Add explain=true to elastic post query
 *
 * @param  array $formatted_args Formatted Elasticsearch query
 * @param  array $args           Query variables
 * @return array
 */
function add_explain_args( $formatted_args, $args ) {
	if ( isset( $_GET['explain'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$formatted_args['explain'] = true;
	}
	return $formatted_args;
}


/**
 * Render an admin notice about the absence of the minimum ElasticPress plugin version.
 *
 * @since 3.0.0
 */
function admin_notice_min_ep_version() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: Min. EP version */
				esc_html__( 'Debug Bar ElasticPress needs at least ElasticPress %s to work properly.', 'debug-bar-elasticpress' ),
				esc_html( EP_DEBUG_MIN_EP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );
