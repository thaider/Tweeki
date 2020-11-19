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

/**
 * Skin subclass for Tweeki
 * @ingroup Skins
 */
class SkinTweeki extends SkinTemplate {
	public $skinname = 'tweeki';
	public $stylename = 'Tweeki';
	public $template = 'TweekiTemplate';
	public $useHeadElement = true;
	/**
	 * @var Config
	 */
	private $tweekiConfig;
	private $responsiveMode = false;

	public function __construct() {
		$this->tweekiConfig = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'tweeki' );
	}

	/**
	 * Initializes output page and sets up skin-specific parameters
	 * @param OutputPage $out Object to initialize
	 */
	public function initPage( OutputPage $out ) {
		parent::initPage( $out );

		$out->addMeta( 'viewport', 'width=device-width, initial-scale=1' );
		$out->addModules( 'skins.tweeki.messages' );

		// load scripts
		if( $this->tweekiConfig->get( 'TweekiSkinCustomScriptModule' ) ) {
			$out->addModules( $this->tweekiConfig->get( 'TweekiSkinCustomScriptModule' ) );
		} elseif( !$this->tweekiConfig->get( 'TweekiSkinUseBootstrap4' ) ) {
			$out->addModules( 'skins.tweeki.scripts' );
		} else {
			if( !$this->tweekiConfig->get( 'TweekiSkinUseCustomFiles' ) ) {
				$out->addModules( 'skins.tweeki.bootstrap4.scripts' );
			} else {
				$out->addModules( 'skins.tweeki.bootstrap4.custom.scripts' );
			}
		}
	}
	
	/**
	 * Override to pass our Config instance to it
	 * @param string $classname
	 * @param bool|string $repository
	 * @param bool|string $cache_dir
	 * @return QuickTemplate
	 */
	public function setupTemplate( $classname, $repository = false, $cache_dir = false ) {
		return new $classname( $this->tweekiConfig );
	}

	/**
	 * Whether the logo should be preloaded with an HTTP link header or not
	 * @since 1.29
	 * @return bool
	 */
	public function shouldPreloadLogo() {
		return true;
	}
}
