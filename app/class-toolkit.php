<?php

namespace WP_PHX_Dev_Kit\V_1_0;

use WP_PHX_Dev_Kit\V_1_0\Admin;
use WPAZ_Plugin_Base\V_2_5\Abstract_Plugin;

/**
 * Main Plugin
 */
class Toolkit extends Abstract_Plugin {

	public static $autoload_class_prefix = __NAMESPACE__;
	public static $autoload_type = 'psr-4';
	public static $autoload_ns_match_depth = 2;

	protected static $current_file = __FILE__;

	public function onload( $instance ) {
	}

	public function init() {
		do_action( get_called_class() . '_before_init' );
		do_action( get_called_class() . '_after_init' );
	}

	public function authenticated_init() {
		do_action( get_called_class() . '_before_authenticated_init' );
		if ( is_user_logged_in() ) {
			new Admin\Toolkit_Dashboard_Page( $this->installed_dir, $this->installed_url );
			new Admin\Generators_Page( $this->installed_dir, $this->installed_url, $this->version );
			new Admin\Plugin_Generator_Defaults( $this->installed_dir, $this->installed_url );
//			new Admin\Documentation_Viewer( $this->installed_dir, $this->installed_url );
		}
		do_action( get_called_class() . '_after_authenticated_init' );
	}

	protected function defines_and_globals() {
	}

}
