<?php

namespace ||PLUGIN_PRIMARY_NAMESPACE||\||PLUGIN_SECONDARY_NAMESPACE||\Includes;
/**
 * Class Assets
 */
class Assets {
	/**
	 * @var
	 */
	public $installed_dir;
	/**
	 * @var
	 */
	public $installed_url;
	/**
	 * Assets constructor.
	 *
	 * @param $dir string
	 * @param $url string
	 */
	function __construct( $dir, $url ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		// register assets early
		add_action( 'init', array( $this, 'register_stylesheets' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );
	}

	/**
	 * Register CSS with WordPress for ||PLUGIN_PACKAGE||
	 */
	function register_stylesheets() {
		wp_register_style(
			'||PLUGIN_SLUG||-main',
			trailingslashit( $this->installed_url ) . 'app/includes/css/||PLUGIN_SLUG||-main.css'
		);
	}

	/**
	 * Register JavaScript with WordPress for ||PLUGIN_PACKAGE||
	 */
	function register_scripts() {
		wp_register_script(
			'||PLUGIN_SLUG||-main',
			trailingslashit( $this->installed_url ) . 'app/includes/js/||PLUGIN_SLUG||-main.js',
			array(),
			false,
			true // load in footer
		);
	}

}
