<?php
/**
 * [WPOP] WordPress Phoenix Options Panel - Field Builder Classes
 *
 * @authors 🌵 WordPress Phoenix 🌵 / Seth Carstens, David Ryan
 * @package wpop
 * @version 3.1.0
 * @license GPL-2.0+ - please retain comments that express original build of this file by the author.
 */

namespace WPOP\V_3_1;

/**
 * Some tips:
 * * Panels/Pages contain Sections, Sections contain Parts (which are either option inputs or markup for display)
 */
if ( ! function_exists( 'add_filter' ) ) { // avoid direct calls to file
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Class Panel
 * @package WPOP\V_3_0
 */
class Panel {

	/**
	 * @var null - string used by class to determine wordpress data api
	 */
	public $api = null;

	/**
	 * @var null|string - string/slug for a panel
	 */
	public $id = null;

	/**
	 * @var string - capability user must have for panel to display.
	 */
	public $capability = 'manage_options';

	/**
	 * @var array - array of fields (aka parts because fields can also be file includes or markup)
	 */
	public $parts = [];

	/**
	 * @var array - string notifications to print at top of panel
	 */
	public $notifications = [];

	/**
	 * @var null|void - preset with WP Core Object ID from query param
	 * @see $this->maybe_capture_wp_object_id();
	 */
	public $obj_id = null;

	/**
	 * @var
	 */
	public $page_title;

	/**
	 * @var
	 */
	public $panel_object;

	/**
	 * @var int
	 */
	public $part_count = 0;

	/**
	 * @var int
	 */
	public $section_count = 0;

	/**
	 * @var int
	 */
	public $data_count = 0;

	/**
	 * @var array used to track what happens during save process
	 */
	public $updated_counts = array( 'created' => 0, 'updated' => 0, 'deleted' => 0 );

	/**
	 * Container constructor.
	 *
	 * @param array $args
	 * @param array $sections
	 */
	public function __construct( $args = [], $sections = [] ) {
		global $pagenow;
		if ( ! isset( $args['id'] ) ) {
			echo "Setting a panel ID is required";
			exit;
		}
		if ( ! defined( 'WPOP_ENCRYPTION_KEY' ) ) {
			// IMPORTANT: If you don't define a key, the class hashes the AUTH_KEY found in wp-config.php,
			// locking the encrypted value to the current environment.
			$trimmed_key = substr( wp_salt(), 0, 15 );
			define( 'WPOP_ENCRYPTION_KEY', Password::pad_key( sha1( $trimmed_key, true ) ) );
		}
		// establish panel id
		$this->id = preg_replace( '/_/', '-', $args['id'] );

		// magic-set class object vars from array
		foreach ( $args as $key => $val ) {
			$this->$key = $val;
		}

		// establish data storage api
		$this->api = $this->detect_data_api_and_permissions();

		// maybe establish wordpress object id when api is one of the metadata APIs
		$this->obj_id = $this->maybe_capture_wp_object_id();

		// loop over sections
		foreach ( $sections as $section_id => $section ) {
			if ( isset( $section['parts'] ) ) {
				$this->section_count ++;
				// loop over current section's parts
				foreach ( $section['parts'] as $part_id => $part_config ) {
					if ( isset( $part_config['part'] ) ) {
						$current_part_classname = __NAMESPACE__ . '\\' . ucfirst( $part_config['part'] );
					}
					$current_part_classname    = __NAMESPACE__ . '\\' . $part_config['part'];
					$part_config['panel_id']   = $this->id;
					$part_config['section_id'] = $section_id;
					$part_config['panel_api']  = $this->api;

					// add part to panel/section
					$this->add_part(
						$section_id,
						$section,
						$current_part = new $current_part_classname( $part_id, $part_config )
					);
					$this->part_count ++;
					if ( is_object( $current_part ) && $current_part->data_store ) {
						$this->data_count ++;
						if ( $current_part->updated ) {
							if ( isset( $this->updated_counts[ $current_part->update_type ] ) ) {
								$this->updated_counts[ $current_part->update_type ] ++;
							}
						}
					}

				}

				$update_message = '';
				foreach ( $this->updated_counts as $count_type => $count ) {
					$update_message .= $count . ' ' . ucfirst( $count_type ) . '. ';
				}

				$this->notifications = [ 'notification' => $update_message ];
			}
		}
	}

	public function __toString() {
		return $this->id;
	}

	/**
	 * Listen for query parameters denoting Post, User or Term object IDs for metadata api or network/site option apis
	 */
	public function detect_data_api_and_permissions() {
		$error = null;
		$api   = null;
		if ( isset( $_GET['page'] ) ) {
			if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
				$api                = 'post';
				$this->page_title   = $this->page_title . ' for ' . get_the_title( $_GET['post'] );
				$this->panel_object = get_post( $_GET['post'] );
			} elseif ( isset( $_GET['user'] ) && is_numeric( $_GET['user'] ) ) {
				if ( is_multisite() && is_network_admin() ) {
					$api                 = 'user-network';
				} else {
					$api = 'user';
				}
				$this->page_title   = esc_attr( $this->page_title ) . ' for ' . esc_attr( get_the_author_meta( 'display_name', absint( $_GET['user'] ) ) );
				$this->panel_object = get_user_by( 'id', absint( $_GET['user'] ) );
			} elseif ( isset( $_GET['term'] ) && is_numeric( $_GET['term'] ) ) {
				$api  = 'term';
				$term = get_term( $_GET['term'] );
				if ( is_object( $term ) && ! is_wp_error( $term ) && isset( $term->name ) ) {
					$this->page_title   = esc_attr( $this->page_title ) . ' for ' . esc_attr( $term->name );
					$this->panel_object = $term;
				}
			} elseif ( is_multisite() && is_network_admin() ) {
				$api                 = 'network';
			} else {
				$api = 'site';
			}
		} else {
			$api = '';
		}

		// allow api auto detection if 'api' not set in config array, but if its set and doesn't match then ignore and
		// use config value for safety
		//   (tl;dr - will ignore &term=1 param on a site options panel when 'api' is defined to prevent accidental API
		//   override)
		if ( isset( $this->api ) && $api !== $this->api ) {
			return $this->api;
		}

		return $api;
	}

	/**
	 * @return int|null
	 */
	public function maybe_capture_wp_object_id() {
		switch ( $this->api ) {
			case 'post':
				return absint( $_GET['post'] );
				break;
			case 'user':
				return absint( $_GET['user'] );
				break;
			case 'term':
				return absint( $_GET['term'] );
				break;
			default:
				return null;
				break;
		}
	}

	/**
	 * Old external developer method used to add parts (sections/fields/markup/etc) to a Panel
	 *
	 * Now used internally, but still available public
	 *
	 * @param $section_id
	 * @param $section
	 * @param $part object - one of the part classes from this file
	 */
	public function add_part( $section_id, $section, $part ) {
		if ( ! isset( $this->parts[ $section_id ] ) ) {
			$this->parts[ $section_id ]          = $section;
			$this->parts[ $section_id ]['parts'] = array();
		}

		array_push( $this->parts[ $section_id ]['parts'], $part );
	}

	/**
	 * Print WordPress Admin Notifications
	 * @example $note_data = array( 'notification' => 'My text', 'type' => 'notice-success' )
	 */
	public function echo_notifications() {
		foreach ( $this->notifications as $note_data ) {
			$data         = is_array( $note_data ) ? $note_data : [ 'notification' => $note_data ];
			$data['type'] = isset( $data['type'] ) ? $data['type'] : 'notice-success';
			echo HTML::tag(
				'div',
				[ 'class' => 'notice ' . $data['type'] ],
				HTML::tag( 'p', [], $data['notification'] )
			);
		}
	}

	/**
	 * Get class name without versioned namespace.
	 *
	 * @return string
	 */
	public function get_clean_classname() {
		return strtolower( explode( '\\', get_called_class() )[2] );
	}
} // END Container

/**
 * Class Page
 * @package WPOP\V_3_0
 */
class Page extends Panel {

	/**
	 * @var string
	 */
	public $parent_page_id = '';

	/**
	 * @var string
	 */
	public $page_title = 'Custom Site Options';

	/**
	 * @var string
	 */
	public $menu_title = 'Custom Site Options';

	/**
	 * @var
	 */
	public $dashicon;

	/**
	 * @var bool
	 */
	public $disable_styles = false;


	public $initialized = false;

	/**
	 * Page constructor.
	 *
	 * @param array $args
	 * @param array $fields
	 */
	public function __construct( $args = [], $fields ) {
		parent::__construct( $args, $fields );
	}

	/**
	 * !!! USE ME TO RUN THE PANEL !!!
	 *
	 * Main method called by extending class to initialize the panel
	 */
	public function initialize_panel() {
		if ( ! empty( $this->api ) && is_string( $this->api ) ) {
			$dashboard = 'admin_menu';
			if ( 'network' === $this->api || 'user-network' === $this->api ) {
				$dashboard = 'network_admin_menu';
			}
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dependencies' ) );
			add_action( $dashboard, array( $this, 'add_settings_submenu_page' ) );
		}
	}

	/**
	 * Register Submenu Page with WordPress to display the panel on
	 */
	public function add_settings_submenu_page() {
		add_submenu_page(
			$this->parent_page_id, // file.php to hook into
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->id,
			array( $this, 'build_parts' )
		);
	}


	/**
	 *
	 */
	public function build_parts() {
		$page_icon = ! empty( $this->dashicon ) ? HTML::dashicon( $this->dashicon . ' page-icon' ) . ' ' : '';
		$screen    = get_current_screen();
		$screen_id = $screen->id;
		add_action( 'admin_print_footer_scripts-' . $screen_id, function () {
			$this->footer_scripts();
		} );
		if ( 'site' !== $this->api && 'network' !== $this->api && ! is_object( $this->panel_object ) ) {
			echo '<h1>Please select a ' . $this->api . '.</h1>';
			echo '<code>?' . $this->api . '=ID</code>';
			exit;
		}
		ob_start(); ?>
		<div id="wpopOptions">
			<?php
			if ( ! $this->disable_styles ) {
				$this->inline_styles_and_scripts();
			}
			?>
			<!-- IMPORTANT: allows core admin notices -->
			<section class="wrap wp">
				<header><h2></h2></header>
			</section>
			<section id="wpop" class="wrap">
				<div id="panel-loader-positioning-wrap">
					<div id="panel-loader-box">
						<div class="wpcore-spin panel-spinner"></div>
					</div>
				</div>
				<form method="post" class="pure-form wpop-form">
					<header class="wpop-head">
						<div class="inner">
							<?php echo HTML::tag( 'h1', [], $page_icon . $this->page_title ); ?>
							<input type="submit"
								   class="button button-primary button-hero save-all"
								   value="Save All"
								   name="submit">
						</div>
					</header>
					<?php
					if ( isset( $_POST['submit'] ) && $_POST['submit'] ) {
						$this->echo_notifications();
					}
					?>
					<div id="wpopContent" class="pure-g">
						<div id="wpopNav" class="pure-u-1 pure-u-md-6-24">
							<div class="pure-menu wpop-options-menu">
								<ul class="pure-menu-list">
									<?php
									foreach ( $this->parts as $section_id => $section ) {
										$section_icon = ! empty( $section['dashicon'] ) ?
											HTML::dashicon( $section['dashicon'] . ' menu-icon' ) : '';
										$pcount       = count( $section['parts'] ) > 1 ? HTML::tag( 'small', [ 'class' => 'part-count' ], count( $section['parts'] ) ) : '';
										echo HTML::tag(
											'li',
											[
												'id'    => $section_id . '-nav',
												'class' => 'pure-menu-item',
											],
											HTML::tag(
												'a',
												[
													'href'  => '#' . $section_id,
													'class' => 'pure-menu-link',
												],
												$section_icon . $section['label'] . $pcount )
										);
									}
									?>
								</ul>
							</div>
							<?php echo wp_nonce_field( $this->id, '_wpnonce', true, false ); ?>
						</div>
						<div id="wpopMain" class="pure-u-1 pure-u-md-18-24">
							<ul id="wpopOptNavUl" style="list-style: none;">
								<?php
								foreach ( $this->parts as $section_key => $section ) {
									$built_section = new Section( $section_key, $section );
									$built_section->echo_html();
								} ?>
							</ul>
						</div>
						<footer class="pure-u-1">
							<div class="pure-g">
								<div class="pure-u-1 pure-u-md-1-3">
									<div>
										<ul>
											<li>
												Sections: <?php echo HTML::tag( 'code', [], $this->section_count ); ?></li>
											<li>Total Data
												Parts: <?php echo HTML::tag( 'code', [], $this->data_count ); ?></li>
											<li>Total
												Parts: <?php echo HTML::tag( 'code', [], $this->part_count ); ?></li>
											<li>Stored
												in: <?php echo HTML::tag( 'code', [], $this->get_storage_table() ); ?></li>
										</ul>
									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">
									<div>

									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">

								</div>
							</div>
						</footer>
					</div>
				</form>
			</section>
		</div> <!-- end #wpopOptions -->
		<?php
		echo ob_get_clean();
	}

	/**
	 *
	 */
	public function inline_styles_and_scripts() {
		ob_start(); ?>
		<style>
			@-webkit-keyframes wp-core-spinner{from{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@keyframes wp-core-spinner{from{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}.wpcore-spin{position:relative;width:20px;height:20px;border-radius:20px;background:#A6A6A6;-webkit-animation:wp-core-spinner 1.04s linear infinite;animation:wp-core-spinner 1.04s linear infinite}.wpcore-spin:after{content:"";position:absolute;top:2px;left:50%;width:4px;height:4px;border-radius:4px;margin-left:-2px;background:#fff}#panel-loader-positioning-wrap{background:#fff;display:flex;align-items:center;justify-content:center;height:100%;min-height:10vw;position:absolute!important;width:99%;max-width:1600px;z-index:50}#panel-loader-box{max-width:50%}#panel-loader-box .wpcore-spin{width:60px;height:60px;border-radius:60px}#panel-loader-box .wpcore-spin:after{top:6px;width:12px;height:12px;border-radius:12px;margin-left:-6px}.onOffSwitch-inner,.onOffSwitch-switch{transition:all .5s cubic-bezier(1,0,0,1)}.onOffSwitch{position:relative;width:110px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;margin-left:auto;margin-right:12px}input[type=checkbox].onOffSwitch-checkbox{display:none}.onOffSwitch-label{display:block;overflow:hidden;cursor:pointer;border:2px solid #EEE;border-radius:28px}.onOffSwitch-inner{display:block;width:200%;margin-left:-100%}.onOffSwitch-inner:after,.onOffSwitch-inner:before{display:block;float:left;width:50%;height:40px;padding:0;line-height:40px;font-size:17px;font-family:Trebuchet,Arial,sans-serif;font-weight:700;box-sizing:border-box}.pure-menu-link .part-count,.radio-wrap{float:right}.pure-menu-link .part-count{float:right;position:relative;top:-6px;padding:.33rem .66rem;border-radius:50%;-webkit-border-radius:50%;-moz-border-radius:50%;background:#aaa;color:#222}.onOffSwitch-inner:before{content:"ON";padding-left:10px;background-color:#21759B;color:#FFF}.onOffSwitch-inner:after{content:"OFF";padding-right:10px;background-color:#EEE;color:#BCBCBC;text-align:right}.onOffSwitch-switch{display:block;width:28px;margin:6px;background:#BCBCBC;position:absolute;top:0;bottom:0;right:66px;border:2px solid #EEE;border-radius:20px}.cb,.cb-wrap,.desc:after,.pwd-clear,.radio-wrap,.save-all,span.menu-icon,span.page-icon:before,span.spacer{position:relative}.onOffSwitch-checkbox:checked+.onOffSwitch-label .onOffSwitch-inner{margin-left:0}.onOffSwitch-checkbox:checked+.onOffSwitch-label .onOffSwitch-switch{right:0;background-color:#D54E21}.radio-wrap{top:-1rem}.cb,.save-all,.wpop-option.color .iris-picker{float:right;position:relative;top:-30px}.wpop-option .selectize-control.multi .selectize-input:after{content:'Select one or more options...'}li.wpop-option.color input[type=text]{height:50px}.wpop-option.media h4.label{margin-bottom:.33rem}.wpop-form{margin-bottom:0}#wpop{max-width:1600px;margin:0 auto 0 0!important}#wpopMain{background:#fff}#wpopOptNavUl{margin-top:0}.wpop-options-menu{margin-bottom:8em}#wpopContent{background:#F1F1F1;width:100%!important;border-top:1px solid #D8D8D8}.pure-g [class*=pure-u]{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif}.pure-form select{min-width:320px}.selectize-control{max-width:98.5%}.pure-menu-disabled,.pure-menu-heading,.pure-menu-link{padding:1.3em 2em}.pure-menu-active>.pure-menu-link,.pure-menu-link:focus,.pure-menu-link:hover{background:inherit}#wpopOptions header{overflow:hidden;max-height:88px}#wpopNav li.pure-menu-item{height:55px}#wpopNav p.submit input{width:100%}#wpop{border:1px solid #D8D8D8;background:#fff}.opn a.pure-menu-link{color:#fff!important}.opn a.pure-menu-link:focus{box-shadow:none;-webkit-box-shadow:none}#wpopContent .section{display:none;width:100%}#wpopContent .section.active{display:inline-block}span.page-icon{margin:0 1.5rem 0 0}span.menu-icon{left:-.5rem}span.page-icon:before{font-size:2.5rem;top:-4px;right:4px;color:#777}.clear{clear:both}.section{padding:0 0 5px}.section h3{margin:0 0 10px;padding:2rem 1.5rem}.section h4.label{margin:0;display:table-cell;border:1px solid #e9e9e9;background:#f1f1f1;padding:.33rem .66rem .5rem;font-weight:500;font-size:16px}.section ul li:nth-child(even) h4.label{background:#ddd}.section li.wpop-option{margin:1rem 1rem 1.25rem}.twothirdfat{width:66.6%}span.spacer{display:block;width:100%;border:0;height:0;border-top:1px solid rgba(0,0,0,.1);border-bottom:1px solid rgba(255,255,255,.3)}li.even.option{background-color:#ccc}input[disabled=disabled]{background-color:#CCC}.cb{right:20px}.card-wrap{width:100%}.fullwidth{width:100%!important;max-width:100%!important}.wpop-head{background:#f1f1f1}.wpop-head>.inner{padding:1rem 1.5rem 0}.save-all{top:-2.5rem}.desc{margin:.5rem 0 0 .25rem;font-weight:300;font-size:12px;line-height:16px;color:#888}.desc:after{display:block;width:98%;border-top:1px solid rgba(0,0,0,.1);border-bottom:1px solid rgba(255,255,255,.3)}.wpop-option input[type=email],.wpop-option input[type=number],.wpop-option input[type=password],.wpop-option input[type=range],.wpop-option input[type=text],.wpop-option input[type=url]{width:90%}.wpop-option input[data-part=color]{width:25%}li[data-part=markdown]{padding:1rem}li[data-part=markdown]+span.spacer{display:none}li[data-part=markdown] p{margin:0!important}li[data-part=markdown] ol,li[data-part=markdown] p,li[data-part=markdown] ul{font-size:1rem}[data-part=markdown] h1{padding-top:1.33rem;padding-bottom:.33rem}[data-part=markdown] h1:first-of-type{padding-top:.33rem;padding-bottom:.33rem}[data-part=markdown] h1,[data-part=markdown] h2,[data-part=markdown] h3,[data-part=markdown] h4,[data-part=markdown] h5,[data-part=markdown] h6{padding-left:0!important}input[data-assigned]{width:100%!important}.add-button{margin:3em auto;display:block;width:100%;text-align:center}.img-preview{max-width:320px;display:block;margin:0 0 1rem}.media-stats,.wpop-option .wp-editor-wrap{margin-top:.5rem}.img-remove{border:2px solid #cd1713!important;background:#f1f1f1!important;color:#cd1713!important;box-shadow:none;-webkit-box-shadow:none;margin-left:1rem!important}.pwd-clear{margin-left:.5rem!important;top:1px}.pure-form footer{background:#f1f1f1;border-top:1px solid #D8D8D8}.pure-form footer div div>*{padding:1rem .33rem}.wpop-option.color input{width:50%}.cb-wrap{display:block;right:1.33rem;max-width:110px;margin-left:auto;top:-1.66rem}
		</style>
		<?php
		$css = ob_get_clean();
		ob_start(); ?>
		<script type="text/javascript">
			!function(t,o){"use strict";t.wp=t.wp||{},t.wp.hooks=t.wp.hooks||new function(){function t(t,o,i,n){var e,r,p;if(a[t][o])if(i)if(e=a[t][o],n)for(p=e.length;p--;)(r=e[p]).callback===i&&r.context===n&&e.splice(p,1);else for(p=e.length;p--;)e[p].callback===i&&e.splice(p,1);else a[t][o]=[]}function o(t,o,i,n,e){var r={callback:i,priority:n,context:e},p=a[t][o];p?(p.push(r),p=function(t){for(var o,i,n,e=1,a=t.length;e<a;e++){for(o=t[e],i=e;(n=t[i-1])&&n.priority>o.priority;)t[i]=t[i-1],--i;t[i]=o}return t}(p)):p=[r],a[t][o]=p}function i(t,o,i){var n,e,r=a[t][o];if(!r)return"filters"===t&&i[0];if(e=r.length,"filters"===t)for(n=0;n<e;n++)i[0]=r[n].callback.apply(r[n].context,i);else for(n=0;n<e;n++)r[n].callback.apply(r[n].context,i);return"filters"!==t||i[0]}var n=Array.prototype.slice,e={removeFilter:function(o,i){return"string"==typeof o&&t("filters",o,i),e},applyFilters:function(){var t=n.call(arguments),o=t.shift();return"string"==typeof o?i("filters",o,t):e},addFilter:function(t,i,n,a){return"string"==typeof t&&"function"==typeof i&&o("filters",t,i,n=parseInt(n||10,10),a),e},removeAction:function(o,i){return"string"==typeof o&&t("actions",o,i),e},doAction:function(){var t=n.call(arguments),o=t.shift();return"string"==typeof o&&i("actions",o,t),e},addAction:function(t,i,n,a){return"string"==typeof t&&"function"==typeof i&&o("actions",t,i,n=parseInt(n||10,10),a),e}},a={actions:{},filters:{}};return e}}(window),jQuery(document).ready(function(t){var o;wp.hooks.addAction("wpopPreInit",p),wp.hooks.addAction("wpopInit",r,5),wp.hooks.addAction("wpopFooterScripts",c),wp.hooks.addAction("wpopInit",l),wp.hooks.addAction("wpopInit",f),wp.hooks.addAction("wpopInit",e,100),wp.hooks.addAction("wpopSectionNav",n),wp.hooks.addAction("wpopPwdClear",d),wp.hooks.addAction("wpopImgUpload",u),wp.hooks.addAction("wpopImgRemove",w),wp.hooks.addAction("wpopSubmit",a),wp.hooks.doAction("wpopPreInit");var i=wp.template("wpop-media-stats");function n(o,i){i.preventDefault();var n=t(t(o).attr("href")).addClass("active"),e=t(t(o).attr("href")+"-nav").addClass("active wp-ui-primary opn");return window.location.hash=t(o).attr("href"),window.scrollTo(0,0),t(n).siblings().removeClass("active"),t(e).siblings().removeClass("active wp-ui-primary opn"),!1}function e(){t("#panel-loader-positioning-wrap").fadeOut(345)}function a(){t("#panel-loader-positioning-wrap").fadeIn(345)}function r(){(hash=window.location.hash)?t(hash+"-nav a").trigger("click"):t("#wpopNav li:first a").trigger("click")}function p(){t("html, body").animate({scrollTop:0})}function c(){t('[data-part="color"]').iris({width:215,hide:!1,border:!1,create:function(){""!==t(this).attr("value")&&s(t(this).attr("name"),t(this).attr("value"),new Color(t(this).attr("value")).getMaxContrastColor())},change:function(o,i){s(t(this).attr("name"),i.color.toString(),new Color(i.color.toString()).getMaxContrastColor())}})}function l(){t("[data-select]").selectize({allowEmptyOption:!1,placeholder:t(this).attr("data-placeholder")}),t("[data-multiselect]").selectize({plugins:["restore_on_backspace","remove_button","drag_drop","optgroup_columns"]})}function s(o,i,n){t("#"+o).css("background-color",i).css("color",n)}function d(o,i){i.preventDefault(),t(o).prev().val(null)}function f(){t('[data-part="media"]').each(function(){if(""!==t(this).attr("value")){var o=t(this).closest(".wpop-option");wp.media.attachment(t(this).attr("value")).fetch().then(function(t){o.find(".img-remove").after(i(t))})}})}function u(n,e){e.preventDefault();var a=t(n).data();o||(o=wp.media.frames.wpModal||wp.media({title:a.title,button:{text:a.button},library:{type:"image"},multiple:!1})).on("select",function(){var e=o.state().get("selection").first().toJSON();if("object"==typeof e){console.log(e);var a=t(n).closest(".wpop-option");a.find('[type="hidden"]').val(e.id),a.find("img").attr("src",e.sizes.thumbnail.url).show(),t(n).attr("value","Replace "+t(n).attr("data-media-label")),a.find(".img-remove").show().after(i(e))}}),o.open()}function w(o,i){if(i.preventDefault(),confirm("Remove "+t(o).attr("data-media-label")+"?")){var n=t(o).closest(".wpop-option"),e=n.find(".blank-img").html();n.find('[type="hidden"]').val(null),n.find("img").attr("src",e),n.find(".button-hero").val("Set Image"),n.find(".media-stats").remove(),t(o).hide()}}t("#wpopNav li a").click(function(t){wp.hooks.doAction("wpopSectionNav",this,t)}),wp.hooks.doAction("wpopInit"),t('input[type="submit"]').click(function(t){wp.hooks.doAction("wpopSubmit",this,t)}),t(".pwd-clear").click(function(t){wp.hooks.doAction("wpopPwdClear",this,t)}),t(".img-upload").on("click",function(t){wp.hooks.doAction("wpopImgUpload",this,t)}),t(".img-remove").on("click",function(t){wp.hooks.doAction("wpopImgRemove",this,t)})});
		</script>
		<?php
		$js = ob_get_clean();
		echo PHP_EOL . $css . PHP_EOL . $js . PHP_EOL;
	}

	public function footer_scripts() {
		ob_start(); ?>
		<script type="text/html" id="tmpl-wpop-media-stats">
			<div class="pure-g media-stats">
				<div class="pure-u-1 pure-u-sm-18-24">
					<table class="widefat striped">
						<thead>
						<tr>
							<td colspan="2"><?php echo HTML::dashicon( 'dashicons-format-image' ); ?>
								<a href="{{{ data.url }}}">{{{ data.filename }}}</a>
								<a href="{{{ data.editLink }}}" class="button" style="float:right;">Edit Image</a>
							</td>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td>uploaded</td>
							<td>{{{ data.dateFormatted }}}</td>
						</tr>
						<tr>
							<td>orientation</td>
							<td>{{{ data.orientation }}}</td>
						</tr>
						<tr>
							<td>size</td>
							<td>{{{ data.width }}}x{{{ data.height }}} {{{ data.filesizeHumanReadable }}}</td>
						</tr>
						</tbody>
					</table>
				</div>
				<div class="pure-u-sm-1-24"></div>
				<div class="pure-u-1 pure-u-sm-5-24">
					<img src="{{{ data.sizes.thumbnail.url }}}" class="img-preview"/>
				</div>
			</div>

		</script>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				wp.hooks.doAction( 'wpopFooterScripts' );
			} );
		</script>
		<?php
		echo PHP_EOL . ob_get_clean() . PHP_EOL;
	}

	/**
	 * @return string
	 */
	public function get_storage_table() {
		global $wpdb;
		switch ( $this->api ) {
			case 'post':
				return $wpdb->prefix . 'postmeta';
				break;
			case 'term':
				return $wpdb->prefix . 'termmeta';
				break;
			case 'user':
				return is_multisite() ? $wpdb->base_prefix . 'usermeta' : $wpdb->prefix . 'usermeta';
				break;
			case 'network':
				return $wpdb->prefix . 'sitemeta';
				break;
			case 'site':
			default:
				return $wpdb->prefix . 'options';
				break;
		}
	}

	/**
	 *
	 */
	public function enqueue_dependencies() {
		$unpkg = 'https://unpkg.com/purecss@1.0.0/build/';
		wp_register_style( 'wpop-pure-base', $unpkg . 'base-min.css' );
		wp_register_style( 'wpop-pure-grids', $unpkg . 'grids-min.css', array( 'wpop-pure-base' ) );
		wp_register_style( 'wpop-pure-grids-r', $unpkg . 'grids-responsive-min.css', array( 'wpop-pure-grids' ) );
		wp_register_style( 'wpop-pure-menus', $unpkg . 'menus-min.css', array( 'wpop-pure-grids-r' ) );
		wp_register_style( 'wpop-pure-forms', $unpkg . 'forms-min.css', array( 'wpop-pure-menus' ) );
		wp_enqueue_style( 'wpop-pure-forms' ); // cue enqueue cascade

		// Enqueue media (needed for media modal)
		wp_enqueue_media();

		wp_enqueue_script( array( 'iris', 'wp-util', 'wp-shortcode' ) );

		$selectize_cdn = 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/';
		wp_register_script( 'wpop-selectize', $selectize_cdn . 'js/standalone/selectize.min.js', array( 'jquery-ui-sortable' ) );
		wp_enqueue_script( 'wpop-selectize' );
		wp_register_style( 'wpop-selectize', $selectize_cdn . 'css/selectize.default.min.css' );
		wp_enqueue_style( 'wpop-selectize' );
		wp_register_script( 'clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js' );
		wp_enqueue_script( 'clipboard' );
	}
}

/**
 * Class Section
 * @package WPOP\V_3_0
 */
class Section {

	/**
	 * @var
	 */
	public $id;

	/**
	 * @var array
	 */
	public $classes = array( 'section' );

	/**
	 * @var string
	 */
	public $label = 'My Custom Section';

	/**
	 * @var
	 */
	public $dashicon;

	/**
	 * @var
	 */
	protected $parts;

	/**
	 * Section constructor.
	 *
	 * @param string $id
	 * @param array  $args
	 */
	public function __construct( $id, $args = [] ) {
		$this->id = $id;
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	/**
	 * Print Panel Markup
	 */
	public function echo_html() {
		ob_start();

		$section_content = '';

		foreach ( $this->parts as $part ) { // parts are wrapped in <li>'s
			$section_content .= $part->get_html() . HTML::tag( 'span', [ 'class' => 'spacer' ] );
		}

		echo HTML::tag( 'li', [
			'id'    => $this->id,
			'class' => implode( ' ', $this->classes )
		], HTML::tag( 'ul', [], $section_content ) );

		echo ob_get_clean();
	}
}

/**
 * Class Part
 * @package WPOP\V_3_0
 */
class Part {

	public $id;
	public $field_id;
	public $saved;
	public $part_type = 'option';
	public $label = 'Option';
	public $description = '';
	public $default_value = '';
	public $classes = array();
	public $atts = [];
	public $data_store = false;
	public $field_before = null;
	public $field_after = null;
	public $panel_api = false;
	public $panel_id = false;
	public $update_type = '';

	public function __construct( $i, $args = [] ) {
		$this->id       = $i;
		$this->field_id = $this->id;

		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}

		if ( $this->data_store ) {
			$old_value     = $this->get_saved();
			$this->updated = $this->run_save_process();
			$this->saved   = $this->get_saved();
			if ( empty( $old_value ) && $this->updated && ! empty( $this->saved ) ) {
				$this->update_type = 'created';
			} elseif ( ! empty( $old_value ) && $this->updated && ! empty( $this->saved )
			           && ( $old_value !== $this->saved )
			) {
				$this->update_type = 'updated';
			} elseif ( ! empty( $old_value ) && $this->updated && empty( $this->saved ) ) {
				$this->update_type = 'deleted';
			}
		}
	}

	public function get_clean_classname() {
		return explode( '\\', get_called_class() )[2];
	}

	public function build_base_markup( $field ) {
		$desc = ( $this->description ) ? HTML::tag( 'div', [ 'class' => 'desc clear' ], $this->description ) : '';

		return HTML::tag(
			'li',
			[ 'class' => 'wpop-option ' . strtolower( $this->get_clean_classname() ), 'data-part' => $this->id ],
			HTML::tag( 'h4', [ 'class' => 'label' ], $this->label ) . $this->field_before . $field . $this->field_after
			. $desc . HTML::tag( 'div', [ 'class' => 'clear' ] )
		);
	}

	public function run_save_process() {
		if ( ! isset( $_POST['submit'] )
		     || ! is_string( $_POST['submit'] )
		     || 'Save All' !== $_POST['submit']
		) {
			return false; // only run logic if submiting
		}
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->panel_id ) ) {
			return false; // check for nonce
		}

		$type = ( ! empty( $this->field_type ) ) ? $this->field_type : $this->input_type;

		$field_input = isset( $_POST[ $this->id ] ) ? $_POST[ $this->id ] : false;

		$sanitize_input = $this->sanitize_data_input( $type, $this->id, $field_input );

		$updated = new Save_Single_Field(
			$this->panel_id, // used to check nonce
			$this->panel_api, // doing this way to allow multi-api saving from single panel down-the-road
			$this->id, // this is the data storage key in the database
			$sanitize_input, // sanitized input (maybe empty, triggering delete)
			isset( $this->obj_id ) ? $this->obj_id : null // maybe an object ID needed for metadata API
		);

		if ( $updated ) {
			return $this->id;
		}

		return false;
	}

	public function get_saved() {
		$pre_ = apply_filters( 'wpop_custom_option_enabled', false ) ? SM_SITEOP_PREFIX : '';

		switch ( $this->panel_api ) {
			case 'post':
				$obj_id = sanitize_text_field( $_GET['post'] );
				break;
			case 'term':
				$obj_id = sanitize_text_field( $_GET['term'] );
				break;
			case 'user':
			case 'user-network':
				$obj_id = sanitize_text_field( $_GET['user'] );
				break;
			case 'network':
			case 'site':
			default:
				$obj_id = null;
				break;
		}

		$response = new Get_Single_Field(
			$this->panel_id,
			$this->panel_api,
			$pre_ . $this->id,
			$this->default_value,
			$obj_id
		);

		return $response->response;
	}

	protected function sanitize_data_input( $input_type, $id, $value ) {
		switch ( $input_type ) {
			case 'password':
				if ( $_POST[ 'stored_' . $id ] === $value && ! empty( $value ) ) {
					return '### wpop-encrypted-pwd-field-val-unchanged ###';
				}

				return ! empty( $value ) ? Password::encrypt( $value ) : false;
				break;
			case 'media':
				return absint( $value );
				break;
			case 'color':
				return sanitize_hex_color_no_hash( $value );
				break;
			case 'editor':
				return wp_filter_post_kses( $value );
				break;
			case 'textarea':
				return sanitize_textarea_field( $value );
				break;
			case 'checkbox':
			case 'toggle_switch':
				return sanitize_key( $value );
				break;
			case 'multiselect':
				if ( ! empty( $value ) && is_array( $value ) ) {
					return json_encode( array_map( 'sanitize_key', $value ) );
				}

				return false;
				break;
			case 'email':
				return sanitize_email( $value );
				break;
			case 'url':
				return esc_url_raw( $value );
				break;
			case 'text':
			default:
				return sanitize_text_field( $value );
				break;
		}
	}

}

/**
 * Class Input
 * @package WPOP\V_3_0
 */
class Input extends Part {
	public $input_type;
	public $data_store = true;

	public function get_html() {
		$option_val = ( false === $this->saved || empty( $this->saved ) ) ? $this->default_value : $this->saved;

		$type = ! empty( $this->input_type ) ? $this->input_type : 'hidden';

		$input = [
			'id'           => $this->field_id,
			'name'         => $this->field_id,
			'type'         => $type,
			'value'        => $option_val,
			'data-part'    => strtolower( $this->get_clean_classname() ),
			'autocomplete' => 'false', // prevents pwd field autofilling, among other things
		];

		if ( ! empty( $this->classes ) ) {
			$input['classes'] = implode( ' ', $this->classes );
		}

		if ( ! empty( $this->atts ) ) {
			foreach ( $this->atts as $key => $val ) {
				$input[ $key ] = $val;
			}
		}

		return $this->build_base_markup( HTML::tag( 'input', $input ) );
	}

}

/**
 * Class Text
 * @package WPOP\V_3_0
 */
class Text extends Input {
	public $input_type = 'text';
}

/**
 * Class Color
 * @package WPOP\V_3_0
 */
class Color extends Input {
	public $input_type = 'text';
	public $field_type = 'color';
}

/**
 * Class Number
 * @package WPOP\V_3_0
 */
class Number extends Input {
	public $input_type = 'number';
}

/**
 * Class Email
 * @package WPOP\V_3_0
 */
class Email extends Input {
	public $input_type = 'email';
}

/**
 * Class Url
 * @package WPOP\V_3_0
 */
class Url extends Input {
	public $input_type = 'url';
}

/**
 * Class password
 * @package WPOP\V_2_8
 * @notes   how to use: echo $this->decrypt( get_option( $this->id ) );
 */
class Password extends Input {
	public $input_type = 'password';

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );

		$this->field_after = $this->pwd_clear_and_hidden_field();
	}

	protected function pwd_clear_and_hidden_field() {
		return HTML::tag( 'a', [ 'href' => '#', 'class' => 'button button-secondary pwd-clear' ], 'clear' ) .
		       HTML::tag( 'input', [
			       'id'           => 'stored_' . $this->id,
			       'name'         => 'stored_' . $this->id,
			       'type'         => 'hidden',
			       'value'        => $this->saved,
			       'autocomplete' => 'off',
		       ] );
	}

	/**
	 * Fixes PHP7 issues where mcrypt_decrypt expects a specific key size. Used on WPOP_ENCRYPTION_KEY constant.
	 * You'll still have to run trim on the end result when decrypting,as seen in the "unencrypted_pass" function.
	 *
	 * @see http://stackoverflow.com/questions/27254432/mcrypt-decrypt-error-change-key-size
	 *
	 * @param $key
	 *
	 * @return bool|string
	 */
	static function pad_key( $key ) {

		if ( strlen( $key ) > 32 ) { // key too large
			return false;
		}

		$sizes = array( 16, 24, 32 );

		foreach ( $sizes as $s ) { // loop sizes, pad key
			while ( strlen( $key ) < $s ) {
				$key = $key . "\0";
			}
			if ( strlen( $key ) == $s ) {
				break; // finish if the key matches a size
			}
		}

		return $key;
	}

	/**
	 * Field is encrypted using 256-bit encryption using mcrypt and then run through base64 for db env parity/safety
	 *
	 * @param $unencrypted_string
	 *
	 * @return string
	 */
	public static function encrypt( $unencrypted_string ) {
		return base64_encode(
			mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256,
				WPOP_ENCRYPTION_KEY,
				$unencrypted_string,
				MCRYPT_MODE_ECB
			)
		);
	}

	/**
	 * 📢 ⚠️ NEVER USE TO PRINT IN MARKUP, IN INPUT VALUES -- ONLY CALL IN SERVER-SIDE ACTIONS OR RISK THEFT ⚠️ 📢
	 *
	 * Field is base64 decoded, then decrypted using mcrypt, then trimmed of any excess characters left from transforms
	 *
	 * @param $encrypted_encoded
	 *
	 * @return string
	 */
	public static function decrypt( $encrypted_encoded ) {
		return trim(
			mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256,
				WPOP_ENCRYPTION_KEY,
				base64_decode( $encrypted_encoded ),
				MCRYPT_MODE_ECB
			)
		);
	}
}

/**
 * Class Textarea
 * @package WPOP\V_3_0
 */
class Textarea extends Part {

	public $cols;
	public $rows;
	public $input_type = 'textarea';
	public $data_store = true;

	/**
	 * @return string
	 */
	public function get_html() {
		$this->cols = ! empty( $this->cols ) ? $this->cols : 80;
		$this->rows = ! empty( $this->rows ) ? $this->rows : 10;

		$field = [ 'id' => $this->id, 'name' => $this->id, 'cols' => $this->cols, 'rows' => $this->rows ];

		if ( ! empty( $this->atts ) && is_array( $this->atts ) ) {
			foreach ( $this->atts as $key => $val ) {
				$field[ $key ] = $val;
			}
		}

		return $this->build_base_markup( HTML::tag( 'textarea', $field, stripslashes( $this->get_saved() ) ) );
	}

}

/**
 * Class Editor
 * @package WPOP\V_3_0
 */
class Editor extends Part {

	public $input_type = 'editor';
	public $data_store = true;

	public function get_html() {

		ob_start();
		wp_editor(
			stripslashes( $this->get_saved() ),
			$this->id . '_editor',
			array(
				'textarea_name' => $this->id, // used for saving value
				'tinymce'       => array( 'min_height' => 300 ),
				'editor_class'  => 'edit',
				'quicktags'     => isset( $this->no_quicktags ) ? false : true,
				'teeny'         => isset( $this->teeny ) ? true : false,
				'media_buttons' => isset( $this->no_media ) ? false : true
			)
		);

		return $this->build_base_markup( ob_get_clean() ); // no return param in wp_editor so buffer it is ¯\_//(ツ)_/¯
	}
}

/**
 * Class Select
 * @package WPOP\V_3_0
 */
class Select extends Part {

	public $values;
	public $meta;
	public $empty_default = true;
	public $input_type = 'select';
	public $data_store = true;

	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$default_option = isset( $this->meta['option_default'] ) ? $this->meta['option_default'] : 'Select an option';

		ob_start();

		if ( $this->empty_default ) {
			echo HTML::tag( 'option', [ 'value' => '' ] );
		}

		foreach ( $this->values as $value => $label ) {
			$option = [ 'value' => $value ];
			if ( $value === $this->get_saved() ) {
				$option['selected'] = 'selected';
			}
			echo HTML::tag( 'option', $option, $label );
		}

		return $this->build_base_markup(
			HTML::tag(
				'select',
				[
					'id'               => $this->id,
					'name'             => $this->id,
					'data-select'      => true,
					'data-placeholder' => $default_option
				],
				ob_get_clean()
			)
		);
	}

}

/**
 * Class Multiselect
 * @package WPOP\V_3_0
 */
class Multiselect extends Part {

	public $values;
	public $meta;
	public $allow_reordering = false;
	public $create_options = false;
	public $input_type = 'multiselect';
	public $data_store = true;


	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$save = ! empty( $this->saved ) ? json_decode( $this->saved ) : false;

		$opts_markup = '';

		if ( ! empty( $save ) && is_array( $save ) ) {
			foreach ( $save as $key ) {
				$opts_markup .= HTML::tag( 'option', [
					'value'    => $key,
					'selected' => 'selected'
				], $this->values[ $key ] );
				unset( $this->values[ $key ] );
			}
		}

		if ( ! empty( $this->values ) && is_array( $this->values ) ) {
			foreach ( $this->values as $key => $value ) {
				$opts_markup .= HTML::tag( 'option', [ 'value' => $key ], $value );
			}
		}

		return $this->build_base_markup( HTML::tag( 'select', [
			'id'               => $this->id,
			'name'             => $this->id . '[]',
			'multiple'         => 'multiple',
			'data-multiselect' => '1'
		], $opts_markup
		) );
	}

	function multi_atts( $pairs, $atts ) {
		$return = [];
		foreach ( $atts as $key ) {
			$return[ $key ] = $pairs[ $key ];
		}

		return $return;
	}

}

/**
 * Class Checkbox
 * @package WPOP\V_3_0
 */
class Checkbox extends Part {

	public $value = 'on';
	public $label_markup;
	public $input_type = 'checkbox';
	public $data_store = true;


	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function get_html() {
		$classes = ! empty( $this->label_markup ) ? 'onOffSwitch-checkbox' : 'cb';
		$input   = [ 'type' => 'checkbox', 'id' => $this->id, 'name' => $this->id, 'class' => $classes ];
		if ( $this->get_saved() === $this->value ) {
			$input['checked'] = 'checked';
		}

		return $this->build_base_markup(
			HTML::tag( 'div', [ 'class' => 'cb-wrap' ], HTML::tag( 'input', $input ) . $this->label_markup )
		);
	}

}

/**
 * Class Toggle_Switch
 * @package WPOP\V_3_0
 */
class Toggle_Switch extends Checkbox {
	public $input_type = 'toggle_switch';

	/**
	 * Toggle_Switch constructor.
	 *
	 * @param string $i
	 * @param array  $args
	 */
	function __construct( $i, array $args = [] ) {
		parent::__construct( $i, $args );
		$this->label_markup = HTML::tag(
			'label',
			[ 'class' => 'onOffSwitch-label', 'for' => $this->id ],
			'<div class="onOffSwitch-inner"></div><span class="onOffSwitch-switch"></span>'
		);
	}
}

/**
 * Class Radio_Buttons
 * @package WPOP\V_3_0
 */
class Radio_Buttons extends Part {

	public $values;
	public $default_value = '';
	public $input_type = 'radio_buttons';
	public $data_store = true;

	public function __construct( $i, $c ) {
		parent::__construct( $i, $c );
		$this->values = ( ! empty( $c['values'] ) ) ? $c['values'] : [];
	}

	public function get_html() {
		$table_body = '';
		foreach ( $this->values as $key => $value ) {
			$selected_val = $this->get_saved() ? $this->get_saved() : $this->default_value;

			$input = [
				'type'  => 'radio',
				'id'    => $this->id . '_' . $key,
				'name'  => $this->field_id,
				'value' => $value,
				'class' => 'radio-item'
			];

			if ( $selected_val === $value ) {
				$input['checked'] = 'checked';
			}

			$label      = HTML::tag( 'td', [], HTML::tag( 'label', [
				'class' => 'opt-label',
				'for'   => $this->id . '_' . $key
			], $value )
			);
			$input_mark = HTML::tag( 'td', [], HTML::tag( 'input', $input ) );

			$table_body .= HTML::tag( 'tr', [], $label . $input_mark );
		}

		$table = HTML::tag( 'table', [ 'class' => 'widefat striped' ], $table_body );

		return $this->build_base_markup( HTML::tag( 'div', [ 'class' => 'radio-wrap' ], $table ) );
	}

}

/**
 * Class Media
 * @package WPOP\V_3_0
 */
class Media extends Part {
	public $media_label = 'Image';
	public $input_type = 'media';
	public $data_store = true;

	public function get_html() {
		$empty        = ''; // TODO: REPLACE EMPTY IMAGE WITH CSS YO
		$saved        = array( 'url' => $empty, 'id' => '' );
		$option_val   = $this->get_saved();
		$insert_label = 'Insert ' . $this->media_label;
		if ( ! empty( $option_val ) && absint( $option_val ) ) {
			$img          = wp_get_attachment_image_src( $option_val );
			$saved        = array( 'url' => is_array( $img ) ? $img[0] : 'err', 'id' => $option_val );
			$insert_label = 'Replace ' . $this->media_label;
		}

		ob_start();
		echo '<div class="blank-img" style="display:none;">' . $empty . '</div>';

		$image_btn = [
			'id'               => $this->id . '_button',
			'data-media-label' => $this->media_label,
			'type'             => 'button',
			'class'            => 'button button-secondary button-hero img-upload',
			'value'            => $insert_label,
			'data-id'          => $this->id,
			'data-button'      => 'Use ' . $this->media_label,
			'data-title'       => 'Select or Upload ' . $this->media_label,
		];

		$hidden = [
			'id'        => $this->id,
			'name'      => $this->id,
			'type'      => 'hidden',
			'value'     => $saved['id'],
			'data-part' => strtolower( $this->get_clean_classname() )
		];

		if ( ! empty( $this->atts ) ) {
			foreach ( $this->atts as $key => $val ) {
				$hidden[ $key ] = $val;
			}
		}

		echo HTML::tag( 'input', $image_btn );
		echo HTML::tag( 'input', $hidden );
		echo HTML::tag( 'a', [
			'href'             => '#',
			'class'            => 'button button-secondary img-remove',
			'data-media-label' => $this->media_label
		], 'Remove ' . $this->media_label
		);

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Include_Partial
 * @package WPOP\V_3_0
 */
class Include_Partial extends Part {

	public $filename;
	public $input_type = 'include_partial';

	public function __construct( $i, $config ) {
		parent::__construct( $i, [] );
		$this->filename = ( ! empty( $config['filename'] ) ) ? $config['filename'] : 'set_the_filename.php';
	}

	public function get_html() {
		return $this->echo_html();
	}

	public function echo_html() {
		if ( ! empty( $this->filename ) && is_file( $this->filename ) ) {
			return HTML::tag( 'li', [ 'class' => $this->get_clean_classname() ], file_get_contents( $this->filename ) );
		}
	}
}

/**
 * Class Markdown
 * @package WPOP\V_3_1
 */
class Markdown extends Include_Partial {
	public $field_type = 'markdown_file';

	public function echo_html() {
		if ( is_file( $this->filename ) && class_exists( '\\Parsedown' ) ) {
			$converter = new \Parsedown();
			$markup    = file_get_contents( $this->filename );
			if ( ! empty( $markup ) ) {
				return HTML::tag(
					'li',
					[
						'class'     => $this->get_clean_classname(),
						'data-part' => strtolower( $this->get_clean_classname() )
					],
					$converter->text( do_shortcode( $markup ) )
				);
			}
		} else {
			return 'File Status: ' . strval( is_file( $this->filename ) ) .
			       ' and class exists: ' . strval( class_exists( '\\Parsedown' ) );
		}
	}
}

/**
 * Class HTML
 * @package WPOP\V_2_10
 * @link    https://github.com/Automattic/amp-wp/blob/master/includes/utils/class-amp-html-utils.php
 */
class HTML {
	/**
	 * Dashicon Markup Helper
	 *
	 * @param $class_str - the dashicons-* class and any addl
	 *
	 * @return string
	 */
	public static function dashicon( $class_str ) {
		return self::tag( 'span', [ 'class' => 'dashicons ' . $class_str, 'data-dashicon' ] );
	}

	/**
	 * Create markup for HTML tag from array fully sanitized and prepared
	 *
	 * @param        $tag_name
	 * @param array  $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public static function tag( $tag_name, $attributes = array(), $content = '' ) {
		$attr_string = self::build_attributes_string( $attributes );

		return sprintf( '<%1$s %2$s>%3$s</%1$s>', sanitize_key( $tag_name ), $attr_string, $content );
	}

	/**
	 * Built Escaped, Sanitized Attribute String for HTML Tag
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public static function build_attributes_string( $attributes ) {
		$string = array();
		foreach ( $attributes as $name => $value ) {
			if ( empty( $value ) ) {
				$string[] = sprintf( '%s', sanitize_key( $name ) );
			} else {
				$string[] = sprintf( '%s="%s"', sanitize_key( $name ), esc_attr( $value ) );
			}
		}

		return implode( ' ', $string );
	}

	/**
	 * WordPress Admin Notification Markup (can be printed anywhere in the DOM and will be relocated to top of page)
	 *
	 * @param $class_str - the dashicons-* class and any addl
	 *
	 * @return string
	 */
	public static function notification( $class_str ) {
		return self::tag( 'div', [ 'class' => 'dashicons ' . $class_str, 'data-dashicon' ] );
	}
}

/**
 * Helper used by panel for tapping various WordPress APIs
 *
 * Class Get_Single_Field
 * @package WPOP\V_3_0
 */
class Get_Single_Field {

	public $response;

	protected $type;
	protected $key;
	protected $obj_id;
	protected $single;

	/**
	 * Get_Single_Field constructor.
	 *
	 * @param      $panel_id
	 * @param      $type
	 * @param      $key
	 * @param null $default
	 * @param null $obj_id
	 * @param bool $single
	 */
	function __construct( $panel_id, $type, $key, $default = null, $obj_id = null, $single = true ) {
		if ( false !== wp_verify_nonce( $panel_id, $panel_id ) ) {
			return false; // check for nonce, only allow panel to use this class
		}
		$this->type   = $type;
		$this->key    = $key;
		$this->obj_id = $obj_id;
		$this->single = $single;

		$this->get_data();

		return $this->response;
	}

	function get_data() {
		switch ( $this->type ) {
			case 'site':
				$this->response = get_option( $this->key, '' );
				break;
			case 'network':
				$this->response = get_site_option( $this->key );
				break;
			case 'user': // single-site user option, or per-site user option in multisite
				$this->response = is_multisite() ? get_user_option( $this->key, $this->obj_id ) : get_user_meta( $this->obj_id, $this->key, $this->single );
				break; // traditional user meta
			case 'user-network': // user network option applied globally across all blogs/sites
				$this->response = get_user_meta( $this->obj_id, $this->key, $this->single );
				break;
			case 'term':
				$this->response = get_metadata( 'term', $this->obj_id, $this->key, $this->single );
				break;
			case 'post':
				$this->response = get_metadata( 'post', $this->obj_id, $this->key, $this->single );
				break;
			default:
				$this->response = false;
				break;
		}
	}

}

/**
 * Class Save_Single_Field
 * @package WPOP\V_3_0
 */
class Save_Single_Field {
	/**
	 * Save_Single_Field constructor.
	 *
	 * @param      $panel_id
	 * @param      $type
	 * @param      $key
	 * @param      $value
	 * @param null $obj_id
	 * @param bool $autoload
	 */
	function __construct( $panel_id, $type, $key, $value, $obj_id = null, $autoload = true ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $panel_id )            // only allow class to be used by panel
		     || '### wpop-encrypted-pwd-field-val-unchanged ###' === $value // encrypted pwds never updated after insert
		) {
			return false;
		}

		return $this->save_data( $panel_id, $type, $key, $value, $obj_id, $autoload );
	}

	private function save_data( $panel_id, $type, $key, $value, $obj_id = null, $autoload = true ) {
		switch ( $type ) {
			case 'site':
				return self::handle_site_option_save( $key, $value, $autoload );
				break;
			case 'network':
				return self::handle_network_option_save( $key, $value );
				break;
			case 'user':
				return self::handle_user_site_meta_save( $obj_id, $key, $value );
				break; // traditional user meta
			case 'user-network':
				return self::handle_user_network_meta_save( $obj_id, $key, $value );
				break;
			case 'term':
				return self::handle_term_meta_save( $obj_id, $key, $value );
				break;
			case 'post':
				return self::handle_post_meta_save( $obj_id, $key, $value );
				break;
			default:
				return new \WP_Error(
					'400',
					'WPOP failed to select proper WordPress Data API -- check your config.',
					compact( $type, $key, $value, $obj_id, $autoload )
				);
				break;
		}
	}

	private static function handle_site_option_save( $key, $value, $autoload ) {
		return empty( $value ) ? delete_option( $key ) : update_option( $key, $value, $autoload );
	}

	private static function handle_network_option_save( $key, $value ) {
		return empty( $value ) ? delete_site_option( $key ) : update_site_option( $key, $value );
	}

	private static function handle_user_site_meta_save( $user_id, $key, $value ) {
		return empty( $value ) ? delete_user_meta( $user_id, $key ) : update_user_meta( $user_id, $key, $value );
	}

	private static function handle_user_network_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_user_option( $id, $key, true ) : update_user_option( $id, $key, true );
	}

	private static function handle_term_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_metadata( 'term', $id, $key ) : update_metadata( 'term', $id, $key, $value );
	}

	private static function handle_post_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_post_meta( $id, $key ) : update_post_meta( $id, $key, $value );
	}
}
