<?php
/**
 * Tweeki skin (and hooks)
 *
 * @file
 * @ingroup Skins
 * @author Tobias Haider, Garrett LeSage
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );
 
$wgExtensionCredits['skin'][] = array(
        'path' => __FILE__,
        'name' => 'Tweeki',
        'version' => '0.1.1',
        'url' => "http://tweeki.thai-land.at",
        'author' => 'Tobias Haider (based on the work of Garrett LeSage)',
        'descriptionmsg' => 'tweeki-desc',
);

$wgValidSkinNames['tweeki'] = 'Tweeki';
$wgAutoloadClasses['SkinTweeki'] = dirname(__FILE__).'/Tweeki.skin.php';
$wgAutoloadClasses['TweekiHooks'] = dirname( __FILE__ ) . '/Tweeki.hooks.php';
$wgMessagesDirs['SkinTweeki'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['TweekiMagic'] = dirname( __FILE__ ) . '/Tweeki.i18n.magic.php';
 
$wgHooks['GetPreferences'][] = 'TweekiHooks::getPreferences';
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::ButtonsSetup';
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::AccordionSetup';
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::TweekiHideSetup';
$wgHooks['DoEditSectionLink'][] = 'TweekiHooks::EditSectionLinkButton';
$wgHooks['ParserBeforeTidy'][] = 'TweekiHooks::HeadlineFix';

# Styles and Scripts have to be splitted in order to get the dependencies right
$wgResourceModules['skins.tweeki.styles'] = array(
  'styles' => array(
		'tweeki/bootstrap/css/bootstrap.min.css' => array( ),
		'tweeki/bootstrap/css/bootstrap-theme.min.css' => array( 'media' => 'screen' ),
		'tweeki/screen.less' => array( 'media' => 'screen' ),
		'tweeki/print.less' => array( 'media' => 'print' ),
		'tweeki/theme.less' => array( 'media' => 'screen' ),
	),
  'remoteBasePath' => &$GLOBALS['wgStylePath'],
  'localBasePath' => &$GLOBALS['wgStyleDirectory'],
);

$wgResourceModules['skins.awesome.styles'] = array(
  'styles' => array(
		'tweeki/awesome/css/font-awesome.min.css' => array( )
	),
  'remoteBasePath' => &$GLOBALS['wgStylePath'],
  'localBasePath' => &$GLOBALS['wgStyleDirectory'],
);

$wgResourceModules['skins.tweeki.scripts'] = array(
	'scripts' => array(
		'tweeki/bootstrap/js/bootstrap.min.js',
		'tweeki/jquery.mousewheel.min.js',
		'tweeki/jquery.smoothDivScroll-1.3.js',
		'tweeki/tweeki.js',
	),
	'dependencies' => array(
		'jquery.ui.widget',
		'mediawiki.jqueryMsg'
	),
  'remoteBasePath' => &$GLOBALS['wgStylePath'],
  'localBasePath' => &$GLOBALS['wgStyleDirectory'],
  'messages' => array(
  	'tweeki-toc-top'
  )
);

# Default options
$wgTweekiSkinHideAll = array( 'footer-info' );
$wgTweekiSkinHideable = array( 'firstHeading' );
$wgTweekiSkinHideAnon = array( 'navbar' );
$wgTweekiSkinHideNonPoweruser = array( 'TOOLBOX', 'EDIT-EXT-special' );
$wgTweekiSkinFooterIcons = true;
$wgTweekiSkinNavigationalElements = array();
$wgTweekiSkinSpecialElements = array(
			'FIRSTHEADING' => 'self::renderFirstHeading',
			'TOC' => 'self::renderTOC',
			'SEARCH' => 'self::renderSearch',
			'LOGO' => 'self::renderLogo',
			'LOGIN-EXT' => 'self::renderLoginExt' );
$wgTweekiSkinUseAwesome = true;