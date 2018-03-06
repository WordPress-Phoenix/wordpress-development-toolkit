<?php

namespace PHX_WP_DEVKIT\V_2_5\Admin;

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
	 * @param string $filename
	 * @param string $origin_dir
	 * @param string $tmp_dir
	 * @param array $data
	 * @param array $config
	 */
	public function __construct( $filename, $origin_dir, $tmp_dir, $data, $config ) {
		/**
		 * Each file gets passed through this filter for processing
		 */
		add_filter( 'wp_phx_plugingen_file_contents', array( $this, 'process_file_contents_via_filter' ) );

		// make zip download
		self::make_zip_download( $filename, $origin_dir, $tmp_dir, $data, $config );
	}

	/**
	 * Create a .ZIP download for the plugin generator
	 *
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

		// check we have filesystem write access
		if ( $creation_success ) {
			// write json containing configuration data
			$plugin_data = apply_filters( 'wp_phx_plugingen_datazip', array( 'data' => $data, 'config' => $config ) );

			$zip->addFromString( 'plugin-data.json', json_encode( $plugin_data ) );
			// maybe include license
			if ( 'gpl' === $data['plugin_license'] ) {
				$zip->addFromString( 'LICENSE', file_get_contents( dirname( __FILE__ ) . '/../boilerplates/GPL.txt' ) );
			}
			// find every file within origin directory including nested files
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $origin_dir ) );
			foreach ( $iterator as $current_file ) {
				if ( !! stristr( strval( $current_file ), 'ds_store' ) ) {
					continue; // skip system files
				}

				// VITAL FOR ALL FILES: give file relative path for zip
				$current_file_stub = str_replace( trailingslashit( $origin_dir ), '', $current_file );

				// Run WordPress Filter on File
				$processed_file = apply_filters( 'wp_phx_plugingen_file_contents', [
					'contents' => file_get_contents( $current_file ), // modified
					'filename' => $current_file_stub, // modified
					'data'     => $data, // data passthru, for read only
				] );

				// add maybe renamed, maybe rebuilt file to new zip
				if ( is_array( $processed_file ) && ! empty( $processed_file['contents'] ) && is_string( $processed_file['contents'] ) ) {
					$zip->addFromString( $processed_file['filename'], $processed_file['contents'] );
				}
			}

			// only run these operations for standard plugins
			// add empties to key directories
			$blank_file = '<?php ' . PHP_EOL . '// *gentle wave* not the code you\'re looking for..' . PHP_EOL;
			$idx        = '/index.php';
			$zip->addFromString( 'app' . $idx, $blank_file );
			$zip->addFromString( 'app/admin' . $idx, $blank_file );

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

	/**
	 * Main file processor used by generator to filter a file's contents and filename
	 * @param $file
	 *
	 * @return array
	 */
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

	/**
	 * @param $file
	 *
	 * @return mixed
	 */
	static function process_file_contents( $file ) {
		$contents = $file['contents'];
		$d        = $file['data'];
		$filename = $file['filename'];

		$contents       = str_ireplace( '<%= NAME %>', $d['plugin_name'], $contents );
		$contents       = str_ireplace( '<%= PRIMARY_NAMESPACE %>', $d['plugin_primary_namespace'], $contents );
		$contents       = str_ireplace( '<%= SECONDARY_NAMESPACE %>', $d['plugin_secondary_namespace'], $contents );
		$sanitized_name = sanitize_title_with_dashes( $d['plugin_name'] );
		$contents       = str_ireplace( '<%= DASHES_SLUG %>', $sanitized_name, $contents );
		$contents       = str_ireplace( '<%= US_SLUG %>', strtolower( str_ireplace( '-', '_', $sanitized_name ) ), $contents );
		$contents       = str_ireplace( '<%= PKG %>', str_ireplace( '-', '_', ucwords( $sanitized_name ) ), $contents );

		$important_files = apply_filters( 'wp_phx_plugingen_file_contents', array(
			'main.php',
			'README.md',
			'composer.json',
		) );

		if ( in_array( $filename, $important_files ) ) {
			$contents = str_ireplace( '<%= AUTHORS %>', $d['plugin_authors'], $contents );
			$contents = str_ireplace( '<%= TEAM_NAME %>', ! empty( $d['plugin_teamorg'] ) ? $d['plugin_teamorg'] : '', $contents );
			$contents = str_ireplace( '<%= TEAM %>', ! empty( $d['plugin_teamorg'] ) ? ' - ' . $d['plugin_teamorg'] : '', $contents );
			$contents = str_ireplace( '<%= LICENSE_TEXT %>', self::version_text( $d ), $contents );
			$contents = str_ireplace( '<%= LICENSE_COMPOSER_STR %>', self::version_composer( $d ), $contents );
			$contents = str_ireplace( '<%= VERSION %>', ! empty( $d['plugin_ver'] ) ? $d['plugin_ver'] : '0.1.0', $contents );
			$contents = str_ireplace( '<%= DESC %>', $d['plugin_description'], $contents );
			if ( ! empty( $d['plugin_url'] ) ) {
				$url = $d['plugin_url'];
			} elseif ( ! empty( $d['plugin_repo_url'] ) ) {
				$url = $d['plugin_repo_url'];
			} else {
				$url = '';
			}
			$contents = str_ireplace( '<%= GITHUB_URL %>', $d['plugin_repo_url'], $contents );
			$contents = str_ireplace( '<%= URL %>', $url, $contents );
			$contents = str_ireplace( '<%= YEAR %>', current_time( "Y" ), $contents );
			$contents = str_ireplace( '<%= CURRENT_TIME %>', current_time( 'l jS \of F Y h:i:s A' ), $contents );
			$contents = str_ireplace( '<%= GENERATOR_VERSION %>', $d['generator_version'], $contents );
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

	static function version_composer( $data ) {
		if ( isset( $data['plugin_license'] ) ) {
			switch ( $data['plugin_license'] ) {
				case 'private':
					return 'proprietary';
					break;
				case 'gpl':
				default:
					return 'GPL-3.0-or-later';
					break;
			}
		} else {
			return 'proprietary';
		}
	}

	static function process_filename( $file ) {
		if ( 'main.php' === $file['filename'] ) {
			return $file['data']['mainFilename'] . '.php';
		}

		return $file['filename'];
	}
}
