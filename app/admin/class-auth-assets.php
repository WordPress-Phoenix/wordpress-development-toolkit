<?php

namespace PHX_WP_DEVKIT\V_2_1\Admin;

/**
 * Register and enqueue assets used in the WordPress Admin or only when a user is authenticated here.
 *
 * Class Auth_Assets
 */
class Auth_Assets {

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
	public $asset_url;

	/**
	 * @var string
	 */
	public $version;

	/**
	 * Auth_Assets constructor.
	 *
	 * @param string $dir
	 * @param string $url
	 * @param string $version
	 */
	function __construct( $dir, $url, $version ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->asset_url     = $this->installed_url . 'app/assets/';
		$this->version       = $version;

		/**
		 * Enqueue Assets
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_auth_assets' ) );

		/**
		 * Register Assets
		 */
		$this->register_scripts();
		$this->register_stylesheets();
	}

	/**
	 * Enqueue Assets for Authenticated Users
	 * @package Wordpress_development_toolkit
	 */
	function enqueue_auth_assets( $hook ) {
		if ( false === strpos( $hook, 'wp-phx-dev-kit' ) ) {
			return;
		}
		wp_enqueue_style( 'wordpress-development-toolkit-admin' );
		wp_enqueue_script( 'wordpress-development-toolkit-admin' );
	}

	/**
	 * Register CSS with WordPress
	 * @package Wordpress_development_toolkit
	 */
	function register_stylesheets() {
		wp_register_style(
			'wordpress-development-toolkit-admin',
			$this->asset_url . 'wordpress-development-toolkit-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Register JavaScript with WordPress for Wordpress_development_toolkit
	 * @package Wordpress_development_toolkit
	 */
	function register_scripts() {
		wp_register_script(
			'wordpress-development-toolkit-admin',
			$this->asset_url . 'wordpress-development-toolkit-admin.js',
			array( 'jquery' ), // jquery is loaded everywhere in the wp-admin, so not enqueueing addl. scripts
			false,
			true // load in footer
		);
	}

} // END class Auth_Assets
