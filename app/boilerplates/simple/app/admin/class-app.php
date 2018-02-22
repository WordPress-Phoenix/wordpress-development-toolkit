<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;

// protect file source
defined( 'ABSPATH' ) or die();

/**
 * Class App
 * @package <%= PKG %>
 */
class App {

	/**
	 * @var string
	 */
	public $installed_dir;

	/**
	 * @var string
	 */
	public $installed_url;

	/**
	 * @var string
	 */
	public $version;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 * @param string $version
	 */
	function __construct( $installed_dir, $installed_url, $version ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->version       = $version;

		// put your new Class() or add_action() here.
	}

}
