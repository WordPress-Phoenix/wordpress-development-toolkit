<?php

namespace ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||;

use WPAZ_Plugin_Base\V_2_5\Abstract_Plugin;
use ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Admin;
use ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Includes;

/**
 * Class Plugin
 */
class Plugin extends Abstract_Plugin {

	/**
	 * @var string
	 */
	public static $autoload_class_prefix = __NAMESPACE__;
	/**
	 * @var string
	 */
	public static $autoload_type = 'psr-4';
	/**
	 * @var int
	 */
	public static $autoload_ns_match_depth = 2;

	/**
	 * @var string
	 */
	protected static $current_file = __FILE__;

	/**
	 * @param mixed $instance
	 */
	public function onload( $instance ) {
	}

	/**
	 * Initialize public or shared functionality
	 */
	public function init() {
		do_action( get_called_class() . '_before_init' );
		new Includes\Init( $this->installed_dir, $this->installed_url );
		do_action( get_called_class() . '_after_init' );
	}

	/**
	 * Initialize functionality only for logged-in users
	 */
	public function authenticated_init() {
		if ( is_user_logged_in() ) {
			do_action( get_called_class() . '_before_authenticated_init' );
			new Admin\Init( $this->installed_dir, $this->installed_url );
			do_action( get_called_class() . '_after_authenticated_init' );
		}
	}

	protected function defines_and_globals() {
	}

} // END class Plugin