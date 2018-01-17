<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Admin;

/**
 * Class Init
 * @package <%= PKG %>
 */
class Init {

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

		// handle authenticated stylesheets and scripts
		new Auth_Assets(
			$this->installed_dir,
			$this->installed_url,
			$this->version
		);

		// initialize site options panel
		new Options_Panel(
			$this->installed_dir,
			$this->installed_url
		);
	}

}
