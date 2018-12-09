<?php
/*
 Plugin Name: Debug Bar ElasticPress
 Plugin URI: http://wordpress.org/plugins/debug-bar-elasticpress
 Description: Extends the debug bar plugin for ElasticPress queries.
 Author: 10up
 Version: 1.3
 Author URI: http://10up.com
 */

define( 'EP_DEBUG_VERSION', '1.3' );

/**
 * Register panel
 *
 * @param array $panels
 * @return array
 */
function ep_add_debug_bar_panel( $panels ) {
	require_once( dirname( __FILE__ ) . '/classes/class-ep-debug-bar-elasticpress.php' );
	$panels[] = new EP_Debug_Bar_ElasticPress();
	return $panels;
}

add_filter( 'debug_bar_panels', 'ep_add_debug_bar_panel' );

/**
 * Registers EP and ES versions on panel
 *
 * @param $statuses
 * @return array
 */
function ep_add_debug_bar_status( $statuses ) {
	$statuses[] = array(
		'ep_version',
		__( 'ElasticPress Version', 'debug-bar' ),
		get_option( 'ep_version' ),
	);
	$statuses[] = array(
		'es_version',
		__( 'ElasticSearch Version', 'debug-bar' ),
		ep_get_elasticsearch_version(),
	);
	return $statuses;
}
add_filter( 'debug_bar_statuses', 'ep_add_debug_bar_status' );

/**
 * Add explain=true to elastic post query
 *
 * @param array $formatted_args
 * @param array $args
 * @return array
 */
function ep_add_explain_args( $formatted_args, $args ) {
	if( isset( $_GET['explain'] ) ){
		$formatted_args['explain'] = true;
	}
	return $formatted_args;
}
add_filter( 'ep_formatted_args', 'ep_add_explain_args', 10, 2 );

require_once( dirname( __FILE__ ) . '/classes/class-ep-query-log.php' );

/**
 * Set up error log
 *
 * @since  1.3
 */
function ep_setup_query_log() {
	EP_Debug_Bar_Query_Log::factory();
}
add_action( 'plugins_loaded', 'ep_setup_query_log' );
