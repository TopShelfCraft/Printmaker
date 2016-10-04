<?php
namespace Craft;


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

	private $_devMode = false;

	private $_url;
	private $_path;
	private $_hash;


	// ----------- CRAFT MODEL METHODS -----------

	function __construct($props) {

		// Construct the Model

		parent::__construct();

		// See if we're in devMode...

		if ( craft()->config->get('devMode') || (isset($props['devMode']) && $props['devMode']) )
		{
			$this->_devMode = true;
		}

		// Set any props we're given

		if (isset($props['path'])) $this->_path = $props['path'];
		if (isset($props['url'])) $this->_url = $props['url'];
		if (isset($props['hash'])) $this->_hash = $props['hash'];

		// TODO: Validate that we do have a file on disk.

	}


	/**
	 * ... TODO
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


	// ----------- OUTPUT METHODS ----------


	/**
	 * Downloads the merged PDF to the browser as a download
	 */
	public function download($filename = null)
	{

		HeaderHelper::setContentTypeByExtension('pdf');
		if (isset($filename))
		{
			HeaderHelper::setDownload($filename);
		}
		else
		{
			HeaderHelper::setDownload($this->getFilename());
		}

		readfile($this->getPath());
		craft()->end();

	}


	/**
	 * Streams the merged PDF to the browser
	 */
	public function stream()
	{
		HeaderHelper::setContentTypeByExtension('pdf');
		readfile($this->getPath());
		craft()->end();
	}

	/**
	 * Alias to stream()
	 * @deprecated
	 */
	public function output()
	{
		$this->stream();
	}


	/**
	 * ... TODO
	 *
	 * @return string The full path to the PDF file
	 */
	public function getPath()
	{
		return $this->_path;
	}


	/**
	 * ... TODO
	 *
	 * @return string The URL of the PDF file
	 */
	public function getUrl()
	{
		return $this->_url;
	}


	/**
	 * Returns the full filename with extension
	 *
	 * @param bool $includeExtension
	 *
	 * @return string
	 */
	public function getFilename($includeExtension = true)
	{

		$parts = pathinfo($this->getPath());

		if ($includeExtension)
		{
			if (isset($parts['basename']))
			{
				return $parts['basename'];
			}
		}
		else
		{
			if (isset($parts['filename']))
			{
				return $parts['filename'];
			}
		}

		return null;

	}


	/**
	 * ... TODO
	 *
	 * @param $filename string Overrides the name of the file as it is attached to the email
	 * @param $attributes array Attributes to set on the EmailModel
	 * @param $variables array The variables that will be available to to the email template (in addition to `fileUrl`)
	 *
	 * @throws Exception
	 * @returns void|false
	 */
	public function email($filename = null, $attributes = array(), $variables = array())
	{

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

		if (empty($filename) || !is_string($filename))
		{
			$filename = $this->getFilename();
		}

		$email->addAttachment($this->getPath(), $filename, 'base64', 'application/pdf');

		// Render and send email

		if (!is_array($variables))
		{
			$variables = array();
		}
		$variables['pdfUrl'] = $this->getUrl();

		return craft()->email->sendEmail($email, $variables);

	}


	/**
	 * ... TODO
	 *
	 * @param $transform The desired width, the handle of an Asset Transform, or an array of Transform properties
	 * @param int $page The page number of the PDF from which to generate the image
	 * @param int $resolution The resolution (in DPI) of the generated image
	 *
	 * @throws Exception
	 * @returns array
	 */
	public function image($transform = null, $page = 1, $resolution = 72)
	{

		// Coerce the page param

		$page = intval($page) - 1;
		if (!$this->_devMode) { $page = max($page, 0); }

		// Coerce the resolution param

		$resolution = intval($resolution);
		if (!$this->_devMode) { $page = max($page, 1); }

		// Clean up the image cache paths

		if (!empty($imageCacheDirectory = craft()->config->get('imageCacheDirectory', 'printmaker')))
		{
			$imageCacheDirectory = trim($imageCacheDirectory, '/') . '/';
		}
		else
		{
			$imageCacheDirectory = '';
		}

		$imageCachePath = craft()->printmaker->getImageCachePath() . '/' . $imageCacheDirectory;
		$imageCacheUrl = craft()->printmaker->getImageCacheUrl() . '/' . $imageCacheDirectory;

		// Normalize transform properties as needed

		if ($transform)
		{

			if (is_numeric($transform))
			{
				$width = round($transform);
				$transform = array('width' => $width);
				$transform = craft()->assetTransforms->normalizeTransform($transform);
			}
			else
			{
				$transform = craft()->assetTransforms->normalizeTransform($transform);
			}

		}

		// Detect the format

		if ($transform instanceof AssetTransformModel && !empty($transform->format))
		{
			$format = $transform->format;
		}
		else
		{
			$format = craft()->config->get('imageFormat', 'printmaker');
		}

		// Generate the image

		$im = new \Imagick();
		$im->setResolution($resolution, $resolution);
		$im->readImage($this->getPath().'['.$page.']');
		$im->setImageFormat($format);

		// Write the image file

		$imagePath = $imageCachePath . $this->getFilename(false) . '.' . $format;
		$imageUrl = $imageCacheUrl . $this->getFilename(false) . '.' . $format;

		try {
			IOHelper::writeToFile($imagePath, $im);
		}
		catch (Exception $e)
		{
			PrintmakerPlugin::log($e->getMessage(), LogLevel::Error);
			if ($this->_devMode)
			{
				throw new Exception($e->getMessage());
			}
			return null;
		}

		// Load up an Image object

		$image = new Image();
		$image->loadImage($imagePath);

		$imageWidth = $image->getWidth();
		$imageHeight = $image->getHeight();

		// Transform and re-save the Image if necessary

		if ($transform instanceof AssetTransformModel)
		{

			$quality = $transform->quality ? $transform->quality : craft()->config->get('defaultImageQuality');
			if ($image instanceof Image)
			{
				$image->setQuality($quality);
			}

			switch ($transform->mode)
			{
				case 'fit':
				{
					$image->scaleToFit($transform->width, $transform->height);
					break;
				}

				case 'stretch':
				{
					$image->resize($transform->width, $transform->height);
					break;
				}

				default:
				{
					$image->scaleAndCrop($transform->width, $transform->height, true, $transform->position);
					break;
				}
			}

			$imageWidth = $image->getWidth();
			$imageHeight = $image->getHeight();

			$transformName = $transform->isNamedTransform() ? $transform->handle : $imageWidth . '-' . $imageHeight . '-' . $transform->position;
			$imagePath = $imageCachePath . $this->getFilename(false) . '-' . $transformName . '.' . $format;
			$imageUrl = $imageCacheUrl . $this->getFilename(false) . '-' . $transformName . '.' . $format;

			$image->saveAs($imagePath);

		}

		// Return an info blob for the final image

		return array(
			'url' => $imageUrl,
			'path' => $imagePath,
			'width' => $imageWidth,
			'height' => $imageHeight,
		);

	}


}
