/**
 * Tweeki-specific script to setup SmoothDivScroll
 */

jQuery( function( $ ) {
			
	// initialize smoothdivscroll
	$(document).ready(function() {
		$("#tweekiTOC").smoothDivScroll({ 
			visibleHotSpotBackgrounds: "always",
			hotSpotScrollingStep: 5,
			mousewheelScrolling: "",
		});
	});
	
	});
