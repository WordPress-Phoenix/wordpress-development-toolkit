<?php
/**
 * Main App File
 *
 * @package    WordPress
 * @subpackage <%= PKG %>
 */

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;

/**
 * Class App
 */
class App {

	/**
	 * Plugins class object installed directory on the server
	 *
	 * @var string $installed_dir Installed server directory
	 */
	public $installed_dir;

	/**
	 * Plugins URL for access to any static files or assets like css, js, or media
	 *
	 * @var string $installed_url Installed URL
	 */
	public $installed_url;

	/**
	 * If plugin_data is built, this represents the version number defined the the main plugin file meta
	 *
	 * @var string $version Version
	 */
	public $version;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir Installed server directory
	 * @param string $installed_url Installed URL
	 * @param string $version       Version
	 */
	function __construct( $installed_dir, $installed_url, $version ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->version       = $version;

		// Put your new Class() or add_action() here.
	}

}
