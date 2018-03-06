<?php

namespace PHX_WP_DEVKIT\V_2_1\Admin;

class Plugin_Generator_Form {

	public static function enqueue( $url, $data ) {
		$generator = 'wp-phx-plugin-generator';

		wp_register_style(
			$generator,
			trailingslashit( $url ) . 'app/admin/css/generator.css',
			array(),
			rand( 0, 1000000000 )
		);

		wp_register_script(
			$generator,
			trailingslashit( $url ) . 'app/admin/js/generator.js',
			array( 'wp-util' ),
			rand( 0, 1000000000 )
		);

		wp_enqueue_style( $generator );
		wp_enqueue_script( $generator );
		wp_localize_script( $generator, 'wpPhxGenerator', $data );
	}

	public static function markup() {
		ob_start(); ?>
			<div id="generator-app"></div>
			<script type="text/html" id="tmpl-generator-input">
				<# if ( 'checkbox' === data.inputType ) { #>
					<div class="onoffswitch-wrap">
						<div class="onoffswitch">
							<input type="checkbox" name="{{{ data.key }}}" class="onoffswitch-checkbox"
								   id="field-{{{ data.key }}}" <# if ( 'true' === data.default ) { #> checked <# } #> placeholder="{{{ data.placehold	}}}" data-generator-field>
							<label class="onoffswitch-label" for="field-{{{ data.key }}}">
								<span class="onoffswitch-inner"></span>
								<span class="onoffswitch-switch"></span>
							</label>
						</div>
					</div>
				<# } else { #>
					<input type="{{{ data.inputType }}}" id="field-{{{ data.key }}}" name="{{{ data.key }}}"
						   class="widefat generator-input" placeholder="{{{ data.placehold }}}" data-generator-field><br />
				<# } #>
				<small><em>{{{ data.desc }}}</em></small>
			</script>
			<script type="text/html" id="tmpl-generator-textarea">
				<textarea type="{{{ data.inputType }}}" id="field-{{{ data.key }}}" name="{{{ data.key }}}"
					   class="widefat generator-textarea" placeholder="{{{ data.placehold }}}"
						  data-generator-field></textarea>
				<small><em>{{{ data.desc }}}</em></small>
			</script>
			<script type="text/html" id="tmpl-generator-radios">
				<small><strong>{{{ data.desc }}}</strong></small>
				<ul id="field-{{{ data.key }}}" name="field-{{{ data.key }}}">
					<# var radioMarkup = function( v, k ) {
							var maybeChecked = ( k === data.default ) ? ' checked="checked" ' : '';
							return '<li><input id="field-' + k +'" name="' + data.key
							+ '" type="radio" value="'+ k +'"' + maybeChecked + 'data-generator-field>' +
						    '<label for="field-' + k + '"><code>' + v + '<\/code><\/label> <\/li>';
						};
						_.each( data.choices, function( v, k ) {
							print( radioMarkup( v, k ) );
						} ); #>
				</ul>
			</script>
			<script type="text/html" id="tmpl-generator-row">
				<# var currentTmpl = wp.template( 'generator-' + data.type ); #>
					<tr id="{{{ data.key }}}-wrap">
						<# print( data.html_pre ); #>
						<# if ( ! data.hide_label ) { #><td class="primary"><code>{{{ data.label }}}</code></td><# } #>
						<td <# if ( data.hide_label ) { #>colspan="2"<# } #>><# print( currentTmpl( data ) ); #></td>
						<# print( data.html_post ); #>
					</tr>
			</script>
			<script type="text/html" id="tmpl-generator-app">
				<div class="wrap generator-wrap">
					<section id="generator-head">
						<h1>
							<span style="color:#eee;"><span class="dashicons dashicons-wordpress-alt" style="font-size:
							 26px;margin: 0.075rem 0.35rem 0 0;"></span> Plugin Generator</span>
						</h1><br />
						<div>
							<img src="https://robohash.org/wp-phx-plugin-machine" width="200px" height="200px"
							     style="float:left;"/>
							<h2 style="color:#eee;">Hey <?php echo wp_get_current_user()->display_name; ?>!</h2>
							<h3 style="color:#eee;">Lets scaffold a new WordPress Plugin.</h3>
						</div>
					</section>
					<form method="POST">
						<table class="widefat striped">
							<# var fieldsMarkup = '';
							   var rowTemplate = wp.template( 'generator-row' );
								_.each( data.fields, function( field ) {
									fieldsMarkup += rowTemplate( field );
								});
								print( fieldsMarkup ); #>
						</table>
						<div id="gen-button-wrap">
							<input type="submit"
								   name="wp-phx-create-abstract-plugin"
								   class="button button-hero button-primary"
								   value="Generate Plugin">
						</div>
					</form>
				</div>
			</script>
		<?php return ob_get_clean();
	}

}
