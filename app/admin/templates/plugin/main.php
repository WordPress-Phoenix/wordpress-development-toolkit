<?php
/**
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
 * Text Domain: <%= DASHES_SLUG %>
 * License: <%= LICENSE_TEXT %>
 */
defined( 'ABSPATH' ) or die(); // protect file source

/**
 * 3RD PARTY DEPENDENCIES
 */
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_6\\Abstract_Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'lib/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}

/**
 * LOAD & RUN <%= NAME %>
 */
include_once trailingslashit( dirname( __FILE__ ) ) . 'app/class-plugin.php';

<%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Plugin::run( __FILE__ );
// Please don't edit below this line.