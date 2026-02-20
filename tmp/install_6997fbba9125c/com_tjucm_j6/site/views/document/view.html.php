<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
/**
 * TjUcm Document details view
 *
 * @since  1.0.0
 */
class TjucmViewDocument extends HtmlView
{
	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * Joomla user object
	 *
	 * @var  Joomla\CMS\User\User
	 */
	protected $user;

	/**
	 * Joomla user object
	 *
	 * @var  Integer
	 */
	protected $clusterId;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		
	
		$app  = Factory::getApplication();
		$this->user = Factory::getUser();
		$this->clusterId = $app->input->get('cluster_id', '0', 'INT');
		$model = $this->getmodel();



		// Validate user login. Also validate the cluster
		if (empty($this->user->id) || empty($this->clusterId))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		$user    	   = Factory::getUser();
		$orgAdmin      = ComponentHelper::getParams('com_multiagency')->get('multiagency_school_admin_group');

		// Check document generate permission and also check the user is school admin or not
		$isDocumentGenerate = true;

	    if (!$this->user->authorise('core.manageall', 'com_cluster') && (!in_array($orgAdmin,$user->groups)))
		{
			$isDocumentGenerate = RBACL::check($this->user->id, 'com_cluster', 'document.generate', 'com_multiagency', $this->clusterId);
		}

		if (!$isDocumentGenerate)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		$model->setState("cluster_id", $this->clusterId);
		$this->item = $this->get('Item');

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}


		parent::display($tpl);
	}

	/**
	 * Download the generated document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function download()
	{
		$app             = Factory::getApplication();
		$this->user      = Factory::getUser();
		$this->clusterId = $app->input->get('cluster_id', '0', 'INT');
		$this->ucmDataID = $app->input->get('ucm_id', '0', 'INT');
		$model           = $this->getmodel();
		$params          = new Registry($this->param);
		$pageSize        = $params->get('document_page_size', 'A4');
		$orientation     = $params->get('orientation', 'landscape');
		$font            = $params->get('document_font', 'DeJaVu Sans');

		// Validate user login. Also validate the cluster
		if (empty($this->user->id) || empty($this->clusterId))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		// If the pagesize is custom then get the correct size and width.
		if ($pageSize === 'custom')
		{
			$height   = $params->get('document_pdf_width', '80') * 28.3465;
			$width    = $params->get('document_pdf_height', '80') * 28.3465;
			$pageSize = array(0, 0, $width, $height);
		}

		// If the font is custom then get the custmized font.
		if ($font === 'custom')
		{
			$font = $params->get('document_custom_font', 'DeJaVu Sans');
		}
		
		$user    	   = Factory::getUser();
		$orgAdmin      = ComponentHelper::getParams('com_multiagency')->get('multiagency_school_admin_group');

		// Check document generate permission and also check the user is school admin or not
		$isDocumentGenerate = true;

	    if (!$this->user->authorise('core.manageall', 'com_cluster') && (!in_array($orgAdmin,$user->groups)))
		{
			$isDocumentGenerate = RBACL::check($this->user->id, 'com_cluster', 'document.generate', 'com_multiagency', $this->clusterId);
		}

		if (!$isDocumentGenerate)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		$model->setState("cluster_id", $this->clusterId);

		// Set UCM ID Filter
		if ($this->ucmDataID)
		{
			$model->setState('ucm.id', $this->ucmDataID);
		}

		$this->item = $this->get('Item');

		/**
		require_once  JPATH_SITE . '/libraries/tjphpoffice/phpword/bootstrap.php';

		Settings::loadConfig();
		define('CLI', (PHP_SAPI == 'cli') ? true : false);
		define('EOL', CLI ? PHP_EOL : '<br />');
		define('SCRIPT_FILENAME', basename($_SERVER['SCRIPT_FILENAME'], '.php'));
		define('IS_INDEX', SCRIPT_FILENAME == 'index');

		Settings::loadConfig();

		$dompdfPath = $vendorDirPath . '/dompdf/dompdf';

		if (file_exists($dompdfPath))
		{
			define('DOMPDF_ENABLE_AUTOLOAD', false);
			Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, $vendorDirPath . '/dompdf/dompdf');
		}

		// Turn output escaping on
		Settings::setOutputEscapingEnabled(true);

		$documentHtml = StringHelper::str_ireplace('<br>', '<br/>', $this->loadTemplate('body'));

		$filename = File::makeSafe($this->item->title) . ".docx";
		$phpWord = new \PhpOffice\PhpWord\PhpWord;
		$phpWord->addParagraphStyle('Heading2', array('alignment' => 'center'));
		$section = $phpWord->addSection();
		\PhpOffice\PhpWord\Shared\Html::addHtml($section, $documentHtml, false);

		$phpWord->save($filename, 'Word2007', true);
		*/
		require_once JPATH_SITE . "/libraries/techjoomla/dompdf/autoload.inc.php";

		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' .
		$this->loadTemplate('body') . '</body></html>';
		

		// DPE hack to get rid of error  Call to undefined function get_magic_quotes_gpc()
		// if (get_magic_quotes_gpc())
		// {
			$html = stripslashes($html);
		// }

		// Set font for the pdf download.
		$options   = new Options;
		$options->setDefaultFont($font);
		$dompdf    = new DOMPDF($options);
		$dompdf->loadHTML($html);

		// Set the page size and oriendtation.
		$dompdf->setPaper($pageSize, $orientation);

		$dompdf->render();

		$cerficatePdfName = File::makeSafe($this->item->title) . ".pdf";

		$dompdf->stream($cerficatePdfName, array("Attachment" => 1));

		$app->close();
	}
}
