/**
 * Tweeki-specific script to setup tooltips
 */

jQuery( function( $ ) {
			
	if( mw.config.get('wgTweekiSkinUseTooltips') === true ) {
		// initialize tooltips
		$(document).ready(function() {
			$('[data-toggle="tooltip"]').tooltip()
		});
	}
	
});
