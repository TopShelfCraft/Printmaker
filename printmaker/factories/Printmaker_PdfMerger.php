<?php
namespace Craft;

require_once CRAFT_PLUGINS_PATH . 'printmaker/vendor/autoload.php';

use iio\libmergepdf\Merger;


/**
 * Printmaker_PdfMerger
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class Printmaker_PdfMerger extends BaseModel
{


	// ----------- PROPERTIES -----------


	private $_merger;
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
		'tempPath',
		'devMode',

	);

	private $_defaultSettings = array();
	private $_settings = array();

	private $_devMode = false;
	private $_cachePath;
	private $_cacheUrl;
	private $_tempPath;

	private $_path;
	private $_url;
	private $_pdf;


	// ----------- CONSTRUCTOR -----------


	function __construct($pdfs = array(), $settings = array()) {

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

		if (isset($this->_settings['tempPath']))
		{
			$this->_tempPath = rtrim($this->_settings['tempPath'], '/') . '/';
		}
		else
		{
			$this->_tempPath = craft()->printmaker->getTempPath() . '/';
		}

		// Instantiate the merge tool

		$this->_merger = new Merger();

		// Add any seed PDFs

		$this->add($pdfs);

	}



	// ----------- PDF GENERATION ----------


	/**
	 * Confirms type and existence of objects passed into the method and then stages them to be merged
	 *
	 * @param array $pdfs The list of PDFs to queue for concatenation
	 *
	 * @throws Exception
	 * @return $this
	 */
	public function add($pdfs = array())
	{

		if(!is_array($pdfs)) {
			$pdfs = array($pdfs);
		}

		foreach ($pdfs as $file)
		{

			// Clear the output caches
			$this->_output = null;
			$this->_hash = null;
			$this->_saved = false;
			$this->_pdf = null;

			$fileToAdd = null;
			$temp = tempnam($this->_tempPath, 'temp');

			if ($file instanceof Printmaker_PdfModel)
			{
				$fileToAdd = $file->getPath();
			}
			elseif (file_exists($file))
			{
				$fileToAdd = $file;
			}
			elseif (UrlHelper::isProtocolRelativeUrl($file))
			{
				$file = (craft()->request->getIsSecureConnection() ? 'https:' : 'http:') . $file;
				file_put_contents($temp, file_get_contents($file));
				// TODO: Handle file_get_contents/file_put_contents failure
				$fileToAdd = $temp;
			}
			elseif (UrlHelper::isAbsoluteUrl($file))
			{
				file_put_contents($temp, file_get_contents($file));
				// TODO: Handle file_get_contents/file_put_contents failure
				$fileToAdd = $temp;
			}
			else
			{
				PrintmakerPlugin::log("Unable to locate file: {$file}", LogLevel::Error);
				$fileToAdd = null;
			}

			if (!empty($fileToAdd))
			{
				try {
					$this->_merger->addFromFile($fileToAdd);
					$this->_saved = false;
				}
				catch (\Exception $e)
				{
					PrintmakerPlugin::log("Error adding file to Merger -- " . $e->getMessage(), LogLevel::Error);
					if ($this->_devMode)
					{
						throw new Exception($e->getMessage());
					}
				}
			}

		}

		return $this;

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
				PrintmakerPlugin::log("Error writing to path {$this->_cachePath} -- " . $e->getMessage(), LogLevel::Error);
				if ($this->_devMode)
				{
					throw new Exception($e->getMessage());
				}
			}

		}

	}


	// ----------- GETTERS / SETTERS -----------


	/**
	 * Provides access to the internal Merger instance
	 *
	 * @returns Merger
	 */
	public function getMerger()
	{
		return $this->_merger;
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
			$this->_output = $this->_merger->merge();
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
