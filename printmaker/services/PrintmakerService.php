<?php
namespace Craft;


/**
 * Printmaker_PdfService
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com.>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class PrintmakerService extends BaseApplicationComponent
{


	/**
	 * ... TODO
	 */
	public function init()
	{
		parent::init();
		Craft::import('plugins.printmaker.factories.*');
	}


	/**
	 * @param string $html
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	public function createFromHtml($html = '', $settings = array())
	{
		$factory = new Printmaker_PdfMaker($html, $settings);
		return $factory->getPdf();
	}


	/**
	 * Returns a PDF created by rendering the specified template with any provided vars
	 *
	 * @param null $template
	 * @param array $vars
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	public function createFromTemplate($template = null, $vars = array(), $settings = array()) {

		$templatesPath = craft()->path->getTemplatesPath();
		craft()->path->setTemplatesPath(CRAFT_TEMPLATES_PATH);
		$html = craft()->templates->render($template, $vars);
		craft()->path->setTemplatesPath($templatesPath);

		return $this->createFromHtml($html, $settings);

	}


	/**
	 * ... TODO
	 *
	 * @param $pdfs
	 *
	 * @return Printmaker_PdfModel
	 */
	public function merge($pdfs)
	{
		$factory = new Printmaker_PdfMerger($pdfs);
		return $factory->getPdf();
	}


	/**
	 * ... TODO
	 *
	 * @param $pdf
	 *
	 * @return Printmaker_PdfModel
	 */
	public function load($pdf)
	{
		$factory = new Printmaker_PdfLoader($pdf);
		return $factory->getPdf();
	}


	/**
	 * ... TODO
	 *
	 * @return string
	 */
	public function getCacheUrl()
	{
		if (!empty($cacheUrl = craft()->config->get('cacheUrl', 'printmaker')))
		{
			return rtrim($cacheUrl, '/');
		}
		else
		{
			return rtrim(UrlHelper::getSiteUrl(), '/') . '/' . 'Printmaker';
		}
	}


	/**
	 * ... TODO
	 *
	 * @return string
	 */
	public function getCachePath()
	{
		if (!empty($cachePath = craft()->config->get('cachePath', 'printmaker')))
		{
			return rtrim($cachePath, DIRECTORY_SEPARATOR);
		}
		else
		{
			return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Printmaker';
		}
	}


	/**
	 * ... TODO
	 *
	 * @return string
	 */
	public function getImageCacheUrl()
	{
		if (!empty($imageCacheUrl = craft()->config->get('imageCacheUrl', 'printmaker')))
		{
			return rtrim($imageCacheUrl, '/');
		}
		else
		{
			return $this->getCacheUrl();
		}
	}


	/**
	 * ... TODO
	 *
	 * @return string
	 */
	public function getImageCachePath()
	{
		if (!empty($imageCachePath = craft()->config->get('imageCachePath', 'printmaker')))
		{
			return rtrim($imageCachePath, DIRECTORY_SEPARATOR);
		}
		else
		{
			return $this->getCachePath();
		}
	}


	/**
	 * ... TODO
	 *
	 * @return string
	 */
	public function getTempPath()
	{
		if (!empty($tempPath = craft()->config->get('imageCachePath', 'printmaker')))
		{
			return rtrim($tempPath, DIRECTORY_SEPARATOR);
		}
		else
		{
			return CRAFT_STORAGE_PATH . DIRECTORY_SEPARATOR . 'Printmaker';
		}
	}


}
