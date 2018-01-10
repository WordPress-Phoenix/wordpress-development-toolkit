<?php
/**
 * ||PLUGIN_NAME||
 *
 * @package     ||PLUGIN_PACKAGE||
 * @author      ||PLUGIN_AUTHORS|| ||PLUGIN_TEAM_DASH||
 * @license     ||PLUGIN_LICENSE_TEXT||
 *
 * @wordpress-plugin
 * Plugin Name: ||PLUGIN_NAME||
 * Plugin URI:  ||PLUGIN_GITHUB_REPO||
 * Description: ||PLUGIN_DESC||
 * Version:     ||PLUGIN_VER||
 * Author:      ||PLUGIN_AUTHORS|| ||PLUGIN_TEAM||
 * Text Domain: ||PLUGIN_SLUG||
 * License:     ||PLUGIN_LICENSE_TEXT||
 */

if ( ! function_exists( 'add_filter' ) ) { // prevent snooping file source, check wp loaded
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Load Abstract Plugin Base for use in Main Plugin Class below
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_5\\Abstract_Plugin' ) ) {
	include_once  trailingslashit( dirname( __FILE__ ) )  . 'vendor/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}||INSTANTIATE_OPTIONS_PANEL||

// Make Main Plugin Class Available
include_once trailingslashit( dirname( __FILE__ ) ) . 'app/class-plugin.php';

// Start Main Plugin Class for ||PLUGIN_NAME||.
||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Plugin::run( __FILE__ );

/**
 * Created with...
 *  // WORDPRESS PHOENIX //
 * --------------------------
 *  \\ Abstract Plugin Base \\
 * --------------------------
 * Created ||CURRENT_TIME|| with the WordPress Phoenix Plugin Generator v||GENERATOR_VER||
 *
 * Please don't edit below this line.
 */