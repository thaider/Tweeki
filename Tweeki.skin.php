<?php
/**
 * Tweeki - Tweaked version of Vector, using Twitter bootstrap.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @todo document
 * @file
 * @ingroup Skins
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( -1 );
}

/**
 * SkinTemplate class for Tweeki skin
 * @ingroup Skins
 */
class SkinTweeki extends SkinTemplate {

	protected static $bodyClasses = array( 'tweeki-animateLayout' );

  var $skinname = 'tweeki', $stylename = 'tweeki',
    $template = 'TweekiTemplate', $useHeadElement = true;

	/**
	 * Initializes output page and sets up skin-specific parameters
	 * @param $out OutputPage object to initialize
	 */
	public function initPage( OutputPage $out ) {
		global $wgLocalStylePath;

		parent::initPage( $out );

		// Append CSS which includes IE only behavior fixes for hover support -
		// this is better than including this in a CSS file since it doesn't
		// wait for the CSS file to load before fetching the HTC file.
		$min = $this->getRequest()->getFuzzyBool( 'debug' ) ? '' : '.min';
		$out->addHeadItem( 'csshover',
			'<!--[if lt IE 7]><style type="text/css">body{behavior:url("' .
				htmlspecialchars( $wgLocalStylePath ) .
				"/{$this->stylename}/csshover{$min}.htc\")}</style><![endif]-->"
		);

		$out->addMeta("viewport", "width=device-width, initial-scale=1.0");
    $out->addModules( 'skins.tweeki.scripts' );
  }

	/**
	 * Loads skin and user CSS files.
	 * @param $out OutputPage object
	 */
	function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );

//		$styles = array( 'mediawiki.skinning.interface', 'skins.tweeki.styles' );
		$styles = array( 'skins.tweeki.styles' ); /* TODO: something's not working as it should */
		wfRunHooks( 'SkinTweekiStyleModules', array( $this, &$styles ) );
		$out->addModuleStyles( $styles );
	}

	/**
	 * Adds classes to the body element.
	 *
	 * @param $out OutputPage object
	 * @param &$bodyAttrs Array of attributes that will be set on the body element
	 */
	function addToBodyAttributes( $out, &$bodyAttrs ) {
		if ( isset( $bodyAttrs['class'] ) && strlen( $bodyAttrs['class'] ) > 0 ) {
			$bodyAttrs['class'] .= ' ' . implode( ' ', static::$bodyClasses );
		} else {
			$bodyAttrs['class'] = implode( ' ', static::$bodyClasses );
		}
	}
}

/**
 * QuickTemplate class for Tweeki skin
 * @ingroup Skins
 */
class TweekiTemplate extends BaseTemplate {

	/* Functions */

	/**
	 * Outputs the entire contents of the (X)HTML page
	 */
	public function execute() {
		global $wgVectorUseIconWatch;
		global $wgTweekiSkinHideAnon;
		global $wgGroupPermissions;

		// Build additional attributes for navigation urls
		$nav = $this->data['content_navigation'];

		if ( $wgVectorUseIconWatch ) {
			$mode = $this->getSkin()->getUser()->isWatched( $this->getSkin()->getRelevantTitle() ) ? 'unwatch' : 'watch';
			if ( isset( $nav['actions'][$mode] ) ) {
				$nav['views'][$mode] = $nav['actions'][$mode];
				$nav['views'][$mode]['class'] = rtrim( 'icon ' . $nav['views'][$mode]['class'], ' ' );
				$nav['views'][$mode]['primary'] = true;
				unset( $nav['actions'][$mode] );
			}
		}

		$xmlID = '';
		foreach ( $nav as $section => $links ) {
			foreach ( $links as $key => $link ) {
				if ( $section == 'views' && !( isset( $link['primary'] ) && $link['primary'] ) ) {
					$link['class'] = rtrim( 'collapsible ' . $link['class'], ' ' );
				}

				$xmlID = isset( $link['id'] ) ? $link['id'] : 'ca-' . $xmlID;
				$nav[$section][$key]['attributes'] =
					' id="' . Sanitizer::escapeId( $xmlID ) . '"';
				if ( $link['class'] ) {
					$nav[$section][$key]['attributes'] .=
						' class="' . htmlspecialchars( $link['class'] ) . '"';
					unset( $nav[$section][$key]['class'] );
				}
				if ( isset( $link['tooltiponly'] ) && $link['tooltiponly'] ) {
					$nav[$section][$key]['key'] =
						Linker::tooltip( $xmlID );
				} else {
					$nav[$section][$key]['key'] =
						Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( $xmlID ) );
				}
			}
		}
		$this->data['namespace_urls'] = $nav['namespaces'];
		$this->data['view_urls'] = $nav['views'];
		$this->data['action_urls'] = $nav['actions'];
		$this->data['variant_urls'] = $nav['variants'];

    //set userStateClass
    if ( $this->data['loggedin'] ) {
      $userStateClass = "user-loggedin";
    } else {
      $userStateClass = "user-loggedout";
    }
    
		if ( $wgGroupPermissions['*']['edit'] || $this->data['loggedin'] ) {
			$userStateClass += " editable";
		} else {
			$userStateClass += " not-editable";
		}
		
		/* TODO: beautify!!! */
		//set 'namespace', 'shortnamespace', and 'title_formatted' variables
		reset( $this->data['namespace_urls'] );
		$currentNamespace = current( $this->data['namespace_urls'] );
		$this->data[ 'namespace' ] = $currentNamespace['text'];

		/* TODO: generalize SKRIFO specific parts!!!! */
		$this->data[ 'shortNamespace' ] = $this->data[ 'namespace' ];
		if ( stripos( $this->data[ 'namespace' ], "fragen" ) !== false ) { $this->data[ 'shortNamespace' ] = "Fragen"; } /* needs some rework */
		if ( $this->data[ 'namespace' ] == "Datei" ) { $this->data[ 'shortNamespace' ] = "Dateiseite"; } /* ugly */

		$this->data['title_formatted'] = $this->data['title'];
		if( strpos( $this->data['title'],":" ) !== false ) { /* does not work for titles in the main namespace with colons! */
			$this->data['title_formatted'] = '<span class="namespace">' . str_replace( ":", ":</span> ", $this->data['title'] );
		}

		// Reverse horizontally rendered navigation elements
		if ( $this->data['rtl'] ) {
			$this->data['view_urls'] =
				array_reverse( $this->data['view_urls'] );
			$this->data['namespace_urls'] =
				array_reverse( $this->data['namespace_urls'] );
			$this->data['personal_urls'] =
				array_reverse( $this->data['personal_urls'] );
		}
		// Output HTML Page
		$this->html( 'headelement' );
?>
		<div id="mw-page-base"></div>
		<div id="mw-head-base"></div>
		<a id="top"></a>
    <!-- content -->
    <div class="container <?php echo $userStateClass; echo ( $this->checkVisibility( 'navbar' ) ) ? ' with-navbar' : ' without-navbar'; ?>">

			<?php if( wfMessage( 'tweeki-subnav' )->plain() !== '-' && $this->checkVisibility( 'subnav' ) ) { ?>
			<!-- subnav -->
			<div id="page-header" class="row">
				<div class="<?php echo ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) ? 'col-md-offset-3 col-md-9' : 'col-md-offset-1 col-md-10'; ?>">
					<ul class="navigation nav nav-pills pull-right">
					<?php	$this->renderSubnav(); ?>
					</ul>
				</div>
			</div>
			<!-- /subnav -->
			<?php } ?>

			<div class="row">
				<div id="content" class="mw-body <?php echo ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) ? 'col-md-offset-3 col-md-9' : 'col-md-offset-1 col-md-10'; ?>" role="main">
					<div id="mw-js-message" style="display:none;"<?php $this->html( 'userlangattributes' ) ?>></div>
					<?php if ( $this->data['sitenotice'] ) { ?>
					<!-- sitenotice -->
					<div id="siteNotice"><?php $this->html( 'sitenotice' ) ?></div>
					<!-- /sitenotice -->
					<?php } ?>
					<?php if ( $this->checkVisibility( 'firstHeading' ) ) { ?>
					<h1 id="firstHeading" class="firstHeading page-header" lang="<?php
						$this->data['pageLanguage'] = $this->getSkin()->getTitle()->getPageViewLanguage()->getHtmlCode();
						$this->text( 'pageLanguage' );
					?>"><span dir="auto"><?php $this->html( 'title_formatted' ) ?></span></h1>
					<?php } ?>
					<?php $this->html( 'prebodyhtml' ) ?>
					<!-- bodyContent -->
					<div id="bodyContent">
						<?php if ( $this->data['isarticle'] ) { ?>
						<div id="siteSub"><?php $this->msg( 'tagline' ) ?></div>
						<?php } ?>
						<div id="contentSub"<?php $this->html( 'userlangattributes' ) ?>><?php $this->html( 'subtitle' ) ?></div>
						<?php if ( $this->data['undelete'] ) { ?>
						<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
						<?php } ?>
						<?php if ( $this->data['newtalk'] ) { ?>
						<div class="usermessage"><?php $this->html( 'newtalk' ) ?></div>
						<?php } ?>
						<div id="jump-to-nav" class="mw-jump">
							<?php $this->msg( 'jumpto' ) ?>
							<a href="#mw-navigation"><?php $this->msg( 'jumptonavigation' ) ?></a><?php $this->msg( 'comma-separator' ) ?>
							<a href="#p-search"><?php $this->msg( 'jumptosearch' ) ?></a>
						</div>
						<?php $this->html( 'bodycontent' ) ?>
						<?php if ( $this->data['printfooter'] ) { ?>
						<div class="printfooter">
						<?php $this->html( 'printfooter' ); ?>
						</div>
						<?php } ?>
						<?php if ( $this->data['catlinks'] ) { ?>
						<?php $this->html( 'catlinks' ); ?>
						<?php } ?>
						<?php if ( $this->data['dataAfterContent'] ) { ?>
						<?php $this->html( 'dataAfterContent' ); ?>
						<?php } ?>
						<div class="visualClear"></div>
						<?php $this->html( 'debughtml' ); ?>
					</div>
					<!-- /bodyContent -->
				</div>
			</div>
    </div>
    <!-- /content -->

		<?php if ( $this->checkVisibility( 'navbar' ) ) { ?>
		<!-- navbar -->
		<div id="mw-navigation" class="navbar navbar-default navbar-fixed-top" role="navigation">
			<h2><?php $this->msg( 'navigation-heading' ) ?></h2>
			<div id="mw-head" class="navbar-inner">
				<div class="container-fluid">
				
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>

						<?php if ( $this->checkVisibility( 'navbar-brand' ) ) { 
							$this->renderBrand(); 
							} ?>
					
					</div>

					<?php if ( $this->checkVisibility( 'navbar-left' ) ) { ?>
					<div id="left-navbar" class="navbar-left navbar-collapse collapse">
						<ul class="nav navbar-nav">
						<?php $this->renderNavbar( 'left' ); ?>
					</ul>
					</div>
					<?php } ?>

					<?php if ( $this->checkVisibility( 'navbar-right' ) ) { ?>
					<div id="right-navbar" class="navbar-right navbar-collapse collapse">
						<ul class="nav navbar-nav">
						<?php $this->renderNavbar( 'right' ); ?>
						</ul>
					</div>
					<?php } ?>

				</div>
			</div>
		</div>
		<!-- /navbar -->
		<?php } ?>

		<?php if ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) { ?>
		<!-- sidebar -->
		<div id="sidebar">
			<?php $this->renderSidebar(); ?>
		</div>
		<!-- /sidebar -->
		<?php } ?>
			
		<?php if ( $this->checkVisibility( 'footer' ) ) { ?>
		<!-- footer -->
		<div id="footer" role="contentinfo" class="footer container"<?php $this->html( 'userlangattributes' ) ?>>
		<?php $this->renderFooter(); ?>
		</div>
		<!-- /footer -->
		<?php } ?>
	
		<?php $this->printTrail(); ?>

  </body>
</html>
<?php
  }

  /**
   * Render logo
   */
  private function renderLogo() {
        $mainPageLink = $this->data['nav_urls']['mainpage']['href'];
        $toolTip = Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( 'p-logo' ) );
?>
        		<a id="p-logo" href="<?php echo htmlspecialchars( $this->data['nav_urls']['mainpage']['href'] ) ?>" <?php echo Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( 'p-logo' ) ) ?>>
        			<img src="<?php $this->text( 'logopath' ); ?>" alt="<?php $this->html('sitename'); ?>">
        		</a>
<?php
  }

  /**
   * Render one or more navigations elements by name, automatically reveresed
   * when UI is in RTL mode
   *
   * @param $elements array
   */
  private function renderNavigation( $elements ) {
    global $wgUser,
    	$wgTweekiSkinHideNonPoweruser, 
    	$wgParser;

    // If only one element was given, wrap it in an array, allowing more
    // flexible arguments
    if ( !is_array( $elements ) ) {
      $elements = array( $elements );
    // If there's a series of elements, reverse them when in RTL mode
    } elseif ( $this->data['rtl'] ) {
      $elements = array_reverse( $elements );
    }
    // Render elements
    foreach ( $elements as $name => $element ) {
    	if ( !$this->checkVisibility( $element ) ) {
    		return array();
    		}
      switch ( $element ) {

        case 'EDIT':
          if ( array_key_exists('edit', $this->data['content_actions']) ) {
						return array(array( 
								'href' => '#',
								'icon' => 'icon-edit',
								'text' => $this->data['content_actions']['edit']['text'],
								'id' => 'b-edit'
								));          
          }
        	break;

        case 'EDIT-EXT':
        	$views = $this->data['view_urls'];
					if(count( $views ) > 0) {
						unset( $views['view'] );
						$link = array_shift( $views );
						if ( $this->checkVisibility( 'EDIT-EXT-special' ) ) {
							$button = array(
								'href' => $link['href'],
								'key' => $link['key'],
								'href_implicit' => false,
								'icon' => 'icon-pencil icon-white',
								'text' => wfMessage( 'tweeki-edit-ext', $this->data[ 'shortNamespace' ] )->plain(),
								'class' => 'btn-primary btn-edit'
								);
							$button['items'] = $views;
							if(count($this->data['action_urls']) > 0) {
								$button['items'][] = array(); #divider
								$actions = $this->renderNavigation( 'ACTIONS' ); 
								$button['items'] = array_merge( $button['items'], $actions[0]['items'] );
								}
							}
						else {
							$button = $link;
							$button['icon'] = 'icon-pencil icon-white';
							$button['text'] = wfMessage( 'tweeki-edit-ext', $this->data[ 'shortNamespace' ] )->plain();
							$button['class'] = 'btn-primary btn-block';
							}
						return array($button);
						}
					return array();
					break; 

        case 'PAGE':
          $items = array_merge($this->data['namespace_urls'], $this->data['view_urls']);
          $test = wfMessage( 'namespaces' );
          foreach ( $items as $link ) {
            if ( array_key_exists( 'context', $link ) && $link['context'] == 'subject' ) {
            	$text = $link['text'];
            	}
            if (preg_match('/^ca-(view|edit)$/', $link['id'])) { 
            	unset($link); 
            	}
            }
					return array(array( 
							'href' => '#',
							'text' => $text,
							'id' => 'p-namespaces',
							'items' => $items
							));          
        	break;

        case 'TOOLBOX':
					$items = array_reverse($this->getToolbox());
					$divideditems = array();
					$text = (wfMessage( 'tweeki-toolbox' )->plain() == "") ? wfMessage( 'toolbox' )->plain() : wfMessage( 'tweeki-toolbox' )->plain();
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = $this->translator->translate( isset( $item['msg'] ) ? $item['msg'] : $key );
							} 
						if(preg_match( '/specialpages|whatlinkshere/', $key )) {
							$divideditems[] = array();
							}
						$divideditems[$key] = $item;
						}
					return array(array( 
							'href' => '#',
							'text' => $text,
							'id' => 'p-toolbox',
							'items' => $divideditems
							));          
        	break;

        case 'VARIANTS':
          $theMsg = 'variants';
          $items = $this->data['variant_urls'];
          if (count($items) > 0) { 
						return array(array( 
							'href' => '#',
							'text' => wfMessage( 'variants' ),
							'id' => 'p-variants',
							'items' => $items
							));    
						}      
        	break;

        case 'VIEWS':
          $items = $this->data['view_urls'];
          if (count($items) > 0) { 
						return array(array( 
							'href' => '#',
							'text' => wfMessage( 'views' ),
							'id' => 'p-views',
							'items' => $items
							));    
						}      
        	break;

        case 'ACTIONS':
          $items = array_reverse($this->data['action_urls']);
          if (count($items) > 0) { 
						return array(array(
							'href' => '#',
							'text' => wfMessage( 'actions' ),
							'id' => 'p-actions',
							'items' => $items
							));    
						}      
        	break;

        case 'PERSONAL':
          $items = $this->getPersonalTools();
          $divideditems = array();
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = $this->translator->translate( isset( $item['msg'] ) ? $item['msg'] : $key );
							}
						if(!isset( $item['href'] ) ) {
							$item['href'] = $item['links'][0]['href'];
							}
						if(preg_match( '/preferences|logout/', $key )) {
							$divideditems[] = array();
							}
						$divideditems[$key] = $item;
						}
					if ( array_key_exists( 'login', $divideditems ) ) {
						$divideditems['login']['links'][0]['text'] = wfMessage( 'tweeki-login' );
						return array( $divideditems['login'] );
						}
					if ( array_key_exists( 'anonlogin', $divideditems ) ) {
						$divideditems['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' );
						return array( $divideditems['anonlogin'] );
						}
          if (count($items) > 0) { 
						return array(array( 
								'href' => '#',
								'text' => $this->data['username'],
								'icon' => 'icon-user',
								'id' => 'p-personaltools',
								'items' => $divideditems
								));
						}
        break;

        case 'LOGIN':
          $items = $this->getPersonalTools();
					if ( array_key_exists( 'login', $items ) ) {
						$items['login']['links'][0]['text'] = wfMessage( 'tweeki-login' );
						return array( $items['login'] );
						}
					if ( array_key_exists( 'anonlogin', $items ) ) {
						$items['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' );
						return array( $items['anonlogin'] );
						}
          return array();
        break;

        case 'SIDEBAR':
        	$sidebar = array();
          foreach ( $this->data['sidebar'] as $name => $content ) {
            if ( empty ( $content ) ) {
            	// traditional sidebar formatting with pipe character has to be reversed
            	if( strpos( $name, '|' ) !== false ) {
              	$name = explode( '|', $name );
              	$name = array_reverse( $name );
              	$name = implode( '|', $name );
              	$sidebarItem = TweekiHooks::parseButtonLink( $name, $wgParser, false );
              	$sidebar[] = $sidebarItem[0];
              	continue;
              	}
              // navigational keywords
              $navigation = $this->renderNavigation( $name );
              if( is_array( $navigation ) ) {
              	$sidebar[] = $navigation[0];
              	continue;
              	}
            	}
            $msgObj = wfMessage( $name );
            $name = htmlspecialchars( $msgObj->exists() ? $msgObj->text() : $name );
            $sidebar[] = array(
            								'href' => '#',
            								'text' => $name,
            								'items' => $content
            								);
            }
          return $sidebar;
        	break;

        case 'LANGUAGES':
          $items = $this->data['language_urls'];
          if (count($items) > 0 && $items) { 
						return array(array( 
							'href' => '#',
							'text' => wfMessage( 'otherlanguages' ),
							'id' => 'p-otherlanguages',
							'items' => $items
							));    
						}
						return array();
          break;
        
        case 'SEARCH':
        	return array( array( 'special' => 'SEARCH' ) );
        	break;

        case 'LOGO':
        	return array( array( 'special' => 'LOGO' ) );
        	break;

        case 'FIRSTHEADING':
        	return array( array( 'special' => 'FIRSTHEADING' ) );
        	break;

        case 'TOC':
        	return array( array( 'special' => 'TOC' ) );
        	break;

				default:
					return $element;
					break;
      }
    }
  }


  /**
   * Elements can be hidden for anonymous users or for everybody who has not opted
   * to be a poweruser in the preferences
   *
   * @param $item String
   */
	private function checkVisibility( $item ) {
		global $wgUser, $wgTweekiSkinHideNonPoweruser, $wgTweekiSkinHideAnon, $wgTweekiSkinHideAll;
		if ( ( !in_array( $item, $wgTweekiSkinHideNonPoweruser ) || $wgUser->getOption( 'tweeki-poweruser' ) ) && // not hidden for non-powerusers or poweruser
			( !in_array( $item, $wgTweekiSkinHideAnon ) || $this->data['loggedin'] )  && // not hidden for anonymous users or non-anonymous user
			!in_array( $item, $wgTweekiSkinHideAll ) ) { // not hidden for all
			return true;
			}
		else {
			return false;
			}
		}

  /**
   * Render Subnavigation
   */
	private function renderSubnav() {
		$options = array( 
					'wrapper' => 'li', 
					'wrapperclass' => 'nav dropdown', 
					'dropdownclass' => 'pull-right'
					);
		$this->buildItems( wfMessage( 'tweeki-subnav' )->plain(), $options, 'subnav' );
		}
		
	
  /**
   * Render Navbar
   *
   * @param $side string
   */
	private function renderNavbar( $side ) {
		$otherside = ( $side == 'right' ) ? 'left' : 'right';
		$options = array( 
					'wrapper' => 'li', 
					'wrapperclass' => 'nav dropdown'
					);
		$this->buildItems( wfMessage( 'tweeki-navbar-' . $side )->plain(), $options, 'navbar' );    
		}


  /**
   * Render Sidebar
   */
	private function renderSidebar() {
		$options = array( 
					'class' => 'btn',
					'wrapperclass' => 'btn-group btn-block'
					);
		$this->buildItems( wfMessage ( 'tweeki-sidebar' )->plain(), $options, 'sidebar' );
    }
    

  /**
   * Build Items for navbar, subnav, sidebar
   *
   * @param $items String
   * @param $options Array
   * @param $context String
   */
	private function buildItems( $items, $options, $context ) {
		$buttons = array();		
    $customItems = array();
		$navbarItems = explode( ',', $items );
		foreach( $navbarItems as $navbarItem ) {
			$navbarItem = trim( $navbarItem );
			$navbarItem = $this->renderNavigation( $navbarItem );
			if ( is_array( $navbarItem ) ) {
				$this->renderCustomNavigation( $buttons, $customItems );
				if(count($navbarItem) !== 0) {
					$buttons = array_merge( $buttons, $navbarItem );
					}
				}
			else {
				$customItems[] = $navbarItem;
				}
			}
		$this->renderCustomNavigation( $buttons, $customItems );
		foreach( $buttons as $button ) {
			/* standard button rendering */
			if( !isset( $button['special'] ) ) {
				echo TweekiHooks::renderButtons( array( $button ), $options );
				}
			/* special cases */
			else {
				switch ( $button['special'] ) {
					case 'SEARCH':
	          ?>
            <?php if( $context == 'subnav' ) echo '<li class="nav dropdown">'; ?>
            <form <?php if( $context == 'navbar' ) echo 'class="navbar-form navbar-left"'; ?> action="<?php $this->text( 'wgScript' ) ?>" id="searchform">
            	<div class="form-group">
								<input id="searchInput" class="search-query form-control" type="search" accesskey="f" title="<?php $this->text('searchtitle'); ?>" placeholder="<?php $this->msg('search'); ?>" name="search" value="<?php echo $this->data['search']; ?>">
								<?php echo $this->makeSearchButton( 'go', array( 'id' => 'mw-searchButton', 'class' => 'searchButton btn hidden' ) ); ?>
            	</div>
            </form>
	          <?php if( $context == 'subnav' ) echo '</li>'; ?>
	          <?php
						break;
					case 'LOGO':
						$this->renderLogo( $context );
						break;
					case 'FIRSTHEADING':
						echo '<div class="tweekiFirstHeading">' . $this->data[ 'title_formatted' ] . '</div>';
						break;
					case 'TOC':
						echo '<div id="tweekiTOC"></div>';
						break;
					}
				}
			}
		}


  /**
   * Render navigations elements that renderNavigation hasn't dealt with
   *
   * @param $buttons array
   * @param $customItems array
   */
	private function renderCustomNavigation( &$buttons, &$customItems ) {
		global $wgParser;	
		if( count( $customItems ) !== 0 ) {
			$buttons = array_merge( $buttons, TweekiHooks::parseButtons( implode( chr(10), $customItems ), $wgParser, false ) );
			$customItems = array();
			}
		}

		
  /**
   * Render brand (linking to mainpage)
   */
	private function renderBrand() {
		$brand = wfMessage( 'tweeki-navbar-brand' )->text();
		/* is it a file? */
		$brandimageTitle = Title::newFromText( $brand );
		if ( $brandimageTitle->exists() ) {
			$brandimageWikiPage = WikiPage::factory( $brandimageTitle );
			if ( method_exists( $brandimageWikiPage, 'getFile' ) ) {
				$brandimage = $brandimageWikiPage->getFile()->getFullUrl();
				$brand = '<img src="' . $brandimage . '" alt="' . $this->data['sitename'] . '" />';
				}
			}
		echo '<a href="' . htmlspecialchars( $this->data['nav_urls']['mainpage']['href'] ) . '" class="navbar-brand">' . $brand . '</a>';
		}


  /**
   * Render footer
   */
  	private function renderFooter() {
  		global $wgTweekiSkinFooterIcons;
			$options = array( 
					'wrapper' => 'li',
					'wrapperclass' => '',
					);
  		
			foreach ( $this->getFooterLinks() as $category => $links ) { 
				if ( $this->checkVisibility( 'footer-' . $category ) ) { ?>
					<ul id="footer-<?php echo $category ?>">
						<?php foreach ( $links as $link ) { 
							if ( $this->checkVisibility( 'footer-' . $category . '-' . $link ) ) { ?>
								<li id="footer-<?php echo $category ?>-<?php echo $link ?>"><?php $this->html( $link ) ?></li>
							<?php } 
						} ?>
					</ul>
				<?php } 
				} 
				if ( wfMessage ( 'tweeki-footer' )->plain() !== "" ) {?>
				<ul id="footer-custom">
<?php			$this->buildItems( wfMessage ( 'tweeki-footer' )->plain(), $options, 'footer' ); ?>
				</ul>
<?php		}
				$footericons = $this->getFooterIcons( "icononly" );
				if ( count( $footericons ) > 0 && $this->checkVisibility( 'footer-icons' ) ) { ?>
				<ul id="footer-icons">
<?php		foreach ( $footericons as $blockName => $footerIcons ) { 
					if ( $this->checkVisibility( 'footer-' . $blockName ) ) { ?>
					<li id="footer-<?php echo htmlspecialchars( $blockName ); ?>ico">
<?php				foreach ( $footerIcons as $icon ) { 
						  if($wgTweekiSkinFooterIcons) {
								echo $this->getSkin()->makeFooterIcon( $icon ); 
							}
							else {
								echo '<span>' . $this->getSkin()->makeFooterIcon( $icon, 'withoutImage' ) . '</span>'; 
							}
						} ?>
					</li>
<?php			}
				} ?>
				</ul>
			<?php } ?>
			<div style="clear:both"></div>
<?php
	}
	
}
