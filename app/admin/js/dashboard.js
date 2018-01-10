jQuery( document ).ready( function( $ ) {
	var appData = window.wpPhxDevKit || {};
	var appHeader = wp.template('wp-phx-dev-kit-header');
	var appTemplate = wp.template( 'wp-phx-dev-kit-main' );
	var appMarkup = appHeader( appData ) + appTemplate( appData );
	var appContainer = $('#app');

	appContainer.html( appMarkup ).fadeIn();
} );