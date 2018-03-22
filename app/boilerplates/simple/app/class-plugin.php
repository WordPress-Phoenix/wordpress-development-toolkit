<?php
/**
 * Plugin <%= SECONDARY_NAMESPACE %>
 *
 * @package WordPress
 * @subpackage <%= PKG %>
 */

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>;

use <%= PRIMARY_NAMESPACE %>\< %= SECONDARY_NAMESPACE %>\Admin;;
use <%= ABSTRACT_PLUGIN_NAMESPACE %>\Abstract_Plugin;

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit(); /* protects plugin source from public view */
}

/**
 * Class Plugin
 */
class Plugin extends Abstract_Plugin {

	/**
	 * Use magic constant to tell abstract class current namespace as prefix for all other namespaces in the plugin.
	 *
	 * @var string $autoload_class_prefix magic constant
	 */
	public static $autoload_class_prefix = __NAMESPACE__;

	/**
	 * Action prefix is used to automatically and dynamically assign a prefix to all action hooks.
	 *
	 * @var string
	 */
	public static $action_prefix = '<%= US_SLUG %>_';

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
	 * @since   0.1
	 * @return  void
	 */
	public function onload( $instance ) {
	}

	/**
	 * Initialize public / shared functionality using new Class(), add_action() or add_filter().
	 */
	public function init() {
		do_action( static::$action_prefix . 'before_init' );

		do_action( static::$action_prefix . 'after_init' );
	}

	/**
	 * Initialize functionality only loaded for logged-in users.
	 */
	public function authenticated_init() {
		if ( is_user_logged_in() ) {
			do_action( static::$action_prefix . 'before_authenticated_init' );
			// Object $this->admin is in the abstract plugin base class.
			$this->admin = new Admin\App(
				$this->installed_dir,
				$this->installed_url,
				$this->version
			);
			do_action( static::$action_prefix . 'after_authenticated_init' );
		}
	}

	/**
	 * @return mixed|void
	 */
	protected function defines_and_globals() {
	}

} // END class Plugin
