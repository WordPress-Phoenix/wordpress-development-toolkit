jQuery( document ).ready( function( $ ) {

	var appData = window.wpPhxGenerator || {};
	var appTemplate = wp.template( 'generator-app' );
	var appContainer = $( '#generator-app' ).fadeIn();
	var dependencyLoader = $( '#plugin_autoload-wrap' );
	var versionField = $( '#plugin_ver-wrap' );
	var teamField = $( '#plugin_teamorg-wrap' );
	var githubField = $( '#plugin_repo_url-wrap' );
	var includeOptionsPanel = $( '#plugin_opts_panel-wrap' );
	var includeRest = $( '#plugin_rest_api-wrap' );
	var adminMarkdown = $( '#plugin_admin_pages-wrap' );
	var blankAdmin = $( '#plugin_admin_blank-wrap' );
	var assetScaffold = $( '#plugin_register_enqueue_assets-wrap' );

	if ( 'true' === appData.doLocalAlert ) {
		if ( confirm( 'Hmm... appears you\'re running this on a live server. It\'s' +
				' designed for local development' +
				' environments so your mileage could vary and performance could be affected -- continue?' )
		) {
			initApp();
		}

	} else {
		initApp();
	}

	function initApp() {
		appContainer.html( appTemplate( appData ) );

		$( '#field-builder_show_adv' ).bind( 'change', function( evt ) {
			var isChecked = $( this ).prop( 'checked' );
			advToggle( isChecked );
		} );

		$( '#field-plugin_opts_panel' ).bind( 'change', function( evt ) {
			var isChecked = $( this ).prop( 'checked' );
			panelToggle( isChecked );
		} );
		$( '#field-plugin_assets' ).bind( 'change', function( evt ) {
			var isChecked = $( this ).prop( 'checked' );
			if ( isChecked ) {
				$( '#plugin_assets_enqueue-wrap' ).fadeIn();
			} else {
				$( '#plugin_assets_enqueue-wrap' ).fadeOut();
			}
		} );
	}

	function advToggle( toggle ) {
		var advFields = [
			dependencyLoader, versionField, teamField, githubField,
			includeOptionsPanel, includeRest, adminMarkdown, blankAdmin, assetScaffold
		];
		if ( toggle ) {
			$.each( advFields, function( val, field ) {
				$( field.selector ).fadeIn();
			} );
		} else {
			$.each( advFields, function( val, field ) {
				$( field.selector ).fadeOut();
			} );
		}
	}

	function panelToggle( toggle ) {
		if ( toggle ) {
			$( '#plugin_opts_panel_type-wrap' ).fadeIn();
		} else {
			$( '#plugin_opts_panel_type-wrap' ).fadeOut();
		}
	}

} );