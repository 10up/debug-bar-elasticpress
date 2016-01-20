<?php
/*
 Plugin Name: Debug Bar ElasticPress
 Plugin URI: http://wordpress.org/plugins/debug-bar-elasticpress
 Description: Extends the debug bar plugin for ElasticPress queries.
 Author: 10up
 Version: 1.0
 Author URI: http://10up.com
 */

/**
 * Register panel
 *
 * @param array $panels
 * @return array
 */
function ep_add_debug_bar_panel( $panels ) {
	require_once( dirname( __FILE__ ) . '/classes/class-ep-debug-bar-elasticpress.php' );
	$panels[] = EP_Debug_Bar_ElasticPress::factory();
	return $panels;
}

add_filter( 'debug_bar_panels', 'ep_add_debug_bar_panel' );
