<?php
/**
 * @package    Com_Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.


defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Document controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmControllerDocument extends TjucmController
{
	/**
	 * Method to download the document
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function download()
	{
		$app         = Factory::getApplication();
		$document    = Factory::getDocument();
		$viewType    = $document->getType();
		$viewName    = $this->input->get('view', 'document');
		$viewLayout  = $this->input->get('layout', 'default', 'string');
		$id          = $this->input->get('id', 0, 'INT');

		if (!$id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		/** @var $view TjucmViewDocument */
		$view        = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

		/** @var $model TjucmModelDocument */
		$model       = $this->getModel($viewName);
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$documentTemplate       = $model->getTable();

		// Get the params for the current Document template.
		$documentTemplate->load(array('id' => (int) $id));
		$view->param = isset($documentTemplate->params) ? $documentTemplate->params : '';

		$view->setModel($model, true);
		$view->document = $document;
		$view->download();
	}
}
