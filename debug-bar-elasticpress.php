<?php
/**
 * Plugin Name:       ElasticPress Debugging Add-On
 * Plugin URI:        https://wordpress.org/plugins/debug-bar-elasticpress
 * Description:       Extends the Query Monitor and Debug Bar plugins for ElasticPress queries.
 * Author:            10up
 * Version:           3.1.0
 * Author URI:        https://10up.com
 * Requires PHP:      7.0
 * Requires at least: 5.6
 * Text Domain:       debug-bar-elasticpress
 * Domain Path:       /lang
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package DebugBarElasticPress
 */

namespace DebugBarElasticPress;

define( 'EP_DEBUG_VERSION', '3.1.0' );
define( 'EP_DEBUG_URL', plugin_dir_url( __FILE__ ) );
define( 'EP_DEBUG_MIN_EP_VERSION', '4.4.0' );

spl_autoload_register(
	function( $class ) {
		// project-specific namespace prefix.
		$prefix = 'DebugBarElasticPress\\';

		// base directory for the namespace prefix.
		$base_dir = __DIR__ . '/classes/';

		// does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// if the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

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

	// If Query Monitor is active, do not add the Debug Bar panel (it will be potentially duplicated otherwise)
	if ( class_exists( '\QM_Collectors' ) ) {
		\QM_Collectors::add( new QueryMonitorCollector() );
		add_filter( 'qm/outputter/html', $n( 'register_qm_output' ) );
		add_action( 'qm/output/enqueued-assets', [ new CommonPanel(), 'enqueue_scripts_styles' ] );
	} else {
		add_filter( 'debug_bar_panels', $n( 'add_debug_bar_panel' ) );
		add_filter( 'debug_bar_statuses', $n( 'add_debug_bar_stati' ) );
	}

	add_filter( 'ep_formatted_args', $n( 'add_explain_args' ), 10, 2 );

	add_action( 'wp', $n( 'retrieve_raw_document_from_es' ) );

	load_plugin_textdomain( 'debug-bar-elasticpress', false, basename( __DIR__ ) . '/lang' );

	QueryLog::factory();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );

/**
 * Register panel
 *
 * @param  array $panels Debug Bar Panels
 * @return array
 */
function add_debug_bar_panel( $panels ) {
	include_once __DIR__ . '/classes/EP_Debug_Bar_ElasticPress.php';
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
 * Check if the current page is an indexable singular of an ES document or not.
 *
 * @since 3.1.0
 * @return boolean
 */
function is_indexable_singular() {
	if ( ! is_singular() ) {
		return false;
	}

	$id        = get_the_ID();
	$post_type = get_post_type( $id );

	$post_indexable       = \ElasticPress\Indexables::factory()->get( 'post' );
	$indexable_post_types = $post_indexable->get_indexable_post_types();

	return in_array( $post_type, $indexable_post_types, true );
}

/**
 * Get document from Elasticsearch.
 *
 * @since 3.1.0
 * @return void
 */
function retrieve_raw_document_from_es() {
	if ( empty( $_GET['ep-retrieve-es-document'] ) || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ep-retrieve-es-document' ) ) {
		return;
	}

	if ( ! is_indexable_singular() ) {
		return;
	}

	\ElasticPress\Indexables::factory()->get( 'post' )->get( get_the_ID() );
}

/**
 * Include our QM Output
 *
 * @since 3.1.0
 * @param array $output Array of registered output
 * @return array
 */
function register_qm_output( $output ) {
	$collector = \QM_Collectors::get( 'elasticpress' );

	if ( $collector ) {
		$output['elasticpress'] = new QueryMonitorOutput( $collector );
	}

	return $output;
}
