<?php

namespace PHX_WP_DEVKIT\V_1_2\Includes;

/**
 * Class Assets
 * @package Wordpress_development_toolkit
 */
class Assets {

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
	 * Assets constructor.
	 *
	 * @param $dir
	 * @param $url
	 * @param $version
	 */
	function __construct( $dir, $url, $version ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->asset_url     = $this->installed_url . 'app/assets/';

		/**
		 * Enqueue Assets
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_auth_assets' ) );

		/**
		 * Register Assets
		 */
		add_action( 'init', array( $this, 'register_stylesheets' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );
	}

	/**
	 * Enqueue Assets
	 * @package Wordpress_development_toolkit
	 */
	function enqueue_auth_assets() {
		wp_enqueue_style( 'wordpress-development-toolkit-main' );
		wp_enqueue_script( 'wordpress-development-toolkit-main' );
	}

	/**
	 * Register CSS with WordPress
	 * @package Wordpress_development_toolkit
	 */
	function register_stylesheets() {
		wp_register_style(
			'wordpress-development-toolkit-main',
			$this->asset_url . 'wordpress-development-toolkit-main.css'
		);
	}

	/**
	 * Register JavaScript with WordPress
	 * @package Wordpress_development_toolkit
	 */
	function register_scripts() {
		wp_register_script(
			'wordpress-development-toolkit-main',
			$this->asset_url . 'wordpress-development-toolkit-main.js'
		);
	}

} // END class Assets
