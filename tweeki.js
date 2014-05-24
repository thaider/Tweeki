/*
 * Strapping-specific scripts
 */
jQuery(function($) {
  var $nav = $('#page-header ul.navigation'),
      $searchLink = $nav.find('a.search-link'),
      $search = $('#nav-searchform .search-query');

  var nav = {
    focus: function(ev) {
      nav.toggle(true);
    },
    blur: function(ev) {
      nav.toggle(false);
    },
    toggle: function(bool) {
      $nav.toggleClass('searchform-enabled', bool).toggleClass('searchform-disabled', !bool);
    }
  };

  $searchLink.on({
    'click': function() {
      nav.focus();
      setTimeout(function(){
        $search.focus().select();
      }, 100);
    }
  });

  $search.on({
    'blur': nav.blur,
    'keypress': function(ev) {
      // Convert <esc> into a blur
      if (ev.keyCode === 27) { this.blur(); }
    }
  });
});

		/*
		 * Skrifo-specific scripts
		 */
		jQuery( function( $ ) {
			var $pCactions = $( '#p-cactions' );
			$pCactions.find( 'h5 a' )
				// For accessibility, show the menu when the hidden link in the menu is clicked (bug 24298)
				.click( function( e ) {
					$pCactions.find( '.menu' ).toggleClass( 'menuForceShow' );
					e.preventDefault();
				})
				// When the hidden link has focus, also set a class that will change the arrow icon
				.focus( function() {
					$pCactions.addClass( 'vectorMenuFocus' );
				})
				.blur( function() {
					$pCactions.removeClass( 'vectorMenuFocus' );
				});
	
			//user-icon vor personal-link in der navigation
			$( '.pa-user' ).prepend('<i class="icon-user icon-grey"></i> ');
	
			//footer nicht fixieren, wenn dokument kleiner als fenstergröße
			if($(document).height() != $(window).height()) { $( '#footer' ).css('position','static'); }
			//korrigieren bei resize
			$(window).resize(function() {
		  		if($(document).height() != $(window).height()) { $( '#footer' ).css('position','static'); }
		  		else { $( '#footer' ).css('position','fixed'); }
			});

		});


		/* Move TOC elsewhere */
		if( $("#tweekiTOC").length = 1 ) {
			$("#toc").appendTo("#tweekiTOC");
			$("#toctitle").insertBefore("#tweekiTOC");
			$(window).resize(function() {
				$("#tweekiTOC").height($(window).height()-$("#tweekiTOC").position().top-130);
				}).resize();

			$(document).ready(function() {
				$("#tweekiTOC").smoothDivScroll({ 
					visibleHotSpotBackgrounds: "always",
					hotSpotScrollingStep: 5,
					mousewheelScrolling: "vertical",
					});
				});
			}
		$("#toc").css('display','table');

		/* Add Icon to Editsection-Buttons */
		$(".mw-editsection a").html("<i class='icon-pencil icon-darkgrey'></i> Abschnitt bearbeiten");
		$(".mw-editsection a").addClass("btn btn-mini");
		$(".mw-editsection").html(function() {
			return $(this).children();
			});
