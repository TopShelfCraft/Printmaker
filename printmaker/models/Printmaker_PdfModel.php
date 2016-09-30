<?php
namespace Craft;

/**
 * ==============================================
 * DOMPDF library, etc.
 * @see http://dompdf.github.io/
 */
require_once CRAFT_PLUGINS_PATH . 'printmaker/vendor/autoload.php';

use Dompdf\Dompdf;
use Imagine\Imagick\Imagick;


/**
 * Printmaker_PdfModel
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class Printmaker_PdfModel extends BaseModel
{

	// ----------- PROPERTIES -----------

	private $_dompdf;

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


	// ----------- CRAFT DEFAULT MODEL METHODS -----------

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

		$this->_devMode = craft()->config->get('devMode') || (!empty($this->_settings['devMode']));

		// Set up the full cachePath and cacheUrl if they're not defined already (i.e. if only the cacheDirectory is defined)

		$cacheDirectory = trim($this->_settings['cacheDirectory'], '/') . '/';

		if (isset($this->_settings['cachePath']))
		{
			$this->_cachePath = trim($this->_settings['cachePath'], '/') . '/';
		}
		else
		{
			$this->_cachePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $cacheDirectory;
		}

		if (isset($this->_settings['cacheUrl']))
		{
			$this->_cacheUrl = trim($this->_settings['cacheUrl'], '/') . '/';
		}
		else
		{
			$this->_cacheUrl = rtrim(UrlHelper::getSiteUrl(), '/') . '/' . $cacheDirectory;
		}

		// Generate the PDF

		$this->_dompdf = $this->generatePdf($html, $this->_settings);

	}


	// ----------- CRAFT DEFAULT MODEL METHODS -----------

	/**
	 * Return the URL of the forged image, or the URL of the first in the list of forged images
	 *
	 * @return string
	 */
	function __toString()
	{
		return '';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array();
	}


	// ----------- PDF GENERATION ----------

	/**
	 * Generates a new PrintmakerPdfModel
	 *
	 * @param string $html
	 * @param array $settings
	 *
	 * @throws Exception
	 * @return DOMPDF|false
	 */
	public function generatePdf($html = '', $settings = array()) {

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

		} catch (Exception $e) {
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

	}


	/**
	 * Streams the generated PDF to the browser as output
	 *
	 * @throws Exception
	 * @returns void|false
	 */
	public function output()
	{

		try
		{

			$options = array(
				'Attachment' => false,
				'compress' => $this->_settings['compress']
			);

			$this->_dompdf->stream($this->_settings['filename'], $options);
			exit;

		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

	}


	/**
	 * Streams the generated PDF to the browser as an attachment for download
	 *
	 * @throws Exception
	 * @returns void|false
	 */
	public function download()
	{

		try {

			$options = array(
				'Attachment' => true,
				'compress' => $this->_settings['compress']
			);

			$this->_dompdf->stream($this->_settings['filename'], $options);
			exit;

		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

	}


	/**
	 * Saves the generated PDF to the cache directory and returns the new file's URL
	 *
	 * @throws Exception
	 * @returns void|false
	 */
	public function url()
	{

		IOHelper::ensureFolderExists($this->_cachePath);
		$fileExtension = '.' . $this->_settings['extension'];
		$filePath = $this->_cachePath . $this->_settings['filename'] . $fileExtension;
		$fileUrl = $this->_cacheUrl . $this->_settings['filename'] . $fileExtension;

		try {
			IOHelper::writeToFile($filePath, $this->_dompdf->output());
			return $fileUrl;
		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

	}


	/**
	 * Saves the generated PDF to the cache directory and returns the new file's URL
	 *
	 * @param $filename string Overrides the name of the file as it is attached to the email
	 * @param $attributes array Attributes to set on the EmailModel
	 * @param $variables array The variables that will be available to to the email template (in addition to `fileUrl`)
	 *
	 * @throws Exception
	 * @returns void|false
	 */
	public function email($filename = '', $attributes = array(), $variables = array())
	{

		// Generate the PDF, and get its URL/path

		IOHelper::ensureFolderExists($this->_cachePath);
		$fileExtension = '.' . $this->_settings['extension'];
		$filePath = $this->_cachePath . $this->_settings['filename'] . $fileExtension;
		$fileUrl = $this->_cacheUrl . $this->_settings['filename'] . $fileExtension;

		try {
			IOHelper::writeToFile($filePath, $this->_dompdf->output());
		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

		// Set up email

		$email = new EmailModel();

		if (is_array($attributes))
		{
			$email->setAttributes($attributes);
		}

		if (empty($email->toEmail))
		{
			$settings = craft()->email->getSettings();
			$email->toEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
		}
		if (empty($email->subject))
		{
			$email->subject = "";
		}
		if (empty($email->body))
		{
			$email->body = "";
		}

		if (!is_string($filename) || empty($filename))
		{
			$filename = $this->_settings['filename'];
		}

		$email->addAttachment($filePath, $filename, 'base64', 'application/pdf');

		// Render and send email

		if (!is_array($variables))
		{
			$variables = array();
		}
		$variables['pdfUrl'] = $fileUrl;

		return craft()->email->sendEmail($email, $variables);

	}


	/**
	 * Saves the generated PDF and generates a thumbnail image of it
	 *
	 * @param $width The desired width, or null to use native size
	 * @param $height The desired height, or null to keep aspect ratio
	 * @param format The desired image format ('jpg' or 'png')
	 *
	 * @throws Exception
	 * @returns array
	 */
	public function image($width = null, $height = null, $format = 'jpg')
	{

		// Generate the PDF, and get its URL/path

		IOHelper::ensureFolderExists($this->_cachePath);
		$fileExtension = '.' . $this->_settings['extension'];
		$filePath = $this->_cachePath . $this->_settings['filename'] . $fileExtension;
		$fileUrl = $this->_cacheUrl . $this->_settings['filename'] . $fileExtension;

		try {
			IOHelper::writeToFile($filePath, $this->_dompdf->output());
		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

		// Generate the thumbnail

		$im = new Imagick();
		$im->readImage($filePath.'[0]');
		$im->setImageFormat($format);

		// Write the image file

		$imagePath = $this->_cachePath . $this->_settings['filename'] . '.' . $format;
		$imageUrl = $this->_cacheUrl . $this->_settings['filename'] . '.' . $format;

		try {
			IOHelper::writeToFile($imagePath, $im);
		}
		catch (Exception $e)
		{
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			else
			{
				return false;
			}
		}

		$image = new Image();
		$image->loadImage($imagePath);

		$imageWidth = $image->getWidth();
		$imageHeight = $image->getHeight();

		if ($width)
		{

			$image->scaleAndCrop($width, $height);

			$imageWidth = $image->getWidth();
			$imageHeight = $image->getHeight();

			$imagePath = $this->_cachePath . $this->_settings['filename'] . "-{$imageWidth}x{$imageHeight}". '.' . $format;
			$imageUrl = $this->_cacheUrl . $this->_settings['filename'] . "-{$imageWidth}x{$imageHeight}". '.' . $format;

			$image->saveAs($imagePath);

		}

		return array(
			'url' => $imageUrl,
			'path' => $imagePath,
			'width' => $imageWidth,
			'height' => $imageHeight,
		);

	}


	// ----------- GETTERS / SETTERS -----------

	/**
	 * Returns the DOMPDF instance for this PdfModel
	 *
	 * @returns DOMPDF
	 */
	public function getDompdf()
	{
		return $this->_dompdf;
	}


} // Printmaker_PdfModel
