<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_tjucm/includes/tjucm", JPATH_SITE);

/**
 * View to show the itemform details
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmViewItemform extends HtmlView
{
	/**
	 * The Form object
	 *
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  Joomla\CMS\Object\CMSObject
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $item;

	/**
	 * The model state
	 *
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * The model state
	 *
	 * @var  Joomla\Registry\Registry
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $params;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canSave;

	/**
	 * The Record Id
	 *
	 * @var  Int
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $id;

	/**
	 * The Copy Record Id
	 *
	 * @var  Int
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $copyRecId;

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
		$input = $app->input;
		$user = Factory::getUser();
		$this->state   = $this->get('State');
		$this->id = $input->getInt('id', $input->getInt('content_id', 0));

		/* Get model instance here */
		$model = $this->getModel();
		$model->setState('item.id', $this->id);
		$this->params  = $app->getParams('com_tjucm');

		// DPE hack - Can go in core
		$model->setState('params', $this->params);

		$this->client = $input->get('client');
		$clusterId = $input->getInt('cluster_id', 0);

		$tjUcmModelType = Tjucm::model('Type');
		$typeId = $tjUcmModelType->getTypeId($this->client);

		// DPE hack - Can go in core
		if ($typeId)
		{
			$model->setState('ucmType.id', $typeId);
		}

		$this->item    = $this->get('Data');
		$this->canSave = $this->get('CanSave');
		$this->form = $this->get('Form');

		// Set cluster_id in request parameters
		if ($this->id && !$clusterId)
		{
			$input->set('cluster_id', $this->item->cluster_id);
			$clusterId = $this->item->cluster_id;
		}
		// DPE hack to set the cluster for admin and single cluster

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);
		if (count($clusters) == 1)
		{
			$input->set('cluster_id', $clusters[0]->cluster_id);
			$clusterId = $this->item->cluster_id = $clusters[0]->cluster_id;
		}
		
		// DPE hack end

		// Get com_subusers component status
		$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

		// Check user have permission to edit record of assigned cluster
		if ($subUserExist && !empty($clusterId) && !$user->authorise('core.manageall', 'com_cluster'))
		{

			$this->canEdit = TjucmAccess::canEdit($typeId, $this->item->id);

			if (!$this->item->id)
			{
				if (!TjucmAccess::canCreate($typeId))
				{
					echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
					$app->close();
				}
			}
			elseif (!$this->canEdit)
			{
<<<<<<< HEAD
				PluginHelper::importPlugin('system');
				$dispatcher = JDispatcher::getInstance();
				$result = $dispatcher->trigger('tjucmOnBeforeItemFormDisplay', array(&$this->item, &$this->form_extra));
=======
				PluginHelper::importPlugin('tjucmdpe');
				
				$result = Factory::getApplication()->triggerEvent('onBeforeTjucmItemFormDisplay', array(&$this->item, &$this->form_extra));
>>>>>>> daea3599e1811b189095769f1c4681ebd8c32628


				// DPE hack start to don't allow access for draft record

				if (!$this->item->draft)
				{
					$this->assignedUsers = trim(implode("\n", $result));
				}
				// DPE hack end

				if (empty($this->assignedUsers))
				{
					echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
					$app->close();
				}
			}
		}

		// Get a copy record id
		$this->copyRecId = (int) $app->getUserState('com_tjucm.edit.itemform.data.copy_id', 0);

		// Check copy id set and empty request id record
		if ($this->copyRecId && !$this->id)
		{
			$this->id = $this->copyRecId;
		}

		// Code check cluster Id of URL with saved cluster_id both are equal in edit mode
		if (!$this->copyRecId && $this->id)
		{
			$clusterId = $input->getInt("cluster_id", 0);

			if ($clusterId != $this->item->cluster_id)
			{
				echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
				$app->close();
			}
		}

		if (empty($this->client))
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_ITEM_DOESNT_EXIST'), true);
			$app->close();
		}

		// Check the view access to the itemform (the model has already computed the values).
		if ($this->item->params->get('access-view') == false)
		{
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
			$app->close();
		}

		// Get if user is allowed to save the content

		$typeData = $tjUcmModelType->getItem($typeId);

		// Check if the UCM type is unpublished
		if ($typeData->state == "0")
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_ITEM_DOESNT_EXIST'), true);
			$app->close();
		}

		// Set Layout to type view
		$layout = isset($typeData->params['layout']) ? $typeData->params['layout'] : '';
		$this->setLayout($layout);

		$allowedCount = $typeData->allowed_count;
		$userId = $user->id;

		if (empty($this->id))
		{
			$this->allowedToAdd = $model->allowedToAddTypeData($userId, $this->client, $allowedCount);

			if (!$this->allowedToAdd)
			{
				JLoader::import('controllers.itemform', JPATH_SITE . '/components/com_tjucm');
				$itemFormController = new TjucmControllerItemForm;
				$itemFormController->redirectToListView($typeId, $allowedCount);
			}
		}

		$view = explode('.', $this->client);
		$this->form_extra = $model->getFormExtra(
		array(
			"clientComponent" => 'com_tjucm',
			"client" => $this->client,
			"view" => $view[1],
			"layout" => 'edit',
			"content_id" => $this->id, )
			);

		$tjUcmTypeTable = Tjucm::table('Type');
		$tjUcmTypeTable->load(array('unique_identifier' => $this->client));
		$typeParams = json_decode($tjUcmTypeTable->params);
		$this->allow_auto_save = (isset($typeParams->allow_auto_save) && empty($typeParams->allow_auto_save)) ? 0 : 1;
		$this->allow_draft_save = (isset($typeParams->allow_draft_save) && !empty($typeParams->allow_draft_save)) ? 1 : 0;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		// Ucm triggger before item form display
		PluginHelper::importPlugin('tjucm');
		Factory::getApplication()->triggerEvent('onBeforeTjucmItemFormDisplay', array(&$this->item, &$this->form_extra));

		$doc = Factory::getDocument();
		$response = new stdClass;
		$response->html = $this->loadTemplate($tpl);
		$response->script = $doc->_script['text/javascript'];

		echo new JsonResponse($response);
		$app->close();
	}
}
