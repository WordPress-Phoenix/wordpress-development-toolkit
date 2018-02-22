<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>;

use <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;
use WPAZ_Plugin_Base\V_2_6\Abstract_Plugin;

defined( 'ABSPATH' ) or die(); // protect file source

/**
 * Class Plugin
 * @package <%= PKG %>
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
	public static $action_prefix = '<%= US_SLUG %>_';

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
			// $this->admin is in the abstract plugin base class
			$this->admin = new Admin\App();
			do_action( static::$action_prefix . 'after_authenticated_init' );
		}
	}

	/**
	 * @return mixed|void
	 */
	protected function defines_and_globals() {
	}

} // END class Plugin
