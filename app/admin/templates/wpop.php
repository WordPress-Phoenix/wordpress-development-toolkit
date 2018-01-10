<?php
/**
 * [WPOP] WordPress Phoenix Options Panel - Builder Classes
 *
 * @authors 🌵 WordPress Phoenix 🌵 / Seth Carstens, David Ryan
 * @package wpop
 * @version 2.9.0
 * @license GPL-2.0+ - please retain comments that express original build of this file by the author.
 */

namespace WPOP\V_2_9;

if ( ! function_exists( 'add_filter' ) ) { // avoid direct calls to file
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( apply_filters( 'wpop_custom_option_enabled', false )
     && defined( 'SITEOPTION_PREFIX' )
     && ! function_exists( 'get_custom_option' )
) {
	function get_custom_option( $s = '', $network_option = false ) {
		return $network_option ? get_site_option( SITEOPTION_PREFIX . $s ) : get_option( SITEOPTION_PREFIX . $s );
	}
}

class Container {

	public $id = null;
	public $container = null;
	public $parts = [];
	public $parent_id = '';
	public $capability = 'read';
	public $notifications = [];
	public $security_check = false;
	public $field_order = false;
	public $network_page = false;

	public function __construct( $i, $args = [] ) {
		$this->id        = preg_replace( '/_/', '-', $i );
		$this->container = get_called_class();
	}

	public function add_part( $part ) {
		$part                     = $this->insert_parent_vars( $part );
		$this->parts[ $part->id ] = $part;
	}

	public function insert_parent_vars( $part ) {
		switch ( $this->get_clean_classname() ) {
			case 'section':
			case 'tab':
				$parent = get_object_vars( $this );
				unset( $parent['parts'], $parent['notifications'], $parent['security_check'] );
				foreach ( $parent as $key => $val ) {
					$name        = 'section_' . $key;
					$part->$name = $val;
				}
				break;
			default:
				$parent = get_object_vars( $this );
				if ( isset( $this->network_page ) || $parent->network_page ) {
					$part->network_option = true;
				}
				break;
		}

		return $part;
	}

	public function run_legacy_values_wipe() {
		if ( ! isset( $_GET['wipe-defaults'] ) || ! isset( $_GET['page'] ) ) {
			return false;
		}

		$deleted_something = false;
		foreach ( $this->parts as $section ) {
			foreach ( $section->parts as $part ) {
				if ( isset( $part->legacy_key ) && ! empty( $part->legacy_key ) ) {
					$network           = is_multisite() && is_network_admin() && is_super_admin();
					$delete_key        = $network ? delete_site_option( $part->legacy_key ) : delete_option( $part->legacy_key );
					$deleted_something = ! $deleted_something && $delete_key ? $delete_key : false;
				}
			}
		}
		if ( $deleted_something ) {
			$this->notifications['update'] = implode(
				'', array(
					'<div class="notice notice-success"><p>',
					__( 'Successfully deleted legacy options. Please remove the `legacy_key` parameter and any old field registration code.', 'wpop' ),
					'</p></div>',
				)
			);
		}

		return $deleted_something;
	}

	public function run_options_save_process() {
		if ( ! isset( $_POST['submit'] )
		     || ! is_string( $_POST['submit'] )
		     || 'Save All' !== $_POST['submit']
		) {
			return false; // only run logic if submiting
		}
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->id ) ) {
			return false; // check for nonce
		}

		$any_updated = false;

		// note $_POST[ $part->id ] that taps the key's value from the submit array
		foreach ( $this->parts as $section ) {
			foreach ( $section->parts as $part ) {
				// todo: cleanup, but shim to get passwords working
				if ( isset( $part->password ) && $part->password ) {
					$updated = $part->save_password( $this->network_page );
				} else {
					$updated = $this->do_options_save( $part->id, $_POST[ $part->id ], $this->network_page );
				}
				$any_updated = ( $updated && ! $any_updated ) ? true : $any_updated;
			}
		}

		if ( $any_updated ) {
			$this->notifications['update'] = implode(
				'', array(
					'<div class="notice notice-success"><p>',
					__( 'Some options were saved!', 'wpop' ),
					'</p></div>',
				)
			);
		}

		return $any_updated;
	}

	public function do_options_save( $key, $value, $network = false, $obj_id = null ) {
		if ( ! empty( $obj_id ) && absint( $obj_id ) ) {
			return false; // TODO: build term meta API saving for multisite
		}
		switch ( $network ) {
			case true:
				return ! empty( $value ) ? update_site_option( $key, $value ) : delete_site_option( $key );
				break;
			case false:
			default:
				return ! empty( $value ) ? update_option( $key, $value ) : delete_option( $key );
				break;
		}
	}

	public function echo_notifications() {
		do_action( 'wpop_after_option_save', $this );
		foreach ( $this->notifications as $notify_html ) {
			echo $notify_html;
		}
	}

	public function get_clean_classname() {
		return explode( '\\', get_called_class() )[2]; // get class name w/o versioned-NS
	}
}

class Page extends Container {

	public $page_title = 'Custom Site Options';
	public $menu_title = 'Custom Site Options';
	public $capability = 'manage_options';
	public $dashicon;
	public $disable_styles = false;
	public $theme_page = false;

	public function __construct( $args = [] ) {
		parent::__construct( $args['id'] );
		foreach ( $args as $key => $val ) {
			$this->$key = $val;
		}
	}

	public function initialize_panel() {
		$decide_network_or_single_site_admin = $this->network_page ? 'network_admin_menu' : 'admin_menu';
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dependencies' ) );
		add_action( $decide_network_or_single_site_admin, array( $this, 'add_settings_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'run_options_save_process' ) );
		add_action( 'admin_init', array( $this, 'run_legacy_values_wipe' ) );
	}

	public function add_settings_submenu_page() {
		add_submenu_page(
			$this->theme_page ? 'themes.php' : $this->parent_id, // file.php to hook into
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->id,
			array( $this, 'build_parts' )
		);
	}

	public function build_parts() {
		$dashicon = ! empty( $this->dashicon ) ? '<span class="dashicons ' . $this->dashicon . ' page-icon"></span> ' : '';
		ob_start(); ?>
		<div id="wpopOptions">
			<?php
			if ( ! $this->disable_styles ) {
				$this->inline_styles_and_scripts();
			}
			?>
			<section class="wrap wp">
				<header><h2></h2></header> <!-- IMPORTANT: allows core admin notices -->
			</section>
			<section id="wpop" class="wrap">
				<form method="post" class="pure-form" onReset="return confirm('Reset ALL options? (Save still req.)">
					<header class="wpop-head">
						<div class="inner">
							<h1><?php echo $dashicon . $this->page_title; ?></h1>
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
						<div class="wpop-loader-wrapper">
							<div class="loader-inner ball-clip-rotate-multiple">
								<div></div>
								<div></div>
							</div>
						</div>
						<div id="wpopNav" class="pure-u-1 pure-u-md-6-24">
							<div class="pure-menu wpop-options-menu">
								<ul class="pure-menu-list">
									<?php
									foreach ( $this->parts as $key => $part ) {
										$dashicon = ! empty( $part->dashicon ) ? '<span class="dashicons ' .
										                                         $part->dashicon . ' menu-icon"></span> ' : '';
										echo '<li id="' . $part->id . '-nav" class="pure-menu-item"><a href="#'
										     . $part->id . '" class="pure-menu-link">' . $dashicon . $part->title
										     . '</a></li>';
									}
									?>
								</ul>
							</div>
							<?php echo wp_nonce_field( $this->id, '_wpnonce', true, false ); ?>
						</div>
						<div id="wpopMain" class="pure-u-1 pure-u-md-18-24">
							<ul id="wpopOptNavUl" style="list-style: none;">
								<?php
								foreach ( $this->parts as $key => $part ) {
									$part->echo_html();
								} ?>
							</ul>
						</div>
						<footer class="pure-u-1">
							<div class="pure-g">
								<div class="pure-u-1 pure-u-md-1-3">
									<div>
										<span>Stored in: <code><?php echo $this->get_storage_table(); ?></code></span>
									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">
									<div>

									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">
									<?php $func = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
									$page       = $this->theme_page ? 'themes.php' : $this->parent_id; ?>
									<div>
										<a href="<?php echo $func( $page . '?page=' . $this->id . '&wipe-defaults=all' ); ?>"
										   id="wipe-all-legacy" name="wipe-all-legacy" class="button">Wipe
											Legacy Values</a>
									</div>
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

	public function inline_styles_and_scripts() {
		ob_start(); ?>
		<style>
			.onOffSwitch-inner, .onOffSwitch-switch {
				transition: all .5s cubic-bezier(1, 0, 0, 1)
			}

			.onOffSwitch {
				position: relative;
				width: 110px;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				margin-left: auto;
				margin-right: 12px
			}

			input[type=checkbox].onOffSwitch-checkbox {
				display: none
			}

			.onOffSwitch-label {
				display: block;
				overflow: hidden;
				cursor: pointer;
				border: 2px solid #EEE;
				border-radius: 28px
			}

			.onOffSwitch-inner {
				display: block;
				width: 200%;
				margin-left: -100%
			}

			.onOffSwitch-inner:after, .onOffSwitch-inner:before {
				display: block;
				float: left;
				width: 50%;
				height: 40px;
				padding: 0;
				line-height: 40px;
				font-size: 17px;
				font-family: Trebuchet, Arial, sans-serif;
				font-weight: 700;
				box-sizing: border-box
			}

			.cb, .save-all, .wpop-option.color_picker .iris-picker {
				float: right
			}

			.onOffSwitch-inner:before {
				content: "ON";
				padding-left: 10px;
				background-color: #21759B;
				color: #FFF
			}

			.onOffSwitch-inner:after {
				content: "OFF";
				padding-right: 10px;
				background-color: #EEE;
				color: #BCBCBC;
				text-align: right
			}

			.onOffSwitch-switch {
				display: block;
				width: 28px;
				margin: 6px;
				background: #BCBCBC;
				position: absolute;
				top: 0;
				bottom: 0;
				right: 66px;
				border: 2px solid #EEE;
				border-radius: 20px
			}

			.onOffSwitch-checkbox:checked + .onOffSwitch-label .onOffSwitch-inner {
				margin-left: 0
			}

			.onOffSwitch-checkbox:checked + .onOffSwitch-label .onOffSwitch-switch {
				right: 0;
				background-color: #D54E21
			}

			.wpop-loader-wrapper {
				position: fixed;
				top: 45%;
				right: 45%;
				z-index: 99999;
				display: none
			}

			.ball-clip-rotate-multiple {
				position: relative
			}

			.ball-clip-rotate-multiple > div {
				position: absolute;
				left: -20px;
				top: -20px;
				border: 3px solid #cd1713;
				border-bottom-color: transparent;
				border-top-color: transparent;
				border-radius: 100%;
				height: 35px;
				width: 35px;
				-webkit-animation: rotate .99s 0 ease-in-out infinite;
				animation: rotate 1s 0 ease-in-out infinite
			}

			.cb, .cb-wrap, .desc:after, .pwd-clear, .save-all, span.menu-icon, span.spacer {
				position: relative
			}

			.ball-clip-rotate-multiple > div:last-child {
				display: inline-block;
				top: -10px;
				left: -10px;
				width: 15px;
				height: 15px;
				-webkit-animation-duration: .33s;
				animation-duration: .33s;
				border-color: #cd1713 transparent;
				-webkit-animation-direction: reverse;
				animation-direction: reverse
			}

			@keyframes rotate {
				0% {
					-webkit-transform: rotate(0) scale(1);
					transform: rotate(0) scale(1)
				}
				50% {
					-webkit-transform: rotate(180deg) scale(.6);
					transform: rotate(180deg) scale(.6)
				}
				100% {
					-webkit-transform: rotate(360deg) scale(1);
					transform: rotate(360deg) scale(1)
				}
			}

			#wpopMain {
				background: #fff
			}

			#wpopOptNavUl {
				margin-top: 0
			}

			.wpop-options-menu {
				margin-bottom: 8em
			}

			#wpopContent {
				background: #F1F1F1;
				width: 100% !important;
				border-top: 1px solid #D8D8D8
			}

			.pure-g [class*=pure-u] {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif
			}

			.pure-form select {
				min-width: 320px
			}

			.selectize-control {
				max-width: 98.5%
			}

			.pure-menu-disabled, .pure-menu-heading, .pure-menu-link {
				padding: 1.3em 2em
			}

			.pure-menu-active > .pure-menu-link, .pure-menu-link:focus, .pure-menu-link:hover {
				background: inherit
			}

			#wpopOptions header {
				overflow: hidden;
				max-height: 88px
			}

			#wpopNav p.submit input {
				width: 100%
			}

			#wpop {
				border: 1px solid #D8D8D8;
				background: #fff
			}

			.opn a.pure-menu-link {
				color: #fff !important
			}

			.opn a.pure-menu-link:focus {
				box-shadow: none;
				-webkit-box-shadow: none
			}

			#wpopContent .section {
				display: none;
				width: 100%
			}

			#wpopContent .section.active {
				display: inline-block
			}

			span.page-icon {
				margin: 0 1.5vw 0 0
			}

			span.menu-icon {
				left: -.5rem
			}

			span.page-icon:before {
				font-size: 2.5rem;
				position: relative;
				top: -4px;
				right: 4px;
				color: #777
			}

			.clear {
				clear: both
			}

			.section {
				padding: 0 0 5px
			}

			.section h3 {
				margin: 0 0 10px;
				padding: 2vw 1.5vw
			}

			.section h4.label {
				margin: 0;
				display: table-cell;
				border: 1px solid #e9e9e9;
				background: #f1f1f1;
				padding: .33vw .66vw .5vw;
				font-weight: 500;
				font-size: 16px
			}

			.section li.wpop-option {
				margin: 1rem 1rem 1.25rem
			}

			.twothirdfat {
				width: 66.6%
			}

			span.spacer {
				display: block;
				width: 100%;
				border: 0;
				height: 0;
				border-top: 1px solid rgba(0, 0, 0, .1);
				border-bottom: 1px solid rgba(255, 255, 255, .3)
			}

			li.even.option {
				background-color: #ccc
			}

			input[disabled=disabled] {
				background-color: #CCC
			}

			.cb {
				right: 20px
			}

			.card-wrap {
				width: 100%
			}

			.fullwidth {
				width: 100% !important;
				max-width: 100% !important
			}

			.wpop-head {
				background: #f1f1f1
			}

			.wpop-head > .inner {
				padding: 1vw 1.5vw 0
			}

			.save-all {
				top: -48px
			}

			.desc {
				margin: .5rem 0 0 .25rem;
				font-weight: 300;
				font-size: 12px;
				line-height: 16px;
				transition: all 1s ease;
				color: #888;
				-webkit-transition: all 1s ease;
				-moz-transition: all 1s ease;
				-o-transition: all 1s ease
			}

			.desc:after {
				display: block;
				width: 98%;
				border-top: 1px solid rgba(0, 0, 0, .1);
				border-bottom: 1px solid rgba(255, 255, 255, .3)
			}

			.wpop-option input[type=text] {
				width: 90%
			}

			input[data-assigned] {
				width: 100% !important
			}

			.add-button {
				margin: 3em auto;
				display: block;
				width: 100%;
				text-align: center
			}

			.img-preview {
				max-width: 320px;
				display: block;
				margin: 0 0 1rem
			}

			.img-remove {
				border: 2px solid #cd1713 !important;
				background: #f1f1f1 !important;
				color: #cd1713 !important;
				box-shadow: none;
				-webkit-box-shadow: none;
				margin-left: 1rem !important
			}

			.pwd-clear {
				margin-left: .5rem !important;
				top: 1px
			}

			.pure-form footer {
				background: #f1f1f1;
				border-top: 1px solid #D8D8D8
			}

			.pure-form footer div div > * {
				padding: 1rem .33rem
			}

			.wpop-option .wp-editor-wrap {
				margin-top: .5rem
			}

			.wpop-option.color_picker input {
				width: 50%
			}

			.cb-wrap {
				display: block;
				right: 1.33vw;
				max-width: 110px;
				margin-left: auto;
				top: -1.66rem
			}
		</style>
		<?php
		$css = ob_get_clean();
		ob_start(); ?>
		<script type="text/javascript">
			!function ( t, o ) {
				"use strict";
				t.wp = t.wp || {}, t.wp.hooks = t.wp.hooks || new function () {
					function t( t, o, i, n ) {
						var e, a, p;
						if ( r[ t ][ o ] ) if ( i ) if ( e = r[ t ][ o ], n ) for ( p = e.length; p--; ) (a = e[ p ]).callback === i && a.context === n && e.splice( p, 1 ); else for ( p = e.length; p--; ) e[ p ].callback === i && e.splice( p, 1 ); else r[ t ][ o ] = []
					}

					function o( t, o, n, e, a ) {
						var p = { callback: n, priority: e, context: a }, c = r[ t ][ o ];
						c ? (c.push( p ), c = i( c )) : c = [ p ], r[ t ][ o ] = c
					}

					function i( t ) {
						for ( var o, i, n, e = 1, a = t.length; a > e; e++ ) {
							for ( o = t[ e ], i = e; (n = t[ i - 1 ]) && n.priority > o.priority; ) t[ i ] = t[ i - 1 ], --i;
							t[ i ] = o
						}
						return t
					}

					function n( t, o, i ) {
						var n, e, a = r[ t ][ o ];
						if ( !a ) return "filters" === t && i[ 0 ];
						if ( e = a.length, "filters" === t ) for ( n = 0; e > n; n++ ) i[ 0 ] = a[ n ].callback.apply( a[ n ].context, i ); else for ( n = 0; e > n; n++ ) a[ n ].callback.apply( a[ n ].context, i );
						return "filters" !== t || i[ 0 ]
					}

					var e = Array.prototype.slice, a = {
						removeFilter: function ( o, i ) {
							return "string" == typeof o && t( "filters", o, i ), a
						}, applyFilters: function () {
							var t = e.call( arguments ), o = t.shift();
							return "string" == typeof o ? n( "filters", o, t ) : a
						}, addFilter: function ( t, i, n, e ) {
							return "string" == typeof t && "function" == typeof i && (n = parseInt( n || 10, 10 ), o( "filters", t, i, n, e )), a
						}, removeAction: function ( o, i ) {
							return "string" == typeof o && t( "actions", o, i ), a
						}, doAction: function () {
							var t = e.call( arguments ), o = t.shift();
							return "string" == typeof o && n( "actions", o, t ), a
						}, addAction: function ( t, i, n, e ) {
							return "string" == typeof t && "function" == typeof i && (n = parseInt( n || 10, 10 ), o( "actions", t, i, n, e )), a
						}
					}, r = { actions: {}, filters: {} };
					return a
				}
			}( window ), jQuery( document ).ready( function ( t ) {
				function o() {
					wp.hooks.addAction( "wpopPreInit", a ), wp.hooks.addAction( "wpopInit", e ), wp.hooks.addAction( "wpopInit", r ), wp.hooks.addAction( "wpopInit", p ), wp.hooks.addAction( "wpopSectionNav", i ), wp.hooks.addAction( "wpopPwdClear", c ), wp.hooks.addAction( "wpopImgUpload", l ), wp.hooks.addAction( "wpopImgRemove", s ), wp.hooks.addAction( "wpopSubmit", n )
				}

				function i( o, i ) {
					i.preventDefault();
					var n = t( t( o ).attr( "href" ) ).addClass( "active" ),
						e = t( t( o ).attr( "href" ) + "-nav" ).addClass( "active wp-ui-primary opn" );
					return window.location.hash = t( o ).attr( "href" ), window.scrollTo( 0, 0 ), t( n ).siblings().removeClass( "active" ), t( e ).siblings().removeClass( "active wp-ui-primary opn" ), !1
				}

				function n() {
					t( ".wpop-loader-wrapper" ).css( "display", "inherit" )
				}

				function e() {
					(hash = window.location.hash) ? t( hash + "-nav a" ).trigger( "click" ) : t( "#wpopNav li:first a" ).trigger( "click" )
				}

				function a() {
					t( "html, body" ).animate( { scrollTop: 0 } )
				}

				function r() {
					"undefined" != typeof iris && t( '[data-field="color_picker"]' ).iris( { width: 320, hide: !1 } )
				}

				function p() {
					t( "[data-select]" ).selectize( {
						allowEmptyOption: !1,
						placeholder: t( this ).attr( "data-placeholder" )
					} );
					t( "[data-multiselect]" ).selectize( { plugins: [ "restore_on_backspace", "remove_button", "drag_drop", "optgroup_columns" ] } )
				}

				function c( o, i ) {
					i.preventDefault(), t( o ).prev().val( null )
				}

				function l( o, i ) {
					i.preventDefault();
					var n = t( o ).data();
					d || (d = wp.media.frames.wpModal || wp.media( {
						title: n.title,
						button: { text: n.button },
						library: { type: "image" },
						multiple: !1
					} ), d.on( "select", function () {
						var i = d.state().get( "selection" ).first().toJSON();
						if ( "object" == typeof i ) {
							var n = t( o ).closest( ".wpop-option" );
							n.find( '[type="hidden"]' ).val( i.id ), n.find( "img" ).attr( "src", i.url ).show(), t( o ).attr( "value", "Replace " + t( o ).attr( "data-media-label" ) ), n.find( ".img-remove" ).show()
						}
					} )), d.open()
				}

				function s( o, i ) {
					i.preventDefault();
					var n = confirm( "Remove " + t( o ).attr( "data-media-label" ) + "?" );
					if ( n ) {
						var e = t( o ).closest( ".wpop-option" ), a = e.find( ".blank-img" ).html();
						e.find( '[type="hidden"]' ).val( null ), e.find( "img" ).attr( "src", a ), e.find( ".button-hero" ).val( "Set Image" ), t( o ).hide()
					}
				}

				var d;
				o(), wp.hooks.doAction( "wpopPreInit" ), t( "#wpopNav li a" ).click( function ( t ) {
					wp.hooks.doAction( "wpopSectionNav", this, t )
				} ), wp.hooks.doAction( "wpopInit" ), t( 'input[type="submit"]' ).click( function ( t ) {
					wp.hooks.doAction( "wpopSubmit", this, t )
				} ), t( ".pwd-clear" ).click( function ( t ) {
					wp.hooks.doAction( "wpopPwdClear", this, t )
				} ), t( ".img-upload" ).on( "click", function ( t ) {
					wp.hooks.doAction( "wpopImgUpload", this, t )
				} ), t( ".img-remove" ).on( "click", function ( t ) {
					wp.hooks.doAction( "wpopImgRemove", this, t )
				} ), t( ".add-button" ).on( "click", function ( t ) {
					wp.hooks.doAction( "wpopRepeaterAdd", this, t )
				} )
			} );
		</script>
		<?php
		$js = ob_get_clean();
		echo PHP_EOL . $css . PHP_EOL . $js . PHP_EOL;
	}

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

		wp_enqueue_script( 'iris' ); // core color picker
		$selectize_cdn = 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/';
		wp_register_script( 'wpop-selectize', $selectize_cdn . 'js/standalone/selectize.min.js', array( 'jquery-ui-sortable' ) );
		wp_enqueue_script( 'wpop-selectize' );
		wp_register_style( 'wpop-selectize', $selectize_cdn . 'css/selectize.default.min.css' );
		wp_enqueue_style( 'wpop-selectize' );
		wp_register_script( 'clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js' );
		wp_enqueue_script( 'clipboard' );
	}

	public function get_storage_table() {
		switch ( is_multisite() ) {
			case true:
				return $this->network_page ? 'wp_sitemeta' : $this->get_multisite_table( get_current_blog_id() );
				break;
			case false:
			default:
				return 'wp_options';
				break;
		}
	}

	public function get_multisite_table( $blog_id ) {
		return 1 === intval( $blog_id ) ? 'wp_options' : 'wp_' . $blog_id . '_options';
	}
}

class Section extends Container {

	public $wrapper = array( '<ul>', '</ul>' );
	public $classes = array( 'section', 'active' );
	public $title = 'My Custom Section';
	public $dashicon;

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i );
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	private function get_classes() {
		return ! empty( $this->classes ) ? 'class="' . implode( ' ', $this->classes ) . '"' : null;
	}

	public function echo_html() {
		ob_start();
		echo '<li id="' . $this->id . '" ' . $this->get_classes() . '>' . $this->wrapper[0];
		foreach ( $this->parts as $part ) {
			//			if ( $this->rollup_options ) {
			//				$part->field_id = $this->id . '[' . $part->id . ']';
			//			}
			echo $part->get_html();
		}
		echo $this->wrapper[1] . '</li>';
		echo apply_filters( 'echo_html_option', ob_get_clean() );
	}
}

class Option {

	public $id;
	public $field_id;
	public $part_type = 'option';
	public $label = 'Option';
	public $description = '';
	public $default_value = '';
	public $classes = array( 'option' );
	public $atts = array( 'disabled' => null );
	public $wrapper;
	public $field_before = null;
	public $field_after = null;
	public $network_option = false;

	public function __construct( $i, $args = [] ) {
		$this->id       = $i;
		$this->field_id = $this->id;
		$this->wrapper  = array(
			'<li class="wpop-option ' . $this->get_clean_classname() . '">',
			'</li><span class="spacer"></span>',
		);
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function html_process_atts( $atts ) {
		$att_markup = [];
		foreach ( $atts as $key => $att ) {
			if ( false === empty( $att ) ) {
				$att_markup[] = sprintf( '%s="%s"', $key, $att );
			}
		}

		return implode( ' ', $att_markup );
	}

	public function get_classes( $class_str = '' ) {
		$maybe_classes = ! empty( $this->classes ) ? implode( ' ', $this->classes ) : null;
		$clean_return  = ( ! empty( $maybe_classes ) || ! empty( $passed_str_classes ) ) ? 'class="' . $maybe_classes . $class_str . '"' : null;

		return $clean_return;
	}

	public function build_base_markup( $field ) {
		ob_start();
		echo $this->wrapper[0] . '<h4 class="label">' . $this->label . '</h4>';
		echo $this->field_before . $field . $this->field_after;
		echo ( $this->description ) ? '<div class="desc clear">' . $this->description . '</div>' : '';
		echo '<div class="clear"></div>' . $this->wrapper[1];

		return ob_get_clean();
	}

	public function get_saved() {
		$network = is_multisite() && is_network_admin();
		$pre_    = apply_filters( 'wpop_custom_option_enabled', false ) ? SM_SITEOP_PREFIX : '';

		if ( $network ) {
			return get_site_option( $pre_ . $this->id, $this->get_legacy_value() );
		} else {
			return get_option( $pre_ . $this->id, $this->get_legacy_value() );
		}
	}

	public function get_legacy_value() {
		$network = is_multisite() && is_network_admin();
		if ( isset( $this->legacy_key )
		     && ! empty( $this->legacy_key )
		     && isset( $this->input_type )
		     && 'password' === $this->input_type
		) {
			$legacy_pwd = isset( $this->legacy_pwd ) ? $this->legacy_pwd : false;
			$stored     = $network ? get_site_option( $this->legacy_key ) : get_option( $this->legacy_key );

			if ( $legacy_pwd && ! empty( $stored ) ) {
				return $stored;

			} elseif ( false === $legacy_pwd && ! empty( $stored ) ) {
				return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, WPOP_ENCRYPTION_KEY, $stored, MCRYPT_MODE_ECB ) );
			} else {
				return false;
			}
		} elseif ( isset( $this->legacy_key )
		           && ! empty( $this->legacy_key )
		) {
			return $network ? get_site_option( $this->legacy_key ) : get_option( $this->legacy_key );
		} else {
			return false;
		}
	}

	public function get_clean_classname() {
		return explode( '\\', get_called_class() )[2];
	}

}

class Section_Desc extends Option {

	public function get_html() {
		ob_start();
		echo $this->wrapper[0];
		echo $this->description;
		echo $this->wrapper[1];
		echo '<span class="spacer"></span>';

		return ob_get_clean();
	}

}

class Input extends Option {
	public $input_type;
	public $password = false;

	public function get_html() {
		$option_val = ( false === $this->get_saved() || empty( $this->get_saved() ) ) ? $this->default_value : $this->get_saved();

		$type = ! empty( $this->input_type ) ? $this->input_type : 'hidden';
		ob_start();
		echo '<input id="' . esc_attr( $this->field_id ) . '" name="' . esc_attr( $this->field_id ) . '" type="' .
		     esc_attr( $type ) . '" value="' . esc_attr( $option_val ) .
		     '" data-field="' . esc_attr( $this->get_clean_classname() ) . '" ' . $this->get_classes() . ' ' . $this->html_process_atts( $this->atts ) . ' />';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Text extends Input {
	public $input_type = 'text';
}

class Color_Picker extends Input {
	public $input_type = 'text';
}

class Number extends Input {
	public $input_type = 'number';
}

class Url extends Input {
	public $input_type = 'url';
}

class Hidden extends Input {
	public $input_type = 'hidden';
}

/**
 * Class password
 * @package WPOP\V_2_8
 * @notes how to use: echo $this->decrypt( get_option( $this->id ) );
 */
class Password extends Input {
	public $input_type = 'password';

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );
		$this->field_after = $this->pwd_clear_and_hidden_field();
		$this->password    = true;
		if ( ! defined( 'WPOP_ENCRYPTION_KEY' ) ) {
			// IMPORTANT: If you don't define a key, the class hashes the AUTH_KEY found in wp-config.php,
			// effectively locking the encrypted value to the current environment.
			$trimmed_key = substr( wp_salt(), 0, 15 );
			define( 'WPOP_ENCRYPTION_KEY', static::pad_key( sha1( $trimmed_key, true ) ) );
		}
	}

	public function pwd_clear_and_hidden_field() {
		if ( isset( $this->legacy_key )
		     && ! empty( $this->get_legacy_value() )
		     && $this->get_saved() === $this->get_legacy_value()
		     && ! isset( $this->legacy_pwd )
		) {
			$hidden_val = ''; // when importing legacy
		} else {
			$hidden_val = $this->get_saved();
		}
		ob_start();
		echo '<a href="#" class="button button-secondary pwd-clear">clear</a>';
		echo '<input id="' . esc_attr( 'stored_' . $this->id ) . '" name="' . esc_attr( 'stored_' . $this->id ) . '" type="hidden"' .
		     ' value="' . esc_attr( $hidden_val ) . '" readonly="readonly" />';

		return ob_get_clean();
	}

	public static function decrypt( $encrypted_encoded ) {
		// Only call in server actions -- never use to print in markup or risk theft
		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, WPOP_ENCRYPTION_KEY, base64_decode( $encrypted_encoded ), MCRYPT_MODE_ECB ) );
	}

	public function save_password( $network = false ) {
		// overriding default
		if ( $_POST[ $this->id ] === $_POST[ 'stored_' . $this->id ] ) {
			return false;
		}

		$pre_ = apply_filters( 'wpop_custom_option_enabled', false ) ? SM_SITEOP_PREFIX : '';

		if ( empty( $_POST[ $this->id ] ) ) {
			return $network ? delete_site_option( $pre_ . $this->id ) : delete_option( $pre_ . $this->id );
		} elseif ( isset( $this->legacy_key ) && ! empty( $this->get_legacy_value() ) ) {
			return $network ? update_site_option( $pre_ . $this->id, $_POST[ $this->id ] ) : update_option( $pre_ . $this->id, $_POST[ $this->id ] );
		} else {
			$encrypted        = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, WPOP_ENCRYPTION_KEY, $_POST[ $this->id ], MCRYPT_MODE_ECB );
			$base64_encrypted = base64_encode( $encrypted ); // base64 is req'd. Without, storing encryption in option value isn't reliable cross-env.

			return $network ? update_site_option( $pre_ . $this->id, $base64_encrypted ) : update_option( $pre_ . $this->id, $base64_encrypted );
		}

	}

	/**
	 * Fixes PHP7 issues where mcrypt_decrypt expects a specific key size. Used on MYSECRETKEY constant.
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
}

class Textarea extends Option {

	public $cols;
	public $rows;

	public function get_html() {
		$option_val = $this->get_saved();
		$att_markup = $this->html_process_atts( $this->atts );
		$this->cols = ! empty( $this->cols ) ? $this->cols : 80;
		$this->rows = ! empty( $this->rows ) ? $this->rows : 10;

		ob_start();
		echo '<textarea id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->id ) . '" cols="' .
		     esc_attr( $this->cols ) . '" rows="' . esc_attr( $this->rows ) . '" ' . $att_markup . '>' . stripslashes( $option_val ) . '</textarea>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Editor extends Option {
	public function get_html() {
		$option_val = $this->get_saved();
		ob_start();
		wp_editor(
			stripslashes( $option_val ),
			$this->id . '_editor',
			array(
				'textarea_name'    => $this->id, // used for saving val
				'drag_drop_upload' => false, // no work if multiple
				'tinymce'          => array( 'min_height' => 300 ),
				'editor_class'     => 'edit',
				'quicktags'        => true,
			)
		);

		return $this->build_base_markup( ob_get_clean() );
	}
}

class Select extends Option {

	public $values;
	public $meta;
	public $empty_default = true;

	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$option_val     = $this->get_saved();
		$default_option = isset( $this->meta['option_default'] ) ? $this->meta['option_default'] : 'Select an option';

		ob_start();
		echo '<select id="' . $this->id . '" name="' . $this->id . '" value="' . $option_val
		     . '" data-select data-placeholder="' . $default_option . '">';
		if ( $this->empty_default ) {
			echo '<option value=""></option>';
		}
		foreach ( $this->values as $label => $value ) {
			$selected = ( $value === $option_val ) ? 'selected="selected"' : '';
			echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
		}
		echo '</select>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Multiselect extends Option {

	public $values;
	public $meta;
	public $allow_reordering = false;
	public $create_options = false;

	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$save = $this->get_saved();
		ob_start();
		echo '<select multiple="multiple" id="' . $this->id . '" name="' . $this->id . '[]" data-multiselect />';
		$ordered_vals = ! empty( $save ) ? $this->multi_atts( $this->values, $save ) + $this->values : $this->values;
		foreach ( $ordered_vals as $key => $value ) {
			$selected = in_array( $key, $save, true ) ? 'selected="selected"' : '';
			echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
		}
		echo '</select>'; ?>
		<?php

		return $this->build_base_markup( ob_get_clean() );
	}

	function multi_atts( $pairs, $atts ) {
		$return = [];
		foreach ( $atts as $key ) {
			$return[ $key ] = $pairs[ $key ];
		}

		return $return;
	}

}

class Checkbox extends Option {

	public $value;
	public $label_markup;

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function get_html() {
		$checked = ( $this->get_saved() === $this->value ) ? ' checked="checked"' : '';
		$classes = ! empty( $this->label_markup ) ? 'onOffSwitch-checkbox' : 'cb';
		ob_start();
		echo '<div class="cb-wrap"><input type="checkbox" name="' . $this->id . '" id="' . $this->id . '" '
		     . $checked . ' class="' . $classes . '" value="' . $this->value . '" />' . $this->label_markup . '</div>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Toggle_Switch extends Checkbox {
	function __construct( $i, array $args = [] ) {
		parent::__construct( $i, $args );
		$this->label_markup = '<label class="onOffSwitch-label" for="' . $this->id .
		                      '"><div class="onOffSwitch-inner"></div><span class="onOffSwitch-switch"></span></label>';
	}
}

class Radio_Buttons extends Option {

	public $values;
	public $default_value;

	public function __construct( $i, $c ) {
		parent::__construct( $i, $c );
		$this->values        = ( ! empty( $c['values'] ) ) ? $c['values'] : [];
		$this->default_value = ! empty( $this->default_value ) ? $this->default_value : '';
	}

	public function get_html() {
		ob_start();
		echo '<div class="radio-wrap">';
		foreach ( $this->values as $key => $value ) {
			$selected_val = $this->get_saved() ? $this->get_saved() : $this->default_value;
			$checked     = ( $selected_val === $value ) ? ' checked="checked"' : '';
			$echo        = ! is_numeric( $key ) ? $key : $value;
			echo '<input type="radio" name="' . $this->field_id . '" value="' . $value . '"' . ' id="' . $this->id .
			     '" ' . $checked . '/><label class="option-label" for="' . $this->field_id . '">' . $echo . '</label>';
			echo '<div class="clear"></div>';
		}
		echo '</div>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Media extends Option {
	public $media_label = 'Image';

	public function get_html() {
		$empty        = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAAEsCAYAAAB5fY51AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABV0RVh0Q3JlYXRpb24gVGltZQA4LzEzLzExa0nrpAAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAACAASURBVHic7V3PjxxXEa61op0L4wNeH9i9eC/+EWEbKWsj5ZdkQAIigZP/Aok/ByFx4JiA4AD2BQcJkUgmkSBGytoIryOBc/HmwO7Fk8vsZTlEPZkdT3e/qvrqx+udT7KU9Kuq97r7vW+qvn7bvfbgwYNjqhhra2sQG44d11Zij/avpc8Gx8f+01LbJ9efY19qW2IXcW1ReCl6AFIMnag8yCKSkPrQNzaLRbfYJ7ePxr/Uj2Nfaltixx1nJlRJWBFk5UFUVgSSmZikaDsn5CKc74MT15q4UHalsTKhKsLKTlRZsqkhElQptFlSX1wJcZX6IbOoUrvasq1qCAtFVlmICkkqp5mg+oAmMI+sy4K4hpJtVUFYfQsSSUIZ9SyrOFn6WQarxYMkMAl5WREXKtvKTlqpCStrVhVBVKdN3+oaVwadqi1OlG6FIrfsJWJawsqYVdW8lSErMUlgJbgjyMuKuFbZ1ldISVgIsqqVqDJnY9mx7Ly1xCONEUlcyGwrG2mlI6xaySqSpE4rQZUAoVlpyEvypNBz20JtpJWGsE4bUdVCUhFkaLlAUJmTxD/rtoU+m0y6VgrCykRWmYnKgjwyZmd9Y0LvrZLGlC7krNsWasi2wglLS1beRMW19fbxjBcFpFbVFtNyZ/u8H2p7A9ImM2mFElZtZOUlvq+Edz7QJCYhIUnGZrG9wTqTiiStMMLqWlAIkqmNqGrRtGpCpOCe/SlhCSl1xYgirRDC0pBVRj3Lyx7tn60v6wUQIbhbEpfHJtGuGBGk5U5YlmQ1dKKyJI0MGZqX2L6sr0yvkim1z6BbeZOWG2FZk1EN4vtKeNehbeyIBZOt9Ju3z65bSR86SOBCWNFkFZ1VRRFVzeTEQSbBHUlEnNgeTwAziPHmhBVNRpFZ1ZA1reyIEty5T/24saOfAEaTlilhachkRVT2vtn6qWGHezRxIbItRHsUaZkR1lDIKgupoXw9Y6LHkGGHO9fXkriidasI0jIhrMxkZUEqHkSFJJQM5CQBWqtajGn5pBBNRqUxh0ZacMLKTEZoAloJ7/FAkpjHk8JSWyTBRT4hRJMWlLCsyCpbCZhVz0L4DwFDEtw52Za1rpVhrxaMsLKS1WkgqiEI7yvBXbdlgRMrogREkRaEsFZkxbeT2mv9vGOi+s4kuFsRl2e2VStprbY1AGw4dlxbhJ9VHE+0jRmxQZQbh+uHzpBKY2UkLS3UhGWx+CyJjmMXXSZKfSxiZAVCr1qMk0FwR9l09ReRLWnJTEVYUuKwaCtpR9pY2Ent0f41oxbBPUu2pSE1i7Y+iAnrNJNVRqLyIKkaRfesgntEtmWRTXmTloiwhkhWtRHVSnTX92FV+nHsUZnUaSGtMyxrWpEVyq6xlZAbStda/JcZFuP1uP6emqp1jIj1vQhWhpXpZLzKOyui4gC1OIcGhF61GCd6k2ifXUmWVBKjyyZzplVMWJkIKcuvUamNta2Ff41AEBiXvKyIK7pEzNQ2D9NtDbWSFTqr8iAqL4Ky6MdDcJf0Y0FGjW30rvUSm+hsahmKCKttkp5WsspCVBbkEZGZ9fUZvcs9qvxr7E4LaZWMtZewhkJWniVgLURVS9m4bJxaEuOQkMQHRUglNhpNqsQmE2l1EhZ6MVmRVZasypKoVprWSaBITJJ1oYkLaSPdIFri71UCdvm0bmvQZiuoeEMjq7U1/mNxja6l8a8N2vO1vDde88yyPQMnsEV3SSe1klVNepbGL7oPC9F9cZyS0q/UD6lbaTMh63Z0mcfNwJYSFpJ4MpLVSs/yjyntb6iCe7RuVQNpLTv+AmGhiUfiE0lWp4moaigV28aoITIOCUl8MulWEaSFjLd4/MxiIxeeWVeJb01kJdVMEJpWDWTVBcS5SPxr060s10REchOyD0vqk1nvKrXh2EnttX41QqNZzfujnxTWoFtpY6P6K+lnRlgSUmoDmpWlMb3akTYSW4RfRD9WO9yJ9JpVqZ+3bpWNtLzKxub4S83/tDl3BfbwkcZE+GrjW9ppfSJicvuoTXD3+nObrhi1kJbUB/4+LKRPn19kVoa04dhpfSzjoLFsXJl3uKOyLS2xWZKWJCbS5yVu1uMpwlm0ebSX2nDspPZo/2igSEySddWkW2kyMXQb0mfpTnfkr/2KrNptuJqWJkvV+GeH9vys7oWH5GBVZaDbUJwCeYHfEEpEj3auHddW4yOBph8rwX1xTFaaVWPvpVtl2k8l9ZNkYItQbxy1YFt0tmbpi7aR2Gp8PONxYqMF9+y720tKuL52NGlJYyL7Wjz+0mIjCmiy0vhEZmWlNhJbib1VDDTaxiQlMkvNqtTWQ7fy3E/V1S7N3Pr6KPoIBTLrkvSj6asWsuLoKCjNpkZdCzF2rj/XVmsTMac1MVF9lfQzIyxUcOmJe16wkpiI9tLJa7Fw2nxrI6g+oMgLaVtiF0la6JjIRKPveOfGUSS8icyKHFFZlWWZKPWRQNJP7YJ7qW1JOdUVp6S9hjYU1tZ6No56ZF2S/iPaEO2lNhJbjY9HLG5cxOTnkJDEp8S21MZCt/ImnzY/1HEiojNZSek0kxW3xEGUe/MxMpSOyPFIYnBLRa2NVQnoVcr1AcUn7C8/c4G+KDWSFVL70Ngv881ATqVAjNnqGpfYnQbSsp5LkDeOep1YlhtW2o60kdgi/DJj8ZxqeCtDX5yS9gwloCQe4vgLGVY0WQ0p64osE+d9hkhWyyA9X4vy77RnU1Y8ov7yMxJDIqs+IBcIwicqvvUTw+xvZahBbJdkYFYo2unukV3VUCIi2pE2GnvveJJ+kE8KOfFQhFRik420uOPwLg17RfesulU2siotAyw0LQS5zJdTWUpI9Ji4cZDln+W8Q7Z5JQ7S/nt3unsg+uKVtGmJGDX5JbZd/pkIqg+oMVtcZ62NZo5lSBKs0fR9pmsgkaUg+tchskREZV5c2zbfWgiqD9rzQV/3Uhtpu2c2lXHtr60JXpHsRTDcWFIfa7LqA2fBSGBFToi4SCF3fjwSfabUb22tTt1KOhaOj2Rs3P7Zr0j2QPasy6Oda6f1sYwjia8hMyl5lRJXiV020vIgEyTa+ma9Ijk6HUT1oW3TtPfF58RB+Cz6ZikbUeOxvI7aErGkHdlW2zpddlz9pzledbCkf4mPddaFsFlmL70OGcipFN7nirpfmnbNfLT2QScKJVDvdPdA9qwL0V5qM28rzRxqIqk2aM5FQlxaG2/SQvtYo3RMqp3u0Skmqm9Nm0e71Fbj4xHTQnSPfpVMLboVdwyo49JxNVDtdG9DVlLixiqJp01/rcgKRVKWv7ptsb0Fd84CKiGdrr6tSIsbL5LMNPHhO92R8Kido8iKU5ZY2bb5RpeNqHFEXmPveeVR7SAh7Ru+0926FPQqiaIm5KKdJVFlIKdSaMZqSVzSdqRMYOETuV674nRmWFElogReWZc0Zmm7xI5bKtZAUH2QnAeSjErtLOaKxZzmIJIXoB+hqCVLk/pIY5a2l9pw7KT2lnHRmxGbMVi8jaHUzkqXamuXxEQd5wIZp/UpYRSLZi0R+/w0RMax42YUCKDJriuep+COIqSSWBrS0oyr1CeK5LhxWv80py24pC0CyKzLqg1tI7FF+iKwrH/pYi71LbFFkJtnGyqrQQFF2Kw/zekaTM3HI9rQNo2dNOPMrGtpxofWrbQ2nm21HG/DMvviP82pRZ9Cl4iRZFW62CSLOTtJtUE6buS1zEhaXJ/I9aaJ84KGZZ1dRSFL1lVqgy4TJfYWcaNfKYPSrUrKmFUJiNe4iv40p/bsKhNZ1UhUSKLriyVddKVExLHvsymNEU1aUce5KIlzoiTMlF0NrUREtJfaNHZa3SeiZNT2zfX1yIaHVAJajqUkjvmXn5d1WnLcGp5ZF6odpcF0+WUs5bXnhLDLRlo1rRskXL+aYxU7c9bl0c61m7fPSFJtkI4Xdf2iSIvrk2n9oGPPNCzLSVtLiYjOuix9JXZcW4uYKGF4vk/0JtEuu5J2tKaF9JH0UQpEjL7YnaJ7Jua1Ro1k5UlUqPvSFcdDcOcQl/ef23S1WZJBFxDjQZJn52e+EKghu0KXiH3tnmSF0LS8fkS0fXL8ENfYI3su8YlcBxYxumK3alhWOkGXbQS5WcSy1rtKFxv3nngTVB+kY0Jeo4h77TlXvWKj+ORMBDEh+rMuUaTxpRMYsXhK42jsI2FJXJo4GtLi+mWd99IY3P5M/5aQY2sZA5kae7c17ajMa94WSVSL2dCyfxZ9cewRNhnaIkrALFkW6yMUWbIr7xglsSzJSjMuiV2Ev5fgHiW2S9u4QMTyjsGxNf0uYamtdwxJHO8SsbTdokxc9PMoG7V9Ia+FVaYliRk1371jlMLku4RZ6mCv1BldWiLaGxvudfQiKKuxcIjLqt1rHkWUgBxYrGFVhpXlpCLhTVYoPUZjHwGLc+qziSCtCGQmskWE/y2hla3Hr5K0dJG2IzKHeTskUS1mRMv+ofsptdfYIDJdRH/ZsywvIoZ/SDWDLTcGmqyylogcO7Rvn7/la5BL7NbWukXxLv82376Y1n9uwwEnhrft/HFxhpWBmDKm3VnJSpLlWGRIFn2V+lhe56HMxYy284B+SDXbyZXEQBKMxeTsG4dWp2mzj9ZaJONAXA/Pe+hRGnJQw/oVv16G25GXrfWN8yofNW0cm3nbaJJqg4S4NDaebdbkmTnLkviLSsKsJMaN4bFAI8iKUyIhiWqxrLOMX2rbZ+PZhkKNWRbKH/a2hgwkZh3Do6zUtJW0NzbS+yUlJTSZcYhL2u6VTaEzM88YXv5NjM4Ma1kn3tmRFshfd8Rxq7aS9lKbRXt0poTs4zSQFge1rTkux0D2YWWoh71/caSwWhyoMnHeNmryc/tHnP/QS8DabNvgtnG0C5kvUFecWrIu5ML3hgVxebXVkGXVVjG1vg8rumbNnl0hr1tkidjYZZiMXeASl7QdnU1lmm81aVZt/urXy2S9CNk0Ac+sq6S91AbptwzcHdpN39leE9PXXyki4mj71PbF8Q8vCTOQWLbj2jZkmThvb5GJSWNn1K2yHdfaWvhrAXnj6ND8o/vMoGdJ7BGwIi50Gxc1zsmM/sUZlkXnGQQ/a2E0Q9ZV0j5vl0HT4o7Dm7Qyzg8NtGvRi9zC34eliZmB8Lpi10RWGUiqDaVjy05aEXPQ09YjJkt0L0H0CUZoWtawLGmk54S4FhaCe5/N2prPu9U5aOuXe5wTW2tbCnTMolckR9ey2pg1Z1dZyGq+RENdT2lM7blFZlM1ZlleMUu4x+V9WKX+nhkMSmtAxJDE1paIXE3LA+hxaQm91MdybqDsNciUxEC3NUSztvfEsf4VRWddJe2NTaSmxenfgrQi75/FWGrPvOZhvg8r+gJkueHRJWJJe6mNJ7KRlmUJmGWuevUvQe873Ws5Ke/MaEglokWffeAIsU0/VmJ7V8zM71BHxNDAK+b8sZAX+NUU8zSUiFzdCFEuSmJZ6VanuQSsLSasJPQ6oSixzytGthKxsbNO9bnEJW2PKPW4Y/GMUVtVZaphZdNDumA11qgMDSm+e8JDbOf6ROtClnEtYDlW6FdzSpCZvTVxIya1dIEhy0QraMu/rnbr6+9py0FNa6+tH/Yrki0Hg+zbKtX1nqTorKuvXEJrU6h40vYuP8RxT1tOjKzrR9p3A3ZJGPnL6/nLU9sE7WuzKqFKyAlBYhbZVG0/YDVlXlZ9u7/THQ3PvrNkV4g+pX7ozMmD7FHXMfOPWMa+LPo2E92zppSRiCoREVmGpa7FiY0+z8xCuxY1SzptqP6No9p+LGp9T39N/JK+PQX40r6syzxp35H+0evIC7APqSIRmZ0h+veelBb6jUZrQuhVGs3N4/qVIKtmlbH/EqytdXxItYYTqAnegqmWrEr7thTdPUlLi9XakIPDNeEloQbRpBr9C2zRp1eZiCj/Sto547GwtfDX9lUzuaoJq4YLwvm1tZiM3k+XpNmFVZmojSlt9xbUo+dTDetOO0a3DKsGYmuDxTi9sytteeUhZnuUsagY0XMiEpFrOV1JWPNN84yJ/JXVEEWbj0ZwR49FGyv6Xkci2zjTEdYyZLtobYie2GgBnqNntZGURHBHj3dFTmWoYZxVENYy1CJ8Zp4E2hJMem5a3arWa5qpn8zXsAtnataWFuEpeGuQQZiX+iD1LK1upfWJFtQ1fXNsh7Se3b78XCsiz9uSmNDlowbWZR7XvpZ7PiSUnne1JWEkav61ldp7PCVE9Z2RnIaU+URC9eXn1U3oRkYSs7Ivsen7YMHaGu+jBtb2Unj1UwOWXQvN9XHJsFbE9jW8roVHidi0l/alEdutSVkaczWPu4+hkaokXE2IrxGhF3FsLZ8SZijpVvOu+1gUUhGWFzLdAA6yLE7kU8KIfr3iWqPWcWtQJWGt0v2v4b2I0f159WMdF4msskEGqET3TKjl4n/88cd0eHhIFy9epEuXLs2OHx4e0kcffURERD/96U+L4929e5eIiG7fvj07xiGB0ut2//59Ojg4oDfffJM2NjaK4zeQiKx37twhIqK33377RB+lIu5K/P4aQ7kWgyGsZcj4q3R4eEj7+/v0rW9968Tx6XRKX3zxBTve/v6+ajzLsOwcDw4OaH9/n6bTaZF9mw2HWPb39wexyLpgQSRDIadlGDRhecEjhZ9MJvTll1/SxsYGra+vF9lPJhNaX19vzYgastvY2KDRaPRC+7Nnz2g0GrX6E714ns+ePeuMuba2Rs+fP6fnz5/T2bNnaTwet8Z+++23Wxdec37nzp2b9bO/v996vs25njt3bun129/fp9FoROfOnWsdT0OgJfcA/Th/ha+wIixHcIntV7/6FRER7ezs0IMHD2bHb926RZcvX17qM51O6f333z+ReY3HY/rxj39M58+fJyKivb09+tvf/kZHR0czm+vXr9Prr79ORF9lU3/6059oMpkQEXUSVoPHjx/T/fv3T2Rg3/nOd+iNN94gIqJf/OIXRET0xhtv0P3792c2N2/epJs3by5dzHfu3KHj42P6+c9/TkREv/zlL1+4Huvr6/T973+f7t+/Pxvv5ubmrIzc29ujjz766MS5Xr16lV577TUi+irjvXv37mzcDWEdHh7ST37yE9rc3KTPP/+cPvjgg1mM4+Njunr1Kr366qtEtCIiT1Qpug8diyT28OFDunr16kzz+uCDD1pLwbt379L+/j5tbm7SjRs36PLlyzSZTOju3bs0mUxoOp3SX//6Vzo6OqJLly7RjRs3aDQa0e7uLj1+/JiIaEZWTYzpdEoHBwet45xOp/SXv/yFptMpXblyhW7evEmj0Yg+/fTTWcwGu7u7dPPmzRnh/uMf/5gRTSn29/dpZ2eHxuMxHR0d0b1792hjY4OuXbtGo9GI9vf3aW9vj6bT6YxoLl26RDs7O7S+vk4PHz6kvb09IiK6d+8eTadT2tzcpJ2dHTo6OqLDw8PZ+U0mE/rzn/9MRETf/va36ZVXXqHxeEyPHj2iJ0+esMa9gh6rDKsCfO9736MLFy7Q2toajUaj2YLb3Nw8Yff06VM6PDyk8Xh8QqgmInry5Ant7u7S9evXaWdnh86ePUtXrlwhIqKjoyPa3d2lyWRCT58+pclkQhsbG/TOO+8Q0VfZ169//evW8R0dHdHNmzfp7Nmz9PLLL78Qcx4/+MEPaGtri46Pj+ng4IAODg7o+fPnnaXhIt566y1aX1+n8+fP071792g8HtNbb701y3IePnxIk8mEjo6OZsTWEOT8uD7//PPZuTYPOq5du0bvvvvuLJt6+PAhERG9+uqrsx+Mixcv0m9+8xt69OjRiQcnK9hjRVgV4MKFC7P/3t7eni3IRTRZ0GK5ePnyZXry5AkdHBzQeDymK1eu0O7uLt25c4cODg5OlHFNjO3t7dmx0WhEW1tbM31qEePxmF5++WX69NNP6Q9/+MMLMeextbV1Im4flpXMo9GIjo+PZ/7zZDcfsyGqhw8f0t27d+ng4OBEafi///2PiE5e30YDazLYJtv68MMP6cMPPySirx8cNG0r+GFFWKcMk8mEfve73xER0ZUrV+jGjRv09OlT2t3dVcX87W9/O4v53e9+l/7zn/+oYiIwmUzo97//PRF9Rdpt5zpPYm24ePHijBhXelUcVhqWMxpR97PPPjuxUB49enSifR7zelWjvSwTwptjjc0yn8ePH9PR0RFdu3aNXn/9ddra2jqRDTWL8unTp7Nj0+m0NbsiIvr3v/9N0+mUrl+/Tm+++eYLMaOwt7c3O9fXXnuNNjc3T4yreQjx9OnT2b1otp00aO7HeDymnZ0d2tnZoWvXrtHm5uaJLHQFH6wyLGdcuHCBHj16RJPJhN577z3a2NiYbVlo2hfx/vvv06VLl+jo6Ggm9C5bLNvb23Tu3Dk6PDykO3fu0NbWFk0mE9rb26PRaETXr1+n//73v0T0lTaztrZG+/v7J8hoe3ub1tfX6eDggP74xz/S1tbWCwS4iKYM293dpbW1NXr27FknwXGgyWaarQeNDrW/v3+CjC5cuEAbGxt0cHBA77777olSsMH29jb961//on/+8580mUxoPB7TkydPaDKZ0CuvvNK5DWIFPFYZljM2Nzfphz/84ewJ1xdffEFffvklra+v0yuvvEI7Ozsv+Gxvb8+eSo1GI7p169YLgnuD27dv0+bmJu3v79Mnn3xCe3t7NB6P6fbt2zNN59y5c3R0dESffPIJTadTunHjBhF9pV+NRiN65513aH19fRZjPB6f0J4WceXKFTp//jxNp1P6+9//Pot5fHw804mQWEZiy47Nn+uDBw9oOp3Orm+jP92+fZu2t7fp6OiIDg4O6OrVqyeubXO/vvGNb9Bnn302I66rV68uvVcr2GLt0aNHL9zp0v1CNR7LNJ7Dw8NZKbJIQGtra7N9WD/72c9aN4629bNs4+i8bSOuN2XRsnHObxwtuZZNzL59W21ZUykRlR5rji+Oq7FtSGo0GtE3v/nNmc97771Hk8lktg+rQdfmU/TYEec9xGOrktARx8fHJxZ4U06U7Iofj8d09uzZ4r7G43HnVoGSzaDzWdXi2KUx22ApZLeNazqdzv4W8+rVq7M9XA3RL/6IbG5uskhjBTxWhAXAssVcssCHAO55Zlrc4/GYbt26RR9//PHsoUdz/Ec/+pEq9orYbDDokrCWmG3HmhKk+aX3GrfkeF9b12LllDVtx7W2zUOC+b8nHFJJZxFzVRIqMMSMpk1Y70PbtbDOBD0yCO0CbYP0WteCoWR3VT4ltLj4tabwVmNEkECmfqzjIuE1F2u4FouokrC0qPFGEeVZxKhxRPXrFdcatY5bg1SEVWuWY4EMi7NPd5KOsc8XNUYNVvOu+1gUXAgr+0XwRNZ0X1qacYirxBZVIq5kA1tEXQuV6H6aH+eXwOtaSLYWoO0Rk9WalLzI5bSS2DKgiS1VSVgLIn9pLcslVJkmAbLvDCVlST8rYuOjmLBO68WNPO8IcoogLeQeLUkf2thIrNZZN84Mifm151LLr631OVmJ7dxY6LGjbWvJqoe0nqstCbU3IeNk84aGLCyfEmrHFola5lXma9iFKgirlosb/bQPXeaV9N2Qz/w/TrvHeKOz7NM8f9FIR1g1XDSi+ImN0nb6fKTxpBmYV4m4IrEyZBunG2HVXFtHT1iPLQNeupW0D834OWOwsI2MaYHItawmrBqIiPMra6ENeAvq0qzCUrfSxJS2e2dX0fOphnWnHWO6kpCDaLL0FD6RT+a0/SCIC6FnlbRzxmNha+Gv7SsbsXHQSlhDO9FoWF07tNBe0j5vVyKoS4R37Ti9s5DV2pCDwzVnuhqjEE2WnmUdxx9Z+liVf1Jy4vaNJCsrcosuJzmI7r8Ex8fH8SWh10XJMCkt/DXxvcq/UiDKxMjrGekfvY68YEZYkYwdfVHbECWoIxa4JXGhykSPrEtr64nI9WbVN4SwIm9YdNpsacs9N+m14I4VKbp7EAPqOnrffwvUvlbZhFX7CZfGzTw5LcRmS70KpWtJ2q3LR895MsQHBty+Owmr5pTS6oZnybIsxGx05oSMJ23v8kMc97TlxMi6fqR9NzhTYuQ1mEwxuXEjSgCrbMpTaNeMwSLr4iD7/PCI680bpk8Joyc9BxkmCTqb0rQ3Nt73sLRPqxJXGyvDPIqG5VhhhOXF3hapbqYYyFIFtag9iIvTh+a8PK57KTLE8Cr9UDFFhJX5hNAxozIkVHxk+WehTUn677PhtkWX6OixZF1LiJgnPkJxfOzzUYnomG22iBgcIMZR4tNMjJJPyXM/TuGBmktEbmyrcWQhHEnM+WPmO92jL1T2yZWtBMymlViTVeT90tgiYkSvTQmghBV9AbLc8OwlYEm/EYK7pP9ViYiNEb2G+yAmrFrEPk7/SHtODGS5UhIPrRuhkEHPksSL0J2i14rF+i/BC4SVaXComJY3N2sJiC7/5rMeZAYiiak9t1pKRC6i14RHEqP68nNbpxkFdY4t97g1uvrtayPCi+3zfp6IKhGtkUUXy0hii1BpWNEnGH0z+mJnyaY4ulVGROpZXW2nIbuKXuOLcPvys7bUtLrZ1pOuJtJq7DKQF3cc2ckqKr52HKW2XrLRUsKK1qyi/aP7tCQtiW7lCYsxWuqApahxTmb0r/6No4gsLdtxbZsVcVkQmDQ24jyjs66IUjOahLRgie7Hx7qd8Bxbrb+2L684XfGlbSXtjQ1RHYI7t+8sJSIXEXGikwaO/xmrGxB9Ebi21tqDJJamjTOWDJpVHzLrWZK+IuZbVhLj+IeXhER5L3pfnCwlYBe4Cz0bcaHHX2OJyEX29aSB+zvdM9t63JAadCsrvaoU3P4z6llIZM6uvAmP/Yrk2hg5W5Zl1VbSXmqzaG9NYJo+EOecOeviorY1x+UY2IdUM2hW1jGGRFrS+7VILqVEI/Xri1diJ23PQFZRc11js+//KQAADcFJREFUa+HfxBD9ac7xcV1P+9piIGJL+7Zqa9qJ+p/+zU8i1JNQS6AX2GkuEbmxLfqT+PdqWBkyJ46t9U3Lkk2hyz/Lkk8L7tgykhV63pTCO7uy5gvoV3OyXoSuGFFpein6xlFainH6y0BeknEgrofnPfQqK0tRw/p1eR9WjVmWFBa/pNr2xoZ7XRBak0dfHnpWX/tQ5mJG23n0vtO963jbQKJtuTEk5y1p6/MhateS0LqV5Fp2TbKSeOjFWxrPskTsavcqH4eSXZWcn9vG0SxZlkdp2AWLX3HJmNCZ02KGtOwfup9Se40NIrtF9Ocxn71sNQh/H1bGi8KBdwmI0Gm09hGwOKc+m6GViG3wXoOaGEWvSOZ2koXIvH6VvEmrpL2xkSzyDAQmHUupveX195pHWbIrRIzSMatLQktisYohiSO9gdakZUFci34eBKbtC3ktrMt2jl/UfPeOUQr162UQttljlMSyaCtpL7Vp7Ijkm0S7JluE6M6NaZ15WbRxEV2ySWJwbCFvHM3C0h6pc8SERegxy2zRC6Xvn0VfHHuETYa2TCWiZXa1zL71fViITi2ZGrUAspSAiFKlNI7GPhJW56YlswwlYqb4lhzRqmFlz7I8YktjabOpvnZL4spEYNIxIa9RxL32nKtesVF8AntbA7djRIyI1LjUx5K0Sm0aO+n5eROYtk9umai1sb7HpT6R68AiRlfsTtH9+NjuQ6OIGEhIxtM3VivfeRsinuAdJbiXxJHC4pc+iqysS0EuLLMrSeyX5o2siAIRG0WeEhKxJC2i7j/H6Wrn2s3bltqXIGIxWUkWliViX7tkAZ+m7IpI8XqZTMwbXQJGTnKJ3by9Z8mnhXS8NZOVJF6m9YOOHf63hDWlutGkVaq7aDWrTPDQtErsoshqCOsGiROEhfrlskwvM2dTHpkUJ1tAkJf3wtD2zfVFXHNvspL0lalE1PBM0U73Pp2G07GlDoU6btXWtBPpdKsSG419m38bokV3SVwPouqz8cq6MpWIXSiJ8wJhcclJQgYRyERaJTalMYj4xMXx4cb1BDp75tjVQlZRsMiuiBgaljWLDq0ERP2CozSYNp9sE70P0nEjr2U2spKMo8bsisj4bwlrOR7RhrZp7Dx0H29oxsfx87hfGbKubMfbsMz+JU7ppi2DPJGlBESVdpzyj1sqLvOdh+d99f7FLrVFkF4GsooCKnNsFd1R2hQ3Duq4ZEwav742orINoBk3iXZNKPS74TVAVQYSO00ZKb0eQygRuXFeahotnwKi4iDJTEqAmmwKSUjcLEqTdZXEjUQkUZXYacgKKW9k16dK4nSK7lEsKoHHjdXELG2X2HHLn8x6VSkk54HWs0rsspOVBJG8AP2QqmQQHqxv4eNFWlbENe9TA4Fpxmp1HTPMESufrFka/G8JkUBeHKQPyjcLcS36RpMYahyR19h7XnmUiEhI+4Z/SFUShxvfK1ZJvD5fov6nfxbvyZ+/8RaCO0qrRMM6M9FmRpZEx/GJzMY08VkfoVgWCCVqR5OZFWmVthPZbVmQ+JTGzIJooiqxsSKrjKSEJt4GJt8lRCNDCWj9y1pqM28r+QWLLvdQ0JwL12doZFXzGjf9LqFH7Yz2sSYthM0ye+l1qIm8vM8Vdb+8yUozFo6Pd3ZFxPzTHA82z55N9U1QxAQujYPwWfTNQmKo8VheRy2ZlbQj22pbp8uOt/5pTttxD0jGhPTpa0O1E+E3iM77cP264iwiq+iuiVvqtyoR7dHWN1t09yITbv9Sn0jSKrVp7Ij4RIEir664GaAZD7dM1NqcthJR6rMMnZ/5ypYOWvho2krbETYS2zbfbGQjhWe5iLqPEWRV23rtOg7f6S4BMiW1uuHaX7ZSGw/imvevicBQY7a4zlobzRzzyroycITrV3Oi009NW4kvKpPiliiIiZSRwNBj4sZB/chof/CyZF0e/fT1r9rpzj0u9WmDpB9NG6KdY0MU9/rjrolTkxaGXoBcu5pKRMk4vErBBqqd7mggCdCqDdHe2BDZPiW0esqbJQPrQjRRldhlI6uMpeAi1DvdvUq2LDeP044s/yRlUbYSzxrS8+X4IMtEaXsWsvLOrohaMqzI0rALEZkWkf6TXBneIrp446P22CGhIWIJqSHsaiMyZDzEcfMvP2dgdU0bqh31y6yxX+ZbUwaGGLPVNbbOqvraa8i6EDjjUQJ24bSQVqlNYydZVNqMIxOJIccjiWFRJlq1ZyGrLqD4pFN09ygNJf1HtJW2E8V+/Ubi0xerC5k+QoHoh0tqHjZZyKoPHskP9CMUXchEPn1tRDl1q4yaVXQmNg9tFoa2PS0lYl8bCsfHBTvdkeyYhfFLYiLaUdqHxLbNNxPBIKA9L4vrX2IXRVYWMZGlZd/xItEdSVqSfjR9RZJWqU1jx7VFkFdtBIYYO9efa6u1iZjTmUvEeRTtdJegLVZfH5IxaGJqx1NaIvbZSGzn7Tk+XTHmEbn1AU2kknjoMtGazBA/sKiYyPs3H+sF0Z1LNBIykJKEFfH0kUQpKWXRrZA/Ol2wetcWEhmIqsQuYwnpma2VHmf9aY6UgDixNH6IbMnrpXzcDGoluJdBMyaub4asSttuId9wfTixWK9IRg/Gu82jnWPD/SX30mxqA0rTQtvXTFboNhSnsF+RjC4BrdqIuku8LLqVt2a1bBLU9Gc6KNK1LBUzkFkmIkP6iN7WMIQSEEVKQ3grQ1YSs8gIo4mqxE5LZrWXiF3o3DjqlU1ZE4+lmI7WrbSaVQ2Ce2kfKHgswgxZlbZd6uuZdb00/z8egno20kK0c2yI/N7KYJklZdfDvDIFzzJxKGSl6aeoJERrU9x+EP0NQbeSXM95ZCjzrKAl0MxEVWITpXch+yvp58RTwgwD01546xuDsmnsuGUH16fNP3uG1AfEuUj8OfYZyMpyTURkXeqNo31tXYOIyJa8MilJ+ectuLdNnkyZmAWxon+YJbYeNpFZGSre4nHIG0fRPn1t2vZSwonSraI1q66JZUFmHtmeh6Y1FD2rr90r61p2HPbl5662jKRV2k7kr1tJSchDs6qllNSM8zTrWX3t0SVi69saMqSDJX1ZtyNtGjsrzaTNtxaS0QKlaVnYn1ayQvcleuOo1Mcy0yLS7Wzv8kfaSGzn7Tk+bf6aOFmAIuGVnqX39ySy3pLQs8zTkFaJP9GwdKuh7nBfhEWGmIGoSu2iqwjPtr6xhuzDiiItRHtjQ2SjW2UW3JH9cPqM7ieKqErsThNZEQG+/DxU0iKK+XMbafa0eLOtMqWa9DDtWC2IqtTWI/Pqs8lSIs6jmLAyEROKtIjyvpQPrVllLPXQQJApN0ZE9hWteUUSGeQFflFtRBihPGrbgpfgvhhDEycTUNneadKztDGisy72l5+znUyWm8y1a2wlv+ioTGLxX2ZYjNfj+q/IClsiwt+HFVECepaIfTYcO6n9vE8DRMbUN3ksszIPwtT0YVUqeulZJTbZyYpIIboPkbQ4NkQ5iGvRV+rP7aMWaMccSVSldqeFrIiUTwmz6VYosuEQUkbNaoh6VSlQpaKlj2dW1WeXjcj6YLqtQeqnyaaQZIPMtri2iz5cv7YYmjgZgcr6pHGy6lklNlZkpemzD2rC6oKWeDJsALUo/6QkhCr72iZNZiKzKEe9Fp03UZXYWZKVpXQAISzLbMmatErtrMs/zUbRmne5l/adoR8roiq1rZ2sEPcYlmFlJi0iTIZkWf5lF9wX+6kJWYX3UlvPDC0zWRGBS8IoXaqUbJDZVl9/Ett5e45Pm780Ru0YivCOtItsR/7QwTUsLfFYloDosq5GwV0TKyOQi6E24b3UNmuJKIGJ6B5dAiLLOqu/EYzUrGolsUzCO9c3itSGRFZEhk8JM5NWqU1jR5Trj5uHJriXjiFDP9F6Fsd2aGRFlHxbA5Fct4oU0VeCez7ULrxzbDPrWVqYEhZRvC6FzLYaW6KV4J4dQxLeOfbRZGT9w2ZOWETxpITOtkr61MSW+rTFmMcQSQy9SKyJimuPIKoSm+xkReREWETxTwiRNvO2RH5vZUCQTdukqoHILBeExxNCK/tosvKUC9wIq4G12E7k+/UbD/tFvwZIkjlNojuir6EQVYlNFrIiCiAsIj1pEeX7+o3GnuOD9Jf2VSu8SUriU2rvkVX12UTMiRDCItI9Iezz58Tos+HYSe3nfbh+y/ylMYaGKOFd4udJVCU2GfSqZQgjLKL6dCuvDGoluPNRo/DO9TntZEUUTFhEdepWHGFeEn/Rp8Fp3eG+CIsFo4lpnYXVomd5IJywiHJlUlZlotZv3lfq3xdzGSIIzWtRRGhaHL9shBZNVkRJCIuofyF7Z1JexCXxRfhL+hkCokiK47siqnakIawGmbItrl2DSPKSxhgqIoV3ie+KrLqRjrCIcKRFVM9TwkVfqf9iDG2smoBcXN6ZmDdRldhlIyuipIRFhBHbS+JwY5XYSe3b/DUxlsWaR41EZrWQIrKxWsvEKKQlLKLyLAlJSNnexrAYQxunK+4iIsmsFuFdGidiO0OJXVaiapCasBpkzLa4tvP2HJ++ONpYnH6GgOiyEU1UpXY1Z1XzqIKwiHJvW5BkUJblXo1lnhXQi9B6KwPHNkL3ikY1hEWUf9uClIQQJWPbOJBxa0AmfWsoelYmVEVYDSK3LWT+e8KSuPOomcg8FppHNsW1P81kRUT0f22YgkWbPMGkAAAAAElFTkSuQmCC';
		$saved        = array( 'url' => $empty, 'id' => '' );
		$option_val   = $this->get_saved();
		$insert_label = 'Insert ' . $this->media_label;
		if ( ! empty( $option_val ) && absint( $option_val ) ) {
			$img          = wp_get_attachment_image_src( $option_val );
			$saved        = array( 'url' => is_array( $img ) ? $img[0] : 'err', 'id' => $option_val );
			$insert_label = 'Replace ' . $this->media_label;
		}
		$vis        = empty( $option_val ) ? ' style="display:none;"' : '';
		$att_markup = $this->html_process_atts( $this->atts );

		ob_start();
		echo '<div class="blank-img" style="display:none;">' . $empty . '</div>';
		echo '<img src="' . $saved['url'] . '" class="img-preview" />';
		echo '<input id="' . $this->id . '_button" data-media-label="' . $this->media_label . '" '
		     . 'type="button" class="button button-secondary button-hero img-upload" value="' . $insert_label
		     . '" data-id="' . $this->id . '" data-button="Use ' . $this->media_label
		     . '" data-title="Select or Upload ' . $this->media_label . '"' . $att_markup . '/>';
		echo '<input id="' . $this->id . '" name="' . $this->id
		     . '" type="hidden" value="' . $saved['id'] . '"' . $att_markup . ' />';
		echo '<a href="#" class="button button-secondary img-remove" ' . ' data-media-label="'
		     . $this->media_label . '" ' . $vis . '>Remove ' . $this->media_label . '</a>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

class Include_Partial extends Option {

	public $filename;

	public function __construct( $i, $config, $f ) {
		parent::__construct( $i, [] );
		$this->filename = ( ! empty( $f ) ) ? $f : 'set_the_filename.php';
	}

	public function get_html() {
		return $this->echo_html();
	}

	public function echo_html() {
		if ( ! empty( $this->filename ) ) {
			include_once $this->filename;
		}
	}
}

class Include_Markup extends Option {
	public $markup;

	public function __construct( $i, $v = [], $m ) {
		parent::__construct( $i, $v );
		$this->markup = ( ! empty( $m ) ) ? $m : null;
	}

	public function get_html() {
		return $this->echo_html();
	}

	public function echo_html() {
		if ( is_string( $this->markup ) && ! empty( $this->markup ) ) {
			echo $this->markup;
		}
	}
}
