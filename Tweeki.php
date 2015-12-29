<?php
/**
 * Tweeki skin and hook setup
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
 * @author Tobias Haider, Garrett LeSage
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );
 
$wgExtensionCredits['skin'][] = array(
				'path' => __FILE__,
				'name' => 'Tweeki',
				'version' => '0.1.2-alpha',
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
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::LabelSetup';
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::AccordionSetup';
$wgHooks['ParserFirstCallInit'][] = 'TweekiHooks::TweekiHideSetup';
$wgHooks['DoEditSectionLink'][] = 'TweekiHooks::EditSectionLinkButton';
$wgHooks['ParserBeforeTidy'][] = 'TweekiHooks::HeadlineFix';

# Styles and Scripts have to be splitted in order to get the dependencies right
$wgResourceModules['skins.tweeki.bootstrap.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'bootstrap/css/bootstrap.min.css' => array( ),
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'screen.less' => array( 'media' => 'screen' ),
		'print.less' => array( 'media' => 'print' ),
		'mediawiki/content.css' => array( 'media' => 'screen' ),
		'mediawiki/elements.css' => array( 'media' => 'screen' ),
		'mediawiki/interface.css' => array( 'media' => 'screen' )
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.externallinks.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'components/externallinks.less' => array( 'media' => 'screen' ),
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.corrections.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'components/corrections.less' => array( 'media' => 'screen' ),
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.awesome.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'awesome/css/font-awesome.min.css' => array( )
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.bootstraptheme.styles'] = array(
	'position' => 'top',
	'styles' => array(
		'bootstrap/css/bootstrap-theme.min.css' => array( 'media' => 'screen' ),
		'corrections-theme.less' => array( 'media' => 'screen' )
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.bootstrap.scripts'] = array(
	'position' => 'bottom',
	'scripts' => array(
		'bootstrap/js/bootstrap.min.js',
	),
	'dependencies' => array(
		'jquery.ui.widget',
		'mediawiki.jqueryMsg'
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgResourceModules['skins.tweeki.scripts'] = array(
	'position' => 'bottom',
	'scripts' => array(
		'tweeki.js',
	),
	'dependencies' => array(
		'skins.bootstrap.scripts'
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__,
	'messages' => array(
		'tweeki-toc-top'
	)
);

$wgResourceModules['skins.tweeki.tooltips'] = array(
	'position' => 'bottom',
	'scripts' => array(
		'tweeki-tooltips-setup.js',
	),
	'dependencies' => array(
		'skins.tweeki.scripts'
	),
	'remoteSkinPath' => 'Tweeki',
	'localBasePath' => __DIR__
);

$wgExtensionFunctions[] = 'efTweekiSkinSetup';

function efTweekiSkinSetup() {
	global $wgTweekiSkinCustomizedBootstrap, $wgResourceModules;

	/* Load customized bootstrap files */
	if( isset( $wgTweekiSkinCustomizedBootstrap ) && ! is_null( $wgTweekiSkinCustomizedBootstrap ) ) {
		$wgResourceModules['skins.bootstrap.styles']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
		$wgResourceModules['skins.bootstrap.styles']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
		unset( $wgResourceModules['skins.bootstrap.styles']['remoteSkinPath'] );
		$wgResourceModules['skins.bootstraptheme.styles']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
		$wgResourceModules['skins.bootstraptheme.styles']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
		unset( $wgResourceModules['skins.bootstraptheme.styles']['remoteSkinPath'] );
		$wgResourceModules['skins.bootstrap.scripts']['localBasePath'] = $wgTweekiSkinCustomizedBootstrap['localBasePath'];
		$wgResourceModules['skins.bootstrap.scripts']['remoteExtPath'] = $wgTweekiSkinCustomizedBootstrap['remoteExtPath'];
		unset( $wgResourceModules['skins.bootstrap.scripts']['remoteSkinPath'] );
	}
}

/**
 * DEFAULT SETTINGS
 *
 * don't change this file, instead copy the variables you would like
 * to adjust to LocalSettings.php
 */

/**
 * This variable can be used to hide elements from everybody. 
 * The {{#tweekihide}} parser function will add to this array. 
 * Attention: For the parser function only hiding of elements 
 * that are also listed in $wgTweekiSkinHideable will actually 
 * be put into effect.
 */
$wgTweekiSkinHideAll = array( 'footer-info' );

/**
 * In order to prevent abuse, only elements listed in this array 
 * are allowed to be hidden by the {{#tweekihide}} parser function.
 */
$wgTweekiSkinHideable = array( 'firstHeading' );

/**
 * Elements in this array will be hidden for users who are not logged in.
 */
$wgTweekiSkinHideAnon = array( 'subnav', 'PERSONAL', 'TOOLBOX' );

/**
 * Elements in this array will be hidden for users who are logged in.
 */
$wgTweekiSkinHideLoggedin = array( 'footer-custom' );

/**
 * Elements in this array will only be shown to users who have chosen 
 * in their preferences to show "advanced features".
 */
$wgTweekiSkinHideNonAdvanced = array( 'EDIT-EXT-special' );

/**
 * If set to false, the icons in the footer will be replaced by text aquivalents.
 */
$wgTweekiSkinFooterIcons = false;

/**
 * Use this variable to change the default page layout. Replace the value 
 * with the name of a custom function - use TweekiTemplate::renderPage() 
 * in Tweeki.skin.php as a template to build your own layout.
 */
$wgTweekiSkinPageRenderer = 'self::renderPage';

/**
 * Add to this array to create customized buttons, the array's key is 
 * the keyword for the navigational element to be used in navbars, subnav, 
 * sidebar, or footer, the value is the name of a callback function. This 
 * function will be called with the skin object as argument and should 
 * return either an array of buttons or a string that can be parsed as buttons. 
 */
$wgTweekiSkinNavigationalElements = array();

/** 
 * Use this array to add completely arbitrary code into navbars, subnav, sidebar, 
 * or footer. The value again is a callback function you need to create. It will 
 * be called with two arguments, the skin object and the context as a string 
 * (navbar-left, navbar-right, subnav, sidebar, footer). The function should 
 * directly print the html you want to have.
 */
$wgTweekiSkinSpecialElements = array(
	'FIRSTHEADING' => 'self::renderFirstHeading',
	'TOC' => 'self::renderTOC',
	'SEARCH' => 'self::renderSearch',
	'LOGO' => 'self::renderLogo',
	'LOGIN-EXT' => 'self::renderLoginExt',
	'FOOTER' => 'self::renderStandardFooter'
);

/**
 * Array of resource modules to be loaded
 * skins.tweeki.bootstrap.styles - bootstrap
 * skins.tweeki.styles - basic styling
 * skins.tweeki.corrections.styles - corrections
 */
$wgTweekiSkinStyles = array( 
	'skins.tweeki.bootstrap.styles', 
	'skins.tweeki.styles',
	'skins.tweeki.corrections.styles',
	'skins.tweeki.externallinks.styles'
); 

/** 
 * Whether or not to include Font Awesome to allow the use of its icons.
 */
$wgTweekiSkinUseAwesome = true;

/**
 * Whether or not to include the code for Bootstrap's theme (enhanced styling 
 * for buttons etc.).
 */
$wgTweekiSkinUseBootstrapTheme = true;

/**
 * Whether or not to parse the <btn>-Tag.
 */
$wgTweekiSkinUseBtnParser = true;

/**
 * NOT YET IMPLEMENTED
 * Whether or not to use Bootstrap's scrollspy feature
 */
$wgTweekiSkinUseScrollSpy = true;

/**
 * Whether or not to use tooltips
 */
$wgTweekiSkinUseTooltips = false;

/**
 * Add Resource Modules to this array.
 */
$wgTweekiSkinCustomCSS = array();

/**
 * Use costumized bootstrap files created via http://getbootstrap.com/customize/
 */
$wgTweekiSkinCustomizedBootstrap = NULL;
