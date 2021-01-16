<?php

use MediaWiki\MediaWikiServices;

/**
 * Hooks for Tweeki skin
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

class TweekiHooks {

	protected static $anchorID = 0;
	protected static $realnames = [];

	/**
	 * Is this Wiki configured to use Bootstrap 4?
	 */
	static function isBS4() {
		return $GLOBALS['wgTweekiSkinUseBootstrap4'];
	}

	/**
	 * Expose TweekiSkinUseTooltips configuration variable
	 */
	static function onResourceLoaderGetConfigVars( &$vars ) {
		$vars['wgTweekiSkinUseTooltips'] = $GLOBALS['wgTweekiSkinUseTooltips'];
	}

	/**
	 * Setting up parser functions
	 *
	 * @param $parser Parser current parser
	 */
	static function onParserFirstCallInit( Parser $parser ) {
		if( $GLOBALS['wgOut']->getSkin()->getSkinName() == 'tweeki' ) {
			$parser->setHook( 'TOC', 'TweekiHooks::TOC' );
			$parser->setHook( 'legend', 'TweekiHooks::legend' );
			$parser->setHook( 'footer', 'TweekiHooks::footer' );
			$parser->setHook( 'accordion', 'TweekiHooks::buildAccordion' );
			$parser->setHook( 'label', 'TweekiHooks::buildLabel' );

			if ( true === $GLOBALS['wgTweekiSkinUseBtnParser'] ) {
				$parser->setHook( 'btn', 'TweekiHooks::buildButtons' );
			}

			$parser->setFunctionHook( 'tweekihide', 'TweekiHooks::setHiddenElements' );
			$parser->setFunctionHook( 'tweekihideexcept', 'TweekiHooks::setHiddenElementsGroups' );
			$parser->setFunctionHook( 'tweekibodyclass', 'TweekiHooks::addBodyclass' );
			$parser->setFunctionHook( 'tweekirealname', 'TweekiHooks::renderRealname' );
		}

		return true;
	}

	/**
	 * Adding modules
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		if( $skin->getSkinName() == 'tweeki' ) {
			$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'tweeki' );

			$styles = [];
			// load styles
			if( $config->get( 'TweekiSkinCustomStyleModule' ) ) {
				$styles[] = 'skins.tweeki.bootstrap4.mediawiki.styles';
				$styles[] = $config->get( 'TweekiSkinCustomStyleModule' );
			} elseif( !$config->get( 'TweekiSkinUseBootstrap4' ) ) {
				$styles[] = 'skins.tweeki.styles';
				if( $config->get( 'TweekiSkinUseBootstrapTheme' ) ) {
					$styles[] = 'skins.tweeki.bootstraptheme.styles';
				}
			} else {
				$styles[] = 'skins.tweeki.bootstrap4.mediawiki.styles';
				if( !$config->get( 'TweekiSkinUseCustomFiles' ) ) {
					$styles[] = 'skins.tweeki.bootstrap4.styles';
				} else {
					$styles[] = 'skins.tweeki.bootstrap4.custom.styles';
				}
			}

			// load last minute changes (outside webpack)
			if( $config->get( 'TweekiSkinUseBootstrap4' ) ) {
				$styles[] = 'skins.tweeki.bootstrap4.corrections.styles';
			}

			if( $config->get( 'TweekiSkinUseExternallinkStyles' ) ) {
				$styles[] = 'skins.tweeki.externallinks.styles';
			}
			if( $config->get( 'TweekiSkinUseAwesome' ) ) {
				$styles[] = 'skins.tweeki.awesome.styles';
			}
			// if( $config->get( 'CookieWarningEnabled' ) ) {
			// 	$styles[] = 'skins.tweeki.cookiewarning.styles';
			// }
			foreach( $GLOBALS['wgTweekiSkinCustomCSS'] as $customstyle ) {
				$styles[] = $customstyle;
			}
			Hooks::run( 'SkinTweekiStyleModules', [ $skin, &$styles ] );
			$out->addModuleStyles( $styles );
		}
	}

	/**
	 * Manipulate headlines – we need .mw-headline to be empty because it has a padding
	 * that we need for correct positioning for anchors and this would render links above headlines inaccessible
	 */
	public static function onOutputPageBeforeHTML( &$out, &$text ) {
		// obsolete now with CSS's new scroll-padding-top
		return true;
		// Manipulation is harmful when executed on non-article pages (e.g. stops preview from working)
		if( $out->isArticle() ) {
			$doc = new DOMDocument();
			$html = mb_convert_encoding( $text, 'HTML-ENTITIES', 'UTF-8' );
			if( $html != '' ) {
				libxml_use_internal_errors(true);
				$doc->loadHTML( $html );
				libxml_clear_errors();
				$spans = $doc->getElementsByTagName('span');
				foreach( $spans as $span ) {
					$mw_headline = '';
					if( $span->getAttribute('class') == 'mw-headline' ) {
						$mw_headline = $span;

						/* move the contents of .mw-headline to a newly created .mw-headline-content */
						$mw_headline_content = $doc->createElement("span");
						$mw_headline_content->setAttribute( 'class', 'mw-headline-content' );
						while( $mw_headline->firstChild ) {
							$mw_headline_content->appendChild( $mw_headline->removeChild( $mw_headline->firstChild ) );
						}

						/* put .mw-headline before .mw-headline-content */
						$mw_headline->parentNode->insertBefore( $mw_headline_content, $mw_headline );
						$mw_headline->parentNode->insertBefore( $mw_headline, $mw_headline_content );
						}
					}
				$text = $doc->saveHTML($doc->documentElement->firstChild->firstChild);
			}
		}
	}

	/**
	 * Customizing registration
	 */
	public static function onRegistration() {

		/* Load customized bootstrap files */
		if( 
			isset( $GLOBALS['wgTweekiSkinCustomizedBootstrap'] ) 
			&& ! is_null( $GLOBALS['wgTweekiSkinCustomizedBootstrap'] ) 
		) {
			$wgResourceModules['skins.tweeki.bootstrap.styles']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
			$wgResourceModules['skins.tweeki.bootstrap.styles']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
			unset( $wgResourceModules['skins.tweeki.bootstrap.styles']['remoteSkinPath'] );
			$wgResourceModules['skins.tweeki.bootstraptheme.styles']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
			$wgResourceModules['skins.tweeki.bootstraptheme.styles']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
			unset( $wgResourceModules['skins.tweeki.bootstraptheme.styles']['remoteSkinPath'] );
			$wgResourceModules['skins.tweeki.bootstrap.scripts']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
			$wgResourceModules['skins.tweeki.bootstrap.scripts']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
			unset( $wgResourceModules['skins.tweeki.bootstrap.scripts']['remoteSkinPath'] );
		}
	}

	/**
	 * GetPreferences hook
	 *
	 * Adds Tweeki-releated items to the preferences
	 *
	 * @param $user User current user
	 * @param $defaultPreferences array list of default user preference controls
	 */
	public static function onGetPreferences( $user, &$defaultPreferences ) {
		$defaultPreferences['tweeki-advanced'] = [
			'type' => 'toggle',
			'label-message' => 'prefs-tweeki-advanced-desc',
			'section' => 'rendering/tweeki-advanced',
			'help-message' => 'prefs-tweeki-advanced-help'
		];
		return true;
	}

	/**
	 * Pages could be hidden for anonymous users or only be shown for specific groups
	 * so we put a user's group memberships into the page rendering hash
	 *
	 * @param $confstr
	 * @param $user
	 * @param $options
	 */
	static function onPageRenderingHash( &$confstr, $user, &$options ) {
		$groups = $user->getEffectiveGroups();
		sort( $groups );
		$confstr .= "!groups=" . join(',', $groups );
	}

	/**
	 * Add body classes
	 *
	 * @param $out
	 * @param $sk
	 * @param $bodyAttrs
	 */
	static function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
		if( $sk->getSkinName() == 'tweeki' ) {
			$additionalBodyClasses = [ 'tweeki-animateLayout' ];

			$user = $out->getUser();
			$additionalBodyClasses[] = $user->getOption( 'tweeki-advanced' ) ? 'tweeki-advanced' : 'tweeki-non-advanced';
			$additionalBodyClasses[] = $user->isLoggedIn() ? 'tweeki-user-logged-in' : 'tweeki-user-anon';
			
			$additionalBodyClasses = array_merge( $additionalBodyClasses, $GLOBALS['wgTweekiSkinAdditionalBodyClasses'] );

			Hooks::run( 'SkinTweekiAdditionalBodyClasses', [ $sk, &$additionalBodyClasses ] );

			if( count( $additionalBodyClasses ) > 0 ) {
				$bodyAttrs['class'] = $bodyAttrs['class'] . ' ' . preg_replace( "/[^a-zA-Z0-9_\s-]/", "", implode( " ", $additionalBodyClasses ) );
			}
		}
	}


	/**
	 * Use real names instead of user names
	 */
	static function onHtmlPageLinkRendererEnd(  $linkRenderer, $target, $isKnown, &$text, &$attribs, &$ret ) {
		if( $GLOBALS['wgTweekiSkinUseRealnames'] == true && $target->getNamespace() === 2 ) {
			$userkey = $target->getDBKey();

			// use real name if link text hadn't been set explicitly to be different from the page name
			$title = Title::newFromText( HtmlArmor::getHtml( $text ) );
			if( 
				$title && 
				( 
					$title->getPrefixedText() == $target->getPrefixedText() 
					|| $title->getText() == $target->getText()
				)
			) {
				$text = self::getRealname( $userkey );
			}
		}
	}
	static function onSelfLinkBegin( Title $nt, &$html, &$trail, &$prefix, &$ret ) {
		if( $GLOBALS['wgTweekiSkinUseRealnames'] == true && $nt->getNamespace() === 2 ) {
			$userkey = $nt->getDBKey();

			// use real name if link text hadn't been set explicitly to be different from the page name
			$title = Title::newFromText( HtmlArmor::getHtml( $html ) );
			if( 
				$title && 
				( 
					$title->getPrefixedText() == $nt->getPrefixedText() 
					|| $title->getText() == $nt->getText()
				)
			) {
				$html = self::getRealname( $userkey );
			}
		}
	}


	/**
	 * Enable TOC
	 */
	static function TOC( $input, array $args, Parser $parser, PPFrame $frame ) {
		return [ '<div class="tweeki-toc">' . $parser->recursiveTagParse( $input ) . '</div>' ];
	}

	/**
	 * Enable use of <legend> tag
	 */
	static function legend( $input, array $args, Parser $parser, PPFrame $frame ) {
		return [ '<legend>' . $parser->recursiveTagParse( $input ) . '</legend>', "markerType" => 'nowiki' ];
	}

	/**
	 * Enable use of <footer> tag
	 */
	static function footer( $input, array $args, Parser $parser, PPFrame $frame ) {
		return [ '<footer>' . $parser->recursiveTagParse( $input ) . '</footer>', "markerType" => 'nowiki' ];
	}

	/**
	 * Set elements that should be hidden
	 *
	 * @param $parser Parser current parser
	 * @return string
	 */
	static function setHiddenElements( Parser $parser ) {
		global $wgTweekiSkinHideAll, $wgTweekiSkinHideable;

		$parser->getOutput()->updateCacheExpiry(0);

		for ( $i = 1; $i < func_num_args(); $i++ ) {
			if ( in_array ( func_get_arg( $i ), $wgTweekiSkinHideable ) ) {
				$wgTweekiSkinHideAll[] = func_get_arg( $i );
			}
		}
		return '';
	}

	/**
	 * Set elements that should be hidden except for members of specific groups
	 *
	 * @param $parser Parser current parser
	 * @return string
	 */
	static function setHiddenElementsGroups( Parser $parser ) {
		global $wgTweekiSkinHideAll, $wgTweekiSkinHideable;

		$parser->getOutput()->updateCacheExpiry(0);

		$groups_except = explode( ',', func_get_arg( 1 ) );
		$groups_user = $parser->getUser()->getEffectiveGroups();
		if( count( array_intersect( $groups_except, $groups_user ) ) == 0 ) {
			for ( $i = 2; $i < func_num_args(); $i++ ) {
				if ( in_array ( func_get_arg( $i ), $wgTweekiSkinHideable ) ) {
					$wgTweekiSkinHideAll[] = func_get_arg( $i );
				}
			}
		}
		return '';
	}

	/**
	 * Add classes to body
	 *
	 * @param $parser Parser current parser
	 * @return string
	 */
	static function addBodyclass( Parser $parser ) {
		$parser->getOutput()->updateCacheExpiry(0);

		for ( $i = 1; $i < func_num_args(); $i++ ) {
			$GLOBALS['wgTweekiSkinAdditionalBodyClasses'][] = func_get_arg( $i );
		}
		return '';
	}

	/**
	 * Build accordeon
	 *
	 * @param $input string
	 * @param $args array tag arguments
	 * @param $parser Parser current parser
	 * @param $frame PPFrame current frame
	 * @return string
	 */
	static function buildAccordion( $input, array $args, Parser $parser, PPFrame $frame ) {
		static::$anchorID++;
		$parent = $parser->recursiveTagParse( $args['parent'], $frame );
		if( ! self::isBS4() ) {
			$panel = '
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#' . $parent . '" href="#' . $parent . static::$anchorID . '">
								' . $parser->recursiveTagParse( $args['heading'], $frame ) . '
							</a>
						</h4>
					</div>
					<div id="' . $parent . static::$anchorID . '" class="panel-collapse collapse ' . ( isset( $args['class'] ) ? htmlentities( $args['class'] ) : '' ) . '">
						<div class="panel-body">
				' . $parser->recursiveTagParse( $input, $frame ) . '
						</div>
					</div>
				</div>';
		} else {
			$panel = '
				<div class="card">
					<div class="card-header" id="' . $parent . static::$anchorID . 'Heading">
						<h2 class="mb-0">
							<button class="btn btn-link" type="button" data-toggle="collapse" data-parent="#' . $parent . '" data-target="#' . $parent . static::$anchorID . '" aria-expanded="' . ( isset( $args['class'] ) && $args['class'] == 'show' ? 'true' : 'false' ) . '" aria-controls="' . $parent . static::$anchorID . '">
								' . $parser->recursiveTagParse( $args['heading'], $frame ) . '
							</button>
						</h2>
					</div>
					<div id="' . $parent . static::$anchorID . '" class="collapse ' . ( isset( $args['class'] ) ? htmlentities( $args['class'] ) : '' ) . '" aria-labelledby="' . $parent . static::$anchorID . 'Heading" data-parent="#' . $parent . '">
						<div class="card-body">' . $parser->recursiveTagParse( $input, $frame ) . '</div>
					</div>
				</div>';
		}
		return $panel;
	}

	/**
	 * Build label
	 * @param $input string
	 * @param $args array tag arguments
	 * @param $parser Parser current parser
	 * @param $frame PPFrame current frame
	 * @return string
	 */
	static function buildLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
		return '<label>' . $parser->recursiveTagParse( $input ) . '</label>';
	}

	/**
	 * Build buttons, groups of buttons and dropdowns
	 *
	 * @param $input string
	 * @param $args array tag arguments
	 * @param $parser Parser current parser
	 * @param $frame PPFrame current frame
	 * @return string
	 */
	static function buildButtons( $input, array $args, Parser $parser, PPFrame $frame ) {
		$sizes = [
			'large' => 'btn-lg',
			'lg' => 'btn-lg',
			'small' => 'btn-sm',
			'sm' => 'btn-sm',
			'mini' => 'btn-xs',
			'xs' => 'btn-xs'
		];
		$renderedButtons = '';

		$buttongroups = preg_split( '/\n{2,}/', $input );

		// set standard classes for all buttons in the group
		if ( !isset( $args['class'] ) ) {
			$args['class'][] = !self::isBS4() ? 'btn btn-default' : 'btn btn-secondary';
		}
		else {
			$args['class'] = explode( ' ', $args['class'] );
		}
		if ( isset( $args['size'] ) ) {
			if ( isset( $sizes[$args['size']] ) ) {
				$args['class'][] = $sizes[$args['size']];
			}
		}

		foreach ( $buttongroups as $buttongroup ) {
			$buttons = [];
			$buttons = TweekiHooks::parseButtons( $buttongroup, $parser, $frame );
			$renderedButtons .= TweekiHooks::renderButtons( $buttons, $args );
		}

		// more than one buttongroup build a toolbar
		if ( count( $buttongroups ) > 1 ) {
			$renderedButtons = '<div class="btn-toolbar">' . $renderedButtons . '</div>';
		}

		return $renderedButtons;
	}


	/**
	 * Parse string input into array
	 *
	 * @param $buttongroup string one or more buttons
	 * @param $parser Parser current parser
	 * @param $frame PPFrame current frame
	 * @return array
	 */
	static function parseButtons( $buttongroup, Parser $parser, $frame ) {
		$buttons = [];
		$lines = explode( "\n", $buttongroup );

		foreach ( $lines as $line ) {
			// empty line
			if ( trim( $line ) == "" ) {
				continue;
			}

			// simple buttons
			if ( strpos( $line, '*' ) !== 0 ) {
				$buttons = array_merge( $buttons, TweekiHooks::parseButtonLink( trim( $line ), $parser, $frame ) );
				end( $buttons );
				$currentparentkey = key($buttons);
			}

			// dropdown menus
			else {
				// no parent set?
				if ( count( $buttons ) == 0 ) {
					continue;
				}

				$cleanline = ltrim( $line, '*' );
				$cleanline = trim( $cleanline );
				if ( !isset( $buttons[$currentparentkey]['items'] ) ) {
					$buttons[$currentparentkey]['items'] = [];
				}

				// dropdown-headers (dropdown-lines that start with a colon)
				if ( strpos( $cleanline, ':' ) === 0 ) {
					$buttons[$currentparentkey]['items'][] = [ 'text' => ltrim( $cleanline, ':' ), 'header' => true ];
				} else {
					$buttons[$currentparentkey]['items'] = array_merge( $buttons[$currentparentkey]['items'], TweekiHooks::parseButtonLink( $cleanline, $parser, $frame ) );
				}
			}
		}
		return $buttons;
	}


	/**
	 * Parse specific link
	 *
	 * @param $line string
	 * @param $parser Parser current parser
	 * @param $frame Frame current frame
	 * @return array
	 */
	static function parseButtonLink( $line, $parser, $frame ) {

		$extraAttribs = [];
		$href_implicit = false;
		$active = false;
		$current_title = $parser->getTitle();

		// semantic queries
		if ( strpos( $line, '{{#ask:' ) === 0 ) {
			if ( !is_null( $current_title ) && $current_title instanceof Title ) {
				$semanticQuery = substr( $line, 7, -2 );
				$semanticHitNumber = $parser->recursiveTagParse( '{{#ask:' . $semanticQuery . '|format=count}}', false );
				if ( !is_numeric( $semanticHitNumber ) ) {
					return [ 
						[
							'text' => $semanticQuery, 
							'href' => 'INVALID QUERY',
						]
					];
				}
				if( $semanticHitNumber < 1 ) {
					return [
						[
							'html' => '<span class="dropdown-item-text">' . wfMessage( 'tweeki-no-entries' )->plain() . '</span>', 
						]
					];
				}
				$semanticHits = $parser->recursiveTagParse( '{{#ask:' . $semanticQuery . '|link=none}}', false );
				$semanticHits = explode( ',', $semanticHits );
				$semanticLinks = [];
				foreach ( $semanticHits as $semanticHit ) {
					$semanticLink = TweekiHooks::parseButtonLink( $semanticHit, $parser, $frame );
					$semanticLinks[] = $semanticLink[0];
				}
				return $semanticLinks;
			}
			else {
				$text = 'INVALID-TITLE/QUERY-BROKEN';
			}
		}

		$line = explode( '|', $line );
		foreach ( $line as &$single_line ) {
			$single_line = trim( $single_line );
		}

		// is the text explicitly set?
		$href = $line[0];
		if ( isset( $line[1] ) && $line[1] != "" ) {
			$text = $line[1];
		}
		else {
			$href_implicit = true;
			$text = $line[0];
		}

		// parse text
		$msgText = wfMessage( $text )->inContentLanguage();
		if ( $msgText->exists() ) {
			$text = $msgText->parse();
		}
		else {
			if ( !is_null( $current_title ) && $current_title instanceof Title ) {
				$text = $parser->recursiveTagParse( $text, $frame );
			}
			else {
				$text = 'INVALID-TITLE/PARSER-BROKEN';
			}
		}

		// parse href
		$msgLink = wfMessage( $href )->inContentLanguage();
		if ( $msgLink->exists() ) {
			$href = $msgLink->parse();
		}
		else {
			if ( !is_null( $current_title ) && $current_title instanceof Title ) {
				$href = $parser->replaceVariables( $href, $frame );
			}
			else {
				$href = 'INVALID-HREF/PARSER-BROKEN';
			}
		}

		if ( preg_match( '/^(?i:' . wfUrlProtocols() . ')/', $href ) ) {
			// Parser::getExternalLinkAttribs won't work here because of the Namespace things
			global $wgNoFollowLinks, $wgNoFollowDomainExceptions;
			if ( $wgNoFollowLinks && !wfMatchesDomainList( $href, $wgNoFollowDomainExceptions ) ) {
				$extraAttribs['rel'] = 'nofollow';
			}

			global $wgExternalLinkTarget;
			if ( $wgExternalLinkTarget ) {
				$extraAttribs['target'] = $wgExternalLinkTarget;
			}
		} else {
			$title = Title::newFromText( $href );
			if ( $title ) {
				if( $title->equals( $current_title ) ) {
					$active = true;
				}
				$title = $title->fixSpecialName();
				$href = $title->getLinkURL();
				if( $GLOBALS['wgTweekiSkinUseRealnames'] == true && $title->exists() && $title->getNamespace() === 2 ) {
					$userkey = $title->getDBKey();

					// use real name if link text hadn't been set explicitly
					if( $href_implicit ) {
						$title_from_text = Title::newFromText( HtmlArmor::getHtml( $text ) );
						if( 
							$title_from_text && 
							( 
								$title->getPrefixedText() == $title_from_text->getPrefixedText() 
								|| $title->getText() == $title_from_text->getText()
							)
						) {
							$text = self::getRealname( $userkey );
						}
					}
				}
			} else {
				// allow empty first argument
				if( $href != '' ) {
					$href = 'INVALID-TITLE:' . $href;
				}
			}
		}
		if ( isset( $line[2] ) && $line[2] != "" ) {
			$extraAttribs['class'] = $line[2];
		}

		$link = [
			'html' => $text,
			'href' => $href,
			'href_implicit' => $href_implicit,
			'active' => $active
		];
		if( $line[0] != '' ) {
			$link['id'] = urlencode( strtolower( strtr( $line[0], ' ', '-' ) ) );
			$link['id'] = 'n-' . Sanitizer::escapeIdForAttribute( $link['id'] );
		}
		$link = array_merge( $link, $extraAttribs );
		return [ $link ];
	}


	/**
	 * Render a user's real name
	 *
	 * @param Parser $parser
	 * @param String $user User name
	 *
	 * @return String User's real name
	 */
	static function renderRealName( $parser, $user ) {
		$realname = self::getRealname( $user );

		return [ $realname ];
	}


	/**
	 * Get a user's real name
	 *
	 * @param String $user User name
	 *
	 * @return String User's real name
	 */
	static function getRealname( $userkey ) {
		if( !$userkey ) {
			return $userkey;
		}
		if( !isset( self::$realnames[$userkey] ) ) {
			$user = User::newFromName( $userkey );
			self::$realnames[$userkey] = $user->getRealName() ?: $user->getName();
		}

		return self::$realnames[$userkey];
	}

	/**
	 * Render Buttons
	 *
	 * @param $buttons array
	 * @param $options Array
	 * @return String
	 */
	static function renderButtons( $buttons, $options = [] ) {
		$renderedButtons = '';
		$groupclass = [];
		if ( isset( $options['class'] ) ) {
			if ( !is_array( $options['class'] ) ) {
				$options['class'] = explode( ' ', $options['class'] );
			}
			$groupclass = $options['class'];
		}
		$currentwrapperclass = '';

		// set wrapper
		$wrapper = 'div';
		if ( isset( $options['wrapper'] ) ) {
			$wrapper = $options['wrapper'];
		}

		foreach ( $buttons as $button ) {
			$btnoptions = [];
			// set classes for specific button
			// explicit classes for the specific line?
			if ( isset( $button['class'] ) ) {
				$button['class'] = explode( ' ', $button['class'] );
			}
			else {
				$button['class'] = $groupclass;
			}
			foreach ( $button['class'] as $btnclass ) {
				if ( strpos( $btnclass, 'btn' ) === 0 ) {
					$button['class'][] = 'btn';
					break;
				}
			}

			// set wrapper class
			if ( isset( $options['wrapperclass'] ) ) {
				$wrapperclass = $options['wrapperclass'];
			}
			else {
				if ( in_array( 'btn', $button['class'] ) === false ) {
					$wrapperclass = 'dropdown';
				}
				else {
					$wrapperclass = 'btn-group' . ( self::isBS4() ? ' mr-2' : '' );
				}
			}

			$button['class'] = implode( ' ', array_unique( $button['class'] ) );

			// set attributes
			$allowed_attributes = [
				'aria-controls',
				'aria-expanded',
				'aria-selected',
				'data-target',
				'data-dismiss',
				'data-placement',
				'data-slide',
				'title',
				'role',
			];

			foreach( $allowed_attributes as $attribute ) {
				if ( isset( $options[$attribute] ) ) {
					$button[$attribute] = $options[$attribute];
				}
			}

			// if data-toggle attribute is set, unset wrapper and add attribute and toggle-class
			if ( isset( $options['data-toggle'] ) ) {
				$wrapper = '';
				$button['data-toggle'] = $options['data-toggle'];
				$button['class'] .= ' ' . $options['data-toggle'] . '-toggle';
			}

			// if html is not set, use text and sanitize it
			if ( !isset( $button['html'] ) ) {
				if( isset( $button['text'] ) ) {
					$button['html'] = htmlspecialchars( $button['text'] );
				}
				else {
					$button['html'] = '#';
				}
			}

			// if glyphicon, fa or icon attribute is set, add icon to buttons
			if ( isset( $options['icon'] ) ) {
				if( ! self::isBS4() ) {
					$options['glyphicon'] = $options['icon'];
				} else {
					$options['fa'] = $options['icon'];
				}
			}
			if ( isset( $options['fa'] ) ) {
				$button['html'] = '<span class="fa fa-' . $options['fa'] . '"></span> ' . $button['html'];
			}

			if ( isset( $options['glyphicon'] ) ) {
				$button['html'] = '<span class="glyphicon glyphicon-' . $options['glyphicon'] . '"></span> ' . $button['html'];
			}

			// render wrapper
			if (
				( ( $currentwrapperclass != $wrapperclass || isset( $button['items'] ) ) && $wrapper != '' )
				|| $wrapper == 'li'
			) {
				if ( $currentwrapperclass != '' ) {
					$renderedButtons .= '</' . $wrapper . '>';
				}
				$renderedButtons .= '<' . $wrapper . ' class="' . $wrapperclass;
				if ( isset( $button['active'] ) && $button['active'] === true ) {
					$renderedButtons .= ' active';
				}
				if( self::isBS4() && isset( $button['items'] ) ) {
					$renderedButtons .= ' dropdown';
				}
				if ( isset( $options['wrapperid'] ) ) {
					$renderedButtons .= '" id="' . $options['wrapperid'];
				}
				$renderedButtons .= '">';
				$currentwrapperclass = $wrapperclass;
			}

			// dropdown
			if ( isset( $button['items'] ) ) {
				if ( isset( $options['dropdownclass'] ) ) {
					$renderedButtons .= TweekiHooks::buildDropdown( $button, $options['dropdownclass'] );
				}
				else {
					$renderedButtons .= TweekiHooks::buildDropdown( $button );
				}
			}

			// simple button
			else {
				$renderedButtons .= TweekiHooks::makeLink( $button, $btnoptions );
			}
		}
		// close wrapper
		if ( $wrapper != '' ) {
			$renderedButtons .= '</' . $wrapper . '>';
		}
		return $renderedButtons;
	}


	/**
	 * Build dropdown
	 *
	 * @param $dropdown array
	 * @return String
	 */
	static function buildDropdown( $dropdown, $dropdownclass = '' ) {
		$renderedDropdown = '';

		if( ! self::isBS4() ) {
			// split dropdown
			if ( isset( $dropdown['href_implicit'] ) && $dropdown['href_implicit'] === false ) {
				$renderedDropdown .= TweekiHooks::makeLink( $dropdown );
				$caret = [
					'class' => 'dropdown-toggle ' . $dropdown['class'],
					'href' => '#',
					'html' => '&zwnj;<b class="caret"></b>',
					'data-toggle' => 'dropdown'
				];
				$renderedDropdown .= TweekiHooks::makeLink( $caret );
			}

			// ordinary dropdown
			else {
				$dropdown['class'] .= ' dropdown-toggle';
				$dropdown['data-toggle'] = 'dropdown';
				$dropdown['html'] = $dropdown['html'] . ' <b class="caret"></b>';
				$dropdown['href'] = '#';
				$renderedDropdown .= TweekiHooks::makeLink( $dropdown );
			}
		} else {
			// split dropdown
			if ( isset( $dropdown['href_implicit'] ) && $dropdown['href_implicit'] === false ) {
				$renderedDropdown .= TweekiHooks::makeLink( $dropdown );
				$caret = [
					'class' => 'dropdown-toggle dropdown-toggle-split ' . $dropdown['class'],
					'href' => '#',
					'data-toggle' => 'dropdown',
					'html' => '<span class="sr-only">Toggle Dropdown</span>',
					'aria-haspopup' => 'true'
				];
				$renderedDropdown .= TweekiHooks::makeLink( $caret );
			}

			// ordinary dropdown
			else {
				$dropdown['class'] .= ' dropdown-toggle';
				$dropdown['data-toggle'] = 'dropdown';
				$dropdown['href'] = '#';
				$dropdown['aria-haspopup'] = 'true';
				$renderedDropdown .= TweekiHooks::makeLink( $dropdown );
			}
		}

		$renderedDropdown .= TweekiHooks::buildDropdownMenu( $dropdown['items'], $dropdownclass );
		return $renderedDropdown;
	}


	/**
	 * Build dropdown-menu (ul)
	 *
	 * @param $dropdownmenu array
	 * @return String
	 */
	static function buildDropdownMenu( $dropdownmenu, $dropdownclass ) {
		if( ! self::isBS4() ) {
			$renderedMenu = '<ul class="dropdown-menu ' . $dropdownclass . '" role="menu">';

			foreach ( $dropdownmenu as $entry ) {
				// divider
				if ( ( !isset( $entry['text'] ) || $entry['text'] == "" ) // no 'text'
					&& ( !isset( $entry['html'] ) || $entry['html'] == "" ) // and no 'html'
				) {
					$renderedMenu .= '<li class="divider" />';
				}

				// header
				elseif ( isset( $entry['text'] ) && isset( $entry['header'] ) && $entry['header'] ) {
					$renderedMenu .= '<li class="dropdown-header">' . $entry['text'] . '</li>';
				}

				// standard menu entry
				else {
					$entry['tabindex'] = '-1';
					$renderedMenu .= TweekiHooks::makeListItem( $entry );
				}
			}

			$renderedMenu .= '</ul>';
		} else {
			$renderedMenu = '<div class="dropdown-menu ' . $dropdownclass . '">';

			foreach ( $dropdownmenu as $entry ) {
				// divider
				if ( ( !isset( $entry['text'] ) || $entry['text'] == "" ) // no 'text'
					&& ( !isset( $entry['html'] ) || $entry['html'] == "" ) // and no 'html'
				) {
					$renderedMenu .= '<div class="dropdown-divider"></div>';
				}

				// header
				elseif ( isset( $entry['text'] ) && isset( $entry['header'] ) && $entry['header'] ) {
					$renderedMenu .= '<h6 class="dropdown-header">' . $entry['text'] . '</h6>';
				}

				// standard menu entry
				else {
					$entry['tabindex'] = '-1';
					$entry['class'] = 'dropdown-item';
					$renderedMenu .= TweekiHooks::makeLink( $entry );
				}
			}

			$renderedMenu .= '</div>';
		}
		return $renderedMenu;
	}


	/**
	 * Produce HTML for a link
	 *
	 * This is a slightly adapted copy of the makeLink function in Skin.php
	 * -> some of the changed parts are marked by comments //
	 *
	 * @param $item array
	 * @param $options array
	 */
	static function makeLink( $item, $options = [] ) {
		// tweeki: nested links?
		if ( isset( $item['links'] ) ) {
			if( isset( $item['links'][0] ) ) {
				$item = $item['links'][0];
			} else {
				return false;
			}
		}

		// tweeki: get text
		$text = '';
		if ( isset( $item['text'] ) ) {
			$text = $item['text'];
		} else {
			if( isset( $item['msg'] ) ) {
				$msgText = wfMessage( $item['msg'] )->inContentLanguage();
				if ( $msgText->exists() ) {
					$text = $msgText->parse();
				}
			}
		}

		$html = htmlspecialchars( $text );

		// tweeki: set raw html
		if ( isset( $item['html'] )) {
			$html = $item['html'];
		}

		// tweeki: set icons for individual buttons (used by some navigational elements)
		if ( isset( $item['icon'] )) {
			if( !self::isBS4() ) {
				$html = '<span class="glyphicon glyphicon-' . $item['icon'] . '"></span> ' . $html;
			} else {
				$html = '<span class="fa fa-' . $item['icon'] . '"></span> ' . $html;
			}
		}

		if ( isset( $options['text-wrapper'] ) ) {
			$wrapper = $options['text-wrapper'];
			if ( isset( $wrapper['tag'] ) ) {
				$wrapper = [ $wrapper ];
			}
			while ( count( $wrapper ) > 0 ) {
				$element = array_pop( $wrapper );
				$html = Html::rawElement( $element['tag'], $element['attributes'] ?? null, $html );
			}
		}

		// tweeki: allow empty first argument in the <btn> tag
		if( isset( $item['href'] ) && $item['href'] == '' ) {
			unset( $item['href'] );
			$options['link-fallback'] = 'span';
		}

		if ( isset( $item['href'] ) || isset( $options['link-fallback'] ) ) {
			$attrs = $item;
			foreach ( [ 'single-id', 'text', 'msg', 'tooltiponly', 'context', 'primary', 'href_implicit', 'items', 'icon', 'html', 'tooltip-params', 'exists', 'active' ] as $k ) {
				unset( $attrs[$k] );
			}

			if ( isset( $attrs['data'] ) ) {
				foreach ( $attrs['data'] as $key => $value ) {
					$attrs[ 'data-' . $key ] = $value;
				}
				unset( $attrs[ 'data' ] );
			}

			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				$item['single-id'] = $item['id'];
			}

			$tooltipParams = [];
			if ( isset( $item['tooltip-params'] ) ) {
				$tooltipParams = $item['tooltip-params'];
			}

			if ( isset( $item['single-id'] ) ) {
				$tooltipOption = isset( $item['exists'] ) && $item['exists'] === false ? 'nonexisting' : null;

				if ( isset( $item['tooltiponly'] ) && $item['tooltiponly'] ) {
					$title = Linker::titleAttrib( $item['single-id'], $tooltipOption, $tooltipParams );
					if ( $title !== false ) {
						$attrs['title'] = $title;
					}
				} else {
					$tip = Linker::tooltipAndAccesskeyAttribs( 
						$item['single-id'],
						$tooltipParams,
						$tooltipOption
					);
					if ( isset( $tip['title'] ) && $tip['title'] !== false ) {
						$attrs['title'] = $tip['title'];
					}
					if ( isset( $tip['accesskey'] ) && $tip['accesskey'] !== false ) {
						$attrs['accesskey'] = $tip['accesskey'];
					}
				}
			}
			if ( isset( $options['link-class'] ) ) {
				if ( isset( $attrs['class'] ) ) {
					// In the future, this should accept an array of classes, not a string
					if ( is_array( $attrs['class'] ) ) {
						$attrs['class'][] = $options['link-class'];
					} else {
						$attrs['class'] .= " {$options['link-class']}";
					}
				} else {
					$attrs['class'] = $options['link-class'];
				}
			}
			
			// tweeki: pass on active class
			if ( isset( $item['active'] ) && $item['active'] ) {
				if ( !isset( $attrs['class'] ) ) {
					$attrs['class'] = 'active';
				} else {
					$attrs['class'] = trim( $attrs['class'] . ' active' );
				}
			}

			$html = Html::rawElement( isset( $attrs['href'] ) 
				? 'a' 
				: $options['link-fallback'], $attrs, $html );
		}

		return $html;
	}

	/**
	 * Produce HTML for a list item
	 *
	 * This is a slightly adapted copy of the makeListItem function in SkinTemplate.php
	 * -> some of the changed parts are marked by comments //
	 *
	 * @param $item array
	 * @param $options array
	 */
	static function makeListItem( $item, $options = [] ) {
		if ( isset( $item['links'] ) ) {
			$html = '';
			foreach ( $item['links'] as $linkKey => $link ) {
				$html .= TweekiHooks::makeLink( $link, $options );
			}
		} else {
			$link = $item;
			// These keys are used by makeListItem and shouldn't be passed on to the link
			foreach ( [ 'id', 'class', 'tag' ] as $k ) {
				unset( $link[$k] );
			}
			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				// The id goes on the <li> not on the <a> for single links
				// but makeSidebarLink still needs to know what id to use when
				// generating tooltips and accesskeys.
				$link['single-id'] = $item['id'];
			}
			$html = TweekiHooks::makeLink( $link, $options );
		}

		$attrs = [];
		foreach ( [ 'id', 'class' ] as $attr ) {
			if ( isset( $item[$attr] ) ) {
				$attrs[$attr] = $item[$attr];
			}
		}
		if ( isset( $item['active'] ) && $item['active'] ) {
			if ( !isset( $attrs['class'] ) ) {
				$attrs['class'] = '';
			}
			$attrs['class'] .= ' active';
			$attrs['class'] = trim( $attrs['class'] );
		}
		return Html::rawElement( isset( $options['tag'] ) ? $options['tag'] : 'li', $attrs, $html );
	}

	/**
	 * Customize edit section links
	 *
	 * @param $skin Skin current skin
	 * @param $title Title
	 * @param $section String section
	 * @param $tooltip
	 * @param $links Array link details
	 * @param $lang String language
	 *
	 * @todo: make this work with VisualEditor
	 */
	static function onSkinEditSectionLinks( $skin, $title, $section, $tooltip, &$links, $lang = false ) {
		if(
			$skin->getSkinName() == 'tweeki'
			&& $GLOBALS['wgTweekiSkinCustomEditSectionLink'] == true
		) {
			$icon = wfMessage( 'tweeki-editsection-icon' )->inLanguage( $lang )->parse();
			$text = wfMessage( 'tweeki-editsection-text' )->inLanguage( $lang )->parse();
			$class = wfMessage( 'tweeki-editsection-class' )->inLanguage( $lang )->parse();
			if( version_compare( MW_VERSION, '1.34', '>=' ) ) {
                                $text = new HtmlArmor( $icon . ( ( $icon != '' ) ? ' ' : '' ) . $text );
                        } else {
                                $text = $icon . ( ( $icon != '' ) ? ' ' : '' ) . $text;
                        }

			$links['editsection']['text'] = $text;
			$links['editsection']['attribs']['class'] = $class;
			return true;
		}
	}


	/**
	 * Change TOC and page content of file pages to togglable tabs
	 *
	 * @param $outputPage OutputPage
	 */
	public static function onAfterFinalPageOutput( $outputPage ) {
		if( 
			$outputPage->getSkin()->getSkinName() == 'tweeki'
			&& $outputPage->getTitle()->getNamespace() == 6 
			&& $GLOBALS['wgTweekiSkinImagePageTOCTabs'] == true 
		) {
			$out = ob_get_clean();
			$out = str_replace( '<ul id="filetoc">', '<ul id="tw-filetoc" class="nav nav-tabs nav-justified">', $out );
			$out = str_replace( '<li><a href="#file">', '<li class="active"><a href="#file" class="tab-toggle" data-toggle="tab">', $out );
			$out = str_replace( '<a href="#filehistory">', '<a href="#filehistory" class="tab-toggle" data-toggle="tab">', $out );
			$out = str_replace( '<a href="#filelinks">', '<a href="#filelinks" class="tab-toggle" data-toggle="tab">', $out );
			$out = str_replace( '<a href="#metadata">', '<a href="#metadata" class="tab-toggle" data-toggle="tab">', $out );
			$out = str_replace( '<div class="fullImageLink" id="file"', '<div class="tab-content"><div id="file" class="tab-pane fade in active"><div class="fullImageLink"', $out );
			$out = str_replace( '<h2 id="filehistory"', '</div><div id="filehistory" class="tab-pane fade"><h2', $out );
			$out = str_replace( '<h2 id="filelinks"', '</div><div id="filelinks" class="tab-pane fade"><h2', $out );
			$out = str_replace( '<h2 id="metadata"', '</div><div id="metadata" class="tab-pane fade"><h2', $out );
			$out = $out . '</div></div>';
			ob_start();
			echo $out;
		}
		return true;
	}

	/**
	 * Implement numbered headings
	 * 
	 * code from https://www.mediawiki.org/wiki/Extension:MagicNumberedHeadings
	 */
	/**
	 * @copyright Copyright © 2007, Purodha Blissenabch.
	 * @license GPL-2.0-or-later
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License
	 * as published by the Free Software Foundation, version 2
	 * of the License.
	 *
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	 * SOFTWARE.
	 * See the GNU General Public License for more details.
	 */
	public static function onMagicWordMagicWords( &$magicWords ) {
		$magicWords[] = 'MAG_NUMBEREDHEADINGS';
		return true;
	}

	public static function onMagicWordwgVariableIDs( &$wgVariableIDs ) {
		$wgVariableIDs[] = 'MAG_NUMBEREDHEADINGS';
		return true;
	}

	public static function onInternalParseBeforeLinks( &$parser, &$text, &$strip_state ) {
		$id = 'MAG_NUMBEREDHEADINGS';
		if ( method_exists( MagicWord::class, 'get' ) ) {
			// Before 1.35.
			$magicWord = MagicWord::get( $id );
		} else {
			// 1.35 and above.
			$magicWord = MediaWikiServices::getInstance()
				->getMagicWordFactory()
				->get( $id );
		}
		if ( $magicWord->matchAndRemove( $text ) ) {
			$parser->mOptions->setNumberHeadings( true );
		}
		return true;
	}
}
