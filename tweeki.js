/**
 * Tweeki-specific scripts
 */

jQuery( function( $ ) {

	/** 
	 * FOOTER 
	 */
	// change sticky footer to fixed if the document is smaller than window
	if($(document).height() == $(window).height()) { 
		$( '#footer.footer-sticky' ).addClass( 'sticky-fixed' ); 
	}

	// correct sticky footer on resize
	$(window).resize(function() {
		if($(document).height() != $(window).height()) { 
			$( '#footer.footer-sticky' ).removeClass( 'sticky-fixed' ); 
		} else { 
			$( '#footer.footer-sticky' ).addClass('sticky-fixed'); 
		}
	});

	// fade in initially hidden sticky footer
	$( '#footer.footer-sticky' ).animate( { opacity: 1 }, 1000 );
	
	// correct bottom margin for body when fixed footer
	if( $( '#footer.footer-fixed' ).length == 1 ) {
		var footerheight = $( '#footer.footer-fixed' ).outerHeight();
		$( 'body' ).css( 'margin-bottom', footerheight );
	}


	/**
	 * TOC 
	 */
	// move TOC elsewhere
	if( $( "#tweekiTOC" ).length == 1 && $( "#toc" ).length == 1 ) {
		// to other place than sidebar?
		if( $( "#tweekiTOC" ).parents( "#sidebar" ).length != 1 ) {
			$( "#toc li" ).appendTo( "#tweekiTOC" );
			$( "#tweekiDropdownTOC" ).show();
			}
		// or to sidebar?
		else {
			$( "#toc" ).appendTo( "#tweekiTOC" );
			$( "#toctitle" ).insertBefore( "#tweekiTOC" ).children( "h2" )
				.append( '<a href="javascript:scrollTo(0,0);">' + mw.message( 'tweeki-toc-top' ).plain() + '</a>' );
			$(window).resize(function() {
				$("#tweekiTOC").height($(window).height()-$("#tweekiTOC").position().top-130);
			}).resize();
						
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
