<?php
/**
 * Tweeki - Tweaked version of Vector, using Twitter bootstrap.
 *
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) ) {
  die( -1 );
}

/**
 * SkinTemplate class for Tweeki skin
 * @ingroup Skins
 */
class SkinTweeki extends SkinTemplate {

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
    // this is better than including this in a CSS fille since it doesn't
    // wait for the CSS file to load before fetching the HTC file.
    $min = $this->getRequest()->getFuzzyBool( 'debug' ) ? '' : '.min';
    $out->addHeadItem( 'csshover',
      '<!--[if lt IE 7]><style type="text/css">body{behavior:url("' .
        htmlspecialchars( $wgLocalStylePath ) .
        "/{$this->stylename}/csshover{$min}.htc\")}</style><![endif]-->"
    );

    $out->addHeadItem('responsive', '<meta name="viewport" content="width=device-width, initial-scale=1.0">');
    $out->addModules( 'skins.tweeki.scripts' );
  }

  /**
   * Load skin and user CSS files in the correct order
   * fixes bug 22916
   * @param $out OutputPage object
   */
  function setupSkinUserCss( OutputPage $out ){
    parent::setupSkinUserCss( $out );
    $out->addModuleStyles( 'skins.tweeki.styles' );
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
  	//additional globals
		global $wgRequest, $wgContLang, $wgLang, $wgStylePath, $wgServer, $wgSitename, $wgOut, $wgUser, $wgSkrifoSettings;

    global $wgGroupPermissions;
    global $wgVectorUseIconWatch;
    global $wgSearchPlacement;
    global $wgTweekiSkinLogoLocation;
    global $wgTweekiSkinLoginLocation;
    global $wgTweekiSkinHideAnon;
    global $wgTweekiSkinUseStandardLayout;

    if (!$wgSearchPlacement) {
      $wgSearchPlacement['header'] = true;
      $wgSearchPlacement['nav'] = false;
      $wgSearchPlacement['footer'] = false;
    }

    // Build additional attributes for navigation urls
    $nav = $this->data['content_navigation'];

    if ( $wgVectorUseIconWatch ) {
      $mode = $this->getSkin()->getTitle()->userIsWatching() ? 'unwatch' : 'watch';
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

    if ($this->data['loggedin']) {
      $userStateClass = "user-loggedin";
    } else {
      $userStateClass = "user-loggedout";
    }


	//set 'namespace', 'shortnamespace', and 'title_formatted' variables
	reset($this->data['namespace_urls']);
	$currentNamespace = current($this->data['namespace_urls']);
	$this->data[ 'namespace' ] = $currentNamespace['text'];
	$this->data[ 'shortNamespace' ] = $this->data[ 'namespace' ];
	if(stripos( $this->data[ 'namespace' ],"fragen") !== false) { $this->data[ 'shortNamespace' ] = "Fragen"; } /* needs some rework */
	if( $this->data[ 'namespace' ] == "Datei" ) { $this->data[ 'shortNamespace' ] = "Dateiseite"; } /* ugly */
	$this->data['title_formatted'] = $this->data['title'];
	if(strpos( $this->data['title'],":") !== false) {
		$this->data['title_formatted'] = '<span class="namespace">' . str_replace( ":", ":</span> ", $this->data['title'] );
		}

    // Output HTML Page
    $this->html( 'headelement' );
?>

    <div id="mw-page-base" class="noprint"></div>
    <div id="mw-head-base" class="noprint"></div>


    <!-- content -->
    <section id="content" class="mw-body container-fluid <?php echo $userStateClass; ?>">

		<?php if( wfMessage( 'tweeki-subnav' )->plain() !== '-' && $this->checkVisibility( 'subnav' ) ) { ?>
		<!-- subnav -->
		<div id="page-header" class="row-fluid">
			<div class="<?php echo ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) ? 'offset3 span9' : 'span12'; ?>">
				<ul class="navigation nav nav-pills pull-right searchform-disabled">
				<?php	$this->renderSubnav(); ?>
				</ul>
			</div>
		</div>
		<!-- /subnav -->
		<?php } ?>

    <div class="row-fluid">
			<div class="<?php echo ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) ? 'offset3 span9' : 'span12'; ?>">
				<div id="top"></div>
				<div id="mw-js-message" style="display:none;"<?php $this->html( 'userlangattributes' ) ?>></div>
				<?php if ( $this->data['sitenotice'] ): ?>
				<!-- sitenotice -->
				<div id="siteNotice"><?php $this->html( 'sitenotice' ) ?></div>
				<!-- /sitenotice -->
				<?php endif; ?>
				<!-- bodyContent -->
				<div id="bodyContent">
					<?php if( $this->data['newtalk'] ): ?>
					<!-- newtalk -->
					<div class="usermessage"><?php $this->html( 'newtalk' )  ?></div>
					<!-- /newtalk -->
					<?php endif; ?>
					<?php if ( $this->data['showjumplinks'] ): ?>
					<!-- jumpto -->
					<div id="jump-to-nav" class="mw-jump">
						<?php $this->msg( 'jumpto' ) ?> <a href="#mw-head"><?php $this->msg( 'jumptonavigation' ) ?></a>,
						<a href="#p-search"><?php $this->msg( 'jumptosearch' ) ?></a>
					</div>
					<!-- /jumpto -->
					<?php endif; ?>

					<!-- innerbodycontent -->
					<?php # Peek into the body content, to see if a custom layout is used
					if ($wgTweekiSkinUseStandardLayout || preg_match("/class.*row/i", $this->data['bodycontent'])) { 
						# If there's a custom layout, the H1 and layout is up to the page ?>
						<div id="innerbodycontent" class="layout">
							<h1 id="firstHeading" class="firstHeading page-header">
								<span dir="auto"><?php $this->html( 'title_formatted' ) ?></span>
							</h1>
							<!-- subtitle -->
							<div id="contentSub" <?php $this->html( 'userlangattributes' ) ?>><?php $this->html( 'subtitle' ) ?></div>
							<!-- /subtitle -->
							<?php if ( $this->data['undelete'] ): ?>
							<!-- undelete -->
							<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
							<!-- /undelete -->
							<?php endif; ?>
							<?php $this->html( 'bodycontent' ); ?>
						</div>
					<?php } else {
						# If there's no custom layout, then we automagically add one ?>
						<div id="innerbodycontent" class="nolayout"><div>
							<h1 id="firstHeading" class="firstHeading page-header">
								<span dir="auto"><?php $this->html( 'title_formatted' ) ?></span>
							</h1>
							<!-- subtitle -->
							<div id="contentSub" <?php $this->html( 'userlangattributes' ) ?>><?php $this->html( 'subtitle' ) ?></div>
							<!-- /subtitle -->
							<?php if ( $this->data['undelete'] ): ?>
							<!-- undelete -->
							<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
							<!-- /undelete -->
							<?php endif; ?>
							<?php $this->html( 'bodycontent' ); ?>
						</div></div>
					<?php } ?>
					<!-- /innerbodycontent -->

					<?php if ( $this->data['printfooter'] ): ?>
					<!-- printfooter -->
					<div class="printfooter">
					<?php $this->html( 'printfooter' ); ?>
					</div>
					<!-- /printfooter -->
					<?php endif; ?>
					<?php if ( $this->data['catlinks'] ): ?>
					<!-- catlinks -->
					<?php $this->html( 'catlinks' ); ?>
					<!-- /catlinks -->
					<?php endif; ?>
					<?php if ( $this->data['dataAfterContent'] ): ?>
					<!-- dataAfterContent -->
					<?php $this->html( 'dataAfterContent' ); ?>
					<!-- /dataAfterContent -->
					<?php endif; ?>
					<div class="visualClear"></div>
					<!-- debughtml -->
					<?php $this->html( 'debughtml' ); ?>
					<!-- /debughtml -->
				</div>
				<!-- /bodyContent -->
			</div>
    </div>
    </section>
    <!-- /content -->

		<?php if ( $this->checkVisibility( 'navbar' ) ) { ?>
		<!-- navbar -->
		<div id="userbar" class="navbar navbar-fixed-top">
			<div class="navbar-inner">
			<div class="container-fluid">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>

				<?php $this->renderBrand(); ?>

				<div class="pull-left nav-collapse collapse">
					<ul class="nav" role="navigation">
					<?php $this->renderNavbar( 'left' ); ?>
				</ul>
				</div>

				<div class="pull-right nav-collapse collapse">
					<ul class="nav" role="navigation">
					<?php $this->renderNavbar( 'right' ); ?>
					</ul>
				</div>

			</div>
			</div>
		</div>
		<!-- /navbar -->
		<?php } ?>

		<?php if ( ( count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( 'sidebar' ) ) { ?>
		<!-- sidebar -->
		<div id="sidebar" class="noprint">
			<!-- firstHeadingSidebar -->
			<h1 id="firstHeadingSidebar" class="firstHeadingSidebar"><?php echo $this->data[ 'title_formatted' ]; ?></h1>
			<!-- /firstHeadingSidebar -->			
			<!-- downloadEdit -->
			<div id="DownloadEdit">
			<?php $this->renderSidebar(); ?>
			</div>
			<!-- SideTOC -->
			<div id="SideTOC"></div>
			<!-- /SideTOC -->
		</div>
		<!-- /sidebar -->
		<?php } ?>
			
			<?php if ( $this->checkVisibility( 'footer' ) ) { ?>
      <!-- footer -->
      <div id="footer" class="footer container"<?php $this->html( 'userlangattributes' ) ?>>
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
        			<img src="<?php $this->text( 'logopath' ); ?>" alt="<?php $this->html('sitename'); ?>" style="height:40px">
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
					if(count($this->data['view_urls']) > 0) {
						unset($this->data['view_urls']['view']);
						$link = array_shift($this->data['view_urls']); 
						if ( $this->checkVisibility( 'EDIT-EXT-special' ) ) {
							$button = array(
								'href' => $link['href'],
								'key' => $link['key'],
								'href_implicit' => false,
								'icon' => 'icon-pencil icon-white',
								// TODO: i18n!!!
								'text' => $this->data[ 'shortNamespace' ] . ' bearbeiten',
								'class' => 'btn-primary btn-edit'
								);
							$views = $this->renderNavigation( 'VIEWS' );
							$button['items'] = $views[0]['items'];
							if(count($this->data['action_urls']) > 0) {
								$button['items'][] = array(); #divider
								$actions = $this->renderNavigation( 'ACTIONS' ); 
								$button['items'] = array_merge( $button['items'], $actions[0]['items'] );
								}
							}
						else {
							$button = $link;
							$button['icon'] = 'icon-pencil icon-white';
							$button['text'] = $this->data[ 'shortNamespace' ] . ' bearbeiten';
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
					$text = (wfMessage( 'tweeki-toolbox' ) == "") ? wfMessage( 'toolbox' ) . " " : wfMessage( 'tweeki-toolbox' );
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
            if ( !$content ) {
            	# traditional sidebar formatting with pipe character has to be reversed
            	if( strpos( $name, '|' ) !== false ) {
              	$name = explode( '|', $name );
              	$name = array_reverse( $name );
              	$name = implode( '|', $name );
              	$sidebarItem = TweekiHooks::parseButtonLink( $name, $wgParser );
              	$sidebar[] = $sidebarItem[0];
              	continue;
              	}
              # navigational keywords
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
          if (count($items) > 0) { 
						return array(array( 
							'href' => '#',
							'text' => wfMessage( 'otherlanguages' ),
							'id' => 'p-otherlanguages',
							'items' => $items
							));    
						}      
          break;
        
        case 'SEARCH':
        	return array( 'special' => 'SEARCH' );
        	break;

        case 'LOGO':
        	return array( 'special' => 'LOGO' );
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
		global $wgUser, $wgTweekiSkinHideNonPoweruser, $wgTweekiSkinHideAnon;
		if ( ( !in_array( $item, $wgTweekiSkinHideNonPoweruser ) || $wgUser->getOption( 'tweeki-poweruser' ) ) &&
			( !in_array( $item, $wgTweekiSkinHideAnon ) || $this->data['loggedin'] ) ) {
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
   */
	private function renderNavbar( $side ) {
		$otherside = ( $side == 'right' ) ? 'left' : 'right';
		$options = array( 
					'wrapper' => 'li', 
					'wrapperclass' => 'nav dropdown', 
					'dropdownclass' => 'pull-' . $side 
					);
		$this->buildItems( wfMessage( 'tweeki-navbar-' . $side )->plain(), $options, 'navbar' );    
		}


  /**
   * Render Sidebar
   */
	private function renderSidebar() {
		$options = array( 
					'class' => 'btn',
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
				if(count($navbarItem) !== 0) $buttons[] = $navbarItem;
				}
			else {
				$customItems[] = $navbarItem;
				}
			}
		$this->renderCustomNavigation( $buttons, $customItems );
		foreach( $buttons as $button ) {
			/* standard button rendering */
			if( !isset( $button['special'] ) ) {
				echo TweekiHooks::renderButtons( $button, $options );
				}
			/* special cases */
			else {
				switch ( $button['special'] ) {
					case 'SEARCH':
	          ?>
            <form class="navbar-search" action="<?php $this->text( 'wgScript' ) ?>" id="searchform">
              <input id="searchInput" class="search-query" type="search" accesskey="f" title="<?php $this->text('searchtitle'); ?>" placeholder="<?php $this->msg('search'); ?>" name="search" value="<?php echo $this->data['search']; ?>">
              <?php echo $this->makeSearchButton( 'fulltext', array( 'id' => 'mw-searchButton', 'class' => 'searchButton btn hidden' ) ); ?>
            </form>
	          <?php
						break;
					case 'LOGO':
						$this->renderLogo( $context );
						break;
					}
				}
			}
		}


  /**
   * Render navigations elements that renderNavigation hasn't dealt with
   *
   * @param $buttons Array
   * @param $customItems Array
   */
	private function renderCustomNavigation( &$buttons, &$customItems ) {
		$localParser = new Parser();		
		if( count( $customItems ) !== 0 ) {
			$buttons[] = TweekiHooks::parseButtons( implode( chr(10), $customItems ), $localParser );
			$customItems = array();
			}
		}

		
  /**
   * Render brand (linking to mainpage)
   */
	private function renderBrand() {
		$brand = $this->renderNavigation( wfMessage( 'tweeki-navbar-brand' ) );
		if(!is_array( $brand )) {
			$brand = array( array( 
								'text' => wfMessage( 'tweeki-navbar-brand' )->text(), 
								'href' => $this->data['nav_urls']['mainpage']['href'] 
								) );
			}
		$options = array( 
								'class' => array('brand'), 
								'wrapper' => 'li'
								);
		echo '<ul class="nav" role="navigation">' . TweekiHooks::renderButtons( $brand, $options ) . '</ul>';
		}


  /**
   * Render footer
   */
  	private function renderFooter() {
    	global $wgTweekiSkinLoginLocation, $wgSearchPlacement, $wgTweekiSkinFooterIcons;
    	$footerLinks = $this->getFooterLinks();

      if (is_array($footerLinks)) {
        foreach($footerLinks as $category => $links ):
          if ($category === 'info') { continue; } ?>

            <ul id="footer-<?php echo $category ?>">
              <?php foreach( $links as $link ): ?>
                <li id="footer-<?php echo $category ?>-<?php echo $link ?>"><?php $this->html( $link ) ?></li>
              <?php endforeach; ?>
              <?php
                if ($category === 'places') {

                  # Show sign in link, if not signed in
                  if ($wgTweekiSkinLoginLocation == 'footer' && !$this->data['loggedin']) {
                    $personalTemp = $this->getPersonalTools();

                    if (isset($personalTemp['login'])) {
                      $loginType = 'login';
                    } else {
                      $loginType = 'anonlogin';
                    }

                    ?><li id="pt-login"><a href="<?php echo $personalTemp[$loginType]['links'][0]['href'] ?>"><?php echo $personalTemp[$loginType]['links'][0]['text']; ?></a></li><?php
                  }

                  # Show the search in footer to all
                  if ($wgSearchPlacement['footer']) {
                    echo '<li>';
                    $this->renderNavigation( array( 'SEARCHFOOTER' ) ); 
                    echo '</li>';
                  }
                }
              ?>
            </ul>
          <?php 
              endforeach; 
            }
          ?>
          <?php $footericons = $this->getFooterIcons("icononly");
          if ( count( $footericons ) > 0 ): ?>
            <ul id="footer-icons" class="noprint">
    <?php      foreach ( $footericons as $blockName => $footerIcons ): ?>
              <li id="footer-<?php echo htmlspecialchars( $blockName ); ?>ico">
    <?php        foreach ( $footerIcons as $icon ): 
    				/* TODO: setting: icons or text only? */
    				if($wgTweekiSkinFooterIcons) {
    					echo $this->getSkin()->makeFooterIcon( $icon ); 
    					}
    				else {
						if ( is_string( $icon ) ) {
							$html = $icon;
							} 
						else { // Assuming array
							$url = isset( $icon["url"] ) ? $icon["url"] : null;
							unset( $icon["url"] );
							$html = htmlspecialchars( $icon["alt"] );
							if ( $url ) {
								$html = '<span>' . Html::rawElement( 'a', array( "href" => $url ), $html ) . '</span>';
							 	}
							}
							echo $html;
						}
    	        endforeach; ?>
              </li>
    <?php      endforeach; ?>
            </ul>
          <?php endif;
	}
}
