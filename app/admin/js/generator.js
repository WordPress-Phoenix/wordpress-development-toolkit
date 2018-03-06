jQuery( document ).ready( function( $ ) {

	var appData = window.wpPhxGenerator || {};
	var appTemplate = wp.template( 'generator-app' );
	var appContainer = $( '#generator-app' ).fadeIn();

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
	}

} );