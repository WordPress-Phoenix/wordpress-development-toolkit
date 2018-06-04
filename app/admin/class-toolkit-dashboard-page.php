<?php
/**
 * Toolkit Dashboard Page
 *
 * @package    WordPress
 * @subpackage wordpress-development-toolkit
 */

namespace PHX_WP_DEVKIT\V_3_1\Admin;

/**
 * Class Toolkit_Dashboard_Page
 */
class Toolkit_Dashboard_Page {
	/**
	 * Page ID
	 *
	 * @var string
	 */
	public static $page_id = 'wp-phx-dev-kit';

	/**
	 * Installed Server Directory
	 *
	 * @var string
	 */
	public $installed_dir;

	/**
	 * Installed website URL
	 *
	 * @var string
	 */
	public $installed_url;

	/**
	 * Main_Dashboard_Page constructor.
	 *
	 * @param string $installed_dir Installed server directory.
	 * @param string $installed_url Installed website URL.
	 */
	function __construct( $installed_dir, $installed_url ) {
		// Set Location.
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		// Register Global Admin CSS for Menu.
		add_action( 'admin_head', array( $this, 'global_admin_css' ) );
		// Register "Developer Toolkit" to Admin Menu with Top Level Page.
		add_action( 'admin_menu', array( $this, 'register_top_level_admin_page' ) );
		// Register Top Level Admin Dependencies.
		add_action( 'admin_enqueue_scripts', array( $this, 'top_level_admin_dependencies' ) );
	}

	/**
	 * Needed on every dashboard page for Admin Menu
	 */
	function global_admin_css() {
		if ( wp_doing_ajax() ) {
			return;
		}
		?>
		<style type="text/css">
			#toplevel_page_wp-phx-dev-kit .wp-submenu {
				display: none;
			}

			<?php if ( isset( $_GET['modal_view'] ) ) { ?>
			#wpadminbar, #adminmenumain, #wpfooter {
				display: none; /* hide the things */
			}

			#wpcontent { /* shuffle the page */
				margin: -35px 0 0 0 !important;
				padding: 0 5px !important;
			}

			#wpbody-content { /* tuck the page */
				padding-bottom: 0 !important;
			}

			.wrap { /* nudge the page */
				margin: 10px 0 0 0 !important;
			}

			/* now we have a modal-ready WP-Admin */
			<?php } ?>
		</style>
		<?php
	}

	/**
	 * Register Admin Page
	 */
	function register_top_level_admin_page() {
		// register top-level /wp-admin page with WordPress Core.
		add_menu_page(
			'',
			'Dev Toolkit',
			'edit_posts',
			self::$page_id,
			array( $this, 'top_level_admin_page' ),
			'dashicons-media-code',
			100
		);
	}

	/**
	 * Custom admin page content as a callback.
	 */
	function top_level_admin_page() {
		add_thickbox();
		include( trailingslashit( dirname( __FILE__ ) ) . 'templates/main.php' );
		?>
		<style type="text/css">
			.plugin-card div.desc {
				margin-right: 1rem !important;
			}

			.dashicons-yes.green {
				color: #669933;
			}

			.dashicons-no.red {
				color: #cd1713;
			}
		</style>
		<div class="wrap" style="margin-top:14px;">
			<div id="app">awwyiss</div>
		</div>
		<?php
	}

	/**
	 * Custom admin page script / style enqueues
	 *
	 * @param string $hook Name of hook for scoping when to hook in.
	 */
	function top_level_admin_dependencies( $hook ) {
		if ( stripos( $hook, self::$page_id ) ) {
			wp_register_script(
				'wp-phx-devkit-main',
				trailingslashit( $this->installed_url ) . 'app/admin/js/dashboard.js',
				array( 'wp-util' ),
				rand( 0, 100000000 )
			);
			wp_enqueue_script( 'wp-phx-devkit-main' );
			wp_localize_script( 'wp-phx-devkit-main', 'wpPhxDevKit', $this->localize_data() );
		}
	}

	/**
	 * Get Admin Cards
	 *
	 * @return array|mixed|object
	 */
	function get_admin_cards() {
		$cards = trailingslashit( $this->installed_dir ) . 'admin/dashboard-cards.json';

		return json_decode( file_get_contents( $cards ), true );
	}

	/**
	 * Sync PHP data to javascript data.
	 *
	 * @return array
	 */
	function localize_data() {
		$data = array();

		$cards = $this->get_admin_cards();
		if ( ! empty( $cards ) && is_array( $cards ) ) {
			$data['cards'] = $cards['cards'];
		}

		return $data;
	}
}
