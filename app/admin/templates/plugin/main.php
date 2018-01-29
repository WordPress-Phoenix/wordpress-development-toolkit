<?php
/**
 *
 * <%= NAME %>
 *
 * @wordpress-plugin
 * @package     <%= PKG %>
 * @author      <%= AUTHORS %><%= TEAM %>
 * @license     <%= LICENSE_TEXT %>
 * @link        <%= URL %>
 * @version     <%= VERSION %>
 *
 * Built using WP PHX Plugin Generator v<%= GENERATOR_VERSION %> on <%= CURRENT_TIME %>
 * @link https://github.com/WordPress-Phoenix/wordpress-development-toolkit
 *
 * Plugin Name: <%= NAME %>
 * Plugin URI: <%= URL %>
 * Description: <%= DESC %>
 * Version: <%= VERSION %>
 * Author: <%= AUTHORS %> <%= TEAM %>
 * Text Domain: <%= SLUG %>
 * License: <%= LICENSE_TEXT %>
 */
if ( ! function_exists( 'add_filter' ) ) { // prevent snooping file source, check wp loaded
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Check Abstract_Plugin Instantiated
 */
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_5\\Abstract_Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'vendor/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}<%= INSTANTIATE_OPTIONS_PANEL %>

/**
 * Check <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Plugin Instantiated
 * (The check prevents fatal error if multiple copies of plugin are activated or namespaces aren't unique)
 */
if ( ! class_exists( '<%= PRIMARY_NAMESPACE %>\\<%= SECONDARY_NAMESPACE %>\\Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'app/class-plugin.php';
} else {
	new WP_Error( '500', 'Multiple copies of <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Plugin are active' );
}

/**
 * Start <%= NAME %> Main Plugin Class
 */
<%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Plugin::run( __FILE__ );
// Please don't edit below this line.