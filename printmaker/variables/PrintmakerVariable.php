<?php
namespace Craft;

/**
 * PrintmakerVariable
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class PrintmakerVariable
{

	/**
	 * Template-accessible method to generate a PDF from HTML and return its PdfModel
	 *
	 * @param string $html
	 * @param array $settings
	 */
	function pdfFromHtml($html = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromHtml($html, $settings);
	}

	/**
	 * Template method to generate a PDF using HTML from a remote URL and return its PdfModel
	 *
	 * @param string $url
	 * @param array $settings
	 */
	function pdfFromUrl($url = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromUrl($url, $settings);
	}

	/**
	 * Template method to generate a PDF using HTML from a local file and return its PdfModel
	 *
	 * @param string $path
	 * @param array $settings
	 */
	function pdfFromFile($path = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromFile($path, $settings);
	}

	/**
	 * Template method to generate a PDF using a Craft template and return its PdfModel
	 *
	 * @param string $template
	 * @param mixed $vars
	 * @param array $settings
	 */
	function pdfFromTemplate($template = null, $vars = array(), $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromTemplate($template, $vars, $settings);
	}

}