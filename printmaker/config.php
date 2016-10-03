<?php

/**
 * Printmaker Config
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */

return array(

	// Printmaker settings

	'defaultOrientation' => 'portrait',
	'compress' => false,
	'filename' => null,
	'extension' => 'pdf',

	'cacheUrl' => null, // Default defined in PrintmakerService->getCacheUrl()
	'cachePath' => null, // Default defined in PrintmakerService->getCachePath()
	'cacheDirectory' => null,
	'tempPath' => null, // Default defined in PrintmakerService->getTempPath()

	'imageCacheUrl' => null,
	'imageCachePath' => null, // Default defined in PrintmakerService->getImageCachePath()
	'imageCacheDirectory' => null,
	'imageFormat' => 'jpg',

	'encrypt' => false,
	'userPass' => '',
	'ownerPass' => '',
	'canPrint' => true,
	'canModify' => true,
	'canCopy' => true,
	'canAdd' => true,
	'devMode' => false,

	// DOMPDF system settings

	'tempDir' => null,
	'fontDir' => null,
	'fontCache' => null,
	'logOutputFile' => null,

	// DOMPDF rendering defaults

	'defaultMediaType' => 'screen',
	'defaultPaperSize' => 'letter',
	'defaultFont' => 'serif',
	'dpi' => 96,
	'fontHeightRatio' => 1.1,

	// DOMPDF parsing defaults

	'isPhpEnabled' => false,
	'isRemoteEnabled' => true,
	'isJavascriptEnabled' => null,
	'isHtml5ParserEnabled' => null,
	'isFontSubsettingEnabled' => null,

	// DOMPDF debugging defaults

	'debugPng' => null,
	'debugKeepTemp' => null,
	'debugCss' => null,
	'debugLayout' => null,
	'debugLayoutLines' => null,
	'debugLayoutBlocks' => null,
	'debugLayoutInline' => null,
	'debugLayoutPaddingBox' => null,

);
