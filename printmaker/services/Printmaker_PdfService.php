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
class Printmaker_PdfService extends BaseApplicationComponent
{

	/**
	 * Returns a new Printmaker_PdfModel pre-loaded with the supplied HTML and settings
	 *
	 * @param string $html
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	public function getPdfModel($html = '', $settings = array())
	{
		return new Printmaker_PdfModel($html, $settings);
	}

	/**
	 * Returns a Printmaker_PdfModel created from the supplied HTML
	 *
	 * @param string $html
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	public function pdfFromHtml($html = null, $settings = array())
	{
		return $this->getPdfModel($html, $settings);
	}

	/**
	 * Returns a Printmaker_PdfModel created from the contents at the supplied URL
	 *
	 * @param string $url
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel|false
	 */
	public function pdfFromUrl($url = null, $settings = array()) {
		// TODO: CURL the contents of the URL and make a PDF with it
		return false;
	}

	/**
	 * Returns a Printmaker_PdfModel created from the contents of the supplied file
	 *
	 * @param string $path
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel|false
	 */
	public function pdfFromFile($path = null, $settings = array()) {
		// TODO: Load the contents of the path and make a PDF with it
		return false;
	}

	/**
	 * Returns a Printmaker_PdfModel created by rendering the specified template with any provided vars
	 *
	 * @param string $template
	 * @param mixed $vars
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel|false
	 */
	public function pdfFromTemplate($template = null, $vars = array(), $settings = array()) {

		$templatesPath = craft()->path->getTemplatesPath();
		craft()->path->setTemplatesPath(CRAFT_TEMPLATES_PATH);
		$html = craft()->templates->render($template, $vars);
		craft()->path->setTemplatesPath($templatesPath);

		return $this->getPdfModel($html, $settings);

	}

}