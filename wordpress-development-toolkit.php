<?php
/**
 * WordPress Development Toolkit
 *
 * @package     Wordpress_development_toolkit
 * @author      David Ryan - WordPress Phoenix
 * @license     GNU GPL v2.0+
 * @link        https://github.com/wordpress-phoenix
 * @version     2.5.0
 *
 * Built using WP PHX Plugin Generator v1.1.0 on Tuesday 23rd of January 2018 07:33:12 AM
 * @link https://github.com/WordPress-Phoenix/wordpress-development-toolkit
 *
 * @wordpress-plugin
 * Plugin Name: WordPress Development Toolkit
 * Plugin URI: https://github.com/wordpress-phoenix
 * Description: Tools and resources for WordPress development.
 * Version: 2.5.0
 * Author: David Ryan  - WordPress Phoenix
 * Text Domain: wordpress-development-toolkit
 * License: GNU GPL v2.0+
 */
if ( ! function_exists( 'add_filter' ) ) { // prevent snooping file source, check wp loaded
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Check Abstract_Plugin Instantiated
 */
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_6\\Abstract_Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'lib/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}

/**
 * Check PHX_WP_DEVKIT\V_1_2\Plugin Instantiated
 * (The check prevents fatal error if multiple copies of plugin are activated or namespaces aren't unique)
 */
if ( ! class_exists( 'PHX_WP_DEVKIT\\V_2_5\\Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'app/class-plugin.php';
} else {
	new WP_Error( '500', 'Multiple copies of PHX_WP_DEVKIT\V_1_2\Plugin are active' );
}

/**
 * Start WordPress Development Toolkit Main Plugin Class
 */
PHX_WP_DEVKIT\V_2_5\Plugin::run( __FILE__ );
// Please don't edit below this line.
