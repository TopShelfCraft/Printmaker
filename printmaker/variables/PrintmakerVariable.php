<?php
namespace Craft;


/**
 * PrintmakerVariable
 *
 * @author    Top Shelf Craft <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class PrintmakerVariable
{


	/**
	 * Template method to generate a PDF from HTML
	 *
	 * @param string $html
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	function createFromHtml($html = null, $settings = array())
	{
		return craft()->printmaker->createFromHtml($html, $settings);
	}


	/**
	 * Alias to createFromHtml()
	 * @deprecated
	 *
	 * @param null $html
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	function pdfFromHtml($html = null, $settings = array())
	{
		return $this->createFromHtml($html, $settings);
	}


	/**
	 * Template method to generate a PDF using a Craft template
	 *
	 * @param string $template
	 * @param mixed $vars
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	function createFromTemplate($template = null, $vars = array(), $settings = array())
	{
		return craft()->printmaker->createFromTemplate($template, $vars, $settings);
	}


	/**
	 * Alias to createFromTemplate()
	 * @deprecated
	 *
	 * @param null $template
	 * @param array $vars
	 * @param array $settings
	 *
	 * @return Printmaker_PdfModel
	 */
	function pdfFromTemplate($template = null, $vars = array(), $settings = array())
	{
		return $this->createFromTemplate($template, $vars, $settings);
	}


	/**
	 * Template method to generate a PDF by merging several PDF sources
	 *
	 * @param array $pdfs
	 *
	 * @return Printmaker_PdfModel
	 */
	function merge($pdfs = array())
	{
		return craft()->printmaker->merge($pdfs);

	}


	/**
	 * Template method to import a PDF for further processing
	 *
	 * @param array $pdfs
	 *
	 * @return Printmaker_PdfModel
	 */
	function load($pdfs = array())
	{
		return craft()->printmaker->load($pdfs);

	}


	/**
	 * TODO: upgrade to Updater service
	 * @deprecated For internal use only
	 */
	function phoneHome()
	{
		return craft()->printmaker_beta->phoneHome();
	}


}
