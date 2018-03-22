<?php
/**
 * <%= NAME %>
 *
 * @wordpress-plugin
 * @package        WordPress
 * @subpackage     <%= PKG %>
 * @author         <%= AUTHORS %><%= TEAM %>
 * @license        <%= LICENSE_TEXT %>
 * @link           <%= URL %>
 * @version        <%= VERSION %>
 *
 * Built with WP PHX WordPress Development Toolkit v<%= GENERATOR_VERSION %> on <%= CURRENT_TIME %>
 * @link           https://github.com/WordPress-Phoenix/wordpress-development-toolkit
 *
 * Plugin Name: <%= NAME %>
 * Plugin URI: <%= URL %>
 * Description: <%= DESC %>
 * Version: <%= VERSION %>
 * Author: <%= AUTHORS %> <%= TEAM %>
 * Text Domain: <%= DASHES_SLUG %>
 * License: <%= LICENSE_TEXT %>
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit(); /* protects plugin source from public view */
}

$current_dir = trailingslashit( dirname( __FILE__ ) );

/**
 * 3RD PARTY DEPENDENCIES
 * (manually include_once dependencies installed via composer for safety)
 */
if ( ! class_exists( '<%= ABSTRACT_PLUGIN_NAMESPACE_CHECK %>\\Abstract_Plugin' ) ) {
	include_once $current_dir . 'lib/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}

/**
 * INTERNAL DEPENDENCIES (autoloader defined in main plugin class)
 */
include_once $current_dir . 'app/class-plugin.php';

< %= PRIMARY_NAMESPACE %>\< %= SECONDARY_NAMESPACE %>\Plugin::run( __FILE__ );
