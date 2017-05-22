<?php

/**
 * Class EP_Troubleshoot
 */
class EP_Troubleshoot {
	/**
	 * EP_Troubleshoot constructor.
	 */
	public function __construct() {
	}

	/**
	 * WordPress hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * The admin_init hook.
	 * This method also allows for the debugging info file to be downloaded.
	 */
	public function admin_init() {
		if ( isset( $_GET['ep_export'] ) && wp_verify_nonce( $_GET['ep_export'], 'ep_export' ) ) {
			$this->output_file();
			die();
		}
	}

	/**
	 * Adds a submenu page under Tools.
	 */
	public function admin_menu() {
		add_submenu_page( 'tools.php', 'EP Troubleshooting', 'EP Troubleshooting', 'manage_options', 'ep_troubleshoot', array(
			$this,
			'menu_page'
		) );
	}

	/**
	 * Renders the HTML for the menu page.
	 */
	public function menu_page() {
		include EPT_PATH . 'views/troubleshoot.php';
	}

	/**
	 * Collects version, plugin, indexable items, and cluster status.
	 *
	 * @return array
	 */
	private function get_info() {
		return array(
			'Versions:'             => $this->version_info(),
			'Active Features'       => $this->get_active_features(),
			'Active Plugins'        => $this->get_active_plugins(),
			'Indexable Post Types'  => ( function_exists( 'ep_get_indexable_post_types' ) ) ? ep_get_indexable_post_types() : 'N/A',
			'Indexable Post Status' => ( function_exists( 'ep_get_indexable_post_status' ) ) ? ep_get_indexable_post_status() : 'N/A',
			'Cluster Status'        => ( class_exists( 'EP_API' ) ) ? EP_API::factory()->get_cluster_status() : 'N/A',
		);
	}

	/**
	 * Retrieves version information for ElasticPress, Elasticsearch, WordPress, and WooCommerce.
	 *
	 * @return array
	 */
	private function version_info() {
		global $wp_version;
		$es_version  = ( class_exists( 'EP_API' ) ) ? EP_API::factory()->get_elasticsearch_version() : 'N/A';
		$woocommerce = ( class_exists( 'WooCommerce' ) ) ? WooCommerce::instance()->version : 'N/A';

		return array(
			'WordPress'     => $wp_version,
			'ElasticPress'  => ( defined( 'EP_VERSION' ) ) ? EP_VERSION : 'N/A',
			'Elasticsearch' => $es_version,
			'WooCommerce'   => $woocommerce,
		);
	}

	/**
	 * Iterates through list of plugins and returns an array of only active ones.
	 *
	 * @return array
	 */
	private function get_active_plugins() {
		$plugins     = get_plugins();
		$active      = get_option( 'active_plugins' );
		$active_list = array();
		foreach ( $active as $slug ):
			if ( isset( $plugins[ $slug ] ) ) {
				$active_list[] = $plugins[ $slug ];
			}
		endforeach;

		return $active_list;
	}

	/**
	 * Retrieves a list of activated features.
	 *
	 * @return array
	 */
	private function get_active_features() {
		$active   = array();
		$features = get_option( 'ep_feature_settings', array() );
		foreach ( $features as $key => $feature ):
			if ( $feature['active'] ) {
				$active[] = $key;
			}
		endforeach;

		return $active;
	}

	/**
	 * Provides a JSON file for download.
	 */
	private function output_file() {
		$file = wp_json_encode( $this->get_info(), JSON_PRETTY_PRINT );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="ep-troubleshoot.json"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $file ) );
		echo $file;
		exit;
	}
}