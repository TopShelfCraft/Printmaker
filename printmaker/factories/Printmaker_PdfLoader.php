<?php
namespace Craft;
use Dompdf\Dompdf;


/**
 * Printmaker_PdfLoader
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class Printmaker_PdfLoader extends BaseModel
{


	// ----------- PROPERTIES -----------

	private $_saved = false;
	private $_output;
	private $_hash;

	private $_configurableSettings = array(

		// Printmaker settings

		'filename',
		'extension',
		'cachePath',
		'cacheUrl',
		'cacheDirectory',
		'devMode',

	);

	private $_defaultSettings = array();
	private $_settings = array();

	private $_devMode = false;
	private $_cachePath;
	private $_cacheUrl;

	private $_path;
	private $_filename;
	private $_url;
	private $_pdf;


	// ----------- CONSTRUCTOR -----------


	function __construct($pdf = null, $settings = array()) {

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

		// Set up the temp path and cache path/URL

		if (isset($this->_settings['cacheDirectory']))
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

		// Load the source PDF

		$this->load($pdf);

	}



	// ----------- PDF GENERATION ----------


	/**
	 * ... TODO
	 *
	 * TODO: Handle file_get_contents/file_put_contents failure
	 *
	 * @param mixed $file
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function load($file = null)
	{

		// Make sure we have a source

		if(empty($file)) {
			return false;
		}

		// Clear the output caches

		$this->_output = null;
		$this->_hash = null;
		$this->_saved = false;
		$this->_pdf = null;

		// Try to load this mofo

		if ($file instanceof Printmaker_PdfModel)
		{
			$this->_useLocalFile($file->getPath());
		}
		elseif ($file instanceof AssetFileModel && $file->kind == 'pdf')
		{
			$localSource = craft()->assetTransforms->getLocalImageSource($file);
			$this->_useLocalFile($localSource);
		}
		elseif (file_exists($file))
		{
			$this->_useLocalFile($file);
		}
		elseif (UrlHelper::isProtocolRelativeUrl($file))
		{
			$file = (craft()->request->getIsSecureConnection() ? 'https:' : 'http:') . $file;
			$this->_output = file_get_contents($file);

		}
		elseif (UrlHelper::isAbsoluteUrl($file))
		{
			$this->_output = file_get_contents($file);
		}
		else
		{
			PrintmakerPlugin::log("Unable to locate file: {$file}", LogLevel::Error);
			return false;
		}

		return true;

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
	 * @param $path
	 */
	private function _useLocalFile($path)
	{
		$this->_output = file_get_contents($path);
		$this->_path = $path;
		$this->_filename = pathinfo($path)['basename'];
		$this->_saved = true;
	}


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
	 * ... TODO
	 *
	 * @returns string
	 */
	public function getOutput()
	{
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
		elseif (!empty($this->_filename))
		{
			$filename = $this->_filename;
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
