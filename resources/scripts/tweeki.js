import 'jquery';
import 'bootstrap';

jQuery(document).ready(function ($) {

  if(mw.config.get('wgTweekiSkinUseTooltips') === true ) {
    // initialize tooltips
    $(document).ready(function() {
      $('[data-toggle="tooltip"]').tooltip()
    });
  }

  /**
   * FOOTER
   */
  // move sticky footer to bottom if the document is smaller than window
  function checkFooter() {
    if( $( '#footer.footer-sticky' ).length == 1 ) { // only if footer is sticky
      $( 'body' ).css( 'margin-bottom', 0 );
      // TODO: value shouldn't be hardcoded - use padding on #contentwrapper instead
      var minmargin = 50;
      var currentmargin = $( '#footer.footer-sticky' ).css( 'margin-top' );
      currentmargin = Number( currentmargin.replace( 'px', '' ) );
      var additionalmargin = $( window ).height() - $( 'body' ).height();
      var newmargin = Math.max( currentmargin + additionalmargin, minmargin );
      $( '#footer.footer-sticky' ).css( 'margin-top', newmargin + 'px' );
    }
  }

  // fade in initially hidden sticky footer
  checkFooter();
  $( '#footer.footer-sticky' ).animate( { opacity: 1 }, 1000 );

  // correct sticky footer on resize
  $(window).resize(function() {
    checkFooter();
  });

  // correct sticky footer on tab toggle
  $(document).on('shown.bs.tab', function (e) {
    checkFooter();
  });

  // correct bottom margin for body when fixed footer
  if( $( '#footer.footer-fixed' ).length == 1 ) {
    var footerheight = $( '#footer' ).outerHeight();
    $( 'body' ).css( 'margin-bottom', footerheight );
  }


  /**
   * TOC
   */
  // move TOC elsewhere
  if( $( "#tweekiTOC" ).length == 1 && $( "#toc" ).length == 1 ) {
    // toc copies
    $( '.tweeki-toc' ).each( function() {
      $(this).append( $( "#toc ul" ).clone() );
    });

    // to other place than sidebar?
    // var is_sidebar =
    if( $( "#tweekiTOC" ).parents("[id^='sidebar']").length != 1 ) {
      $( "#toc li" ).appendTo( "#tweekiTOC" );
      $( "#tweekiDropdownTOC" ).show();
    }
    // or to sidebar?
    else {
      $( "#toc" ).appendTo( "#tweekiTOC" );
      $( "#toctitle, .toctitle" ).insertBefore( "#toc" ).children( "h2" )
        .append( '<a href="javascript:scrollTo(0,0);">' + mw.message( 'tweeki-toc-top' ).plain() + '</a>' );
      /* do we need this? could cause problems on small screens */
      /* $(window).resize(function() {
        $("#tweekiTOC").height($(window).height()-$("#tweekiTOC").position().top-130);
      }).resize(); */

      // show toc (hidden by default)
      $( "#toc" ).css( 'display', 'table' );

      // start scrollspy
      $('#toc ul')
        .addClass('nav flex-column')

      $('#toc ul a')
        .addClass('nav-link')

      $('body')
        .scrollspy({target: '#toc ul'});
      }

    }


  /**
   * HEADLINES
   *
   * If the headline is inside the span it's padding will prevent
   * links directly above the headline to be accessible
   */
  /*
  $('.mw-headline').each(function(i) {
    var headline_contents = $(this).contents();

    if (typeof headline_contents !== 'undefined')
      $(this).text('').after(headline_contents);
  });
  */


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

  /**
   * Fix VisualEditor scroll stickiness
   *
   * Had to use the child-parent methods below because the oo-ui-toolbar-bar
   * class exists on multiple divs.
   *
   * The code calculates the navbar height and uses that number as the 'top'
   * CSS attribute. This calculation is probably moot as it doesn't appear
   * that the skin, or VisualEditor plays well on screen resolutions less
   * than 1024 pixels wide. Left the code this way in case something with
   * VE changes in the future.
   *
   **/
   $(window).scroll( function ( e ) {
     // Check to see if the navbar-fixed-top class exists. If it
     // does then the navbar is fixed and run this code if
     if ( $( '.navbar-fixed-top').length ) {
      var $el = $('.oo-ui-toolbar-bar > .oo-ui-toolbar-actions');
      var $headerheight = $('#mw-head').height();
      var isPositionFixed = ($el.parent().css('position') == 'fixed');

      if ($(this).scrollTop() > $headerheight && !isPositionFixed){
        $el.parent().css( 'top', $headerheight );
      }

      if ($(this).scrollTop() < $headerheight )
      {
        $el.parent().css( 'top', '');
      }
     }
  });
});
