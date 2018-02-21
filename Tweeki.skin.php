<?php
/**
 * Tweeki - Tweaked version of Vector, using Twitter Bootstrap.
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
		global $wgLocalStylePath, $wgUser, $wgTweekiSkinUseSmoothDivScroll, $wgTweekiSkinUseTooltips;

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
		if( $wgTweekiSkinUseTooltips ) {
			$out->addModules( 'skins.tweeki.tooltips' );
		}
		if( $wgUser->getOption( 'tweeki-advanced' ) ) {
			static::$bodyClasses[] = 'advanced';
		}
		wfRunHooks( 'SkinTweekiAdditionalBodyClasses', array( $this, &$GLOBALS['wgTweekiSkinAdditionalBodyClasses'] ) );
		static::$bodyClasses = array_merge( static::$bodyClasses, $GLOBALS['wgTweekiSkinAdditionalBodyClasses'] );
	}

	/**
	 * Loads skin and user CSS files.
	 * @param $out OutputPage object
	 */
	function setupSkinUserCss( OutputPage $out ) {
		global $wgTweekiSkinStyles, $wgTweekiSkinUseAwesome, $wgTweekiSkinUseBootstrapTheme, $wgTweekiSkinCustomCSS;

		parent::setupSkinUserCss( $out );
		
		$styles = $wgTweekiSkinStyles; 
		if( $wgTweekiSkinUseAwesome === true ) {
			$styles[] = 'skins.tweeki.awesome.styles';
		}
		if( $wgTweekiSkinUseBootstrapTheme === true ) {
			$styles[] = 'skins.tweeki.bootstraptheme.styles';
		}
		if( isset( $GLOBALS['wgCookieWarningEnabled'] ) && $GLOBALS['wgCookieWarningEnabled'] === true ) {
			$styles[] = 'skins.tweeki.cookiewarning.styles';
		}
		foreach( $wgTweekiSkinCustomCSS as $customstyle ) {
			$styles[] = $customstyle;
		}
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
		global $wgTweekiSkinPageRenderer;

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
			$this->data['userstateclass'] = "user-loggedin";
		} else {
			$this->data['userstateclass'] = "user-loggedout";
		}
		
		if ( $wgGroupPermissions['*']['edit'] || $this->data['loggedin'] ) {
			$this->data['userstateclass'] .= " editable";
		} else {
			$this->data['userstateclass'] .= " not-editable";
		}
		
		//set 'namespace' and 'title_formatted' variables
		$this->data['namespace'] = $this->getSkin()->getTitle()->getNsText();
		$this->data['title_formatted'] = $this->data['title'];
		if( strpos( $this->data['title'], $this->data['namespace'] . ":" ) !== false ) { 
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
		call_user_func_array( $wgTweekiSkinPageRenderer, array( $this ) );
?>
	</body>
</html>
<?php
	}



	/**
	 * Render the whole page 
	 *
	 * copy this function and use $wgTweekiSkinPageRenderer to create
	 * completely custom page layouts
	 *
	 * @param $skin Skin skin object
	 */
	private function renderPage( $skin ) {
		// load defaults for layout without sidebar
		$main_offset = $GLOBALS['wgTweekiSkinGridNone']['mainoffset'];
		$main_width = $GLOBALS['wgTweekiSkinGridNone']['mainwidth'];
		$left_width = 0;
		$left_offset = 0;
		$right_width = 0;
		$right_offset = 0;
		// TODO: check for situational emptiness of sidebar (e.g. on special pages)
		if( true ) { 
			$sidebar_left = $skin->checkVisibility( 'sidebar-left' ) && !$skin->checkEmptiness( 'sidebar-left' );
			$sidebar_right = $skin->checkVisibility( 'sidebar-right' ) && !$skin->checkEmptiness( 'sidebar-right' );
			if( $sidebar_left && $sidebar_right ) { // both sidebars
				$left_offset = $GLOBALS['wgTweekiSkinGridBoth']['leftoffset'];
				$left_width = $GLOBALS['wgTweekiSkinGridBoth']['leftwidth'];
				$main_offset = $left_offset + $left_width + $GLOBALS['wgTweekiSkinGridBoth']['mainoffset'];
				$main_width = $GLOBALS['wgTweekiSkinGridBoth']['mainwidth'];
				$right_offset = $main_offset + $main_width + $GLOBALS['wgTweekiSkinGridBoth']['rightoffset'];
				$right_width = $GLOBALS['wgTweekiSkinGridBoth']['rightwidth'];
			}
			if( $sidebar_left XOR $sidebar_right ) { // only one of the sidebars
				if( $sidebar_left ) {
					$left_offset = $GLOBALS['wgTweekiSkinGridLeft']['leftoffset'];
					$left_width = $GLOBALS['wgTweekiSkinGridLeft']['leftwidth'];
					$main_offset = $left_offset + $left_width + $GLOBALS['wgTweekiSkinGridLeft']['mainoffset'];
					$main_width = $GLOBALS['wgTweekiSkinGridLeft']['mainwidth'];
				}
				else {
					$main_offset = $GLOBALS['wgTweekiSkinGridRight']['mainoffset'];
					$main_width = $GLOBALS['wgTweekiSkinGridRight']['mainwidth'];
					$right_offset = $main_offset + $main_width + $GLOBALS['wgTweekiSkinGridRight']['rightoffset'];
					$right_width = $GLOBALS['wgTweekiSkinGridRight']['rightwidth'];
				}
			}
		}
		$mainclass = 'col-md-offset-' . $main_offset . ' col-md-' . $main_width;
		$contentclass = $skin->data['userstateclass'];
		$contentclass .= ' ' . wfMessage( 'tweeki-container-class' )->escaped();
		$contentclass .= ( $skin->checkVisibility( 'navbar' ) ) ? ' with-navbar' : ' without-navbar';
		if( false !== stripos( wfMessage( 'tweeki-navbar-class' ), 'navbar-fixed' ) ) {
			$contentclass .= ' with-navbar-fixed';
		}

		$skin->renderNavbar();
?>
		<div id="mw-page-base"></div>
		<div id="mw-head-base"></div>
		<a id="top"></a>

		<!-- content -->
		<div id="contentwrapper" class="<?php echo $contentclass; ?>">

			<?php if( !$skin->checkEmptiness( 'subnav' ) ) { $skin->renderSubnav( $mainclass ); } ?>

			<div class="row">
				<div class="<?php echo $mainclass ?>" role="main">
					<?php $skin->renderContent(); ?>
				</div>
			</div>
		</div>
		<!-- /content -->

<?php
		if( !$skin->checkEmptiness( 'sidebar-left' ) ) { 
			$leftclass = 'col-md-' . $left_width . ' col-md-offset-' . $left_offset;
			$skin->renderSidebar( 'left', $leftclass ); 
		}
		if( !$skin->checkEmptiness( 'sidebar-right' ) ) { 
			$rightclass = 'col-md-' . $right_width . ' col-md-offset-' . $right_offset;
			$skin->renderSidebar( 'right', $rightclass ); 
		}
		$skin->renderFooter();
		$skin->printTrail(); 
	}

	/**
	 * Render one or more navigations elements by name, automatically reveresed
	 * when UI is in RTL mode
	 *
	 * @param $elements array
	 */
	public function renderNavigation( $elements ) {
		global $wgUser,
			$wgTweekiSkinHideNonAdvanced, 
			$wgParser,
			$wgTweekiSkinNavigationalElements,
			$wgTweekiSkinSpecialElements;

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
			// was this element defined in LocalSettings?
			if ( isset( $wgTweekiSkinNavigationalElements[ $element ] ) ) {
				return call_user_func( $wgTweekiSkinNavigationalElements[ $element ], $this );
			}
			// is it a special element with special non-buttonesque rendering?
			if ( isset( $wgTweekiSkinSpecialElements[ $element ] ) ) {
				return array( array( 'special' => $element ) );
			}

			switch ( $element ) {

				case 'EDIT':
					$views = $this->data['view_urls'];
					if(count( $views ) > 0) {
						unset( $views['view'] );
						$link = array_shift( $views );
						$link['icon'] = 'pencil';
						return array( $link );					
					}
					return array();
					break;

				case 'EDIT-EXT':
					$views = $this->data['view_urls'];
					if(count( $views ) > 0) {
						unset( $views['view'] );
						$link = array_shift( $views );
						if ( $this->checkVisibility( 'EDIT-EXT-special' ) ) {
							$button = array(
								'href' => $link['href'],
								'href_implicit' => false,
								'id' => 'ca-edit-ext',
								'icon' => 'pencil',
								'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain()
								);
							$button['items'] = $views;
							if(count($this->data['action_urls']) > 0) {
								$button['items'][] = array(); #divider
								$actions = $this->renderNavigation( 'ACTIONS' ); 
								$button['items'] = array_merge( $button['items'], $actions[0]['items'] );
							}
						} else {
							$button = array( 
								'href' => $link['href'],
								'id' => 'ca-edit',
								'icon' => 'pencil',
								'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain()
								);
						}
						return array($button);
					}
					return array();
					break; 

				case 'PAGE':
					$items = array_merge( $this->data['namespace_urls'], $this->data['view_urls'] );
					$text = wfMessage( 'namespaces' );
					foreach ( $items as $key => $link ) {
						if ( array_key_exists( 'context', $link ) && $link['context'] == 'subject' ) {
							$text = $link['text'];
						}
						if (preg_match('/^ca-(view|edit)$/', $link['id'])) { 
							unset( $items[$key] ); 
						}
					}
					return array(array( 
						'href' => '#',
						'text' => $text,
						'id' => 'n-namespaces',
						'items' => $items
						));					
					break;

				case 'NAMESPACES':
					$items = $this->data['namespace_urls'];
					$text = wfMessage( 'namespaces' );
					foreach ( $items as $key => $link ) {
						if ( array_key_exists( 'context', $link ) && $link['context'] == 'subject' ) {
							$text = $link['text'];
						}
						if ( array_key_exists( 'attributes', $link ) && false !== strpos( $link['attributes'], 'selected' ) ) {
							unset( $items[$key] );
						}
					}
					return array(array( 
						'href' => '#',
						'text' => $text,
						'id' => 'n-namespaces',
						'items' => $items
						));					
					break;

				case 'TALK':
					$items = $this->data['namespace_urls'];
					$text = wfMessage( 'namespaces' );
					foreach ( $items as $key => &$link ) {
						if ( array_key_exists( 'context', $link ) && $link['context'] == 'subject' ) {
							$text = $link['text'];
						}
						if ( array_key_exists( 'attributes', $link ) && false !== strpos( $link['attributes'], 'selected' ) ) {
							unset( $items[$key] );
						}
						unset( $link['class'] ); // interferes with btn classing
					}
					return $items;					
					break;

				case 'TOOLBOX':
					$items = array_reverse($this->getToolbox());
					$divideditems = array();
					$html = (wfMessage( 'tweeki-toolbox' )->plain() == "") ? wfMessage( 'toolbox' )->plain() : wfMessage( 'tweeki-toolbox' )->plain();
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
						'html' => $html,
						'id' => 't-tools',
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
							'id' => 'ca-variants',
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
							'id' => 'ca-views',
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
							'id' => 'ca-actions',
							'items' => $items
							));		
					}			
					break;

				case 'WATCH':
					$button = null;
					$actions = array_reverse( $this->data['action_urls'] );
					if( isset( $actions['watch'] )  ) {
						$button = $actions['watch'];
						$options['wrapperid'] = $button['id'];
						unset( $button['id'] );
					} else if( isset( $actions['unwatch'] ) ) {
						$button = $actions['unwatch'];
						$options['wrapperid'] = $button['id'];
						unset( $button['id'] );
					}
					if( !is_null( $button ) ) {
						return array( $button );
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
						$divideditems['login']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return array( $divideditems['login'] );
					}
					if ( array_key_exists( 'anonlogin', $divideditems ) ) {
						$divideditems['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return array( $divideditems['anonlogin'] );
					}
					if (count($items) > 0) { 
						return array(array( 
								'href' => '#',
								'html' => '<span class="tweeki-username">' . $this->data['username'] . '</span>',
								'icon' => 'user',
								'id' => 'pt-personaltools',
								'items' => $divideditems
								));
					}
					break;

				case 'PERSONAL-EXT':
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
						return array( array( 'special' => 'LOGIN-EXT' ) );
					}
					if ( array_key_exists( 'anonlogin', $divideditems ) ) {
						return array( array( 'special' => 'LOGIN-EXT' ) );
					}
					if (count($items) > 0) { 
						return array(array( 
								'href' => '#',
								'text' => $this->data['username'],
								'icon' => 'user',
								'id' => 'pt-personaltools',
								'items' => $divideditems
								));
					}
					break;

				case 'LOGIN':
					$items = $this->getPersonalTools();
					if ( array_key_exists( 'login', $items ) ) {
						$items['login']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return array( $items['login'] );
					}
					if ( array_key_exists( 'anonlogin', $items ) ) {
						$items['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return array( $items['anonlogin'] );
					}
					return array();
				break;

				case 'SIDEBAR':
					$sidebar = array();
					foreach ( $this->data['sidebar'] as $name => $content ) {
						if ( empty ( $content ) ) {
							if( strpos( $name, '|' ) !== false ) {
								/* TODO: replace $wgParser with local parser - might not work properly */
								$sidebarItem = TweekiHooks::parseButtonLink( $name, $wgParser, false );
								$sidebar[] = $sidebarItem[0];
								continue;
							}
							// navigational keywords
							$navigation = $this->renderNavigation( $name );
							if( is_array( $navigation ) ) {
								if( isset( $navigation[0] ) ) {
									$sidebar[] = $navigation[0];
								}
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
				
				default:
					return $element;
					break;
			}
		}
	}


	/**
	 * Check navigational sections for content
	 *
	 * @param $item String
	 */
	public function checkEmptiness( $item ) {
		return wfMessage( 'tweeki-' . $item )->isDisabled();
	}


	/**
	 * Elements can be hidden for anonymous or logged in users or for everybody who has not opted
	 * to show the advanced features in their preferences
	 *
	 * @param $item String
	 */
	public function checkVisibility( $item ) {
		global $wgUser, $wgTweekiSkinHideNonAdvanced, $wgTweekiSkinHideAnon, $wgTweekiSkinHideAll, $wgTweekiSkinHideLoggedin;
		if ( 
			( 
				!$this->checkVisibilitySetting( $item, $wgTweekiSkinHideNonAdvanced ) || 
				$wgUser->getOption( 'tweeki-advanced' ) // not hidden for non-advanced OR advanced
			) && 
			( 
				!$this->checkVisibilitySetting( $item, $wgTweekiSkinHideAnon ) || 
				$this->data['loggedin'] // not hidden for anonymous users OR non-anonymous user
			) && 
			(
				!$this->checkVisibilitySetting( $item, $wgTweekiSkinHideLoggedin ) ||
				!$this->data['loggedin'] // not hidden for logged-in users OR anonymous user
			) &&
			!$this->checkVisibilitySetting( $item, $wgTweekiSkinHideAll ) // not hidden for all
			&&
			false !== wfRunHooks( 'SkinTweekiCheckVisibility', array( $this, $item ) ) // not hidden via hook
		) { 
			return true;
		}	else {
			return false;
		}
	}

	/**
	 * Check if an element has an entry in a configuration option and if it's set to true 
	 * (i.e. the element should be hidden to the corresponding group)
	 *
	 * @param $item Element to be tested
	 * @param $setting Configuration option to be searched
	 *
	 * @return Boolean returns true, if the element is hidden
	 */
	public function checkVisibilitySetting( $item, $setting ) {
		// this is for backwards compatibility
		if( in_array( $item, $setting, true ) ) {
			return true;
		}
		if( array_key_exists( $item, $setting ) ) {
			return $setting[$item] ? true : false;
		}
		return false;
	}


	/**
	 * Render Subnavigation
	 */
	public function renderSubnav( $class ) {
		$options = $this->getParsingOptions( 'subnav' );
		if( !wfMessage( 'tweeki-subnav' )->isDisabled() && $this->checkVisibility( 'subnav' ) ) { ?>
			<!-- subnav -->
			<div id="page-header" class="row">
				<div class="<?php echo $class; ?>">
					<ul class="<?php $this->msg( 'tweeki-subnav-class' ) ?>">
					<?php $this->buildItems( wfMessage( 'tweeki-subnav' )->plain(), $options, 'subnav' ); ?>
					</ul>
				</div>
			</div>
			<!-- /subnav -->
		<?php }
	}
		
	
	/**
	 * Render Navbar
	 */
	public function renderNavbar() {
		if ( $this->checkVisibility( 'navbar' ) ) { ?>
			<!-- navbar -->
			<div id="mw-navigation" class="<?php $this->msg( 'tweeki-navbar-class' ); ?>" role="navigation">
				<h2><?php $this->msg( 'navigation-heading' ) ?></h2>
				<div id="mw-head" class="navbar-inner">
					<div class="<?php $this->msg( 'tweeki-container-class' ); ?>">
				
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

						<div id="navbar" class="navbar-collapse collapse">
						<?php if ( $this->checkVisibility( 'navbar-left' ) ) { ?>
							<ul class="nav navbar-nav">
							<?php $this->renderNavbarElement( 'left' ); ?>
							</ul>
						<?php } ?>

						<?php if ( $this->checkVisibility( 'navbar-right' ) ) { ?>
							<ul class="nav navbar-nav navbar-right">
							<?php $this->renderNavbarElement( 'right' ); ?>
							</ul>
						</div>
						<?php } ?>

					</div>
				</div>
			</div>
			<!-- /navbar -->
		<?php }
	}


	/**
	 * Render Navbarelement
	 *
	 * @param $side string
	 */
	private function renderNavbarElement( $side ) {
		$element = 'navbar-' . $side;
		$options = $this->getParsingOptions( $element );
		$this->buildItems( wfMessage( 'tweeki-' . $element )->plain(), $options, $element );		
	}


	/**
	 * Render Sidebar
	 *
	 * @param $side string
	 * @param $class string
	 */
	public function renderSidebar( $side, $class = '' ) {
		$element = 'sidebar-' . $side;
		$options = $this->getParsingOptions( $element );
		/* TODO: can we move these criteria elsewhere? rather there should be some handling for empty sidebars */
		if ( ( true || count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( $element ) ) { ?>
			<!-- <?php echo $element; ?> -->
			<div class="sidebar-wrapper <?php echo $element; ?>-wrapper">
				<div class="sidebar-container <?php echo wfMessage( 'tweeki-container-class' )->plain(); ?>">
					<div class="row">
						<div id="<?php echo $element; if( $class !== '' ) { echo '" class="' . $class; } ?>">
							<?php $this->buildItems( wfMessage( 'tweeki-' . $element )->plain(), $options, $element ); ?>
						</div>
					</div>
				</div>
			</div>
			<!-- /<?php echo $element;?> -->
		<?php }
	}
		

	/**
	 * Render Content
	 */
	public function renderContent() {
		?>
		<div class="mw-body" id="content">
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
	<?php }


	/**
	 * Render Footer
	 */
	public function renderFooter() {
		$options = $this->getParsingOptions( 'footer' );
		if ( $this->checkVisibility( 'footer' ) ) { ?>
			<!-- footer -->
			<div id="footer" role="contentinfo" class="footer <?php $this->msg( 'tweeki-container-class' ); ?> <?php $this->msg( 'tweeki-footer-class' ); ?>"<?php $this->html( 'userlangattributes' ) ?>>
			<?php $this->buildItems( wfMessage( 'tweeki-footer' )->plain(), $options, 'footer' ); ?>
			</div>
			<!-- /footer -->
		<?php }
	}
		

	/**
	 * Get options for navigational sections
	 *
	 * Options can be set via system messages
	 *
	 * @param $element string
	 */
	private function getParsingOptions( $element ) {
		$options = array();
		$available_options = array( 
			'btnclass',
			'wrapper',
			'wrapperclass',
			'dropdownclass'
			);
		foreach( $available_options as $option ) {
			$msg = wfMessage( 'tweeki-' . $element . '-' . $option );
			if( $msg->exists() ) {
				/* the btnclass option's name for the parser is different */
				if( $option === 'btnclass' ) {
					$option = 'class';
				}
				$options[$option] = $msg->parse();
			}
		}
		return $options;
	}		


	/**
	 * Build Items for navbar, subnav, sidebar
	 *
	 * @param $items String
	 * @param $options Array
	 * @param $context String
	 */
	public function buildItems( $items, $options, $context ) {
		global $wgTweekiSkinSpecialElements;
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
			}	else {
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
				call_user_func_array( $wgTweekiSkinSpecialElements[$button['special']], array( $this, $context, $options ) );
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

		/* TODO: check for unintended consequences, there are probably more elegant ways to do this... */		
		$options = new ParserOptions();
		$localParser = new Parser();
		$localParser->Title ( $this->getSkin()->getTitle() );
		$localParser->Options( $options );
		$localParser->clearState();

		if( count( $customItems ) !== 0 ) {
			$newButtons = TweekiHooks::parseButtons( implode( chr(10), $customItems ), $localParser, false );
			$buttons = array_merge( $buttons, $newButtons );
			$customItems = array();
		}
	}

		
	/**
	 * Render firstheading
	 */
	public function renderFirstHeading( $skin, $context ) {
		echo '<div class="tweekiFirstHeading">' . $skin->data[ 'title_formatted' ] . '</div>';
	}

	/**
	 * Render TOC
	 */
	function renderTOC( $skin, $context ) {
		if( $context == 'sidebar-left' || $context == 'sidebar-right' ) {
			echo '<div id="tweekiTOC"></div>';
		} else {
			echo '<li class="nav dropdown" id="tweekiDropdownTOC"><a id="n-toc" class="dropdown-toggle" data-toggle="dropdown" href="#">' . wfMessage( 'Toc' )->text() . '<span class="caret"></span></a><ul class="dropdown-menu pull-right" role="menu" id="tweekiTOC"><li><a href="#">' . wfMessage( 'tweeki-toc-top' )->text() . '</a></li><li class="divider"></li></ul></li>';
		}
	}

	/**
	 * Render logo
	 */
	function renderLogo( $skin, $context ) {
		$mainPageLink = $skin->data['nav_urls']['mainpage']['href'];
		$toolTip = Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( 'p-logo' ) );
		echo '
				<a id="p-logo" href="' . htmlspecialchars( $skin->data['nav_urls']['mainpage']['href'] ) . '" ' . Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( 'p-logo' ) ) . '>
					<img src="';
		$skin->text( 'logopath' );
		echo '" alt="';
		$skin->html( 'sitename' );
		echo '"></a>';
	}

	/**
	 * Render Login-ext
	 */
	function renderLoginExt( $skin, $context ) {
		global $wgUser, $wgRequest, $wgScript, $wgTweekiReturnto;
		
		if ( session_id() == '' ) {
			wfSetupSession();
		}

		//build path for form action
		$returntotitle = $skin->getSkin()->getTitle();
		$returnto = $returntotitle->getFullText();
		if ( $returnto == SpecialPage::getTitleFor( 'UserLogin' ) 
			|| $returnto == SpecialPage::getTitleFor( 'UserLogout' ) 
			|| !$returntotitle->exists() ) {
			$returnto = Title::newMainPage()->getFullText();
		}
		$returnto = $wgRequest->getVal( 'returnto', $returnto );
	
		if ( isset( $wgTweekiReturnto ) && $returnto == Title::newMainPage()->getFullText() ) {
			$returnto = $wgTweekiReturnto;
		}
		$action = $wgScript . '?title=special:userlogin&amp;action=submitlogin&amp;type=login&amp;returnto=' . $returnto;
		
		//create login token if it doesn't exist
		if( !$wgRequest->getSessionData( 'wsLoginToken' ) ) $wgRequest->setSessionData( 'wsLoginToken', MWCryptRand::generateHex( 32 ) );
		$wgUser->setCookies();

		$dropdown['class'] = ' dropdown-toggle';
		$dropdown['data-toggle'] = 'dropdown';
		$dropdown['text'] = $this->getMsg( 'userlogin' )->text();
		$dropdown['html'] = $dropdown['text'] . ' <b class="caret"></b>';
		$dropdown['href'] = '#';
		$dropdown['type'] = 'button';
		$dropdown['id'] = 'n-login-ext';
		$renderedDropdown = TweekiHooks::makeLink( $dropdown);
		$wrapperclass = ( $context == 'footer' ) ? 'dropup' : 'nav';

		echo '<li class="' . $wrapperclass . '">
		' . $renderedDropdown . '
		<ul class="dropdown-menu" role="menu" aria-labelledby="' . $this->getMsg( 'userlogin' )->text() . '" id="loginext">
			<form action="' . $action . '" method="post" name="userloginext" class="clearfix">
				<div class="form-group">
					<label for="wpName2" class="hidden-xs">
						' . $this->getMsg( 'userlogin-yourname' )->text() . '
					</label>';
		echo Html::input( 'wpName', null, 'text', array(
					'class' => 'form-control input-sm',
					'id' => 'wpName2',
					'tabindex' => '101',
					'placeholder' => $this->getMsg( 'userlogin-yourname-ph' )->text()
				) );					
		echo	'</div>
				<div class="form-group">
					<label for="wpPassword2" class="hidden-xs">
						' . $this->getMsg( 'userlogin-yourpassword' )->text() . '
					</label>';
		echo Html::input( 'wpPassword', null, 'password', array(
					'class' => 'form-control input-sm',
					'id' => 'wpPassword2',
					'tabindex' => '102',
					'placeholder' => $this->getMsg( 'userlogin-yourpassword-ph' )->text()
				) );					
		echo '</div>
				<div class="form-group">
					<button type="submit" name="wpLoginAttempt" tabindex="103" id="wpLoginAttempt2" class="pull-right btn btn-default btn-block">
						' . $this->getMsg( 'pt-login-button' )->text() . '
					</button>
				</div>
				<input type="hidden" value="' . $wgRequest->getSessionData( 'wsLoginToken' ) . '" name="wpLoginToken">
			</form>';
	if( $wgUser->isAllowed( 'createaccount' ) ) {
		echo	'<li class="nav" id="tw-createaccount">
				<a href="' . $wgScript . '?title=special:userlogin&amp;type=signup" class="center-block">
					' . $this->getMsg( 'createaccount' )->text() . '
				</a>
			</li>';
		}
	echo '
		</ul>
		</li>';
	echo '<script>
			$( document ).ready( function() {
				$( "#n-login" ).click( function() {
					if( ! $( this ).parent().hasClass( "open" ) ) {
						setTimeout( \'$( "#wpName2" ).focus();\', 500 );
						}
				});
			});
			</script>';
	}

	/**
	 * Render search
	 */
	function renderSearch( $skin, $context ) {
		if( $context == 'subnav' ) {
			echo '<li class="nav dropdown">';
		}
		if( strpos( $context, 'navbar' ) === 0 ) {
			echo '</ul>';
		}
		echo '
			<form ';
		if( $context == 'navbar-left' ) {
			echo 'class="navbar-form navbar-left" '; 
		}
		if( $context == 'navbar-right' ) {
			echo 'class="navbar-form navbar-right" ';
		}
		echo 'action="';
		$this->text( 'wgScript' );
		echo '" id="searchform">
				<div class="form-group">
					<input id="searchInput" class="search-query form-control" type="search" accesskey="f" title="';
		$skin->text('searchtitle');
		echo '" placeholder="';
		$skin->msg('search');
		echo '" name="search" value="' . htmlspecialchars($this->data['search']) .'">
					' . $skin->makeSearchButton( 'go', array( 'id' => 'mw-searchButton', 'class' => 'searchButton btn hidden' ) ) . '
				</div>
			</form>';
		if( $context == 'navbar-left' ) {
			echo '<ul class="nav navbar-nav">';
		}
		if( $context == 'navbar-right' ) {
			echo '<ul class="nav navbar-nav navbar-right">';
		}
		if( $context == 'subnav' ) {
			echo '</li>';
		}
	}


	/**
	 * Render brand (linking to mainpage)
	 */
	public function renderBrand() {
		$brandmsg = wfMessage( 'tweeki-navbar-brand' );
		if( !$brandmsg->isDisabled() ) {
			$brand = $brandmsg->text();
			/* is it a file? */
			$brandimageTitle = Title::newFromText( $brand );
			if ( ! is_null( $brandimageTitle ) && $brandimageTitle->exists() ) {
				$brandimageWikiPage = WikiPage::factory( $brandimageTitle );
				if ( method_exists( $brandimageWikiPage, 'getFile' ) ) {
					$brandimage = $brandimageWikiPage->getFile()->getFullUrl();
					$brand = '<img src="' . $brandimage . '" alt="' . $this->data['sitename'] . '" />';
				}
			}
			echo '<a href="' . htmlspecialchars( $this->data['nav_urls']['mainpage']['href'] ) . '" class="navbar-brand">' . $brand . '</a>';
		}
	}


	/**
	 * Render standard MediaWiki footer
	 */
		private function renderStandardFooter( $options ) {
			global $wgTweekiSkinFooterIcons;
			$options = $this->getParsingOptions( 'footer-standard' );
			
			foreach ( $this->getFooterLinks() as $category => $links ) { 
				if ( $this->checkVisibility( 'footer-' . $category ) ) { 
					echo '<ul id="footer-' . $category . '">';
					foreach ( $links as $link ) { 
						if ( $this->checkVisibility( 'footer-' . $category . '-' . $link ) ) { 
							echo '<li id="footer-' . $category . '-' . $link . '">';
							$this->html( $link );
							echo '</li>';
						} 
					}
					echo '</ul>';
				} 
			} 
			if ( $this->checkVisibility( 'footer-custom' ) ) { 
				if ( wfMessage ( 'tweeki-footer-custom' )->plain() !== "" ) {
					echo '<ul id="footer-custom">';
					$this->buildItems( wfMessage ( 'tweeki-footer-custom' )->plain(), $options, 'footer' );
					echo '</ul>';
				}
			}
			$footericons = $this->getFooterIcons( "icononly" );
			if ( count( $footericons ) > 0 && $this->checkVisibility( 'footer-icons' ) ) { 
				echo '<ul id="footer-icons">';
				foreach ( $footericons as $blockName => $footerIcons ) { 
					if ( $this->checkVisibility( 'footer-' . $blockName . 'ico' ) ) {
						echo '<li id="footer-' . htmlspecialchars( $blockName ) . 'ico">';
						foreach ( $footerIcons as $icon ) { 
							if($wgTweekiSkinFooterIcons) {
								echo $this->getSkin()->makeFooterIcon( $icon ); 
							} else {
								echo '<span>' . $this->getSkin()->makeFooterIcon( $icon, 'withoutImage' ) . '</span>'; 
							}
						}
						echo '</li>';
					}
				} 
				echo '</ul>';
			}
			echo '<div style="clear:both"></div>';
		}
	}
