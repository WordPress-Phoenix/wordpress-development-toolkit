<?php

namespace <%= PRIMARY_NAMESPACE %>\<%= SECONDARY_NAMESPACE %>\Includes;

/**
 * Class Assets
 * @package <%= PKG %>
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
	 * @package <%= PKG %>
	 */
	function enqueue_auth_assets() {
		wp_enqueue_style( '<%= SLUG %>-main' );
		wp_enqueue_script( '<%= SLUG %>-main' );
	}

	/**
	 * Register CSS with WordPress
	 * @package <%= PKG %>
	 */
	function register_stylesheets() {
		wp_register_style(
			'<%= SLUG %>-main',
			$this->asset_url . '<%= SLUG %>-main.css'
		);
	}

	/**
	 * Register JavaScript with WordPress
	 * @package <%= PKG %>
	 */
	function register_scripts() {
		wp_register_script(
			'<%= SLUG %>-main',
			$this->asset_url . '<%= SLUG %>-main.js'
		);
	}

} // END class Assets
