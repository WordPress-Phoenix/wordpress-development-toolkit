<?php

namespace ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Admin;

class Init {
	/**
	 * @var
	 */
	public $installed_dir;
	/**
	 * @var
	 */
	public $installed_url;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param $installed_dir
	 * @param $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		new Assets( $this->installed_dir, $this->installed_url );
		new Options_Panel( $this->installed_dir, $this->installed_url );
	}
}
