<?php
namespace Craft;

// TODO: Add proper file header
class PrintmakerVariable
{

	/**
	 * TODO: docs
	 * @param string $html
	 * @param array $settings
	 */
	function pdfFromHtml($html = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromHtml($html, $settings);
	}

	/**
	 * TODO: docs
	 * @param string $url
	 * @param array $settings
	 */
	function pdfFromUrl($url = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromUrl($url, $settings);
	}

	/**
	 * TODO: docs
	 * @param string $path
	 * @param array $settings
	 */
	function pdfFromFile($path = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromFile($path, $settings);
	}

	/**
	 * TODO: docs
	 * @param string $template
	 * @param mixed $vars
	 * @param array $settings
	 */
	function pdfFromTemplate($template = null, $vars = null, $settings = array())
	{
		return craft()->printmaker_pdf->pdfFromTemplate($template, $vars, $settings);
	}

	/**
	 * TODO: upgrade to Updater service
	 * @deprecated For internal/Beta purposes only
	 */
	function phoneHome()
	{
		return craft()->printmaker_beta->phoneHome();
	}

}