<?php
/**
 * Main Plugin Class
 *
 * @package WordPress
 */

namespace PHX_WP_DEVKIT\V_3_0;

use PHX_WP_DEVKIT\V_3_0\Admin;
use WPAZ_Plugin_Base\V_2_6\Abstract_Plugin;

/**
 * Class Plugin
 *
 * @package Wordpress_development_toolkit
 */
class Plugin extends Abstract_Plugin {

	/**
	 * Use magic constant to tell abstract class current namespace as prefix for all other namespaces in the plugin.
	 *
	 * @var string $autoload_class_prefix magic constant
	 */
	public static $autoload_class_prefix = __NAMESPACE__;

	/**
	 * Magic constant trick that allows extended classes to pull actual server file location, copy into subclass.
	 *
	 * @var string $current_file
	 */
	protected static $current_file = __FILE__;

	/**
	 * Initialize the plugin - for public (front end)
	 *
	 * @param mixed $instance Parent instance passed through to child.
	 *
	 * @return  void
	 */
	public function onload( $instance ) {
	}

	/**
	 * Initialize the plugin - for public (front end)
	 * Example of building a module of the plugin into init
	 * ```$this->modules->FS_Mail = new FS_Mail( $this, $this->plugin_object_basedir );```
	 *
	 * @since   0.1
	 * @return  void
	 */
	public function init() {
		do_action( get_called_class() . '_before_init' );
		// Add hooks and filters, or other init scopped code here.
		do_action( get_called_class() . '_after_init' );
	}

	/**
	 * Initialize the plugin - for admin (back end)
	 * You would expected this to be handled on action admin_init, but it does not properly handle
	 * the use case for all logged in user actions. Always keep is_user_logged_in() wrapper within
	 * this function for proper usage.
	 *
	 * @return  void
	 */
	public function authenticated_init() {
		if ( is_user_logged_in() ) {
			do_action( get_called_class() . '_before_authenticated_init' );
			new Admin\Init(
				trailingslashit( $this->installed_dir ),
				trailingslashit( $this->installed_url ),
				$this->version
			);
			do_action( get_called_class() . '_after_authenticated_init' );
		}
	}

	/**
	 * Enforce that the plugin prepare any defines or globals in a standard location.
	 *
	 * @return mixed|void
	 */
	protected function defines_and_globals() {
	}

} // END class Plugin
