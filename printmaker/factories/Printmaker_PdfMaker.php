<?php
namespace Craft;

require_once CRAFT_PLUGINS_PATH . 'printmaker/vendor/autoload.php';

use Dompdf\Dompdf;


/**
 * Printmaker_PdfMaker
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class Printmaker_PdfMaker extends BaseModel
{


	// ----------- PROPERTIES -----------


	private $_dompdf;
	private $_saved = false;
	private $_output;
	private $_hash;

	private $_configurableSettings = array(

		// Printmaker settings

		'size',
		'orientation',

		'defaultOrientation',
		'compress',
		'filename',
		'extension',
		'cachePath',
		'cacheUrl',
		'cacheDirectory',
		'encrypt',
		'userPass',
		'ownerPass',
		'canPrint',
		'canModify',
		'canCopy',
		'canAdd',
		'devMode',

		// DOMPDF system settings

		'tempDir',
		'fontDir',
		'fontCache',
		'logOutputFile',

		// DOMPDF rendering defaults

		'defaultMediaType',
		'defaultPaperSize',
		'defaultFont',
		'dpi',
		'fontHeightRatio',

		// DOMPDF parsing defaults

		'isPhpEnabled',
		'isRemoteEnabled',
		'isJavascriptEnabled',
		'isHtml5ParserEnabled',
		'isFontSubsettingEnabled',

		// DOMPDF debugging defaults

		'debugPng',
		'debugKeepTemp',
		'debugCss',
		'debugLayout',
		'debugLayoutLines',
		'debugLayoutBlocks',
		'debugLayoutInline',
		'debugLayoutPaddingBox',

	);

	private $_defaultSettings = array();
	private $_settings = array();

	private $_devMode = false;
	private $_cachePath;
	private $_cacheUrl;

	private $_path;
	private $_url;
	private $_pdf;


	// ----------- CONSTRUCTOR -----------


	function __construct($html = '', $settings = array()) {

		// Construct the Model

		parent::__construct();

		// Assemble default settings from config

		foreach ( $this->_configurableSettings as $k)
		{
			$v = craft()->config->get($k, 'printmaker');
			if (isset($v))
			{
				$this->_defaultSettings[$k] = $v;
			}
		}

		// Assemble instance settings from defaults, merging in any overrides

		if (is_array($settings))
		{
			$this->_settings = array_merge($this->_defaultSettings, $settings);
		}
		else
		{
			$this->_settings = $this->_defaultSettings;
		}

		// See if we're in devMode...

		if ( craft()->config->get('devMode') || (isset($this->_settings['devMode']) && $this->_settings['devMode']) )
		{
			$this->_devMode = true;
		}

		// Set up the full cache path/URL

		if (!empty($this->_settings['cacheDirectory']))
		{
			$cacheDirectory = trim($this->_settings['cacheDirectory'], '/') . '/';
		}
		else
		{
			$cacheDirectory = '';
		}

		if (isset($this->_settings['cachePath']))
		{
			$this->_cachePath = rtrim($this->_settings['cachePath'], '/') . '/' . $cacheDirectory;
		}
		else
		{
			$this->_cachePath = craft()->printmaker->getCachePath() . '/' . $cacheDirectory;
		}

		if (isset($this->_settings['cacheUrl']))
		{
			$this->_cacheUrl = rtrim($this->_settings['cacheUrl'], '/') . '/' . $cacheDirectory;
		}
		else
		{
			$this->_cacheUrl = craft()->printmaker->getCacheUrl() . '/' . $cacheDirectory;
		}

		// Generate the PDF

		$this->_dompdf = $this->generatePdf($html, $this->_settings);

	}



	// ----------- PDF GENERATION ----------


	/**
	 * Sets up a new DOMPDF instance with the provided content and settings.
	 *
	 * @param string $html The HTML from which to generate the PDF
	 * @param array $settings An array of DOMPDF settings
	 *
	 * @throws Exception
	 * @return Dompdf
	 */
	public function generatePdf($html = '', $settings = array()) {

		// Clear the output caches
		$this->_output = null;
		$this->_hash = null;
		$this->_saved = false;
		$this->_pdf = null;

		try {

			$dompdf = new Dompdf($settings);
			$dompdf->loadHtml($html);

			$size = !empty($settings['size']) ? $settings['size'] : $settings['defaultPaperSize'];
			$orientation = !empty($settings['orientation']) ? $settings['orientation'] : $settings['defaultOrientation'];
			$dompdf->setPaper($size, $orientation);

			$dompdf->render();

			if($settings['encrypt']) {

				$permissions = array();

				if($settings['canPrint'])
					$permissions[] = 'print';

				if($settings['canModify'])
					$permissions[] = 'modify';

				if($settings['canCopy'])
					$permissions[] = 'copy';

				if($settings['canAdd'])
					$permissions[] = 'add';

				$dompdf->getCanvas()->get_cpdf()->setEncryption($settings['userPass'], $settings['ownerPass'], $permissions);

			}

			return $dompdf;

		} catch (\Exception $e) {

			PrintmakerPlugin::log("Error generating PDF", LogLevel::Error);
			PrintmakerPlugin::log($e->getMessage(), LogLevel::Error);
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			return null;

		}

	}


	/**
	 * Saves the PDF and returns the full disk path of the cached file
	 *
	 * @return string The full path to the generated PDF on the filesystem
	 */
	public function getPath()
	{
		$this->_savePdf();
		return $this->_path;
	}


	/**
	 * Saves the PDF and returns the URL of the cached file
	 *
	 * @return string The URL of the generated PDF
	 */
	public function getUrl()
	{
		$this->_savePdf();
		return $this->_url;
	}


	/**
	 * ... TODO
	 *
	 * @return Printmaker_PdfModel
	 */
	public function getPdf()
	{
		if (!isset($this->_pdf))
		{
			$this->_savePdf();
			$props = array(
				'path' => $this->getPath(),
				'url' => $this->getUrl(),
				'hash' => $this->getHash(),
			);
			$this->_pdf = new Printmaker_PdfModel($props);
		}
		return $this->_pdf;
	}


	// ----------- PRIVATE METHODS --------------


	/**
	 * Generates the PDF and caches the file to disk
	 *
	 * @throws Exception
	 */
	private function _savePdf()
	{

		if (!$this->_saved)
		{

			try {

				IOHelper::ensureFolderExists($this->_cachePath, !$this->_devMode);
				$filePath = $this->_cachePath . $this->getFilename();

				IOHelper::writeToFile($filePath, $this->getOutput());
				$this->_path = $filePath;
				$this->_url = $this->_cacheUrl . $this->getFilename();

				$this->_saved = true;

			}
			catch (\Exception $e)
			{
				PrintmakerPlugin::log("Error writing to path: {$this->_cachePath}", LogLevel::Error);
				PrintmakerPlugin::log($e->getMessage(), LogLevel::Error);
				if ($this->_devMode)
				{
					throw new Exception($e->getMessage());
				}
			}

		}

	}


	// ----------- GETTERS / SETTERS -----------


	/**
	 * Provides access to the internal DOMPDF instance
	 *
	 * @returns Dompdf
	 */
	public function getDompdf()
	{
		return $this->_dompdf;
	}


	/**
	 * ... TODO
	 *
	 * @returns string
	 */
	public function getOutput()
	{
		if (!isset($this->_output))
		{
			$this->_output = $this->_dompdf->output();
		}
		return $this->_output;
	}


	/**
	 * ... TODO
	 *
	 * @returns string
	 */
	public function getHash()
	{
		if (!isset($this->_hash))
		{
			$this->_hash = md5($this->getOutput());
		}
		return $this->_hash;
	}


	/**
	 * Returns the full filename with extension
	 *
	 * @return string
	 */
	public function getFilename()
	{

		if (!empty($this->_settings['filename']))
		{
			$filename = $this->_settings['filename'] . '.' . $this->_settings['extension'];
		}
		else
		{
			$filename = $this->getHash() . '.' . $this->_settings['extension'];
		}

		return $filename;

	}


	/**
	 * Returns a copy of the active settings
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return $this->_settings;
	}


}
