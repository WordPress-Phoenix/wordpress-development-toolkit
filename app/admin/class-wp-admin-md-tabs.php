<?php
namespace WP_PHX_Dev_Kit\V_1_0\Admin;
use League\CommonMark\CommonMarkConverter as MarkdownToHTML;
class WP_Admin_MD_Tabs {
	/**
	 * Markdown_Render constructor.
	 *
	 * @param       $doc_dir
	 * @param array $wp_page
	 * @param array $menu
	 * @param bool  $render_shortcodes
	 */
	function __construct( $doc_dir, $wp_page = array(), $menu = array(), $render_shortcodes = true ) {
		$this->dir  = $doc_dir;
		$this->wp_page = $wp_page;
		$this->menu = $this->decide_menu( $menu );
		$this->render_shortcodes = $render_shortcodes;
		add_action( 'admin_menu', array( $this, 'register_markdown_page' ) );
	}
	function register_markdown_page() {
		$page_title = isset( $this->wp_page['page_title'] ) ? $this->wp_page['page_title'] : get_bloginfo( 'name') . ' Options';
		if ( isset( $this->wp_page['position'] ) ) {
			add_menu_page(
				$page_title,
				isset( $this->wp_page['menu_title'] ) ? $this->wp_page['menu_title'] : '',
				isset( $this->wp_page['capability'] ) ? $this->wp_page['capability'] : 'manage_options',
				isset( $this->wp_page['menu_slug'] ) ? $this->wp_page['menu_slug'] : sanitize_title_with_dashes( $page_title ),
				array( $this, 'print_markdown_page_output' ),
				isset( $this->wp_page['icon_url'] ) ? $this->wp_page['icon_url'] : 'dashicons-info',
				isset( $this->wp_page['position'] ) ? $this->wp_page['position'] : 100
			);
		} else {
			add_submenu_page(
				isset( $this->wp_page['parent_slug'] ) ? $this->wp_page['parent_slug'] : '',
				isset( $this->wp_page['page_title'] ) ? $this->wp_page['page_title'] : '',
				isset( $this->wp_page['menu_title'] ) ? $this->wp_page['menu_title'] : '',
				isset( $this->wp_page['capability'] ) ? $this->wp_page['capability'] : '',
				isset( $this->wp_page['menu_slug'] ) ? $this->wp_page['menu_slug'] : '',
				array( $this, 'print_markdown_page_output' )
			);
		}
	}
	function print_markdown_page_output() {
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $this->menu ) ) {
			$current = $this->menu[ $_GET['tab'] ];
		} elseif ( in_array( 'intro.md', $this->menu ) ) {
			$current = $this->menu[ 'intro.md' ];
		} else {
			$current = $this->menu[0];
		}
		$this->create_viewer_markup( $current );
	}
	/**
	 * Menu items should be kv pairs: 'file.md' => 'Menu Label'
	 *
	 * @param $menu
	 *
	 * @return array
	 */
	function decide_menu( $menu = array() ) {
		if ( empty( $menu ) && is_file( trailingslashit( $this->dir ) . 'config.json' ) ) {
			$decoded = json_decode( file_get_contents( trailingslashit( $this->dir ) . 'config.json' ), true );
			if ( is_array( $decoded ) ) {
				$menu = $decoded;
			}
		} elseif ( empty( $menu ) ) {
			$menu = array();
			foreach( glob( $this->dir . '*.md') as $file ) {
				$menu[ $file ] = str_ireplace( '-', ' ', ucwords( $file ) );
			}
			if ( isset( $menu['intro.md'] ) ) {
				unset( $menu['intro.md'] );
				array_unshift( $menu, 'intro.md' );
				$menu['intro.md'] = 'Intro';
			}
		}
		return $menu;
	}
	function create_viewer_markup( $current ) {
		echo '<div id="doc" class="wrap">';
		if ( isset( $this->menu['header.md'] ) ) {
			echo $this->get_markup( $this->menu['header.md'] );
		}
		echo $this->get_markup( $current );
		if ( isset( $this->menu['footer.md'] ) ) {
			echo $this->get_markup( $this->menu['header.md'] );
		}
		echo '</div>';
	}
	function get_markup( $file ) {
		$contents = '';
		if ( is_file( $file ) ) {
			$contents = file_get_contents( $file );
			$md_reader = new MarkdownToHTML();
			$contents = $md_reader->convertToHtml( $contents );
			if ( $this->render_shortcodes ) {
				$contents = do_shortcode( $contents );
			}
		}
		return $contents;
	}
}
