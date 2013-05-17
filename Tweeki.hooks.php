<?php
/**
 * Hooks for Tweeki skin
 *
 * @file
 * @ingroup Extensions
 */

class TweekiHooks {

	/* Static Methods */

	/**
	 * GetPreferences hook
	 *
	 * Adds Tweeki-releated items to the preferences
	 *
	 * @param $user User current user
	 * @param $defaultPreferences array list of default user preference controls
	 */
	public static function getPreferences( $user, &$defaultPreferences ) {

        $defaultPreferences['tweeki-poweruser'] = array(
                'type' => 'toggle',
                'label-message' => 'tweeki-poweruser-preference', // a system message
                'section' => 'tweeki/poweruser',
        );

		return true;
	}

	/**
	 * ButtonsSetup hook
	 *
	 * @param $parser Parser current parser
	 */
	static function ButtonsSetup( Parser $parser ) {
			$parser->setHook( 'button', 'TweekiHooks::buildButtons' );
			return true;
	}

	/**
	 * Build buttons, groups of buttons and dropdowns
	 * @param $buttons array
	 * @param $input string
	 * @param $args array tag arguments
	 * @param $parser Parser current parser
	 * @param $frame PPFrame current frame
	 * @return String
	 */
	static function buildButtons( $input, array $args, Parser $parser, PPFrame $frame ) {
		$renderedButtons = '';

		$buttongroups = preg_split( '/\n{2,}/', $input );

		/* set standard classes for all buttons in the group */
		if ( !isset( $args['class'] ) ) {
			$args['class'][] = 'btn';
			}
		else {
			$args['class'] = explode( ' ', $args['class'] );
			}
		if ( isset( $args['size'] ) ) {
			$args['class'][] = 'btn-' . $args['size'];
			}

		foreach ( $buttongroups as $buttongroup ) {
			$buttons = array();
			$buttons = TweekiHooks::parseButtons( $buttongroup, $parser );
			$renderedButtons .= TweekiHooks::renderButtons( $buttons, $args );
			}

		/* more than one buttongroup build a toolbar */
		if ( count( $buttongroups ) > 1 ) {
			$renderedButtons = '<div class="btn-toolbar">' . $renderedButtons . '</div>';
			}

		return $renderedButtons;
		}


	/**
	 * Parse string input into array
	 * @param $buttons array
	 * @param $input string
	 * @param $parser Parser current parser
	 * @return Array
	 */
	static function parseButtons( $buttongroup, $parser ) {
		$buttons = array();
		$lines = explode( "\n", $buttongroup );

		$currentlevel = 0;
		$parent[0] = &$buttons;

		foreach ( $lines as $line ) {
			/* empty line */
			// TODO: eliminate empty lines already when building button groups
			if ( trim( $line ) == "" ) { continue; }
			/* buttons */
			if ( strpos( $line, '*' ) !== 0 ) {
				$parent[0] = array_merge( $parent[0], TweekiHooks::parseButtonLink( trim( $line ), $parser ) );
				end( $parent[0] );
				$parent[1] = &$parent[0][key( $parent[0] )];
				$currentlevel = 1;
				}
			/* dropdown-menus and submenus */
			else {
				$cleanline = ltrim( $line, '*' );
				$newlevel = strlen( $line ) - strlen( $cleanline );
				$cleanline = trim( $cleanline );
				while ( $newlevel > $currentlevel + 1 ) {
					end( $parent[$currentlevel]['items'] );
					$parent[$currentlevel + 1] = &$parent[$currentlevel]['items'][key( $parent[$currentlevel]['items'] )];
					$currentlevel++;
					}
				if ( !isset( $parent[$newlevel]['items'] ) ) {
					$parent[$newlevel]['items'] = array();
					}
				$parent[$newlevel]['items'] = array_merge( $parent[$newlevel]['items'], TweekiHooks::parseButtonLink( $cleanline, $parser ) );
				end( $parent[$newlevel]['items'] );
				$parent[$newlevel + 1] = &$parent[$newlevel]['items'][key( $parent[$newlevel]['items'] )];
				$currentlevel = $newlevel + 1;
				}
			}
		return $buttons;
	}


	/**
	 * Parse specific link
	 * @param $line string
	 * @param $parser Parser current parser
	 * @return Array
	 */
	static function parseButtonLink( $line, $parser ) {

		$extraAttribs = array();
		$href_implicit = false;

		/* semantic queries */
		if ( strpos( $line, '{{#ask:' ) === 0 ) {
			$semanticQuery = substr( $line, 7, -2 );
			$semanticHitNumber = $parser->recursiveTagParse( '{{#ask:' . $semanticQuery . '|format=count}}' );
			if ( !is_numeric( $semanticHitNumber ) || $semanticHitNumber < 1 ) {
				return array( array( 'text' => $semanticQuery, 'href' => 'INVALID-QUERY' ) );
				}
			$semanticHits = $parser->recursiveTagParse( '{{#ask:' . $semanticQuery . '|format=list|link=none}}' );
			$semanticHits = explode( ',', $semanticHits );
			$semanticLinks = array();
			foreach ( $semanticHits as $semanticHit ) {
				$semanticLink = TweekiHooks::parseButtonLink( $semanticHit, $parser );
				$semanticLinks[] = $semanticLink[0];
				}
			return $semanticLinks;
			}

		$line = explode( '|', $line );
		foreach ( $line as $single_line ) {
			$single_line = trim( $single_line );
			}
		$msgText = wfMessage( $line[0] )->inContentLanguage();
		if ( $msgText->exists() ) {
			$text = $msgText->parse();
			}
		else {
			$text = $line[0];
			}

		if ( isset( $line[1] ) && $line[1] != "" ) {
			$href = $line[1];
			}
		else {
			$href_implicit = true;
			$href = $line[0];
			}
		$msgLink = wfMessage( $href )->inContentLanguage();
		if ( $msgLink->exists() ) {
			$href = $msgLink->parse();
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
				$title = $title->fixSpecialName();
				$href = $title->getLinkURL();
			} else {
				$href = 'INVALID-TITLE';
			}
		}
		if ( isset( $line[2] ) && $line[2] != "" ) {
			$extraAttribs['class'] = $line[2];
			}

		$link = array_merge( array(
				'text' => $text,
				'href' => $href,
				'href_implicit' => $href_implicit,
				'id' => 'n-' . Sanitizer::escapeId( strtr( $line[0], ' ', '-' ), 'noninitial' ),
				'active' => false
			), $extraAttribs );
		return array( $link );
	}

	/**
	 * Parse string input into array
	 * @param $renderedButtons String
	 * @param $buttons array
	 * @param $options Array
	 * @return String
	 */
	static function renderButtons( $buttons, $options = array() ) {
		$renderedButtons = '';
		$groupclass = array();
		if ( isset( $options['class'] ) ) {
			if ( !is_array( $options['class'] ) ) {
				$options['class'] = explode( ' ', $options['class'] );
				}
			$groupclass = $options['class'];
			}
		$currentwrapperclass = '';

		/* set wrapper */
		$wrapper = 'div';
		if ( isset( $options['wrapper'] ) ) { $wrapper = $options['wrapper']; }

		foreach ( $buttons as $button ) {
			/* set classes for specific button */
			/* explicit classes for the specific line? */
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

			/* set wrapper class */
			if ( isset( $options['wrapperclass'] ) ) {
				$wrapperclass = $options['wrapperclass'];
				}
			else {
				if ( in_array( 'btn', $button['class'] ) === false ) {
					$wrapperclass = 'dropdown';
					}
				else {
					$wrapperclass = 'btn-group';
					}
				}

			$button['class'] = implode( ' ', array_unique( $button['class'] ) );

			/* render wrapper */
			if ( ( ( $currentwrapperclass != $wrapperclass || isset( $button['items'] ) ) && $wrapper != '' ) || $wrapper == 'li' ) {
				if ( $currentwrapperclass != '' ) {
					$renderedButtons .= '</' . $wrapper . '>';
					}
				$renderedButtons .= '<' . $wrapper . ' class="' . $wrapperclass;
				if ( isset( $options['wrapperid'] ) ) $renderedButtons .= '" id="' . $options['wrapperid'];
				$renderedButtons .= '">';
				$currentwrapperclass = $wrapperclass;
				}

			/* dropdown */
			if ( isset( $button['items'] ) ) {
				if ( isset( $options['dropdownclass'] ) ) {
					$renderedButtons .= TweekiHooks::buildDropdown( $button, $options['dropdownclass'] );
					}
				else {
					$renderedButtons .= TweekiHooks::buildDropdown( $button );
					}
				}

			/* simple button */
			else {
				$renderedButtons .= TweekiHooks::makeLink( 'what?', $button );
				}
			}
		/* close wrapper */
		if ( $wrapper != '' ) $renderedButtons .= '</' . $wrapper . '>';
		return $renderedButtons;
	}


	/**
	 * Build dropdown
	 * @param $dropdown array
	 * @return String
	 */
	static function buildDropdown( $dropdown, $dropdownclass = '' ) {
		$renderedDropdown = '';

		/* split dropdown */
		if ( isset( $dropdown['href_implicit'] ) && $dropdown['href_implicit'] === false ) {
			$renderedDropdown .= TweekiHooks::makeLink( 'what?', $dropdown );
			$caret = array(
									'class' => 'dropdown-toggle ' . $dropdown['class'],
									'href' => '#',
									'text' => '&zwnj;<b class="caret"></b>',
									'html' => '&zwnj;<b class="caret"></b>',
									// TODO: delete ugly &zwnj;!
									'data-toggle' => 'dropdown'
									);
			$renderedDropdown .= TweekiHooks::makeLink( 'what?', $caret );
			}

		/* ordinary dropdown */
		else {
			$dropdown['class'] .= ' dropdown-toggle';
			$dropdown['data-toggle'] = 'dropdown';
			$dropdown['html'] = $dropdown['text'] . '<b class="caret"></b>';
			$renderedDropdown .= TweekiHooks::makeLink( 'what?', $dropdown);
			}

		$renderedDropdown .= TweekiHooks::buildDropdownMenu( $dropdown['items'], $dropdownclass );

		return $renderedDropdown;
	}


	/**
	 * Build dropdown-menu (ul)
	 * @param $dropdownmenu array
	 * @return String
	 */
	static function buildDropdownMenu( $dropdownmenu, $dropdownclass ) {
		$renderedMenu = '<ul class="dropdown-menu ' . $dropdownclass . '" role="menu">';

		foreach ( $dropdownmenu as $entry ) {

			/* divider */
			if ( !isset( $entry['text'] ) || $entry['text'] == "" ) {
				$renderedMenu .= '
					<li class="divider" />';
				continue;
				}

			/* submenu */
			if ( isset( $entry['items'] ) ) {
				$renderedMenu .= '
					<li class="dropdown-submenu"><a tabindex="-1" href="#">' . $entry['text'] . '</a>';
				$renderedMenu .= TweekiHooks::buildDropdownMenu( $entry['items'], $dropdownclass );
				$renderedMenu .= '</li>';
				}

			/* standard menu entry */
			else {
				$entry['tabindex'] = '-1';
				$renderedMenu .= TweekiHooks::makeListItem( 'what?', $entry );
				}
			}

		$renderedMenu .= '
			</ul>';
		return $renderedMenu;
	}


/* this is a slightly adapted copy of the makeLink function in SkinTemplate.php */
	static function makeLink( $key, $item, $options = array() ) {
		/* nested links? */
		if ( isset( $item['links'] ) ) {
			$item = $item['links'][0];
			}


		if ( isset( $item['text'] ) ) {
			$text = $item['text'];
		} else {
//			$text = $this->translator->translate( isset( $item['msg'] ) ? $item['msg'] : $key );
				return '';
		}

		$html = htmlspecialchars( $text );

		/* set raw html */
		if ( isset( $item['html'] )) {
			$html = $item['html'];
			}

		/* set icon */
		if ( isset( $item['icon'] )) {
			$html = '<i class="' . $item['icon'] . '"></i> ' . $html;
			}

		if ( isset( $options['text-wrapper'] ) ) {
			$wrapper = $options['text-wrapper'];
			if ( isset( $wrapper['tag'] ) ) {
				$wrapper = array( $wrapper );
			}
			while ( count( $wrapper ) > 0 ) {
				$element = array_pop( $wrapper );
				$html = Html::rawElement( $element['tag'], isset( $element['attributes'] ) ? $element['attributes'] : null, $html );
			}
		}

		if ( isset( $item['href'] ) || isset( $options['link-fallback'] ) ) {
			$attrs = $item;
//			foreach ( array( 'single-id', 'text', 'msg', 'tooltiponly' ) as $k ) {
			foreach ( array( 'single-id', 'text', 'msg', 'tooltiponly', 'href_implicit', 'items', 'icon', 'html' ) as $k ) {
				unset( $attrs[$k] );
			}

			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				$item['single-id'] = $item['id'];
			}
			if ( isset( $item['single-id'] ) ) {
				if ( isset( $item['tooltiponly'] ) && $item['tooltiponly'] ) {
					$title = Linker::titleAttrib( $item['single-id'] );
					if ( $title !== false ) {
						$attrs['title'] = $title;
					}
				} else {
					$tip = Linker::tooltipAndAccesskeyAttribs( $item['single-id'] );
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
					$attrs['class'] .= " {$options['link-class']}";
				} else {
					$attrs['class'] = $options['link-class'];
				}
			}
//				echo var_dump($attrs);
			$html = Html::rawElement( isset( $attrs['href'] ) ? 'a' : $options['link-fallback'], $attrs, $html );
		}

		return $html;
	}

/* this is a copy of the makeListItem function in SkinTemplate.php */
	static function makeListItem( $key, $item, $options = array() ) {
		if ( isset( $item['links'] ) ) {
			$html = '';
			foreach ( $item['links'] as $linkKey => $link ) {
				$html .= TweekiHooks::makeLink( $linkKey, $link, $options );
			}
		} else {
			$link = $item;
			// These keys are used by makeListItem and shouldn't be passed on to the link
			foreach ( array( 'id', 'class', 'active', 'tag' ) as $k ) {
				unset( $link[$k] );
			}
			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				// The id goes on the <li> not on the <a> for single links
				// but makeSidebarLink still needs to know what id to use when
				// generating tooltips and accesskeys.
				$link['single-id'] = $item['id'];
			}
			$html = TweekiHooks::makeLink( $key, $link, $options );
		}

		$attrs = array();
		foreach ( array( 'id', 'class' ) as $attr ) {
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

}