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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
<<<<<<< HEAD
use Joomla\CMS\MVC\Controller\AdminController;
=======
use Joomla\CMS\Date\Date;

>>>>>>> daea3599e1811b189095769f1c4681ebd8c32628

jimport('joomla.filesystem.file');

require_once JPATH_SITE . "/components/com_tjfields/filterFields.php";

/**
 * Item controller class.
 *
 * @since  1.6
 */
<<<<<<< HEAD
class TjucmControllerItemForm extends AdminController
=======
class TjucmControllerItemForm extends FormController
>>>>>>> daea3599e1811b189095769f1c4681ebd8c32628
{
	// Use imported Trait in model
	use TjfieldsFilterField;

	protected $changeDataLables = [];

	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$app = Factory::getApplication();
		$this->client  = $app->input->get('client', '', 'STRING');

		// If client is empty then get client from post data
		if (empty($this->client))
		{
			$this->client = $app->input->post->get('client', '', 'STRING');
		}

		// Get UCM type id for the client
		if (!empty($this->client))
		{

			 Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
			$tjUcmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));


			$tjUcmTypeTable->load(array('unique_identifier' => $this->client));

			if (!empty($tjUcmTypeTable->id))
			{
				$this->typeId = $tjUcmTypeTable->unique_identifier;
			}
		}

		parent::__construct();
	}

	/**
	 * Function to save ucm data item
	 *
	 * @param   int  $key     admin approval 1 or 0
	 * @param   int  $urlVar  id of user who has enrolle the user
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		//~ Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app   = Factory::getApplication();
		$post  = $app->input->post;
		$model = $this->getModel('itemform');

		$data = array();
		$data['id'] = $post->get('id', 0, 'INT');
		$data['category_id'] = $post->get('category_id', 0, 'INT');

		if (empty($data['id']))
		{
			$client = $post->get('client', '', 'STRING');

			// For new record if there is no client specified or invalid client is given then do not process the request
			if ($client == '' || empty($this->typeId))
			{
				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_SAVE_FAILED_CLIENT_REQUIRED'), true);
				$app->close();
			}

			// Set the state of record as per UCM type config
			$typeTable = $model->getTable('type');
			$typeTable->load(array('unique_identifier' => $client));
			$typeParams = new Registry($typeTable->params);
			$data['state'] = $typeParams->get('publish_items', 0);

			$data['client'] = $client;
		}

		$data['draft'] = $post->get('draft', 0, 'INT');
		$data['parent_id'] = $post->get('parent_id', 0, 'INT');

		try
		{
			$form = $model->getForm();
			$data = $model->validate($form, $data);

			if ($data == false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_VALIDATATION_FAILED'), true);
				$app->close();
			}

			$isNew = (empty($data['id'])) ? 1 : 0;

			// Plugin trigger on before item save
			PluginHelper::importPlugin('actionlog');
			
			Factory::getApplication()->triggerEvent('tjUcmOnBeforeSaveItem', array($data, $isNew));

			// DPE hack for ROP Data Flow
			if ($data['client'] == 'com_tjucm.ropdataflow' )
			{
				$data['PreElementValue'] = $post->get('PreElementValue', 0, 'INT');
			}

			if ($isNew)
			{
				$data['checked_out'] = 0;
			    $data['modified_by'] = 0;
   			    $data['modified_date'] = '0000-00-00 00:00:00';

			}
			else
			{
				$data['checked_out'] = Factory::getUser()->id;
			    $data['modified_by'] = Factory::getUser()->id;
			    $data['modified_date'] = new Date('now');
			}
			

			if ($model->save($data))
			{
				$result['id'] = $model->getState($model->getName() . '.id');
				$data['id']   = $result['id'];

				// Plugin trigger on after item save
				PluginHelper::importPlugin('actionlog');
				
				Factory::getApplication()->triggerEvent('tjUcmOnafterSaveItem', array($data, $isNew));

				echo new JResponseJson($result, Text::_('COM_TJUCM_ITEM_SAVED_SUCCESSFULLY'));
				$app->close();
			}
			else
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);
				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_SAVE_FAILED'), true);
				$app->close();
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
			$app->close();
		}
	}

	/**
	 * Method to save single field data.
	 *
	 * @return  void
	 *
	 * @since   1.2.1
	 */

	 static  $changeData = array();
	 static  $count = 0;
	 
	public function saveFieldData()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app       = Factory::getApplication();
		$post      = $app->input->post;
		$recordId  = $post->get('recordid', 0, 'INT');
		$client    = $post->get('client', '', 'STRING');
		$fieldData = $post->get('jform', array(), 'ARRAY');


		$model     = $this->getModel('itemform');

		if (empty($fieldData))
		{
			$fieldData = $app->input->files->get('jform');
		}

		if (empty($fieldData))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_FORM_VALIDATATION_FAILED'), 'error');
			echo new JsonResponse(null);
			$app->close();
		}

		try
		{ 

			// Create JForm object for the field
			$form = $model->getFieldForm($fieldData);

			// Validate field data
			$data = $model->validate($form, $fieldData);

			if ($data == false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JsonResponse(null);
				$app->close();
			}

			$table = $model->getTable();

			$table->load($recordId);

			$fieldData = array();
			$fieldData['content_id'] = $recordId;
			$fieldData['fieldsvalue'] = $data;
			$fieldData['client'] = $client;
			$fieldData['created_by'] = $table->created_by;

			// Plugin trigger on before item date save
			PluginHelper::importPlugin('actionlog');
			Factory::getApplication()->triggerEvent('onTjUcmBeforeSaveItemData', array($recordId, $client, $data));

			// DPE hack 
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onTjUcmBeforeSaveItemData', array($recordId, $client, $data));

			// DPE hack
			$session = Factory::getApplication()->getSession();
			// Initialize session array if not already
			$changeData = $session->get('changeData', []); // Get existing session data
			$newData = $session->get('newData', []);


			// $found = false;
			$newKey = key($data);
			$newValue = $data[$newKey];

			PluginHelper::importPlugin('tjucmdpe');
			Factory::getApplication()->triggerEvent('onPrepareTjucmChangeSessionData', array($recordId, $newKey, $newValue, $client, &$changeData, &$newData));

			// If data is valid then save the data into DB
			$response = $model->saveFieldsData($fieldData);

			// Plugin trigger on after item data save
			PluginHelper::importPlugin('actionlog');
			
			Factory::getApplication()->triggerEvent('tjUcmOnAfterSaveItemData', array($recordId, $client, $data));
			
			if($client == 'com_tjucm.rop'){

			PluginHelper::importPlugin("dpe");
			Factory::getApplication()->triggerEvent('onAfterUcmSaveRopToFlatTable', array($recordId,$client));
		}
			
			echo new JsonResponse($response);
			$app->close();
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
			$app->close();
		}
	}

	/**
	 * Method to save form data.
	 *
	 * @return  void
	 *
	 * @since   1.2.1
	 */
	public function saveFormData()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app          = Factory::getApplication();
		$post         = $app->input->post;
		$recordId     = $post->get('recordid', 0, 'INT');
		$client       = $post->get('client', '', 'STRING');
		$formData     = $post->get('jform', array(), 'ARRAY');
		$filesData    = $app->input->files->get('jform', array(), 'ARRAY');
		$formData     = array_merge_recursive($formData, $filesData);
		$section      = $post->get('tjUcmFormSection', '', 'STRING');
		$showDraftMsg = $post->get('showDraftMessage', 1, 'INT');
		$draft        = $post->get('draft', 0, 'INT');

		if (empty($formData) || empty($client))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_FORM_VALIDATATION_FAILED'), 'error');
			echo new JsonResponse(null);
			$app->close();
		}

		try
		{
			// Create JForm object for the field
			$model = $this->getModel('itemform');
			$formData['client'] = $client;

			if (!empty($section))
			{
				$formData['section'] = $section;
				$form  = $model->getSectionForm($formData);
			}
			else
			{
				$formData['draft'] = $draft;
				$form  = $model->getTypeForm($formData);
			}

			// Validate field data
			$data = $model->validate($form, $formData);


			// Validate UCM subform data - start
			$fieldSets = $form->getFieldsets();

			foreach ($fieldSets as $fieldset)
			{
				foreach ($form->getFieldset($fieldset->name) as $field)
				{
					if ($field->type == 'Ucmsubform')
					{
						$subForm = $field->loadSubForm();
						$subFormFieldName = str_replace('jform[', '', $field->name);
						$subFormFieldName = str_replace(']', '', $subFormFieldName);

						if (!empty($formData[$subFormFieldName]))
						{
							foreach ($formData[$subFormFieldName] as $ucmSubFormData)
							{
								$ucmSubFormData = $model->validate($subForm, $ucmSubFormData);

								if ($ucmSubFormData === false)
								{
									$data = false;
								}
							}
						}
					}
				}
			}

			// Validate UCM subform data - end

			if ($data === false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JsonResponse(null);
				$app->close();
			}

			$table = $model->getTable();
			$table->load($recordId);

			$formData = array();
			$formData['content_id'] = $recordId;
			$formData['fieldsvalue'] = $data;
			$formData['client'] = $client;
			$formData['created_by'] = $table->created_by;

			// DPE hack needs be Removed - This is used to sent email
			$isNew = $table->draft;

			// Plugin trigger on before item date save
			PluginHelper::importPlugin('actionlog');
			$results = Factory::getApplication()->triggerEvent('onTjUcmBeforeSaveItemData', array($recordId, $client, $data));

			PluginHelper::importPlugin('system');
			$results = Factory::getApplication()->triggerEvent('onTjUcmBeforeSaveItemData', array($recordId, $client, $data));

			// DPE Hack Start
			// To call plugin for previous data for comparision
			PluginHelper::importPlugin('tjucmdpe');
			$oldData = Factory::getApplication()->triggerEvent('onTjUcmBeforeSaveItemOldData', array($recordId, $client));
			$oldData = $oldData[0];

			// To get Generic Cluster ID from Params
			$params    		 = ComponentHelper::getComponent('com_dpe')->getParams();
			$generiClusterId = $params->get('cluster_id');

			// To get Organozation ID in current form
			foreach ($oldData as $key => $value) {
				if (strpos($key, 'clusterclusterid') !== false) {
					$OrganiId = $value;
					break;
				}
			}

			// Check Wheather is Form Generic School then send mail to the Admins
			if ($OrganiId == $generiClusterId) {
					
				// For ROP send Mail 
				if($client == 'com_tjucm.rop'){

					$session = Factory::getApplication()->getSession();

					$changeData = $session->get('changeData'); // Get existing session data
					$newData = $session->get('newData');

					if (!empty($changeData) || !empty($newData)) {

						PluginHelper::importPlugin('tjucmdpe');
						Factory::getApplication()->triggerEvent('onSendEmailsChangeItemData',array($recordId, $client));
						
					} 
							// Set Null values in Session after sending Mails
							$session->clear('changeData');
							$session->clear('newData');
	
				}
				else{

					$oldValues = $oldData;
					$newValues = $formData['fieldsvalue'];

					PluginHelper::importPlugin('tjucmdpe');
					$differences = Factory::getApplication()->triggerEvent('onBeforeTjucmItemCopyCompareFields',array($oldValues, $newValues));

					if (!empty($differences)) {
						Factory::getApplication()->triggerEvent('onSendEmailsChangeItemData',array($recordId, $client));
						
					} 
				}

			}

			// DPE Hack End


			// DPE Hack to get add old assignee data
			$oldStatus = Factory::getApplication()->triggerEvent('onTjUcmGetOldStatusData', array($recordId, $client, $table->draft));
			
			

			// DPE Hack end to get add old assignee data

			// If data is valid then save the data into DB
			$response = $model->saveFieldsData($formData);



			// DPE hack Call the dpe plugin to save the checklist note data saved DPE hack
			PluginHelper::importPlugin("dpe");
			Factory::getApplication()->triggerEvent('onAfterUcmChecklistSave', array($post->get('jform', array(), 'ARRAY')));

			// Plugin trigger on before item date save
			PluginHelper::importPlugin('actionlog');
			
			Factory::getApplication()->triggerEvent('tjUcmOnAfterSaveItemData', array($recordId, $client, $data));

			$msg = null;

			if ($response && empty($section))
			{
				if ($draft)
				{
					if ($showDraftMsg)
					{
						$msg = ($response) ? Text::_("COM_TJUCM_ITEM_DRAFT_SAVED_SUCCESSFULLY") : Text::_("COM_TJUCM_FORM_SAVE_FAILED");
					}
				}
				else
				{
					$msg = ($response) ? Text::_("COM_TJUCM_ITEM_SAVED_SUCCESSFULLY") : Text::_("COM_TJUCM_FORM_SAVE_FAILED");
				}

				// Disable the draft mode of the item if full form is submitted
				$table->load($recordId);
				$table->draft = $draft;
				$table->modified_date = Factory::getDate()->toSql();
				$table->store();


				// Perform actions (redirection or trigger call) after final submit
				if (!$draft)
				{
					// DPE  - Hack - start to redirect on list view
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
					$dpeModel    = DPE::model('Dashboard', array('ignore_request' => true));
					$isChecklist = $dpeModel->isChecklist($recordId);
					$response = array('success' => $response);

					if (count((array) $isChecklist) > 0)  // DPE HACK
					{
						$response['redirectUrl'] = Route::_('index.php?option=com_dpe&view=dashboard', false);
					}
					else
					{ 
						$tjUcmFrontendHelper = new TjucmHelpersTjucm;
						$link = 'index.php?option=com_tjucm&view=items&client=' . $client;
						$itemId = $tjUcmFrontendHelper->getItemId($link);
						$response['redirectUrl'] = Route::_($link . '&Itemid=' . $itemId, false);
					}

					// DPE Hack to add old assignee data

					$data['oldAssignee'] = $results[0];
					$data['oldStatus']   = $oldStatus[0];

					// DPE Hack end to add old assignee data

					// DPE  - Hack - end
				    PluginHelper::importPlugin("system");
					Factory::getApplication()->triggerEvent('onUcmItemAfterSave', array($table->getProperties(), $data, $isNew));

					// Send Email accordign to the status changed.
					$table->load($recordId);

					
					
					if(!$table->draft)
					{	
						PluginHelper::importPlugin("dpe");
						Factory::getApplication()->triggerEvent('onAfterUcmSave', array($table->getProperties(),$client, $data, $formData));
					}

					//DPE Hack END
				}	// To update or save the ROP data into its Flat Table
			 
			 if($client == 'com_tjucm.rop'){

				 PluginHelper::importPlugin("dpe");
				 Factory::getApplication()->triggerEvent('onAfterUcmSaveRopToFlatTable', array($recordId,$client));
			}

			 if(Factory::getUser()->authorise('core.manageall', 'com_cluster') && 
			 	in_array($client, ['com_tjucm.breachlog', 'com_tjucm.FOIlog', 'com_tjucm.sarlog']))
			 {


			 	 $timeLogData['cluster']  = $data[str_replace('.', '_', $client).'_clusterclusterid'];	           

			 	 $post->get('cluster_id', '', 'INT');
			 	 $timeLogData['client']   = $client;		
			 	 $timeLogData['hours']    = $post->get('jform', array(), 'ARRAY')['hours'];
 			 	 $timeLogData['minutes']  = $post->get('jform', array(), 'ARRAY')['minutes'];	
			 	PluginHelper::importPlugin('tjucmdpe');
				Factory::getApplication()->triggerEvent('onAfterTicketCreateSaveTimeSave',array($recordId,$timeLogData));
			 }

			}
			else
			{
				$msg = Text::_("COM_TJUCM_FORM_SAVE_FAILED_AUTHORIZATION_ERROR");
			}

			echo new JsonResponse($response, $msg);
			$app->close();
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
			$app->close();
		}
	}

	/**
	 * Function to save ucm item field data
	 *
	 * @param   int  $key     admin approval 1 or 0
	 * @param   int  $urlVar  id of user who has enrolle the user
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.2.1
	 */
	public function saveItemFieldData($key = null, $urlVar = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$post = Factory::getApplication()->input->post;
		$model = $this->getModel('itemform');

		$data = array();
		$data['id'] = $post->get('id', 0, 'INT');

		if (empty($data['id']))
		{
			$client = $post->get('client', '', 'STRING');

			// For new record if there is no client specified then do not process the request
			if ($client == '')
			{
				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_SAVE_FAILED'), true);
				$app->close();
			}

			$data['created_by'] = Factory::getUser()->id;
			$data['created_date'] = Factory::getDate()->toSql();
			$data['client'] = $client;
		}
		else
		{
			$data['modified_by'] = Factory::getUser()->id;
			$data['modified_date'] = Factory::getDate()->toSql();
		}

		$data['state'] = $post->get('state', 0, 'INT');
		$data['draft'] = $post->get('draft', 0, 'INT');

		try
		{
			$form = $model->getForm();
			$data = $model->validate($form, $data);

			if ($data == false)
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);

				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_VALIDATATION_FAILED'), true);
				$app->close();
			}

			if ($model->save($data))
			{
				$result['id'] = $model->getState($model->getName() . '.id');

				echo new JResponseJson($result, Text::_('COM_TJUCM_ITEM_SAVED_SUCCESSFULLY'));
				$app->close();
			}
			else
			{
				$errors = $model->getErrors();
				$this->processErrors($errors);
				echo new JResponseJson('', Text::_('COM_TJUCM_FORM_SAVE_FAILED'), true);
				$app->close();
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
			$app->close();
		}
	}

	/**
	 * Method to procees errors
	 *
	 * @param   ARRAY  $errors  ERRORS
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	private function processErrors($errors)
	{
		$app = Factory::getApplication();

		if (!empty($errors))
		{
			$code = 500;
			$msg  = array();

			// Push up to three validation messages out to the user.
			for ($i = 0; $i < count($errors); $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$code  = $errors[$i]->getCode();
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			$app->enqueueMessage(implode("<br>", $msg), 'error');
		}
	}

	/**
	 * Method to get updated list of options for related field
	 *
	 * @return  void
	 *
	 * @since 1.2.1
	 */
	public function getRelatedFieldOptions()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$post = $app->input->post;
		$model = $this->getModel('itemform');

		$client = $post->get('client', '', 'STRING');
		$contentId = $post->get('content_id', 0, 'INT');

		if (empty($client) || empty($contentId))
		{
			echo new JsonResponse(null);
			$app->close();
		}

		$app->input->set('id', $contentId);
		$updatedOptionsForRelatedField = $model->getUdatedRelatedFieldOptions($client, $contentId);

		echo new JsonResponse($updatedOptionsForRelatedField);
		$app->close();
	}

/**
	 * Typical view method for MVC based architecture
	 * Method added fo the DPE project
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  \JControllerLegacy  A \JControllerLegacy object to support chaining.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($cachable = false, $urlparams = array())
	{
		// DPE hack - can go in core used for TJUCM - Checklist module display
		$app = Factory::getApplication();
		$clusterId = $app->input->getInt("cluster_id");
		$client = $app->input->getString("client");
		$id = $app->input->getInt("id");

		$id = (Factory::getApplication()->getMenu()->getDefault()->query['id'] != $id)?$id:'';

		// Get the record from submission
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__tj_ucm_types'));
		$query->where($db->quoteName('unique_identifier') . ' = ' . $db->quote($client));
		$db->setQuery($query);
		$typeId = $db->loadResult();

		if (!$typeId)
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_ITEM_DOESNT_EXIST'), true);
			$app->close();
		}

		if ($id)
		{
			parent::display($cachable = false, $urlparams = array());
			$app->close();
		}

		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__tj_ucm_data'));
		$query->where($db->quoteName('cluster_id') . ' = ' . $db->quote($clusterId));
		$query->where($db->quoteName('type_id') . ' = ' . $typeId);
		$db->setQuery($query);
		$recordId = $db->loadResult();

		$app->input->set('id', $recordId);
		$app->input->set('client', $client);
		$app->input->set('cluster_id', $clusterId);

		parent::display($cachable = false, $urlparams = array());
		$app->close();
	}

	/**
	 * Method to copy item
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function copyItem()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$post = $app->input->post;

		$sourceClient = $app->input->get('client', '', 'string');
		$filter       = $app->input->get('filter', '', 'ARRAY'); 
		$targetClient = ($filter)?$filter['target_ucm']:''; // dpe hack php test8.1

		// DPE hack - can go in core
		$fieldGroupValues = $app->input->get('fieldGroupValues');
		$isMasterList     = $app->input->get('isMasterList');
		$recordTitle      = $app->input->get('recordTitle','','string');

		$params              = ComponentHelper::getParams('com_dpe');
		$codeDataFieldConfig = json_decode($params->get('coredatatitlefields'), true);

		if (!empty($sourceClient) && array_key_exists($sourceClient, $codeDataFieldConfig))
		{
			$fieldUniqueName = $codeDataFieldConfig[$sourceClient];
		}

		if (!$targetClient)
		{
			$targetClient = $sourceClient;
		}

		// Get Clusers list and conver to array
		$clusterIds = $app->input->get('cluster_list', '', 'string');
		$clusterIds = explode(',', $clusterIds);

		// DPE Hack - Check RBACL check
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));

		if ($targetClient)
		{
			$typeTable->load(array('unique_identifier' => $targetClient));

			if (property_exists($typeTable, 'id'))
			{
				$ucmTypeId = $typeTable->id;
			}
		}

		foreach ($clusterIds as $clusterId)
		{
			$canCopyItem = TjucmAccess::canCopyItem($ucmTypeId, Factory::getUser()->id, $clusterId);

			if (!$canCopyItem)
			{
				echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
				$app->close();
			}
		}

		JLoader::import('components.com_tjucm.models.type', JPATH_ADMINISTRATOR);
		$typeModel = BaseDatabaseModel::getInstance('Type', 'TjucmModel');

		if ($sourceClient != $targetClient)
		{
			// Server side Validation for source and UCM Type
			$result = $typeModel->getCompatibleUcmTypes($sourceClient, $targetClient);
		}
		else
		{
			$result = true;
		}

		if ($result)
		{
			$copyIds = $app->input->get('cid');
			JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
			$tjFieldsHelper = new TjfieldsHelper;

			if (count($copyIds))
			{
				$model = $this->getModel('itemform');

				// DPE hack
				if(!$model)
				{
					JLoader::import('models.itemform', JPATH_SITE . '/components/com_tjucm');
					$model = new TjucmModelItemForm();
				}

				$ucmConfigs = ComponentHelper::getParams('com_tjucm');
				$useTjQueue = $ucmConfigs->get('tjqueue_copy_items');

				if ($useTjQueue && !$isMasterList)
				{
					foreach ($clusterIds as $clusterId)
					{
						foreach ($copyIds as $cid)
						{
								$response = $model->queueItemCopy($cid, $sourceClient, $targetClient, Factory::getuser()->id, $clusterId);

								$msg = ($response) ? Text::_("COM_TJUCM_ITEM_COPY_TO_QUEUE_SUCCESSFULLY") : Text::_("COM_TJUCM_FORM_SAVE_FAILED");
						}
					}
				}
				else
				{
					$model->setClient($targetClient);

					foreach ($clusterIds as $clusterId)
					{
						foreach ($copyIds as $cid)
						{
							$ucmOldData = array();
							$ucmOldData['clientComponent'] = 'com_tjucm';
							$ucmOldData['content_id'] = $cid;
							$ucmOldData['layout'] = 'edit';
							$ucmOldData['client']     = $sourceClient;
							$fileFieldArray = array();

							// Get the field values
							$extraFieldsData = $model->loadFormDataExtra($ucmOldData);

							// Code to replace source field name with destination field name
							foreach ($extraFieldsData as $fieldKey => $fieldValue)
							{
								$prefixSourceClient = str_replace(".", "_", $sourceClient);
								$fieldName = explode($prefixSourceClient . "_", $fieldKey);
								$prefixTargetClient = str_replace(".", "_", $targetClient);
								$targetFieldName = $prefixTargetClient . '_' . $fieldName[1];
								$tjFieldsTable = $tjFieldsHelper->getFieldData($targetFieldName);
								$fieldId = $tjFieldsTable->id;
								$fieldType = $tjFieldsTable->type;
								$fielParams = json_decode($tjFieldsTable->params);
								$sourceTjFieldsTable = $tjFieldsHelper->getFieldData($fieldKey);
								$sourceFieldParams = json_decode($sourceTjFieldsTable->params);
								$subFormData = array();

								// DPE hack can go in core
								if (!empty($fieldGroupValues) && (!in_array($tjFieldsTable->group_id, $fieldGroupValues)))
								{
									unset($extraFieldsData[$fieldKey]);
									continue;
								}

								if ($tjFieldsTable->type == 'ucmsubform' || $tjFieldsTable->type == 'subform')
								{
									$params = json_decode($tjFieldsTable->params)->formsource;
									$subFormClient = explode('components/com_tjucm/models/forms/', $params);
									$subFormClient = explode('form_extra.xml', $subFormClient[1]);
									$subFormClient = 'com_tjucm.' . $subFormClient[0];

									$params = $sourceFieldParams->formsource;
									$subFormSourceClient = explode('components/com_tjucm/models/forms/', $params);
									$subFormSourceClient = explode('form_extra.xml', $subFormSourceClient[1]);
									$subFormSourceClient = 'com_tjucm.' . $subFormSourceClient[0];

									$subFormData = (array) json_decode($fieldValue);
								}

								if ($subFormData)
								{
									foreach ($subFormData as $keyData => $data)
									{
										$prefixSourceClient = str_replace(".", "_", $sourceClient);
										$fieldName = explode($prefixSourceClient . "_", $keyData);
										$prefixTargetClient = str_replace(".", "_", $targetClient);
										$subTargetFieldName = $prefixTargetClient . '_' . $fieldName[1];
										$data = (array) $data;

										foreach ((array) $data as $key => $d)
										{
											$prefixSourceClient = str_replace(".", "_", $subFormSourceClient);
											$fieldName = explode($prefixSourceClient . "_", $key);
											$prefixTargetClient = str_replace(".", "_", $subFormClient);
											$subFieldName = $prefixTargetClient . '_' . $fieldName[1];

											Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
											$fieldTable = Table::getInstance('field', 'TjfieldsTable');

											$fieldTable->load(array('name' => $key));

											if ($fieldName[1] == 'contentid')
											{
												$d = '';
											}

											$temp = array();
											unset($data[$key]);

											if (is_array($d))
											{
												// TODO Temprary used switch case need to modify code
												switch ($fieldTable->type)
												{
													case 'multi_select':
														foreach ($d as $option)
														{
															$temp[] = $option->value;
														}

														if (!empty($temp))
														{
															$data[$subFieldName] = $temp;
														}
													break;

													case 'tjlist':
													case 'related':

														foreach ($d as $option)
														{
															$data[$subFieldName][] = $option;
														}
													break;

													default:
														foreach ($d as $option)
														{
															$data[$subFieldName] = $option->value;
														}
													break;
												}
											}
											elseif($fieldTable->type == 'file' || $fieldTable->type == 'image')
											{
												Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
												$subDestionationFieldTable = Table::getInstance('field', 'TjfieldsTable');

												$subDestionationFieldTable->load(array('name' => $subFieldName));

												$subformFileData = array();
												$subformFileData['value'] = $d;
												$subformFileData['copy'] = true;
												$subformFileData['type'] = $fieldTable->type;
												$subformFileData['sourceClient'] = $subFormSourceClient;
												$subformFileData['sourceFieldUploadPath'] = json_decode($fieldTable->params)->uploadpath;
												$subformFileData['destFieldUploadPath'] = json_decode($subDestionationFieldTable->params)->uploadpath;
												$subformFileData['user_id'] = Factory::getUser()->id;
												$data[$subFieldName] = $subformFileData;
											}
											elseif ($fieldTable->type == 'cluster')
											{
												$data[$subFieldName] = $clusterId;
											}
											else
											{
												$data[$subFieldName] = $d;
											}
										}

										unset($subFormData[$keyData]);
										$subFormData[$subTargetFieldName] = $data;
									}

									unset($extraFieldsData[$fieldKey]);
									$extraFieldsData[$targetFieldName] = $subFormData;
								}
								else
								{
									unset($extraFieldsData[$fieldKey]);

									if ($fieldType == 'file' || $fieldType == 'image')
									{
										$fileData = array();
										$fileData['value'] = $fieldValue;
										$fileData['copy'] = true;
										$fileData['type'] = $fieldType;
										$fileData['sourceClient'] = $sourceClient;
										$fileData['sourceFieldUploadPath'] = $sourceFieldParams->uploadpath;
										$fileData['destFieldUploadPath'] = $fielParams->uploadpath;
										$fileData['user_id'] = Factory::getUser()->id;
										$extraFieldsData[$targetFieldName] = $fileData;
									}
									elseif($fieldType == 'cluster')
									{
										$extraFieldsData[$targetFieldName] = $clusterId;
									}
									else
									{
										$extraFieldsData[$targetFieldName] = $fieldValue;
									}

									if ($tjFieldsTable->name === $fieldUniqueName && !empty($recordTitle))
									{
										$extraFieldsData[$targetFieldName] = $recordTitle;
									}
								}
							}

							$ucmData = array();
							$ucmData['id']        = 0;
							$ucmData['client']    = $targetClient;
							$ucmData['parent_id'] = 0;

							// DPE hack - Publish records when we copy for core data ucm type

							// Copy state of record
							$ucmData['state'] = 1;
							$ucmData['draft'] = 0;

							if ($clusterId)
							{
								$ucmData['cluster_id']	 	= $clusterId;
							}

							// Save data into UCM data table
							$result = $model->save($ucmData);
							$recordId = $model->getState($model->getName() . '.id');
							PluginHelper::importPlugin('tjucmdpe');
							Factory::getApplication()->triggerEvent('onInsertCopyTrackingRecord',array($clusterId, $cid, $recordId));

							if ($recordId)
							{
								
								$formData = array();
								$formData['content_id'] = $recordId;
								$formData['fieldsvalue'] = $extraFieldsData;
								$formData['client'] = $targetClient;

								// If data is valid then save the data into DB
								$response = $model->saveExtraFields($formData);
								PluginHelper::importPlugin("dpe");
			 					Factory::getApplication()->triggerEvent('onAfterUcmSaveRopToFlatTable', array($recordId,$targetClient));
								if ($isMasterList)
								{
									$msg = ($response) ? Text::_("COM_TJUCM_MASTERLIST_ITEM_COPY_SUCCESSFULLY") : Text::_("COM_TJUCM_FORM_SAVE_FAILED");
								}
								else
								{
									$msg = ($response) ? Text::_("COM_TJUCM_ITEM_COPY_SUCCESSFULLY") : Text::_("COM_TJUCM_FORM_SAVE_FAILED");
								}
							}
						}
					}
				}

				echo new JsonResponse($response, $msg);
				$app->close();
			}
		}
	}

	/**
	 * Method to get Related Field Options for the field.
	 *
	 * @return   null
	 *
	 * @since    1.0.
	 */
	public function getUpdatedRelatedFieldOptions()
	{
		$app       = Factory::getApplication();
		$fieldId   = $app->input->get('fieldId', '', 'STRING');
		$clusterId = $app->input->get('clusterId', 0, 'STRING');

		if (!$clusterId)
		{
			$clusterId = $app->input->get('cluster_id', 0, 'STRING');
		}

		// Set Cluster ID
		if ($clusterId)
		{
			$app->input->set('cluster_id', $clusterId);
		}

		// Check for request forgeries.
		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		// Get object of TJ-Fields field model
		JLoader::import('components.com_tjfields.models.field', JPATH_ADMINISTRATOR);
		$tjFieldsModelField = BaseDatabaseModel::getInstance('Field', 'TjfieldsModel');
		$options = $tjFieldsModelField->getRelatedFieldOptions($fieldId);


	// DPE Hack to show the also known as field in related fields.

		$paramsDpe           = ComponentHelper::getParams('com_dpe');// Also known as field for vendor
 		$alsoKnownAsFieldnames  = json_decode($paramsDpe->get('alsoknownas'));

 		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('id' => $fieldId));
		$fieldtableId = $fieldTable->id;

		$fieldTable->load(array('id' => $fieldTable->id));
		// Get decoded data object
		$fieldParams = new Registry($fieldTable->params);
	
		// UCM fields and fields from which options are to be generated
		$realtedFieldsName  = $fieldParams->get('fieldName');

		// field name of related field
		$fieldTable->load(array('id' => $realtedFieldsName->fieldName0->fieldIds[0]));
		$realtedFieldsName = $fieldTable->name;

		if (array_key_exists($realtedFieldsName, get_object_vars($alsoKnownAsFieldnames)))
		{

					$fieldTable->load(array('name' => $alsoKnownAsFieldnames->$realtedFieldsName));
					$alsoKnownAsFieldId = $fieldTable->id;

					Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			 		$fieldValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable', array('dbo', $db));

					foreach($options as $key => $option)
					{

						$fieldValueTable->load(array('content_id' => $option['value'],'field_id'=>$alsoKnownAsFieldId));
						
						if ($fieldValueTable->value)
						{
							$options[$key]['text'] = $options[$key]['text'].' ('. $fieldValueTable->value.')';
						}
						
					}
		}

		

	// Hack end
		



		$relatedFieldOptions = array();

		foreach ($options as $option)
		{
			$relatedFieldOptions[] = HTMLHelper::_('select.option', trim($option['value']), trim($option['text']));
		}

		echo new JsonResponse($relatedFieldOptions);
		$app->close();
	}


	/**
	 * Method to get Feedback value of the fields.
	 * This is called for subform Rop and field eg: tjlist,radio and checkbox.
	 *
	 * @return   Jsonarray
	 *
	 * @since    1.0.0
	 */
	public function getFeedBack()
	{
		$app            = Factory::getApplication();
		$fieldName      = $app->input->get('fieldName', '', 'STRING');
		$fieldListValue = $app->input->get('fieldValue', '', 'STRING');
		$fieldType      = $app->input->get('type', '', 'STRING');
		$fieldLable     = $app->input->get('lable', '', 'STRING');

		if (strpos($fieldName, '__com') !== false) 
		{ 
		   $fieldName      = substr($fieldName, strrpos($fieldName, '__com') + 2);  
		}
		
		if ($fieldType == 'radio')
		{
			$fieldName = substr($fieldName, 0, -1);
		}
		
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => $fieldName, 'state' => 1));

		// check for allow of feedback showing or not
        if (( $tjFieldFieldTable->showFeedback))
        { 
	        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjfields/models');
            $model = JModelLegacy::getInstance('Fields', 'TjfieldsModel');
	        $fieldFeedbackValue = $model->getFieldValueByFieldId($tjFieldFieldTable->id);

	        foreach($fieldFeedbackValue as $fieldList)
	        { 
	        	if(is_array($fieldListValue))
	        	{
	        		foreach($fieldListValue as $key => $fieldListsingleValue)
		        	{
		        		if ($fieldList->value == $fieldListsingleValue )
			        	{ 
			        		if(($fieldType == 'list') && (!empty($fieldList->feedback)))
			        		{  
			        			$fieldFeedback[] = "<b>" . $fieldLable[$key] .": </b>".$fieldList->feedback;
			        		}
			        		else
			        		{
			        			$fieldFeedback[] = $fieldList->feedback;
			        		}	        		
			        	}
		        	}
	        	}
	        	else
	        	{
	        		if ($fieldList->value == $fieldListValue )
			        { 
			        		$fieldFeedback = $fieldList->feedback;
			        		
			        }
	        	}

	        	if(($fieldType  == 'checkbox' ) && $fieldList->ordering == $fieldListValue)
	        	{
	        		$fieldFeedback = $fieldList->feedback;
	        		continue;
	        	}
	        }

		}
		echo new JsonResponse($fieldFeedback);
		$app->close();
	}
}
