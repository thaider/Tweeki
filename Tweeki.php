<?php

if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'Tweeki' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Tweeki'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['TweekiMagic'] = __DIR__ . '/Tweeki.i18n.magic.php';
	/* wfWarn(
		'Deprecated PHP entry point used for FooBar skin. Please use wfLoadSkin instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'This version of the Tweeki skin requires MediaWiki 1.25+' );
}

// $parser = new ParserOutput();
// $parser->setEnableOOUI(false);