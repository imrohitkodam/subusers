<?php
/**
 * @package     Tjucm
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Response\JsonResponse;

/**
 * View  Json class for a list of Tjucm.
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmViewItems extends BaseHtmlView
{
	/**
	 * The pagination object
	 *
	 * @var  Pagination
	 */
	public $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	public $state;

	/**
	 * Form object for search filters
	 *
	 * @var  Joomla\CMS\Form\Form
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app                 = Factory::getApplication();
		$user                = Factory::getUser();

		// Check the view access to the items.
		if (!$user->id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->params       = $app->getParams('com_tjucm');
		$this->listcolumn   = $this->get('Fields');
		$this->allowedToAdd = false;
		$model              = $this->getModel("Items");
		$this->ucmTypeId    = $id = $model->getState('ucmType.id');
		$this->client       = $model->getState('ucm.client');
		$this->canCreate    = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canView      = $user->authorise('core.type.viewitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEdit      = $user->authorise('core.type.edititem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canChange    = $user->authorise('core.type.edititemstate', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEditOwn   = $user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDelete    = $user->authorise('core.type.deleteitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDeleteOwn = $user->authorise('core.type.deleteownitem', 'com_tjucm.type.' . $this->ucmTypeId);

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
		$ropbusinessFieldData = $tjfieldsModelFields->getItems();

		if (!empty($ropbusinessFieldData))
		{
			$businessFunctionFieldId = $this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;
		}

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'cluster-ownership');
		$ropSchoolData = $tjfieldsModelFields->getItems();

		$this->ropSchoolId = 0;

		if ($this->state->get('filter.process') != 'myprocess')
		{
			$this->ropSchoolId = $ropSchoolData[0]->id;
		}

		// If did not get the client from url then get if from menu param
		if (empty($this->client))
		{
			// Get the active item
			$menuItem = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuItem->params;

			if (!empty($this->menuparams))
			{
				$this->ucm_type = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type))
				{
					$this->client = 'com_tjucm.' . $this->ucm_type;
				}
			}
		}

		// If there are no fields column to show in list view then dont allow to show data
		$this->showList = $model->showListCheck($this->client);

		// Include models
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');

		/* Get model instance here */
		$itemFormModel = BaseDatabaseModel::getInstance('itemForm', 'TjucmModel');

		$input = Factory::getApplication()->input;
		$input->set("content_id", $id);
		$this->created_by = $input->get("created_by", '', 'INT');

		// Get ucm type data
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array('unique_identifier' => $this->client));
		$typeParams = json_decode($typeTable->params);

		if (isset($typeParams->list_layout) && !empty($typeParams->list_layout))
		{
			$this->setLayout($typeParams->list_layout);
		}

		$allowedCount = (!empty($typeTable->allowed_count))?$typeTable->allowed_count:'0';
		$userId = $user->id;

		if (empty($this->id))
		{
			if ($this->canCreate)
			{
				$this->allowedToAdd = $itemFormModel->allowedToAddTypeData($userId, $this->client, $allowedCount);
			}
		}

		if ($this->created_by == $userId)
		{
			$this->canView = true;
		}

		$response = array("total" => $this->get('Total'), "html" => $this->loadTemplate('ropdata'));
		echo new JsonResponse($response);
		$app->close();
	}

	/**
	 * Display the assignment list besed on the filters
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function loadMore()
	{
		$app                 = Factory::getApplication();
		$user                = Factory::getUser();
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		$this->params       = $app->getParams('com_tjucm');
		$this->listcolumn   = $this->get('Fields');
		$this->allowedToAdd = false;
		$this->ucmTypeId    = $id = $this->state->get('ucmType.id');
		$this->client       = $this->state->get('ucm.client');
		$this->canCreate    = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canView      = $user->authorise('core.type.viewitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEdit      = $user->authorise('core.type.edititem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canChange    = $user->authorise('core.type.edititemstate', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEditOwn   = $user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDelete    = $user->authorise('core.type.deleteitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDeleteOwn = $user->authorise('core.type.deleteownitem', 'com_tjucm.type.' . $this->ucmTypeId);

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
		$ropbusinessFieldData = $tjfieldsModelFields->getItems();

		if (!empty($ropbusinessFieldData))
		{
			$this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;
		}

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'cluster-ownership');
		$ropSchoolData = $tjfieldsModelFields->getItems();
		$this->ropSchoolId = 0;

		if ($this->state->get('filter.process') != 'myprocess')
		{
			$this->ropSchoolId = $ropSchoolData[0]->id;
		}

		// If did not get the client from url then get if from menu param
		if (empty($this->client))
		{
			// Get the active item
			$menuItem = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuItem->params;

			if (!empty($this->menuparams))
			{
				$this->ucm_type = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type))
				{
					$this->client = 'com_tjucm.' . $this->ucm_type;
				}
			}
		}

		// Include models
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');

		/* Get model instance here */
		$itemFormModel = BaseDatabaseModel::getInstance('itemForm', 'TjucmModel');

		$input = Factory::getApplication()->input;
		$input->set("content_id", $id);
		$this->created_by = $input->get("created_by", '', 'INT');

		// Get ucm type data
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array('unique_identifier' => $this->client));

		$allowedCount = (!empty($typeTable->allowed_count))?$typeTable->allowed_count:'0';
		$userId = $user->id;

		if ($this->canCreate)
		{
			$this->allowedToAdd = $itemFormModel->allowedToAddTypeData($userId, $this->client, $allowedCount);
		}

		if ($this->created_by == $userId)
		{
			$this->canView = true;
		}

		$response = array("total" => $this->get('Total'), "html" => $this->loadTemplate('ropdata'));

		echo new JsonResponse($response);
		$app->close();
	}
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function displayCoreData($tpl = null)
	{
		$app     = Factory::getApplication();
		$user    = Factory::getUser();
		$model   = $this->getModel("Items");

		// Check the view access to the items.
		if (!$user->id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$this->state         = $this->get('State');

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->params       = $app->getParams('com_tjucm');
		$this->listcolumn   = $this->get('Fields');
		$this->allowedToAdd = false;
		$this->ucmTypeId    = $id = $model->getState('ucmType.id');
		$this->client       = $model->getState('ucm.client');
		$this->canCreate    = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canView      = $user->authorise('core.type.viewitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEdit      = $user->authorise('core.type.edititem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canChange    = $user->authorise('core.type.edititemstate', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEditOwn   = $user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDelete    = $user->authorise('core.type.deleteitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDeleteOwn = $user->authorise('core.type.deleteownitem', 'com_tjucm.type.' . $this->ucmTypeId);

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
		$ropbusinessFieldData = $tjfieldsModelFields->getItems();

		if (!empty($ropbusinessFieldData))
		{
			$businessFunctionFieldId = $this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;
		}

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'cluster-ownership');
		$ropSchoolData = $tjfieldsModelFields->getItems();

		$this->ropSchoolId = 0;

		if ($this->state->get('filter.process') != 'myprocess')
		{
			$this->ropSchoolId = $ropSchoolData[0]->id;
		}

		// If did not get the client from url then get if from menu param
		if (empty($this->client))
		{
			// Get the active item
			$menuItem = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuItem->params;

			if (!empty($this->menuparams))
			{
				$this->ucm_type = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type))
				{
					$this->client = 'com_tjucm.' . $this->ucm_type;
				}
			}
		}

		// If there are no fields column to show in list view then dont allow to show data
		$this->showList = $model->showListCheck($this->client);

		// Include models
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');

		/* Get model instance here */
		$itemFormModel = BaseDatabaseModel::getInstance('itemForm', 'TjucmModel');

		$input = Factory::getApplication()->input;
		$input->set("content_id", $id);
		$this->created_by = $input->get("created_by", '', 'INT');

		// Get ucm type data
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array('unique_identifier' => $this->client));
		$typeParams = json_decode($typeTable->params);

		if (isset($typeParams->list_layout) && !empty($typeParams->list_layout))
		{
			$this->setLayout($typeParams->list_layout);
		}

		$allowedCount = (!empty($typeTable->allowed_count))?$typeTable->allowed_count:'0';
		$userId = $user->id;

		if (empty($this->id))
		{
			if ($this->canCreate)
			{
				$this->allowedToAdd = $itemFormModel->allowedToAddTypeData($userId, $this->client, $allowedCount);
			}
		}

		if ($this->created_by == $userId)
		{
			$this->canView = true;
		}

		$response = array("total" => $this->get('Total'), "html" => $this->loadTemplate('coredata'));
		echo new JsonResponse($response);
		$app->close();
	}

	/**
	 * Display the assignment list besed on the filters
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function loadMoreCoreData()
	{
		$app                 = Factory::getApplication();
		$user                = Factory::getUser();
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->filter_process = $app->input->get('filter_process', 'myprocess', 'STRING');

		$this->params       = $app->getParams('com_tjucm');
		$this->listcolumn   = $this->get('Fields');
		$this->allowedToAdd = false;
		$this->ucmTypeId    = $id = $this->state->get('ucmType.id');
		$this->client       = $this->state->get('ucm.client');
		$this->canCreate    = $user->authorise('core.type.createitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canView      = $user->authorise('core.type.viewitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEdit      = $user->authorise('core.type.edititem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canChange    = $user->authorise('core.type.edititemstate', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canEditOwn   = $user->authorise('core.type.editownitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDelete    = $user->authorise('core.type.deleteitem', 'com_tjucm.type.' . $this->ucmTypeId);
		$this->canDeleteOwn = $user->authorise('core.type.deleteownitem', 'com_tjucm.type.' . $this->ucmTypeId);

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
		$ropbusinessFieldData = $tjfieldsModelFields->getItems();

		if (!empty($ropbusinessFieldData))
		{
			$this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;
		}

		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'cluster-ownership');
		$ropSchoolData = $tjfieldsModelFields->getItems();
		$this->ropSchoolId = 0;

		if ($this->state->get('filter.process') != 'myprocess')
		{
			$this->ropSchoolId = $ropSchoolData[0]->id;
		}

		// If did not get the client from url then get if from menu param
		if (empty($this->client))
		{
			// Get the active item
			$menuItem = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuItem->params;

			if (!empty($this->menuparams))
			{
				$this->ucm_type = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type))
				{
					$this->client = 'com_tjucm.' . $this->ucm_type;
				}
			}
		}

		// Include models
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');

		/* Get model instance here */
		$itemFormModel = BaseDatabaseModel::getInstance('itemForm', 'TjucmModel');

		$input = Factory::getApplication()->input;
		$input->set("content_id", $id);
		$this->created_by = $input->get("created_by", '', 'INT');

		// Get ucm type data
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array('unique_identifier' => $this->client));

		$allowedCount = (!empty($typeTable->allowed_count))?$typeTable->allowed_count:'0';
		$userId = $user->id;

		if ($this->canCreate)
		{
			$this->allowedToAdd = $itemFormModel->allowedToAddTypeData($userId, $this->client, $allowedCount);
		}

		if ($this->created_by == $userId)
		{
			$this->canView = true;
		}

		$response = array("total" => $this->get('Total'), "html" => $this->loadTemplate('coredata'));

		echo new JsonResponse($response);
		$app->close();
	}
}
