<?php

namespace WP_PHX_Dev_Kit\V_1_0\Admin;

class Generators_Page {
	/**
	 * @var string
	 */
	public $installed_dir;
	/**
	 * @var string
	 */
	public $installed_url;

	/**
	 * @var
	 */
	public $version;

	/**
	 * Generators_Page constructor.
	 *
	 * @param $installed_dir
	 * @param $installed_url
	 * @param $version
	 */
	function __construct( $installed_dir, $installed_url, $version ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->version		 = $version;

		if ( isset( $_POST['wp-phx-create-abstract-plugin'] ) ) {
			$this->process_download();
		}

		add_action( 'admin_menu', array( $this, 'register_generator_as_plugin_subpage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_generator_dependencies' ) );
	}

	/**
	 * 1. Gather and validate data to make sure we can build a plugin
	 * 2. If we have valid data, sanitize it.
	 * 3. Create our new .zip
	 * 4. Loop over the generator template directory recursively
	 * 5. Check for exclusion of file or directory
	 * 6. Maybe rewrite filename
	 * 7. Run operations on file using `wp_phx_plugingen_file_contents` filter
	 * 8. Write file into appropriate path in .zip
	 * 9. Set PHP headers() to initiate download of the .zip
	 * 10. Clear the temporary .zip from the server
	 */
	function process_download() {
		$data = $this->gather_submitted_data();
		$data['generator_version'] = $this->version;
		new Product_Machine(
			sanitize_title_with_dashes( $data['plugin_name'] ),
			$this->installed_dir . '/admin/templates/plugin',
			$this->installed_dir . '/tmp',
			$data,
			$this->create_config()
		);
	}

	function gather_submitted_data() {
		$submitted = array();
		$fields    = $this->reduce_to_single_attribute( $this->generator_app_data()['fields'], 'key' );
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$submitted[ $field ] = $_POST[ $field ];
			}
		}

		return $submitted;
	}

	function reduce_to_single_attribute( $array, $key ) {
		return array_map( function ( $v ) use ( $key ) {
			return is_object( $v ) ? $v->$key : $v[ $key ];
		}, $array );
	}

	function generator_app_data() {
		$data = array( 'nonce-zip-generate' => wp_create_nonce( 'zip-generate' ) );

		if ( is_multisite() ) {
			$data['sites'] = $this->generator_sites_data();
		} else {
			$data['sites'][0] = array(
				get_bloginfo( 'name' ),
				get_bloginfo( 'url' ),
				$this->installed_dir,
				$this->installed_url,
			);
		}

		$config_data = file_get_contents( trailingslashit( $this->installed_dir ) . 'admin/plugin-generator-config.json' );
		$config      = ! empty( $config_data ) ? json_decode( $config_data, true ) : null;

		if ( is_array( $config ) && isset( $config['fields'] ) && ! empty( $config['fields'] ) ) {
			$data['fields'] = self::prepare_fields_data( $config );
		}

		if ( is_array( $config ) && isset( $config['app'] ) && ! empty( $config['app'] ) ) {
			$data['app'] = $config['app'];
		}

		if ( '.test' === stristr( get_bloginfo( 'url' ), '.test' ) || '.dev' === stristr( get_bloginfo( 'url' ), '.dev' ) ) {
			$data['doLocalAlert'] = 'false';
		} else {
			$data['doLocalAlert'] = 'true';
		}

		$data['generator_version'] = $this->version;

		return $data;
	}

	function generator_sites_data() {
		$site_query_args = array(
			'public'            => '1',
			'orderby'           => 'domain',
			'order'             => 'ASC',
			'archived'          => false,
			'update_site_cache' => true,
			'no_found_rows'     => true,
		);
		// The Site Query
		$net_sites = new \WP_Site_Query( $site_query_args );

		return ! is_wp_error( $net_sites ) && ! empty( $net_sites->sites ) ? $net_sites->sites : array();
	}

	function prepare_fields_data( $data ) {
//		$user_config = defined( 'WP_PHX_PLUGIN_DEFAULTS' ) ? WP_PHX_PLUGIN_DEFAULTS : null;
		$fields = $data['fields'];

		$final	= array();
		foreach ( $fields as $field ) {
			$parsed = wp_parse_args( $field, $data['app']['field_defaults'] );
			$final[] = $parsed;
		}

		return $final;
	}

	/**
	 * todo: implement
	 * @return array
	 */
	function gather_default_data() {
		$return = array();

		$fields   = $this->reduce_to_single_attribute( $this->generator_app_data()['fields'], 'key' );
		$defaults = $this->reduce_to_single_attribute( $this->generator_app_data()['fields'], 'default' );

		foreach ( $fields as $field ) {
			if ( isset( $defaults[ $field ] ) ) {
				$return[ $field ] = $defaults[ $field ];
			}
		}

		return $return;
	}

	function create_config() {
		return array( 'generated' => current_time( 'timestamp' ) );
	}

	function register_generator_as_plugin_subpage() {
		add_submenu_page(
			Toolkit_Dashboard_Page::$page_id, // parent ID
			'',
			'Generators',
			'manage_options',
			'wp-phx-generator',
			array( $this, 'wp_phx_generator_tool' )
		);
	}

	function wp_phx_generator_tool() {
		if ( isset( $_GET['generator'] ) ) {
			Plugin_Generator_Form::enqueue( $this->installed_url, $this->generator_app_data() );
			echo Plugin_Generator_Form::markup();
		} else {
			ob_start(); ?>
			<div class="wrap select-generator">
				<h1>Select a Generator</h1>
				<select name="do-generator">
					<option value="plugin">WordPress Plugin</option>
					<option value="panel">Options Panel</option>
				</select>
			</div>

			<?php echo ob_get_clean();
		}
	}

	function register_generator_dependencies( $hook ) {
		// scope dependencies
		if ( 'plugins_page_wp-phx-generator' !== $hook ) {
			return;
		}

		$generator = 'wp-phx-generator';

		wp_register_script(
			$generator,
			trailingslashit( $this->installed_url ) . 'app/assets/js/generator.js',
			array( 'wp-util' ),
			rand( 0, 1000000000 )
		);

		wp_enqueue_script( $generator );
		wp_localize_script( 'wp-phx-generator', 'wpPhxGenerator', $this->generator_app_data() );
	}

}
