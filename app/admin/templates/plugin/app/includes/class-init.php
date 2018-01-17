<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Includes;

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
	 * Add public and shared functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 * @param string $version
	 */
	function __construct( $installed_dir, $installed_url, $version ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->version       = $version;

		// handle global assets
		new Assets(
			$this->installed_dir,
			$this->installed_url,
			$version
		);
		// Ex. add_action( 'init', array( $this, 'function_in_this_class' ) );
	}

}
