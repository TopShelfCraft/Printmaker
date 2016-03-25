<?php
namespace Craft;

/**
 * ==============================================
 * DOMPDF library, etc.
 * @see http://dompdf.github.io/
 */
require_once CRAFT_PLUGINS_PATH . 'printmaker/vendor/autoload.php';

use Dompdf\Dompdf;


/**
 * Printmaker_PdfModel
 *
 * @author    Top Shelf Craft <michael@michaelrog.com>
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
	private $_cachePath;
	private $_cacheUrl;

	private $_settings = array();
	private $_devMode = false;

	private $_default_settings = array(
		'compress'		=> false,
		'orientation' 	=> 'portrait', // portrait, landscape
		'size'			=> 'letter', // letter, legal, a5
		'filename'		=> 'Printmaker',
		'cacheDirectory' => 'Printmaker/',
		'encrypt'		=> false,
		'userPass'		=> '',
		'ownerPass'		=> '',
		'canPrint'		=> true,
		'canModify'     => true,
		'canCopy'		=> true,
		'canAdd'		=> true,
	);


	// ----------- CRAFT DEFAULT MODEL METHODS -----------

	function __construct($html = '', $settings = array()) {

		// Construct the Model

		parent::__construct();

		// Whip up the settings array

		if (is_array($settings))
		{
			$this->_settings = array_merge($this->_default_settings, $settings);
		}
		else
		{
			$this->_settings = $this->_default_settings;
		}

		// See if we're in devMode...

		$this->_devMode = craft()->config->get('devMode');

		// Set paths if they're not defined already

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
			$this->_cachePath = trim($this->_settings['cacheUrl'], '/') . '/';
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

			$dompdf = new Dompdf();
			$dompdf->loadHtml($html);
			$dompdf->setPaper($settings['size'], $settings['orientation']);
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
		$filePath = $this->_cachePath . $this->_settings['filename'];
		$fileUrl = $this->_cacheUrl . $this->_settings['filename'];


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
	 * @throws Exception
	 * @returns void|false
	 */
	public function email($filename = false, $attributes = array(), $variables = array())
	{

		// Generate the PDF, and get its URL/path

		IOHelper::ensureFolderExists($this->_cachePath);
		$filePath = $this->_cachePath . $this->_settings['filename'];
		$fileUrl = $this->_cacheUrl . $this->_settings['filename'];

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