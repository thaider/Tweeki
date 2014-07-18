/**
 * Tweeki-specific scripts
 */

jQuery( function( $ ) {

	/* FOOTER */
	// don't fix footer if document smaller than window
	if($(document).height() != $(window).height()) { $( '#footer' ).css('position','static'); }

	// correct on resize
	$(window).resize(function() {
		if($(document).height() != $(window).height()) { $( '#footer' ).css('position','static'); }
		else { $( '#footer' ).css('position','fixed'); }
	});

	/* TOC */
	/* move TOC elsewhere */
	if( $("#tweekiTOC").length == 1 && $("#toc").length == 1 ) {
		$("#toc").appendTo("#tweekiTOC");
		$("#toctitle").insertBefore("#tweekiTOC").children("h2").append('<a href="javascript:scrollTo(0,0);">nach oben</a>');
		$(window).resize(function() {
			$("#tweekiTOC").height($(window).height()-$("#tweekiTOC").position().top-130);
			}).resize();
		/* initialize smoothdivscroll */
		$(document).ready(function() {
			$("#tweekiTOC").smoothDivScroll({ 
				visibleHotSpotBackgrounds: "always",
				hotSpotScrollingStep: 5,
				mousewheelScrolling: "",
				});
			});
		}
	/* show toc (hidden by default) */
	$("#toc").css( 'display', 'table' );
});