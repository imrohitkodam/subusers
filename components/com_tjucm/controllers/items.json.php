<?php
/**
 * @package     Tjucm
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();
use Joomla\CMS\Filter\InputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * JLike extended todos Controller
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmControllerItems extends AdminController
{
	/**
	 * This function display the default assignment list view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \InputFilter::clean()}.
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$app  = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$data = $app->input->getArray();	
		

		// Contain combination of Field id and field value For e.g 209_cctv
		$fieldData = explode("_", $data['field_data']);
		$requestStatusFieldValue = $data['request_status_field_value'];
		$requestStatusFieldId    = $data['request_status_field_id'];

		$processFieldValue       = $data['exisitng_process_field_value'];
		$processStatusFieldId    = $data['exisitng_process__field_id'];

		$document   = Factory::getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
		$listLimit  = Factory::getConfig()->get('list_limit', 20);

		$inputFilter = InputFilter::getInstance();
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModel->setState("filter.client", $data['client']);
		$tjfieldsModel->setState("filter.filterable", 1);
		$fields = $tjfieldsModel->getItems();

		$model       = $this->getModel($viewName, '', array("ignore_request" => true));

		$model->setState("ucm.client", $data['client']);
		$model->setState("ucmType.id", (int) $data['typeId']);
		$model->setState("filter.process", $data['filter']['process']);

		$model->setState("list.ordering", $data['filter_order']);
		$model->setState("list.direction", $data['filter_order_Dir']);

		// DPE HACK CAN GO IN CORE
		if (!empty($data['tags']))
		{	
			$model->setState('filter.tags', $data['tags']);
		}
		else
		{
			$model->setState('filter.tags', '');
		}
		
		
		if (isset($data['filter']))
		{
			foreach ($data['filter'] as $key => $value)
			{
				$model->setState($data['client'] . '.filter.' . $key, $value);
			}
		}

		$model->setState('list.start', $inputFilter->clean(isset($data['limitstart']) ? $data['limitstart'] : 0, 'int'));

		foreach ($fields as $field)
		{
			if ((!empty($fieldData) && count($fieldData) >= 2) && ((int) $field->id == $fieldData[0]))
			{
				if ($fieldData[1] == 'other-options')
				{
					$model->setState('filter.field.' . $field->name . '.optionId', 'other');
				}
				else
				{
					$model->setState('filter.field.' . $field->name, trim($fieldData[1]));
				}
			}

			// Set Request Status Filter
			if (((int) $field->id == $requestStatusFieldId) && !empty($requestStatusFieldValue))
			{
				$model->setState('filter.field.' . $field->name, trim($requestStatusFieldValue));
			}

			// Set Process Status Filter
			if (((int) $field->id == $processStatusFieldId) && !empty($processFieldValue))
			{

				$model->setState('filter.field.' . $field->name, trim($processFieldValue));
			}
		}

		$model->setState("list.start", 0);
		$model->setState("list.limit", $listLimit);
		$view->setModel($model, true);
		$view->document = $document;
		$view->display();
		$app->close();
	}

	/**
	 * This function loads the items based on the filters
	 *
	 * @return  string   html to build a item list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function loadMore()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$data       = $app->input->getArray();
		$fieldData = explode("_", $data['field_data']);
		$requestStatusFieldValue = $data['request_status_field_value'];
		$requestStatusFieldId    = $data['request_status_field_id'];
		$processFieldValue       = $data['exisitng_process_field_value'];
		$processStatusFieldId    = $data['exisitng_process__field_id'];

		$user       = Factory::getUser();

		$document    = Factory::getDocument();
		$viewType    = $document->getType();
		$viewName    = $this->input->get('view', $this->default_view);
		$viewLayout  = $this->input->get('layout', 'default', 'string');

		/** @var $view TjucmViewItems */
		$view        = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
		$inputFilter = InputFilter::getInstance();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModel->setState("filter.client", $data['client']);
		$tjfieldsModel->setState("filter.filterable", 1);
		$fields = $tjfieldsModel->getItems();

		/** @var $model TjucmModelItems */
		$model       = $this->getModel($viewName, '', array("ignore_request" => true));
		$model->setState("ucm.client", $data['client']);
		$model->setState("ucmType.id", (int) $data['typeId']);
		$model->setState("list.ordering", $data['filter_order']);
		$model->setState("list.direction", $data['filter_order_Dir']);
		$model->setState("filter.process", $data['filter']['process']);

		// DPE HACK CAN GO IN CORE
		$model->setState('filter.tags', $data['tags']);

		if (isset($data['filter']))
		{
			foreach ($data['filter'] as $key => $value)
			{
				if (!empty($value))
				{
					$model->setState($data['client'] . '.filter.' . $key, trim($value));
				}
			}
		}

		foreach ($fields as $field)
		{
			if ((!empty($fieldData) && count($fieldData) >= 2) && ((int) $field->id == $fieldData[0]))
			{
				if ($fieldData[1] == 'other-options')
				{
					$model->setState('filter.field.' . $field->name . '.optionId', 'other');
				}
				else
				{
					$model->setState('filter.field.' . $field->name, trim($fieldData[1]));
				}
			}

			// Set Request Status Filter
			if (((int) $field->id == $requestStatusFieldId) && !empty($requestStatusFieldValue))
			{
				$model->setState('filter.field.' . $field->name, trim($requestStatusFieldValue));
			}

			// Set Process Status Filter
			if (((int) $field->id == $processStatusFieldId) && !empty($processFieldValue))
			{

				$model->setState('filter.field.' . $field->name, trim($processFieldValue));
			}
		}

		if (isset($data['limitstart']))
		{
			$model->setState('list.start', $inputFilter->clean($data['limitstart'], 'int'));
		}

		if (isset($data['limit']))
		{
			$model->setState('list.limit', $inputFilter->clean((int) $data['limit'], 'int'));
		}

		$view->setModel($model, true);
		$view->document = $document;
		$view->loadMore();
		$app->close();
	}

	/**
	 * This function display the default assignment list view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \InputFilter::clean()}.
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function displayCoreData($cachable = false, $urlparams = array())
	{
		$app  = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$data = $app->input->getArray();

		// Contain combination of Field id and field value For e.g 209_cctv
		$fieldData = explode("_", $data['field_data']);
		$customeFieldValue = $data['customeFieldValue'];
		$customeFieldId    = $data['customeFieldId'];

		$document   = Factory::getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
		$listLimit  = Factory::getConfig()->get('list_limit', 20);

		$inputFilter = InputFilter::getInstance();
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModel->setState("filter.client", $data['client']);
		$tjfieldsModel->setState("filter.filterable", 1);
		$fields = $tjfieldsModel->getItems();

		// Generic filter
		$app->input->set('filter_process', 'generic');
		$model       = $this->getModel($viewName, '', array("ignore_request" => true));

		$model->setState("ucm.client", $data['client']);
		$model->setState("ucmType.id", (int) $data['typeId']);
		$model->setState("filter.process", $data['filter']['process']);
		$model->setState("filter.published", 1);

		$model->setState("list.ordering", 'a.id');
		$model->setState("list.direction", 'DESC');

		if (isset($data['filter']))
		{
			foreach ($data['filter'] as $key => $value)
			{
				$model->setState($data['client'] . '.filter.' . $key, $value);
			}
		}

		// Pagination commented $model->setState('list.start', $inputFilter->clean(isset($data['limitstart']) ? $data['limitstart'] : 0, 'int'));

		foreach ($fields as $field)
		{
			if ((!empty($fieldData) && count($fieldData) >= 2) && ((int) $field->id == $fieldData[0]))
			{
				if ($fieldData[1] == 'other-options')
				{
					$model->setState('filter.field.' . $field->name . '.optionId', 'other');
				}
				else
				{
					$model->setState('filter.field.' . $field->name, trim($fieldData[1]));
				}
			}

			// Set Request Status Filter
			if (((int) $field->id == $customeFieldId) && !empty($customeFieldValue))
			{
				$model->setState('filter.field.' . $field->name, trim($customeFieldValue));
			}
		}

		$model->setState("list.limit", 0);


		// Pagination commented  $model->setState("list.start", 0);

		// Pagination commented  $model->setState("list.limit", $listLimit);
		$view->setModel($model, true);
		$view->document = $document;
		$view->displayCoreData();
		$app->close();
	}

	/**
	 * This function loads the items based on the filters
	 *
	 * @return  string   html to build a item list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function loadMoreCoreData()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		// Get Post details i.e here Filter details
		$data              = $app->input->getArray();
		$fieldData         = explode("_", $data['field_data']);
		$customeFieldValue = $data['customeFieldValue'];
		$customeFieldId    = $data['customeFieldId'];

		// Get Layout details
		$user        = Factory::getUser();
		$document    = Factory::getDocument();
		$viewType    = $document->getType();
		$viewName    = $this->input->get('view', $this->default_view);
		$viewLayout  = $this->input->get('layout', 'default', 'string');

		/** @var $view TjucmViewItems */
		$view        = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));
		$inputFilter = InputFilter::getInstance();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModel->setState("filter.client", $data['client']);
		$tjfieldsModel->setState("filter.filterable", 1);
		$fields = $tjfieldsModel->getItems();

		/** @var $model TjucmModelItems */
		$model       = $this->getModel($viewName, '', array("ignore_request" => true));
		$model->setState("ucm.client", $data['client']);
		$model->setState("ucmType.id", (int) $data['typeId']);
		$model->setState("filter.published", 1);
		$model->setState("list.ordering", $data['filter_order']);
		$model->setState("list.direction", $data['filter_order_Dir']);
		$model->setState("filter.process", $data['filter']['process']);

		if (isset($data['filter']))
		{
			foreach ($data['filter'] as $key => $value)
			{
				if (!empty($value))
				{
					$model->setState($data['client'] . '.filter.' . $key, trim($value));
				}
			}
		}

		foreach ($fields as $field)
		{
			if ((!empty($fieldData) && count($fieldData) >= 2) && ((int) $field->id == $fieldData[0]))
			{
				if ($fieldData[1] == 'other-options')
				{
					$model->setState('filter.field.' . $field->name . '.optionId', 'other');
				}
				else
				{
					$model->setState('filter.field.' . $field->name, trim($fieldData[1]));
				}
			}

			// Set Request Status Filter
			if (((int) $field->id == $customeFieldId) && !empty($customeFieldValue))
			{
				$model->setState('filter.field.' . $field->name, trim($customeFieldValue));
			}
		}

		if (isset($data['limitstart']))
		{
			// Pagination commented $model->setState('list.start', $inputFilter->clean($data['limitstart'], 'int'));
		}

		if (isset($data['limit']))
		{
			// Pagination commented  $model->setState('list.limit', $inputFilter->clean((int) $data['limit'], 'int'));
		}

		$model->setState('list.limit', 0);

		$view->setModel($model, true);
		$view->document = $document;
		$view->loadMoreCoreData();
		$app->close();
	}
}
