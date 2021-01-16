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

use MediaWiki\MediaWikiServices;
use MediaWiki\Session\SessionManager;

/**
 * QuickTemplate subclass for Vector
 * @ingroup Skins
 */
class TweekiTemplate extends BaseTemplate {

	/* Functions */

	/**
	 * Outputs the entire contents of the (X)HTML page
	 */
	public function execute() {
		$this->data['namespace_urls'] = $this->data['content_navigation']['namespaces'];
		$this->data['view_urls'] = $this->data['content_navigation']['views'];
		$this->data['action_urls'] = $this->data['content_navigation']['actions'];
		$this->data['variant_urls'] = $this->data['content_navigation']['variants'];
		$this->data['watch_urls'] = [];
		if( $GLOBALS['wgTweekiSkinUseRealnames'] == true && $this->data['username'] ) {
			$this->data['username'] = TweekiHooks::getRealname( $this->data['username'] );
		}

		// Remove the watch/unwatch star from the "actions" menu
		if ( $this->config->get( 'TweekiSkinUseIconWatch' ) ) {
			$mode = $this->getSkin()->getUser()->isWatched( $this->getSkin()->getRelevantTitle() )
				? 'unwatch'
				: 'watch';

			if ( isset( $this->data['action_urls'][$mode] ) ) {
				$this->data['watch_urls'][$mode] = $this->data['action_urls'][$mode];
				unset( $this->data['action_urls'][$mode] );
			}
		}
		$this->data['pageLanguage'] =
			$this->getSkin()->getTitle()->getPageViewLanguage()->getHtmlCode();

		//set userStateClass
		if ( $this->data['loggedin'] ) {
			$this->data['userstateclass'] = "user-loggedin";
		} else {
			$this->data['userstateclass'] = "user-loggedout";
		}

		if ( $this->config->get( 'GroupPermissions' )['*']['edit'] || $this->data['loggedin'] ) {
			$this->data['userstateclass'] .= " editable";
		} else {
			$this->data['userstateclass'] .= " not-editable";
		}

		//set 'namespace' and 'title_formatted' variables
		$this->data['title_formatted'] = $this->data['title'];
		$this->data['namespace'] = str_replace( "_", " ", $this->getSkin()->getTitle()->getNsText() );
		if( strpos( $this->data['title_formatted'], $this->data['namespace'] . ':' ) === 0 ) {
			$this->data['title_formatted'] = '<span class="namespace">' . $this->data['namespace'] . ":</span> " . str_replace( $this->data['namespace'] . ':', '', $this->data['title_formatted'] );
		}

		// Output HTML Page
		$this->html( 'headelement' );
		call_user_func_array( $this->config->get( 'TweekiSkinPageRenderer' ), [ $this ] );
?>
	</body>
</html>
<?php
	}



	/**
	 * Render the whole page
	 *
	 * Copy this function and use $wgTweekiSkinPageRenderer to create
	 * completely custom page layouts
	 *
	 * @param $skin Skin skin object
	 */
	private function renderPage( $skin ) {
		// load defaults for layout without sidebar
		$main_offset = $this->config->get( 'TweekiSkinGridNone' )['mainoffset'];
		$main_width = $this->config->get( 'TweekiSkinGridNone' )['mainwidth'];
		$left_width = 0;
		$left_offset = 0;
		$right_width = 0;
		$right_offset = 0;
		// TODO: check for situational emptiness of sidebar (e.g. on special pages)
		if( true ) {
			$sidebar_left = $skin->checkVisibility( 'sidebar-left' ) && !$skin->checkEmptiness( 'sidebar-left' );
			$sidebar_right = $skin->checkVisibility( 'sidebar-right' ) && !$skin->checkEmptiness( 'sidebar-right' );
			if( $sidebar_left && $sidebar_right ) { // both sidebars
				$left_offset = $this->config->get( 'TweekiSkinGridBoth' )['leftoffset'];
				$left_width = $this->config->get( 'TweekiSkinGridBoth' )['leftwidth'];
				$main_offset = $left_offset + $left_width + $this->config->get( 'TweekiSkinGridBoth' )['mainoffset'];
				$main_width = $this->config->get( 'TweekiSkinGridBoth' )['mainwidth'];
				$right_offset = $main_offset + $main_width + $this->config->get( 'TweekiSkinGridBoth' )['rightoffset'];
				$right_width = $this->config->get( 'TweekiSkinGridBoth' )['rightwidth'];
			}
			if( $sidebar_left XOR $sidebar_right ) { // only one of the sidebars
				if( $sidebar_left ) {
					$left_offset = $this->config->get( 'TweekiSkinGridLeft' )['leftoffset'];
					$left_width = $this->config->get( 'TweekiSkinGridLeft' )['leftwidth'];
					$main_offset = $left_offset + $left_width + $this->config->get( 'TweekiSkinGridLeft' )['mainoffset'];
					$main_width = $this->config->get( 'TweekiSkinGridLeft' )['mainwidth'];
				}
				else {
					$main_offset = $this->config->get( 'TweekiSkinGridRight' )['mainoffset'];
					$main_width = $this->config->get( 'TweekiSkinGridRight' )['mainwidth'];
					$right_offset = $main_offset + $main_width + $this->config->get( 'TweekiSkinGridRight' )['rightoffset'];
					$right_width = $this->config->get( 'TweekiSkinGridRight' )['rightwidth'];
				}
			}
		}



		$contentclass = $skin->data['userstateclass'];
		$contentclass .= ' ' . wfMessage( 'tweeki-container-class' )->escaped();
		$contentclass .= ( $skin->checkVisibility( 'navbar' ) ) ? ' with-navbar' : ' without-navbar';

		if( false !== stripos( wfMessage( 'tweeki-navbar-class' ), 'fixed' ) ) {
			$contentclass .= ' with-navbar-fixed';
		}



		if( !$this->isBS4() ) {

			$mainclass = 'col-md-offset-' . $main_offset . ' col-md-' . $main_width;

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

		} else {

			$mainclass = 'col-md-' . $main_width;
			if( $main_offset > 0 ) {
				$mainclass .= ' offset-md-' . $main_offset;
			} 

			$skin->renderNavbar4();
?>
			<main role="main">
				<div id="mw-page-base"></div>
				<div id="mw-head-base"></div>
				<a id="top"></a>


				<div id="contentwrapper" class="<?php echo $contentclass; ?>">

					<div class="row">
						<?php if( !$skin->checkEmptiness( 'subnav' ) ) { $skin->renderSubnav4( $mainclass ); } ?>

						<!-- content -->
						<div class="<?php echo $mainclass ?>" id="maincontentwrapper" role="main">
							<?php $skin->renderContent(); ?>
						</div>
						<!-- /content -->

	<?php
						if( !$skin->checkEmptiness( 'sidebar-left' ) ) {
							$leftclass = 'col-md-' . $left_width;
							$skin->renderSidebar4( 'left', $leftclass );
						}
						if( !$skin->checkEmptiness( 'sidebar-right' ) ) {
							$rightclass = 'col-md-' . $right_width;
							$skin->renderSidebar4( 'right', $rightclass );
						}

	?>
					</div>
				</div>
			</main>


<?php
			$skin->renderFooter4();
		}


		$skin->printTrail();
	}

	/**
	 * Render one or more navigations elements by name, automatically reversed by css
	 * when UI is in RTL mode
	 *
	 * @param $elements
	 * @param String $context
	 */
	protected function renderNavigation( $elements, $context = '' ) {

		// If only one element was given, wrap it in an array, allowing more
		// flexible arguments
		if ( !is_array( $elements ) ) {
			$elements = [ $elements ];
		// If there's a series of elements, reverse them when in RTL mode
		} elseif ( $this->data['rtl'] ) {
			$elements = array_reverse( $elements );
		}
		// Render elements
		foreach ( $elements as $name => $element ) {
			if ( !$this->checkVisibility( $element ) ) {
				return [];
			}
			// was this element defined in LocalSettings?
			if ( isset( $this->config->get( 'TweekiSkinNavigationalElements' )[ $element ] ) ) {
				return call_user_func( $this->config->get( 'TweekiSkinNavigationalElements' )[ $element ], $this );
			}
			// is it a special element with special non-buttonesque rendering?
			if ( isset( $this->config->get( 'TweekiSkinSpecialElements' )[ $element ] ) ) {
				return [ [ 'special' => $element ] ];
			}

			switch ( $element ) {

				case 'EDIT':
					$views = $this->data['view_urls'];
					if(count( $views ) > 0) {
						unset( $views['view'] );
						$link = array_shift( $views );
						$link['icon'] = wfMessage( 'tweeki-edit-icon' )->plain();
						unset( $link['class'] ); // interferes with btn classing
						return [ $link ];
					}
					return [];
					break;

				case 'EDIT-EXT':
					if( !$this->isBS4() ) {
						$views = $this->data['view_urls'];
						if(count( $views ) > 0) {
							unset( $views['view'] );
							$link = array_shift( $views );
							if ( $this->checkVisibility( 'EDIT-EXT-special' ) ) {
								$button = [
									'href' => $link['href'],
									'href_implicit' => false,
									'id' => 'ca-edit',
									'icon' => 'pencil',
									'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain(),
									'name' => 'ca-edit-ext'
								];
								if( isset( $views['edit'] ) ) {
									$views['edit']['id'] = 'ca-edit-source';
								}
								$button['items'] = $views;
								if(count($this->data['action_urls']) > 0) {
									$button['items'][] = []; #divider
									$actions = $this->renderNavigation( 'ACTIONS' );
									$button['items'] = array_merge( $button['items'], $actions[0]['items'] );
								}
							} else {
								$button = [
									'href' => $link['href'],
									'id' => 'ca-edit',
									'icon' => 'pencil',
									'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain()
									];
							}
							return [ $button ];
						}
					} else {
						$views = $this->data['view_urls'];
						if(count( $views ) > 0) {
							unset( $views['view'] );
							$link = array_shift( $views );
							if ( $this->checkVisibility( 'EDIT-EXT-special' ) ) {
								$button = [
									'href' => $link['href'],
									'href_implicit' => false,
									'id' => 'ca-edit',
									'icon' => wfMessage( 'tweeki-edit-ext-icon' )->plain(),
									'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain(),
									'name' => 'ca-edit-ext'
									];
								if( isset( $views['edit'] ) ) {
									$views['edit']['id'] = 'ca-edit-source';
								}
								$button['items'] = $views;
								if(count($this->data['action_urls']) > 0) {
									$button['items'][] = []; #divider
									$actions = $this->renderNavigation( 'ACTIONS' );
									$button['items'] = array_merge( $button['items'], $actions[0]['items'] );
								}
							} else {
								$button = [
									'href' => $link['href'],
									'id' => 'ca-edit',
									'icon' => wfMessage( 'tweeki-edit-ext-icon' )->plain(),
									'text' => wfMessage( 'tweeki-edit-ext', $this->data['namespace'] )->plain()
									];
							}
							return [ $button ];
						}
					}
					return [];
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
					return [[
						'href' => '#',
						'text' => $text,
						'id' => 'n-namespaces',
						'items' => $items
						]];
					break;

				case 'NAMESPACES':
					$items = $this->data['namespace_urls'];
					$text = wfMessage( 'namespaces' );
					/*
					foreach ( $items as $key => $link ) {
						if ( array_key_exists( 'context', $link ) && $link['context'] == 'subject' ) {
							$text = $link['text'];
						}
						if ( 
							array_key_exists( 'attributes', $link ) && false !== strpos( $link['attributes'], 'selected' ) 
							|| array_key_exists( 'class', $link ) && false !== strpos( $link['class'], 'selected' ) 
						) {
							unset( $items[$key] );
						}
					}
					*/
					return [[
						'href' => '#',
						'text' => $text,
						'id' => 'n-namespaces',
						'items' => $items
						]];
					break;

				case 'TALK':
					$items = $this->data['namespace_urls'];
					foreach ( $items as $key => &$link ) {
						if( isset( $link['context'] ) ) {
							$link['icon'] = wfMessage( 'tweeki-namespace-' . $link['context'] . '-icon' )->plain();
						}
						if ( 
							array_key_exists( 'attributes', $link ) && false !== strpos( $link['attributes'], 'selected' ) 
							|| array_key_exists( 'class', $link ) && false !== strpos( $link['class'], 'selected' ) 
						) {
							unset( $items[$key] );
						} else {
							unset( $link['class'] ); // interferes with btn classing
						}
					}
					return $items;
					break;

				case 'TOOLBOX':
					if( version_compare( MW_VERSION, '1.35', '>=' ) ) {
						$items = array_reverse($this->get('sidebar')['TOOLBOX']);
					} else {
						$items = array_reverse($this->getToolbox());
					}
					$divideditems = [];
					$html = (wfMessage( 'tweeki-toolbox' )->plain() == "") ? wfMessage( 'toolbox' )->plain() : wfMessage( 'tweeki-toolbox' )->plain();
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = wfMessage( isset( $item['msg'] ) ? $item['msg'] : $key )->text();
						}
						if(preg_match( '/specialpages|whatlinkshere/', $key )) {
							$divideditems[] = [];
						}
						$divideditems[$key] = $item;
					}
					return [[
						'href' => '#',
						'html' => $html,
						'id' => 't-tools',
						'items' => $divideditems
						]];
					break;

				case 'TOOLBOX-EXT':
					if( version_compare( MW_VERSION, '1.35', '>=' ) ) {
						$items = array_reverse($this->get('sidebar')['TOOLBOX']);
					} else {
						$items = array_reverse($this->getToolbox());
					}
					$divideditems = [];
					$html = (wfMessage( 'tweeki-toolbox' )->plain() == "") ? wfMessage( 'toolbox' )->plain() : wfMessage( 'tweeki-toolbox' )->plain();
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = wfMessage( isset( $item['msg'] ) ? $item['msg'] : $key )->text();
						}
						if(preg_match( '/specialpages|whatlinkshere/', $key )) {
							$divideditems[] = [];
						}
						$divideditems[$key] = $item;
					}
					$divideditems[] = [];
					$divideditems['recent'] = [
						'text' => wfMessage( 'recentchanges' )->plain(),
						'href' => Title::newFromText( 'Special:RecentChanges' )->getLocalURL(),
						'id' => 't-recentchanges',
					];
					return [[
						'href' => '#',
						'html' => $html,
						'id' => 't-tools',
						'items' => $divideditems
						]];
					break;

				case 'VARIANTS':
					$theMsg = 'variants';
					$items = $this->data['variant_urls'];
					if (count($items) > 0) {
						return [[
							'href' => '#',
							'text' => wfMessage( 'variants' ),
							'id' => 'ca-variants',
							'items' => $items
							]];
					}
					break;

				case 'VIEWS':
					$items = $this->data['view_urls'];
					if (count($items) > 0) {
						return [[
							'href' => '#',
							'text' => wfMessage( 'views' ),
							'id' => 'ca-views',
							'items' => $items
							]];
					}
					break;

				case 'HISTORY':
					$button = null;
					$views = $this->data['view_urls'];
					if( isset( $views['history'] )  ) {
						if ( 
							array_key_exists( 'attributes', $views['history'] ) && false !== strpos( $views['history']['attributes'], 'selected' ) 
							|| array_key_exists( 'class', $views['history'] ) && false !== strpos( $views['history']['class'], 'selected' ) 
						) {
							$button = array_shift( $this->data['namespace_urls'] );
						} else {
							$button = $views['history'];
							$button['icon'] = wfMessage( 'tweeki-history-icon' )->plain();
						}
					}
					if( !is_null( $button ) ) {
						$button['options'] = [ 'wrapperid' => $button['id'] ];
						unset( $button['id'] );
						unset( $button['class'] );
						return [ $button ];
					}
					break;

				case 'ACTIONS':
					$items = array_reverse($this->data['action_urls']);
					if (count($items) > 0) {
						return [[
							'href' => '#',
							'text' => wfMessage( 'actions' ),
							'id' => 'ca-actions',
							'items' => $items
							]];
					}
					break;

				case 'WATCH':
					$button = null;
					$actions = $this->data['action_urls'];
					if( isset( $actions['watch'] )  ) {
						$button = $actions['watch'];
					} else if( isset( $actions['unwatch'] ) ) {
						$button = $actions['unwatch'];
					}
					if( !is_null( $button ) ) {
						$button['options'] = [ 'wrapperid' => $button['id'] ];
						unset( $button['id'] );
						return [ $button ];
					}
					break;

				case 'ICONWATCH':
					$button = null;
					$watch = $this->data['watch_urls'];
					if( isset( $watch['watch'] )  ) {
						$button = $watch['watch'];
					} else if( isset( $watch['unwatch'] ) ) {
						$button = $watch['unwatch'];
					}
					if( !is_null( $button ) ) {
						$button['class'] .= 'icon';
						$button['options'] = [
							'wrapperid' => $button['id'],
							'wrapperclass' => 'nav icon'
						];
						if( strpos( $context, 'nav' ) !== false ) {
							$button['class'] .= ' nav-link';
							$button['options']['wrapperclass'] .= ' nav-item';
						}
						unset( $button['id'] );
						return [ $button ];
					}
					break;

				case 'PERSONAL':
					$items = $this->getPersonalTools();
					$divideditems = [];
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = wfMessage( isset( $item['msg'] ) ? $item['msg'] : $key )->text();
						}
						if(!isset( $item['href'] ) ) {
							$item['href'] = $item['links'][0]['href'];
						}
						if(preg_match( '/preferences|logout/', $key )) {
							$divideditems[] = [];
						}
						unset( $item['links'] );
						$divideditems[$key] = $item;
					}
					if ( array_key_exists( 'login', $divideditems ) ) {
						$divideditems['login']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return [ $divideditems['login'] ];
					}
					if ( array_key_exists( 'anonlogin', $divideditems ) ) {
						$divideditems['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return [ $divideditems['anonlogin'] ];
					}
					if (count($items) > 0) {
						return [[
							'href' => '#',
							'html' => '<span class="tweeki-username">' . wfMessage( 'tweeki-personaltools-text', $this->data['username'] )->text() . '</span>',
							'icon' => wfMessage( 'tweeki-personaltools-icon' )->text(),
							'id' => 'pt-personaltools',
							'items' => $divideditems
							]];
					}
					break;

				case 'PERSONAL-EXT':
					$items = $this->getPersonalTools();
					$divideditems = [];
					foreach($items as $key => $item) {
						if(!isset( $item['text'] ) ) {
							$item['text'] = wfMessage( isset( $item['msg'] ) ? $item['msg'] : $key )->text();
						}
						if(!isset( $item['href'] ) ) {
							$item['href'] = $item['links'][0]['href'];
						}
						if(preg_match( '/preferences|logout/', $key )) {
							$divideditems[] = [];
						}
						unset( $item['links'] );
						$divideditems[$key] = $item;
					}
					if ( array_key_exists( 'login', $divideditems ) ) {
						return [ [ 'special' => 'LOGIN-EXT' ] ];
					}
					if ( array_key_exists( 'anonlogin', $divideditems ) ) {
						return [ [ 'special' => 'LOGIN-EXT' ] ];
					}
					if (count($items) > 0) {
						return [[
								'href' => '#',
								'html' => '<span class="tweeki-username">' . wfMessage( 'tweeki-personaltools-text', $this->data['username'] )->text() . '</span>',
								'icon' => wfMessage( 'tweeki-personaltools-icon' )->text(),
								'id' => 'pt-personaltools-ext',
								'items' => $divideditems
								]];
					}
					break;

				case 'LOGIN':
					$items = $this->getPersonalTools();
					if ( array_key_exists( 'login', $items ) ) {
						$items['login']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return [ $items['login'] ];
					}
					if ( array_key_exists( 'anonlogin', $items ) ) {
						$items['anonlogin']['links'][0]['text'] = wfMessage( 'tweeki-login' )->plain();
						return [ $items['anonlogin'] ];
					}
					return [];
				break;

				case 'SIDEBAR':
					$sidebar = [];
					foreach ( $this->data['sidebar'] as $name => $content ) {
						if ( empty ( $content ) ) {
							if( strpos( $name, '|' ) !== false ) {
								$parser = MediaWikiServices::getInstance()->getParser();
								$sidebarItem = TweekiHooks::parseButtonLink( $name, $parser, false );
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
						$sidebar[] = [
								'href' => '#',
								'text' => $name,
								'items' => $content
								];
					}
					return $sidebar;
					break;

				case 'LANGUAGES':
					$items = $this->data['language_urls'];
					if (is_array($items) && count($items) > 0 && $items) { 
						return [[
							'href' => '#',
							'text' => wfMessage( 'otherlanguages' ),
							'id' => 'p-otherlanguages',
							'items' => $items
							]];
					}
					return [];
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
		if (
			(
				!$this->checkVisibilitySetting( $item, $this->config->get( 'TweekiSkinHideNonAdvanced' ) ) ||
				$this->getSkin()->getUser()->getOption( 'tweeki-advanced' ) // not hidden for non-advanced OR advanced
			) &&
			(
				!$this->checkVisibilitySetting( $item, $this->config->get( 'TweekiSkinHideAnon' ) ) ||
				$this->data['loggedin'] // not hidden for anonymous users OR non-anonymous user
			) &&
			(
				!$this->checkVisibilitySetting( $item, $this->config->get( 'TweekiSkinHideLoggedin' ) ) ||
				!$this->data['loggedin'] // not hidden for logged-in users OR anonymous user
			) &&
			!$this->checkVisibilitySetting( $item, $this->config->get( 'TweekiSkinHideAll' ) ) // not hidden for all
			&&
			!$this->checkVisibilityGroups( $item ) // not hidden for all OR user is in exempted group
			&&
			false !== Hooks::run( 'SkinTweekiCheckVisibility', [ $this, $item ] ) // not hidden via hook
		) {
			return true;
		}	else {
			return false;
		}
	}

	/**
	 * Check if an element has an entry in a configuration option and if it's set to true 
	 * (i.e. the element should be hidden)
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
	 * Check if an element has an entry in $wgTweekiSkinExcept or if the user is
	 * in the corresponding group
	 *
	 * @param $item Element to be tested
	 *
	 * @return Boolean returns true, if the element is hidden
	 */
	public function checkVisibilityGroups( $item ) {
		// has the option been used?
		if( !$this->config->has( 'TweekiSkinHideExcept' ) ) {
			return false;
		}

		$group_settings = $this->config->get( 'TweekiSkinHideExcept' );

		// has the option been set for this item?
		if( isset( $group_settings[$item] ) && is_array( $group_settings[$item] ) ) {
			$groups = $this->getSkin()->getUser()->getEffectiveGroups();

			// is the user in the exempted group?
			if( count( array_intersect( $group_settings[$item], $groups ) ) > 0 ) {
				return false;
			}

			return true;
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
			<div id="page-header" class="subnav row">
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
	 * Render Subnavigation for Bootstrap 4
	 */
	public function renderSubnav4( $class ) {
		$options = $this->getParsingOptions( 'subnav' );
		if( !wfMessage( 'tweeki-subnav' )->isDisabled() && $this->checkVisibility( 'subnav' ) ) { ?>
			<!-- subnav -->
			<div id="page-header" class="<?php echo $class; ?>">
				<ul class="<?php $this->msg( 'tweeki-subnav-class' ) ?>">
				<?php $this->buildItems( wfMessage( 'tweeki-subnav' )->plain(), $options, 'subnav' ); ?>
				</ul>
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


	private static function get_navbar_toggler ($nav_id = 'navigation', $icon_class = 'navbar-toggler-icon')
	{
		return "<button class=\"navbar-toggler\" type=\"button\" data-toggle=\"collapse\" data-target=\"#$nav_id\" aria-controls=\"$nav_id\" aria-expanded=\"false\" aria-label=\"Toggle navigation\"><span class=\"$icon_class\"></span></button>";
	}


	/**
	 * Render Navbar for Bootstrap 4
	 */
	public function renderNavbar4() {
		$navbar_class = $this->getMsg( 'tweeki-navbar-class' );
		if ( $this->checkVisibility( 'navbar' ) ) { ?>
			<header>
				<nav id="mw-navigation" class="<?php echo $navbar_class; ?>">
					<div class="<?php echo wfMessage( 'tweeki-container-class' )->plain(); ?>">
						<?php if ( $this->checkVisibility( 'navbar-brand' ) ) {
							$this->renderBrand();
						} ?>

						<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>

						<div id="navbar" class="collapse navbar-collapse">
							<?php if ( $this->checkVisibility( 'navbar-left' ) ) { ?>
								<ul class="navbar-nav mr-auto">
									<?php $this->renderNavbarElement4( 'left' ); ?>
								</ul>
							<?php } ?>

							<?php if ( $this->checkVisibility( 'navbar-right' ) ) { ?>
								<ul class="navbar-nav">
									<?php $this->renderNavbarElement4( 'right' ); ?>
								</ul>
							<?php } ?>
						</div>
					</div>
				</nav>
			</header>
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
	 * Render Navbarelement
	 *
	 * @param $side string
	 */
	private function renderNavbarElement4( $side ) {
		$element = 'navbar-' . $side;
		$options = $this->getParsingOptions( $element );

		$options['wrapperclass'] = 'nav-item';
		$options['class'] = 'nav-link';
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
	 * Render Sidebar for BS4
	 *
	 * @param $side string
	 * @param $class string
	 */
	public function renderSidebar4( $side, $class = '' ) {
		$element = 'sidebar-' . $side;
		$options = $this->getParsingOptions( $element );
		$classes = $class;
		if( wfMessage( 'tweeki-sidebar-class' )->exists() ) {
			$classes .= ' ' . $this->getMsg( 'tweeki-sidebar-class' );
		}
		if( wfMessage( 'tweeki-' . $element . '-class' )->exists() ) {
			$classes .= ' ' . $this->getMsg( 'tweeki-' . $element . '-class' );
		}
		/* TODO: can we move these criteria elsewhere? rather there should be some handling for empty sidebars */
		if ( ( true || count( $this->data['view_urls'] ) > 0 || $this->data['isarticle'] ) && $this->checkVisibility( $element ) ) { ?>
			<!-- <?php echo $element;?> -->

				<div id="<?php echo $element; ?>" class="<?php echo $classes; ?>">
					<?php $this->buildItems( wfMessage( 'tweeki-' . $element )->plain(), $options, $element ); ?>
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

	public function renderFooter4() {
		$options = $this->getParsingOptions( 'footer' );
		if ( $this->checkVisibility( 'footer' ) ) { ?>
			<footer id="footer" role="contentinfo" class="footer <?php $this->msg( 'tweeki-footer-class' ); ?>"<?php $this->html( 'userlangattributes' ) ?>>
				<div class="<?php $this->msg( 'tweeki-container-class' ); ?>">
					<div class="row">
						<?php $this->buildItems( wfMessage( 'tweeki-footer' )->plain(), $options, 'footer' ); ?>
					</div>
				</div>
			</footer>
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
		$options = [];
		$available_options = [
			'btnclass',
			'wrapper',
			'wrapperclass',
			'dropdownclass'
			];
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
		$buttons = [];
		$customItems = [];
		$navbarItems = preg_split( '/[\n,]/', $items );
		foreach( $navbarItems as $navbarItem ) {
			$navbarItem = trim( $navbarItem );
			$navbarItem = $this->renderNavigation( $navbarItem, $context );
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
				$button_options = [];
				if( isset( $button['options'] ) ) {
					$button_options = $button['options'];
					unset( $button['options'] );
				}
				echo TweekiHooks::renderButtons( [ $button ], array_merge( $options, $button_options ) );
			}
			/* special cases */
			else {
				call_user_func_array( $this->config->get( 'TweekiSkinSpecialElements' )[$button['special']], [ $this, $context, $options ] );
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

		if( version_compare( MW_VERSION, '1.35', '>=' ) ) {
			$parser = MediaWikiServices::getInstance()->getParser();
		} else {
			$options = new ParserOptions();
			$parser = new Parser();
			$parser->Title ( $this->getSkin()->getTitle() );
			$parser->Options( $options );
			$parser->clearState();
		}

		if( count( $customItems ) !== 0 ) {
			$newButtons = TweekiHooks::parseButtons( implode( chr(10), $customItems ), $parser, false );
			$buttons = array_merge( $buttons, $newButtons );
			$customItems = [];
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
		SessionManager::getGlobalSession()->persist();

		//build path for form action
		$returntotitle = $skin->getSkin()->getTitle();
		$returnto = $returntotitle->getFullText();
		if ( $returntotitle->equals( SpecialPage::getTitleFor( 'Userlogin' ) )
			|| $returntotitle->equals( SpecialPage::getTitleFor( 'Userlogout' ) )
			|| !$returntotitle->exists() ) {
			$returnto = Title::newMainPage()->getFullText();
		}
		$returnto = $this->getSkin()->getRequest()->getVal( 'returnto', $returnto );

		if ( $this->config->has( 'TweekiReturnto' ) && $returnto == Title::newMainPage()->getFullText() ) {
			$returnto = $this->config->get( 'TweekiReturnto' );
		}
		$action = $GLOBALS['wgScript'] . '?title=Special:UserLogin&amp;action=submitlogin&amp;type=login&amp;returnto=' . $returnto;

		//create login token if it doesn't exist
		if( !$this->getSkin()->getRequest()->getSession()->getToken( '', 'login' ) ) $this->getSkin()->getRequest()->getSession()->resetToken( 'login' );
		$this->getSkin()->getUser()->setCookies();

		$dropdown['class'] = ' dropdown-toggle';
		$dropdown['data-toggle'] = 'dropdown';
		$dropdown['text'] = $this->getMsg( 'tweeki-login' )->text();
		$dropdown['html'] = $dropdown['text'] . ' <b class="caret"></b>';
		$dropdown['href'] = '#';
		$dropdown['type'] = 'button';
		$dropdown['id'] = 'n-login-ext';
		$renderedDropdown = TweekiHooks::makeLink( $dropdown);
		$wrapperclass = ( $context == 'footer' ) ? 'dropup' : 'nav';

		echo '<li class="' . $wrapperclass . '">
		' . $renderedDropdown . '
		<ul class="dropdown-menu" role="menu" aria-labelledby="' . $this->getMsg( 'tweeki-login' )->text() . '" id="loginext">
			<form action="' . $action . '" method="post" name="userloginext" class="clearfix">
				<div class="form-group">
					<label for="wpName2" class="hidden-xs">
						' . $this->getMsg( 'userlogin-yourname' )->text() . '
					</label>';
		echo Html::input( 'wpName', null, 'text', [
					'class' => 'form-control input-sm',
					'id' => 'wpName2',
					'tabindex' => '101',
					'placeholder' => $this->getMsg( 'userlogin-yourname-ph' )->text()
				] );
		echo	'</div>
				<div class="form-group">
					<label for="wpPassword2" class="hidden-xs">
						' . $this->getMsg( 'userlogin-yourpassword' )->text() . '
					</label>';
		echo Html::input( 'wpPassword', null, 'password', [
					'class' => 'form-control input-sm',
					'id' => 'wpPassword2',
					'tabindex' => '102',
					'placeholder' => $this->getMsg( 'userlogin-yourpassword-ph' )->text()
				] );
		echo '</div>
				<div class="form-group">
					<button type="submit" name="wpLoginAttempt" tabindex="103" id="wpLoginAttempt2" class="pull-right btn btn-default btn-block">
						' . $this->getMsg( 'pt-login-button' )->text() . '
					</button>
				</div>
				<input type="hidden" id="wpEditToken" value="+\" name="wpEditToken">
				<input type="hidden" value="Special:UserLogin" name="title">
				<input name="authAction" type="hidden" value="login">
				<input name="force" type="hidden">
				<input type="hidden" value="' . $this->getSkin()->getRequest()->getSession()->getToken( '', 'login' ) . '" name="wpLoginToken">
			</form>';

		if( $this->getSkin()->getUser()->isAllowed( 'createaccount' ) ) {
			echo	'<li class="nav" id="tw-createaccount">
					<a href="' . $GLOBALS['wgScript'] . '?title=special:userlogin&amp;type=signup" class="center-block">
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

		if ( !$this->isBS4() ) {

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
					<div class="form-group">';

			echo $this->makeSearchInput( [
				'id' => 'searchInput',
				'class' => 'search-query form-control',
				'placeholder' => $skin->getMsg( 'search' )->text()
			] );

			echo $skin->makeSearchButton( 'go', [
				'id' => 'mw-searchButton',
				'class' => 'searchButton btn hidden'
				] );

			echo '
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

		} else {

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
					<div class="form-inline">';

			echo $this->makeSearchInput( [
				'id' => 'searchInput',
				'class' => 'search-query form-control',
				'placeholder' => $skin->getMsg( 'search' )->text()
			] );

			echo $skin->makeSearchButton( 'go', [
				'id' => 'mw-searchButton',
				'class' => 'searchButton btn d-none'
				] );

			echo '
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

		// Render Bootstrap 3 Footer
		if (!$this->isBS4() ) :

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
							if($this->config->get( 'TweekiSkinFooterIcons' ) ) {
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

		// Render Bootstrap 4 Footer
		else :

			$options = $this->getParsingOptions( 'footer-standard' );
			$widget_class = 'col-12 col-sm footer-widget';

			foreach ( $this->getFooterLinks() as $category => $links ) {
				if ( $this->checkVisibility( 'footer-' . $category ) ) {
					echo '<div class="'.$widget_class.'"><ul id="footer-' . $category . '">';
					foreach ( $links as $link ) {
						if ( $this->checkVisibility( 'footer-' . $category . '-' . $link ) ) {
							echo '<li id="footer-' . $category . '-' . $link . '">';
							$this->html( $link );
							echo '</li>';
						}
					}
					echo '</ul></div>';
				}
			}

			if ( $this->checkVisibility( 'footer-custom' ) ) {
				if ( wfMessage ( 'tweeki-footer-custom' )->plain() !== "" ) {
					echo '<div class="'.$widget_class.'"><ul id="footer-custom">';
					$this->buildItems( wfMessage ( 'tweeki-footer-custom' )->plain(), $options, 'footer' );
					echo '</ul></div>';
				}
			}

			$footericons = $this->getFooterIcons( "icononly" );
			if ( count( $footericons ) > 0 && $this->checkVisibility( 'footer-icons' ) ) {
				echo '<div class="'.$widget_class.'"><ul id="footer-icons">';
				foreach ( $footericons as $blockName => $footerIcons ) {
					if ( $this->checkVisibility( 'footer-' . $blockName . 'ico' ) ) {
						echo '<li id="footer-' . htmlspecialchars( $blockName ) . 'ico">';
						foreach ( $footerIcons as $icon ) {
							if($this->config->get( 'TweekiSkinFooterIcons' ) ) {
								echo $this->getSkin()->makeFooterIcon( $icon );
							} else {
								echo '<span>' . $this->getSkin()->makeFooterIcon( $icon, 'withoutImage' ) . '</span>';
							}
						}
						echo '</li>';
					}
				}
				echo '</ul></div>';
			}

		endif;
	}

	public function isBS4() {
		return $this->config->get( 'TweekiSkinUseBootstrap4' );
	}
}
