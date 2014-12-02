<?php
namespace Craft;

/**
 * Printmaker PdfModel
 */
class Printmaker_PdfModel extends BaseModel
{

	// ----------- PROPERTIES -----------

	private $_dompdf;
	private $_cachePath;
	private $_cacheUrl;

	private $_settings = array();

	private $_default_settings = array(
		'compress'		=> false,
		'orientation' 	=> 'portrait', // portrait, landscape
		'size'			=> 'letter', // letter, legal, a5
		'filename'		=> 'Printmaker.pdf',
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

		// Set paths if they're not defined already

		$cacheDirectory = trim($this->_settings['cacheDirectory'], '/') . '/';

		if (isset($this->_settings['cachePath']))
		{
			$this->_cachePath = $this->_settings['cachePath'];
		}
		else
		{
			$this->_cachePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $cacheDirectory;
		}

		if (isset($this->_settings['cacheUrl']))
		{
			$this->_cachePath = $this->_settings['cacheUrl'];
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

			$dompdf = new \DOMPDF();
			$dompdf->load_html($html);
			$dompdf->set_paper($settings['size'], $settings['orientation']);
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

				$dompdf->get_canvas()->get_cpdf()->setEncryption($settings['userPass'], $settings['ownerPass'], $permissions);

			}

			return $dompdf;

		} catch (Exception $e) {
			// TODO: Throw exception if devMode is on, otherwise return false
			throw new Exception($e->getMessage());
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
			// TODO: Throw exception if devMode is on, otherwise return false
			throw new Exception($e->getMessage());
			// return false;
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
			// TODO: Throw exception if devMode is on, otherwise return false
			throw new Exception($e->getMessage());
			// return false;
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
			// TODO: Throw exception if devMode is on, otherwise return false
			throw new Exception($e->getMessage());
			// return false;
		}

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