<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

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
	 * Is Tweeki used as a skin for the current page?
	 *
	 * @return boolean
	 */
	public static function getSkinTweekiSkin() {
		return $GLOBALS['wgOut']->getSkin()->getSkinName() === 'tweeki';
	}


	/**
	 * Expose TweekiSkinUseTooltips configuration variable
	 *
	 * @param array $vars
	 */
	public static function onResourceLoaderGetConfigVars( array &$vars ) {
		$vars['wgTweekiSkinUseTooltips'] = $GLOBALS['wgTweekiSkinUseTooltips'];
	}


	/**
	 * Setting up parser functions
	 *
	 * @param Parser $parser Parser object being initialized
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'TOC', 'TweekiHooks::TOC' );
		$parser->setHook( 'legend', 'TweekiHooks::legend' );
		$parser->setHook( 'footer', 'TweekiHooks::footer' );
		$parser->setHook( 'accordion', 'TweekiHooks::buildAccordion' );
		$parser->setHook( 'label', 'TweekiHooks::buildLabel' );

		if ( $GLOBALS['wgTweekiSkinUseBtnParser'] === true ) {
			$parser->setHook( 'btn', 'TweekiHooks::buildButtons' );
		}

		$parser->setFunctionHook( 'tweekihide', 'TweekiHooks::setHiddenElements' );
		$parser->setFunctionHook( 'tweekinav', 'TweekiHooks::setCustomNavElement' );
		$parser->setFunctionHook( 'tweekihideexcept', 'TweekiHooks::setHiddenElementsGroups' );
		$parser->setFunctionHook( 'tweekibodyclass', 'TweekiHooks::addBodyclass' );
		$parser->setFunctionHook( 'tweekirealname', 'TweekiHooks::renderRealname' );
	}


	/**
	 * Adding modules
	 *
	 * @param OutputPage $out The OutputPage object.
	 * @param Skin $skin Skin object that will be used to generate the page
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		if( $skin->getSkinName() == 'tweeki' ) {
			$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'tweeki' );

			$styles = [];

			// load mediawiki styles
			$styles[] = 'skins.tweeki.mediawiki.styles';

			// load font awesome
			$styles[] = 'skins.tweeki.awesome.styles';

			// load externally defined style module
			if( $config->get( 'TweekiSkinCustomStyleModule' ) ) {
				$styles[] = $config->get( 'TweekiSkinCustomStyleModule' );

			// or: load modules defined by tweeki
			} else {
				if( !$config->get( 'TweekiSkinUseCustomFiles' ) ) {
					$styles[] = 'skins.tweeki.styles';
				} else {
					$styles[] = 'skins.tweeki.custom.styles';
				}
			}

			// load external link styles
			if( $config->get( 'TweekiSkinUseExternallinkStyles' ) ) {
				$styles[] = 'skins.tweeki.externallinks.styles';
			}

			// load additional modules
			foreach( $GLOBALS['wgTweekiSkinCustomCSS'] as $customstyle ) {
				$styles[] = $customstyle;
			}

			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'SkinTweekiStyleModules', [ $skin, &$styles ] );

			$out->addModuleStyles( $styles );
		}
	}


	/**
	 * GetPreferences hook
	 *
	 * Adds Tweeki-releated items to the preferences
	 *
	 * @param User $user User whose preferences are being modified
	 * @param array $preferences Preferences description array, to be fed to an HTMLForm object
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['tweeki-advanced'] = [
			'type' => 'toggle',
			'label-message' => 'prefs-tweeki-advanced-desc',
			'section' => 'rendering/tweeki-advanced',
			'help-message' => 'prefs-tweeki-advanced-help'
		];
	}


	/**
	 * Pages could be hidden for anonymous users or only be shown for specific groups
	 * so we put a user's group memberships into the page rendering hash
	 *
	 * @param string $confstr Reference to a hash key string which can be modified
	 * @param User $user User object that is requesting the page
	 * @param array $options Array of options used to generate the $confstr hash key
	 */
	static function onPageRenderingHash( &$confstr, User $user, array &$options ) {
		$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
		$groups = $userGroupManager->getUserEffectiveGroups($user);
		sort( $groups );
		$confstr .= "!groups=" . join(',', $groups );
	}


	/**
	 * Add body classes
	 *
	 * @param OutputPage $out The OutputPage which called the hook, can be used to get the real title
	 * @param Skin $sk The Skin that called OutputPage::headElement
	 * @param array $bodyAttrs An array of attributes for the body tag passed to Html::openElement
	 */
	static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, array &$bodyAttrs ) {
		if( $sk->getSkinName() == 'tweeki' ) {
			$additionalBodyClasses = [ 'tweeki-animateLayout' ];

			$user = $out->getUser();
			$userAdvanced = MediaWikiServices::getInstance()->getUserOptionsLookup()->getOption( $user, 'tweeki-advanced' );
			$additionalBodyClasses[] = $userAdvanced ? 'tweeki-advanced' : 'tweeki-non-advanced';
			$additionalBodyClasses[] = $user->isRegistered() ? 'tweeki-user-logged-in' : 'tweeki-user-anon';
			
			$additionalBodyClasses = array_merge( $additionalBodyClasses, $GLOBALS['wgTweekiSkinAdditionalBodyClasses'] );

			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'SkinTweekiAdditionalBodyClasses', [ $sk, &$additionalBodyClasses ] );

			if( count( $additionalBodyClasses ) > 0 ) {
				$bodyAttrs['class'] = $bodyAttrs['class'] . ' ' . preg_replace( "/[^a-zA-Z0-9_\s-]/", "", implode( " ", $additionalBodyClasses ) );
			}
		}
	}


	/**
	 * Use real names instead of user names
	 *
	 * @param LinkRenderer $linkRenderer the LinkRenderer object
	 * @param LinkTarget $target the LinkTarget that the link is pointing to
	 * @param boolean $isKnown boolean indicating whether the page is known or not
	 * @param string|HtmlArmor $text the contents that the <a> tag should have; either a plain, unescaped string or a HtmlArmor object.
	 * @param array $attribs the final HTML attributes of the <a> tag, after processing, in associative array form.
	 * @param string $ret the value to return if your hook returns false.
	 */
	public static function onHtmlPageLinkRendererEnd(  LinkRenderer $linkRenderer, LinkTarget $target, $isKnown, &$text, array &$attribs, &$ret ) {
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


	/**
	 * Use real names instead of user names in selflinks
	 *
	 * @param Title $nt the title object of the page
	 * @param string $html Link text
	 * @param string $trail Text after link
	 * @param string $prefix Text before link
	 * @param string $ret Self link text to be used if the hook returns false
	 */
	public static function onSelfLinkBegin( Title $nt, &$html, &$trail, &$prefix, &$ret ) {
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
	 * Customize edit section links
	 *
	 * @param Skin $skin Skin object rendering the UI
	 * @param Title $title  Title object for the title being linked to
	 * @param string $section The designation of the section being pointed to, to be included in the link
	 * @param string $tooltip The default tooltip.
	 * @param array $links Array containing all link detail arrays.
	 * @param string $lang The language code to use for the link in the wfMessage function
	 *
	 * @todo: make this work with VisualEditor
	 */
	static function onSkinEditSectionLinks( Skin $skin, Title $title, $section, $tooltip, array &$links, $lang = false ) {
		if(
			$skin->getSkinName() == 'tweeki'
			&& $GLOBALS['wgTweekiSkinCustomEditSectionLink'] == true
		) {
			if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
				$icon = wfMessage( 'tweeki-editsection-icon' )->inLanguage( $lang )->parse();
				$text = wfMessage( 'tweeki-editsection-text' )->inLanguage( $lang )->parse();
				$class = wfMessage( 'tweeki-editsection-class' )->inLanguage( $lang )->parse();
				$text = new HtmlArmor( $icon . ( ( $icon != '' ) ? ' ' : '' ) . $text );
				$links['editsection']['text'] = $text;
				$links['editsection']['attribs']['class'] = $class;
			} else {
				$links['editsection']['link-html'] = wfMessage( 'tweeki-editsection-icon' )->inLanguage( $lang )->parse();
				$links['editsection']['text'] = wfMessage( 'tweeki-editsection-text' )->inLanguage( $lang )->parse();
				$links['editsection']['attribs']['class'] = wfMessage( 'tweeki-editsection-class' )->inLanguage( $lang )->parse();
			}
		}

		return false;
	}


	/**
	 * Change TOC and page content of file pages to togglable tabs
	 *
	 * @param OutputPage $outputPage
	 */
	public static function onAfterFinalPageOutput( OutputPage $outputPage ) {
		if(
			$outputPage->getSkin()->getSkinName() == 'tweeki'
			&& $outputPage->getTitle()->getNamespace() == 6
			&& $GLOBALS['wgTweekiSkinImagePageTOCTabs'] == true
		) {
			$out = ob_get_clean();
			$out = str_replace( '<ul id="filetoc">', '<ul id="tw-filetoc" class="nav nav-tabs nav-justified" role="tablist">', $out );
			$out = str_replace( '<li><a href="#file">', '<li class="nav-item"><a href="#file" id="file-tab" class="nav-link active" data-toggle="tab" role="tab" aria-controls="file" aria-selected="true">', $out );
			$out = str_replace( '<li><a href="#filehistory">', '<li class="nav-item"><a href="#filehistory" id="filehistory-tab" class="nav-link" data-toggle="tab" role="tab" aria-controls="filehistory" aria-selected="false">', $out );
			$out = str_replace( '<li><a href="#filelinks">', '<li class="nav-item"><a href="#filelinks" id="filelinks-tab" class="nav-link" data-toggle="tab" role="tab" aria-controls="filelinks" aria-selected="false">', $out );
			$out = str_replace( '<li><a href="#metadata">', '<li class="nav-item"><a href="#metadata" id="metadata-tab" class="nav-link" data-toggle="tab" role="tab" aria-controls="metadata" aria-selected="false">', $out );
			$out = str_replace( '<div class="fullImageLink" id="file"', '<div class="tab-content"><div id="file" class="tab-pane fade show active" role="tabpanel" aria-labelledby="file-tab"><div class="fullImageLink"', $out );
			$out = str_replace( '<h2 id="filehistory"', '</div><div id="filehistory" class="tab-pane fade" role="tabpanel" aria-labelledby="filehistory-tab"><h2', $out );
			$out = str_replace( '<h2 id="filelinks"', '</div><div id="filelinks" class="tab-pane fade" role="tabpanel" aria-labelledby="filelinks-tab"><h2', $out );
			$out = str_replace( '<h2 id="metadata"', '</div><div id="metadata" class="tab-pane fade" role="tabpanel" aria-labelledby="metadata-tab"><h2', $out );
			$out = $out . '</div></div>';
			ob_start();
			echo $out;
		}
	}


	/**
	 * Enable use of <toc> tag
	 */
	public static function TOC( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return [];
		}
		return [ '<div class="tweeki-toc">' . $parser->recursiveTagParse( $input ) . '</div>' ];
	}


	/**
	 * Enable use of <legend> tag
	 */
	public static function legend( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return [];
		}
		return [ '<legend>' . $parser->recursiveTagParse( $input ) . '</legend>', "markerType" => 'nowiki' ];
	}


	/**
	 * Enable use of <footer> tag
	 */
	public static function footer( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return [];
		}
		return [ '<footer>' . $parser->recursiveTagParse( $input ) . '</footer>', "markerType" => 'nowiki' ];
	}


	/**
	 * Enable use of <accordion> tag
	 */
	public static function buildAccordion( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}
		static::$anchorID++;
		$parent = $parser->recursiveTagParse( $args['parent'], $frame );
		$card = '
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
		return $card;
	}


	/**
	 * Enable use of <label> tag
	 */
	public static function buildLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}
		return '<label>' . $parser->recursiveTagParse( $input ) . '</label>';
	}


	/**
	 * Enable use of <btn> tag
	 */
	public static function buildButtons( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}

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
			$args['class'][] = 'btn btn-secondary';
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
	 * Set elements that should be hidden
	 */
	static function setHiddenElements( Parser $parser ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}

		$parser->getOutput()->updateCacheExpiry(0);

		for ( $i = 1; $i < func_num_args(); $i++ ) {
			if ( in_array ( func_get_arg( $i ), $GLOBALS['wgTweekiSkinHideable'] ) ) {
				$GLOBALS['wgTweekiSkinHideAll'][] = func_get_arg( $i );
			}
		}
		return '';
	}


	/**
	 * Set elements that should be hidden except for members of specific groups
	 */
	static function setHiddenElementsGroups( Parser $parser ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}

		$parser->getOutput()->updateCacheExpiry(0);

		$groups_except = explode( ',', func_get_arg( 1 ) );
		$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
		$groups_user = $userGroupManager->getUserEffectiveGroups($parser->getUser());
		if( count( array_intersect( $groups_except, $groups_user ) ) == 0 ) {
			for ( $i = 2; $i < func_num_args(); $i++ ) {
				if ( in_array ( func_get_arg( $i ), $GLOBALS['wgTweekiSkinHideable'] ) ) {
					$GLOBALS['wgTweekiSkinHideAll'][] = func_get_arg( $i );
				}
			}
		}
		return '';
	}


	/**
	 * Customize a navigational element's content
	 */
	static function setCustomNavElement( Parser $parser, $element, ... $content ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}

		$parser->getOutput()->updateCacheExpiry(0);

		$GLOBALS['wgTweekiSkinCustomNav'][$element] = join( '|', $content );

		return '';
	}


	/**
	 * Add classes to body
	 */
	static function addBodyclass( Parser $parser ) {
		if ( !self::getSkinTweekiSkin() ) {
			return '';
		}

		$parser->getOutput()->updateCacheExpiry(0);

		for ( $i = 1; $i < func_num_args(); $i++ ) {
			$GLOBALS['wgTweekiSkinAdditionalBodyClasses'][] = func_get_arg( $i );
		}
		return '';
	}


	/**
	 * Render a user's real name
	 *
	 * @param Parser $parser
	 * @param String $user User name
	 * @return array User's real name
	 */
	static function renderRealName( Parser $parser, $user ) {
		if ( !self::getSkinTweekiSkin() ) {
			return [];
		}

		$realname = self::getRealname( $user );

		return [ $realname ];
	}


	/**
	 * Parse string input into array
	 *
	 * @param string $buttongroup one or more buttons
	 * @param Parser $parser current parser
	 * @param PPFrame $frame current frame
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
	 * @param Parser $parser current parser
	 * @param PPFrame $frame current frame
	 * @return array
	 */
	static function parseButtonLink( $line, Parser $parser, $frame ) {
		$extraAttribs = [];
		$href_implicit = false;
		$active = false;
		$current_title = $parser instanceof Parser ? $parser->getTitle() : null;

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
				$semanticHits = $parser->recursiveTagParse( '{{#ask:' . $semanticQuery . '|link=none|format=array}}', false );
				$semanticHits = explode( ',', $semanticHits );
				$semanticLinks = [];
				foreach ( $semanticHits as $semanticHit ) {
					$semanticHit = rtrim( str_replace( '&lt;PROP&gt;', '|', $semanticHit ), '|' );
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
				$href = trim( $parser->replaceVariables( $href, $frame ) );
			}
			else {
				$href = 'INVALID-HREF/PARSER-BROKEN';
			}
		}

		if ( preg_match( '/^(?i:' . wfUrlProtocols() . ')/', $href ) ) {
			// Parser::getExternalLinkAttribs won't work here because of the Namespace things
			if ( $GLOBALS['wgNoFollowLinks'] && !wfMatchesDomainList( $href, $GLOBALS['wgNoFollowDomainExceptions'] ) ) {
				$extraAttribs['rel'] = 'nofollow';
			}

			if ( $GLOBALS['wgExternalLinkTarget'] ) {
				$extraAttribs['target'] = $GLOBALS['wgExternalLinkTarget'];
			}
		} else {
			$title = Title::newFromText( $href );
			if ( $title ) {
				if( !is_null( $current_title ) && $current_title instanceof Title && $title->equals( $current_title ) ) {
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
					$wrapperclass = 'btn-group mr-2';
				}
			}

			$button['class'] = implode( ' ', array_unique( $button['class'] ) );
			if( isset( $button['links'] ) ) {
				foreach( $button['links'] as &$link ) {
					$link['class'] = $button['class'];
				}
			}

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
				$options['fa'] = $options['icon'];
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
				if( isset( $button['items'] ) ) {
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
	 * @param array $dropdown
	 * @param string $dropdownclass
	 * @return String
	 */
	static function buildDropdown( $dropdown, $dropdownclass = '' ) {
		$renderedDropdown = '';

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

		$renderedDropdown .= TweekiHooks::buildDropdownMenu( $dropdown['items'], $dropdownclass );
		return $renderedDropdown;
	}


	/**
	 * Build dropdown-menu (ul)
	 *
	 * @param array $dropdownmenu
	 * @param string $dropdownclass
	 * @return String
	 */
	static function buildDropdownMenu( array $dropdownmenu, $dropdownclass ) {
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
		return $renderedMenu;
	}


	/**
	 * Produce HTML for a link
	 *
	 * This is a slightly adapted copy of the makeLink function in Skin.php
	 * -> some of the changed parts are marked by comments //
	 *
	 * @param array $item
	 * @param array $options
	 */
	static function makeLink( array $item, array $options = [] ) {
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
			$html = '<span class="fa fa-' . $item['icon'] . '"></span> ' . $html;
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
	 * @param array $item
	 * @param array $options
	 */
	static function makeListItem( array $item, array $options = [] ) {
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
		$magicWord = MediaWikiServices::getInstance()
			->getMagicWordFactory()
			->get( $id );
		if ( $magicWord->matchAndRemove( $text ) ) {
			return true; // needs reimplementation (with JS?), feature was removed from MW
			// $parser->mOptions->setNumberHeadings( true );
		}
		return true;
	}
}
