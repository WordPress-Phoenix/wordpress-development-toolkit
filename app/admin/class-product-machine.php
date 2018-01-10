<?php

namespace WP_PHX_Dev_Kit\V_1_0\Admin;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class Product_Machine
 * @package WP_PHX_Dev_Kit\V_1_0\Admin
 */
class Product_Machine {

	/**
	 * Product_Machine constructor.
	 *
	 * @param $filename
	 * @param $origin_dir
	 * @param $tmp_dir
	 * @param $data
	 * @param $config
	 */
	public function __construct( $filename, $origin_dir, $tmp_dir, $data, $config ) {
		add_filter( 'wp_phx_plugingen_file_contents', array( $this, 'process_file_contents_via_filter' ) );
		self::make_zip_download( $filename, $origin_dir, $tmp_dir, $data, $config );
	}

	/**
	 * @param $filename
	 * @param $origin_dir
	 * @param $tmp_dir
	 * @param $data
	 * @param $config
	 */
	static function make_zip_download( $filename, $origin_dir, $tmp_dir, $data, $config ) {
		$data['mainFilename'] = $filename;
		$zip                  = new ZipArchive();

		$creation_success = $zip->open(
			trailingslashit( $tmp_dir ) . 'gen.zip',
			ZipArchive::CREATE && ZipArchive::OVERWRITE
		);

		if ( $creation_success ) {
			$zip->addFromString( 'plugin-data.json', json_encode( $data ) );
			// maybe include license
			if ( 'gpl' === $data['plugin_license'] ) {
				$zip->addFromString( 'LICENSE', file_get_contents( dirname( __FILE__ ) . '/templates/gpl.txt' ) );
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $origin_dir ) );
			foreach ( $iterator as $current_file ) {
				if ( !! stristr( strval( $current_file ), 'ds_store' ) ) {
					continue;
				}
				if ( (
					   ! isset( $data['plugin_register_enqueue_assets'] )
				       || 'simple' === $data['plugin_arch_type']
				     )
				     && (
					     !! stristr( strval( $current_file ), '.css' )
				       || !! stristr( strval( $current_file ), '.js' )
				       || !! stristr( strval( $current_file ), 'class-assets.php' )
				     )
				) {
					continue;
				}
				if ( 'simple' === $data['plugin_arch_type']
				     && (
				     	! stristr( strval( $current_file ), 'main.php' )
					    && ! stristr( strval( $current_file ), 'class-plugin.php' )
					    && ! stristr( strval( $current_file ), 'wordpress-phoenix' )
				     )
				) {
					continue;
				}
				// give file relative path for zip
				$current_file_stub = str_replace( trailingslashit( $origin_dir ), '', $current_file );
				// run operations on file
				$processed_file = apply_filters( 'wp_phx_plugingen_file_contents', [
					'contents' => file_get_contents( $current_file ),
					'filename' => $current_file_stub,
					'data'     => $data,
				] );
				// add maybe renamed, maybe rebuilt file to new zip
				if ( is_array( $processed_file ) && ! empty( $processed_file['contents'] ) && is_string( $processed_file['contents'] ) ) {
					$zip->addFromString( $processed_file['filename'], $processed_file['contents'] );
				}
			}

			// only run these operations for standard plugins
			if ( 'simple' !== $data['plugin_arch_type'] ) {
				// add empties to key directories
				$blank_file = '<?php ' . PHP_EOL . '// *gentle wave* not the code you\'re looking for..' . PHP_EOL;
				$idx        = '/index.php';
				$zip->addFromString( 'app' . $idx, $blank_file );
				$zip->addFromString( 'vendor' . $idx, $blank_file );
				$zip->addFromString( 'app/admin' . $idx, $blank_file );
				$zip->addFromString( 'app/includes' . $idx, $blank_file );

				// include options panel
				if ( isset( $data['plugin_opts_panel'] ) && 'on' === $data['plugin_opts_panel'] ) {
					$zip->addFromString(
						'vendor/wordpress-phoenix/wordpress-options-builder-class/wordpress-phoenix-options-panel.php',
						file_get_contents( dirname( __FILE__ ) . '/templates/wpop.php' )
					);
				}
			}

			if ( ! empty( $config ) && is_array( $config ) ) {
				$zip->addFromString( 'plugin-generated.json', json_encode( $config ) );
			}

			// close zip
			$zip->close();

			// Tell PHP we're gonna download the zip using headers
			header( 'Content-type: application/zip' );
			header( sprintf( 'Content-Disposition: attachment; filename="%s.zip"', $filename ) );

			// read and unset temporary file
			readfile( $tmp_dir . '/gen.zip' );
			unlink( $tmp_dir . '/gen.zip' );
			die();
		} else {
			$zip->close();
			unlink( $tmp_dir . '/gen.zip' );
			wp_die( 'ZipArchive failed to create temporary zip. Probably need to chmod the directory permissions.' );
		}
	}

	function process_file_contents_via_filter( $file ) {
		if (
			empty( $file )
			|| ! is_array( $file )
			|| ! isset( $file['contents'] )
			|| ! isset( $file['filename'] )
			|| ! isset( $file['data'] )
		) {
			return $file;
		}

		$file['contents'] = self::process_file_contents( $file );
		$file['filename'] = self::process_filename( $file );

		return $file;
	}

	static function process_file_contents( $file ) {
		$contents = $file['contents'];
		$d        = $file['data'];
		$filename = $file['filename'];

		$contents       = str_ireplace( '||PLUGIN_NAME||', $d['plugin_name'], $contents );
		$contents       = str_ireplace( '||PLUGIN_PRIMARY_NAMESPACE||', $d['plugin_primary_namespace'], $contents );
		$contents       = str_ireplace( '||PLUGIN_SECONDARY_NAMESPACE||', $d['plugin_secondary_namespace'], $contents );
		$sanitized_name = sanitize_title_with_dashes( $d['plugin_name'] );
		$contents       = str_ireplace( '||PLUGIN_SLUG||', $sanitized_name, $contents );
		$contents       = str_ireplace( '||PLUGIN_PACKAGE||', str_ireplace( '-', '_', ucwords( $sanitized_name ) ), $contents );

		if ( 'main.php' === $filename || 'README.md' === $filename ) {
			$contents = str_ireplace( '||PLUGIN_AUTHORS||', $d['plugin_authors'], $contents );
			$contents = str_ireplace( '||PLUGIN_TEAM_DASH||', ! empty( $d['plugin_teamorg'] ) ? ' - ' . $d['plugin_teamorg'] : '', $contents );
			$contents = str_ireplace( '||PLUGIN_LICENSE_TEXT||', self::version_text( $d ), $contents );
			$contents = str_ireplace( '||PLUGIN_VER||', ! empty( $d['plugin_ver'] ) ? $d['plugin_ver'] : '0.1.0', $contents );
			$contents = str_ireplace( '||PLUGIN_DESC||', $d['plugin_description'], $contents );
			$contents = str_ireplace( '||PLUGIN_GITHUB_REPO||', $d['plugin_repo_url'], $contents );
			$contents = str_ireplace( '||PLUGIN_YEAR||', current_time( "Y" ), $contents );
			$contents = str_ireplace( '||CURRENT_TIME||', current_time( 'l jS \of F Y h:i:s A' ), $contents );
			$contents = str_ireplace( '||GENERATOR_VER||', $d['generator_version'], $contents );
			if ( 'simple' === $d['plugin_arch_type'] || ! isset( $d['plugin_register_enqueue_assets'] ) ) {
				$contents = str_ireplace( 'new Includes\Init( $this->installed_dir, $this->installed_url );', '', $contents );
				$contents = str_ireplace( 'new Admin\Init( $this->installed_dir, $this->installed_url );', '', $contents );
			}
			$panel_str = '';
			if ( isset( $d['plugin_opts_panel'] ) && 'on' === $d['plugin_opts_panel'] ) {
				$panel_str = "
				
// Load Options Panel
if ( ! class_exists( 'WPOP\\V_2_9\\Page' ) ) {
	include_once  trailingslashit( dirname( __FILE__ ) )  . 'vendor/wordpress-phoenix/wordpress-options-builder-class/wordpress-phoenix-options-panel.php';
}";
			}
			$contents = str_ireplace( '||INSTANTIATE_OPTIONS_PANEL||', $panel_str, $contents );
		}

		if ( ! isset( $d['plugin_register_enqueue_assets'] ) && stripos( $filename, 'class-init.php' ) ) {
			$initStr = 'new Assets( $this->installed_dir, $this->installed_url );';
			$contents = str_ireplace( $initStr, '', $contents );
		}

		return $contents;
	}

	static function version_text( $data ) {
		if ( isset( $data['plugin_license'] ) ) {
			switch ( $data['plugin_license'] ) {
				case 'private':
					return 'Private. Do not distribute. Copyright ' . date( "Y" ) . ' All Rights Reserved.';
					break;
				case 'gpl':
				default:
					return 'GNU GPL v2.0+';
					break;
			}
		} else {
			return 'Unlicensed [Error in plugin generation].';
		}
	}

	static function process_filename( $file ) {
		if ( 'main.php' === $file['filename'] ) {
			return $file['data']['mainFilename'] . '.php';
		}

		if ( stripos( $file['filename'], '.css' ) || stripos( $file['filename'], '.js' ) ) {
			return str_ireplace( 'plugin-', $file['data']['mainFilename'] . '-', $file['filename'] );
		}

		return $file['filename'];
	}
}
