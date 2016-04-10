/**
 * Tweeki-specific scripts
 */

jQuery( function( $ ) {

	/** 
	 * FOOTER 
	 */
	// change sticky footer to fixed if the document is smaller than window
	function checkFooter() {
		if($(document).height() == $(window).height()) { 
			$( '#footer.footer-sticky' ).addClass( 'sticky-fixed' ); 
			correctBodyMargin();
		}
	}

	// correct bottom margin for body for fixed footer
	function correctBodyMargin() {
		var footerheight = $( '#footer' ).outerHeight();
		$( 'body' ).css( 'margin-bottom', footerheight );
	}	
	
	// correct sticky footer on resize
	$(window).resize(function() {
		$( '#footer.footer-sticky' ).removeClass( 'sticky-fixed' ); 
		$( 'body' ).css( 'margin-bottom', 0 );
		checkFooter();
	});

	// fade in initially hidden sticky footer
	checkFooter();
	$( '#footer.footer-sticky' ).animate( { opacity: 1 }, 1000 );
	
	// correct bottom margin for body when fixed footer
	if( $( '#footer.footer-fixed' ).length == 1 ) {
		correctBodyMargin();
	}


	/**
	 * TOC 
	 */
	// move TOC elsewhere
	if( $( "#tweekiTOC" ).length == 1 && $( "#toc" ).length == 1 ) {
		// to other place than sidebar?
		if( $( "#tweekiTOC" ).parents( ".sidebar-wrapper" ).length != 1 ) {
			$( "#toc li" ).appendTo( "#tweekiTOC" );
			$( "#tweekiDropdownTOC" ).show();
			}
		// or to sidebar?
		else {
			$( "#toc" ).appendTo( "#tweekiTOC" );
			$( "#toctitle" ).insertBefore( "#toc" ).children( "h2" )
				.append( '<a href="javascript:scrollTo(0,0);">' + mw.message( 'tweeki-toc-top' ).plain() + '</a>' );
			/* do we need this? could cause problems on small screens */
			/* $(window).resize(function() {
				$("#tweekiTOC").height($(window).height()-$("#tweekiTOC").position().top-130);
			}).resize(); */
						
			// show toc (hidden by default)
			$( "#toc" ).css( 'display', 'table' );
			
			// start scrollspy
			$( '#toc ul' ).addClass( 'nav' );	
			$( 'body' ).css( 'position', 'relative' ).scrollspy( { target: '#toc' } );
			}
		}


	/**
	 * LOGIN-EXT 
	 */
	// don't close dropdown when clicking in the login form
	$( "#loginext" ).click( function( e ) {
    e.stopPropagation();
		});
	// focus user name field
	$( "#n-login-ext" ).click( function() {
		if( ! $( this ).parent().hasClass( "open" ) ) {
			setTimeout( '$( "#wpName2" ).focus();', 100 );
			}
		});
	});
