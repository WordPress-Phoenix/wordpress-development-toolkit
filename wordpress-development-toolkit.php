<?php
/**
 * Plugin Name: WordPress Development Toolkit
 * Plugin URI: https://github.com/wordpress-phoenix/development-toolkit
 * Author: David Ryan
 * Version: 1.0.0
 */

/**
 * !! Safety Valve !! Prevents return of file source if isn't requested directly
 */
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// fire composer dependencies autoloader, making Abstract_Plugin class and other dependencies available
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_5\\Abstract_Plugin' ) ) {
    include_once  trailingslashit( dirname( __FILE__ ) )  . 'vendor/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}

// make plugin class available for run below
include_once 'app/class-toolkit.php';

// Start the Main Plugin Class
WP_PHX_Dev_Kit\V_1_0\Toolkit::run( __FILE__ );
