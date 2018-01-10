<?php

namespace ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Includes;

/**
 * Class Init
 * @package ||PLUGIN_PACKAGE||
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
	 * Add public and shared functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		new Assets( $this->installed_dir, $this->installed_url );
		// Ex. add_action( 'init', array( $this, 'function_in_this_class_' ) );
	}

}
