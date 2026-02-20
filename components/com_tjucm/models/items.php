<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of Tjucm records.
 *
 * @since  1.6
 */
class TjucmModelItems extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'state',
				'type_id',
				'created_by',
				'created_date',
				'modified_by',
				'modified_date',
				'published',
				'tags'
			);
		}

		$this->fields = array();
		$this->specialSortableFields = array();

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = "a.id", $direction = "DESC")
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();
		$db = Factory::getDbo();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
		$tjUcmModelType = BaseDatabaseModel::getInstance('Type', 'TjucmModel');

		$typeId  = $app->input->get('id', 0, "INT");
		$ucmType = htmlentities($app->input->get('client', '', "STRING"));

		if (empty($typeId) || empty($ucmType))
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
			$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', $db));

			if ($typeId && empty($ucmType))
			{
				$typeTable->load(array('id' => $typeId));
				$ucmType = $typeTable->unique_identifier;
			}

			if ($ucmType && empty($typeId))
			{
				$typeTable->load(array('unique_identifier' => $ucmType));
				$typeId = $typeTable->id;
			}
		}

		if (empty($ucmType) && empty($typeId))
		{
			// Get the active item
			$menuitem   = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuitem->getParams();

			if (!empty($this->menuparams))
			{
				$ucmTypeAlias = $this->menuparams->get('ucm_type');

				if (!empty($ucmTypeAlias))
				{
					JLoader::import('components.com_tjfields.tables.type', JPATH_ADMINISTRATOR);
					$ucmTypeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
					$ucmTypeTable->load(array('alias' => $ucmTypeAlias));
					$ucmType = $ucmTypeTable->unique_identifier;
					$typeId  = $ucmTypeTable->id;
				}
			}
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.' . $ucmType . '.filter.search', 'filter_search', '', 'STRING');
		$this->setState($ucmType . '.filter.search', $search);

		// Set state for field filters
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$fieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$fieldsModel->setState('filter.client', $ucmType);
		$fieldsModel->setState('filter.filterable', 1);
		$fields = $fieldsModel->getItems();

// DPE Hack
		$params              = ComponentHelper::getParams('com_dpe');
		$sarrRquestStatus = json_decode($params->get('sarrequestStatus'), true);
		$foiRquestStatus = json_decode($params->get('foirequestStatus'), true);
		$breachRquestStatus = json_decode($params->get('breachStatus'), true);

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$sarfieldTable = Table::getInstance('field', 'TjfieldsTable');
		$sarfieldTable->load(array('id'=>$sarrRquestStatus));

		$foifieldTable = Table::getInstance('field', 'TjfieldsTable');
		$foifieldTable->load(array('id'=>$foiRquestStatus));

		$breachfieldTable = Table::getInstance('field', 'TjfieldsTable');
		$breachfieldTable->load(array('id'=>$breachRquestStatus));


		$sarrRquestStatusName = $sarfieldTable->name;
		$foiRquestStatusName = $foifieldTable->name;
		$breachRquestStatusName = $breachfieldTable->name;




		foreach ($fields as $field)
		{
			$filterValue = $app->getUserStateFromRequest($this->context . '.' . $field->name, $field->name, '', 'STRING');
			$this->setState('filter.field.' . $field->name, $filterValue);
			// DPE hack
			if($field->name == $sarrRquestStatusName || $field->name == $foiRquestStatusName || $field->name == $breachRquestStatusName )	{			
				$filterValue = $app->getUserStateFromRequest($this->context . '.' . $field->name, $field->name, '', 'STRING');
				$this->setState('filter.field.' . $field->name.'[]', $filterValue);
			}

		}

		// DPE hack  set tag 
		$tags = $app->getUserStateFromRequest($this->context.'.filter.tags', 'filter_tags', '', 'string');
		$this->setState('filter.tags', $tags);

		$numeirccalculation = $clusterId = $app->getUserStateFromRequest($this->context . '.' . $ucmType . '.numeirccalculation', 'numeirccalculation');
		$this->setState($ucmType . '.filter.numeirccalculation', $numeirccalculation);


		// Get the current session instance for Retrieve the selected cluster from the session
		$session = Factory::getApplication()->getSession();
		$selectedCluster=$session->get('selectedCluster'); // Get Cluster ID
		
		// Set the filter state for cluster ID to the selected cluster
		$app->setUserState($this->context . '.' . $ucmType . '.cluster', $selectedCluster);

		$clusterId = $app->getUserStateFromRequest($this->context . '.' . $ucmType . '.cluster', 'cluster');

		if ($clusterId)
		{
			$this->setState($ucmType . '.filter.cluster_id', $clusterId);
		}
		
		$categoryId = $app->getUserStateFromRequest($this->context . '.' . $ucmType . '.itemcategory', 'itemcategory');

		if ($categoryId)
		{
			$this->setState($ucmType . '.filter.category_id', $categoryId);
		}

		$draft = $app->getUserStateFromRequest($this->context . '.draft', 'draft');
		$this->setState('filter.draft', $draft);

		$this->setState('ucm.client', $ucmType);
		$this->setState("ucmType.id", $typeId);

		$createdBy = $app->input->get('created_by', "", "INT");
		$this->setState("created_by", $createdBy);

		if ($this->getUserStateFromRequest($this->context . $ucmType . '.filter.order', 'filter_order', '', 'string'))
		{
			$ordering = $this->getUserStateFromRequest($this->context . $ucmType . '.filter.order', 'filter_order', '', 'string');
			
			// DPE Hack to set the ordering
			$this->setState('list.ordering', $ordering);
		}

		if ($this->getUserStateFromRequest($this->context . $ucmType . '.filter.order_Dir', 'filter_order_Dir', '', 'string'))
		{
			$direction = $this->getUserStateFromRequest($this->context . $ucmType . '.filter.order_Dir', 'filter_order_Dir', '', 'string');

			// DPE Hack to set the direction
			$this->setState('list.direction', $direction);
		}

		
		
		$fromDate = $this->getUserStateFromRequest($this->context . '.fromDate', 'fromDate', '', 'STRING');
		$toDate = $this->getUserStateFromRequest($this->context . '.toDate', 'toDate', '', 'STRING');

		if (!empty($fromDate) || !empty($toDate))
		{
			$fromDate = empty($fromDate) ? Factory::getDate('now -1 month')->toSql() : Factory::getDate($fromDate)->toSql();
			$toDate = empty($toDate) ? Factory::getDate('now')->toSql() : Factory::getDate($toDate)->toSql();

			// If from date is less than to date then swipe the dates
			if ($fromDate > $toDate)
			{
				$tmpDate = $fromDate;
				$fromDate = $toDate;
				$toDate = $tmpDate;
			}

			$this->setState($ucmType . ".filter.fromDate", $fromDate);
			$this->setState($ucmType . ".filter.toDate", $toDate);
		}

		$limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit');

		$this->setState('list.limit',$limit);
		// $this->setState($ucmType .'.list.start', $start);

		parent::populateState($ordering, $direction);

		// DPE - hack - start

		$filterProcess     = $app->getUserStateFromRequest($this->context.'.filter.process', 'filter_process', '', 'string');
		$filterCoredata    = $app->input->get('filter_coredata', '', 'string');
		$filterGenericlist = $app->input->get('filter_genericlist', '', 'string');
		$filterGenericlist = ($filterGenericlist)?$filterGenericlist:$app->input->get('filter_genericlist1', '', 'string');


		if (($filterProcess == 'generic') && (($filterCoredata) || $filterGenericlist))
		{
			$this->setState('filter.process', $filterProcess);
			$this->setState($ucmType . '.filter.cluster_id', null);
		}
		else
		{
			$filterProcess = '';

			$this->setState('filter.process', $filterProcess);

			// Set the filter state for cluster ID to the selected cluster form Sessiom
			if(empty($clusterId)){
				$this->setState($ucmType . '.filter.cluster_id', $selectedCluster);
			}
		}

		// DPE - hack - End
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Call function to initialise fields lists
		$this->getFields();
		$user = Factory::getUser();

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$client = htmlentities($this->getState('ucm.client'));

		// Select the required fields from the table.
		$query->select('a.*');

		foreach ($this->fields as $fieldId => $field)
		{
			if ($field->type == 'number')
			{
				$query->select('CAST(MAX(CASE WHEN fv.field_id=' . $fieldId . ' THEN value END) AS SIGNED)  `' . $fieldId . '`');
			}
			else
			{
				$query->select('MAX(CASE WHEN fv.field_id=' . $fieldId . ' THEN value END) `' . $fieldId . '`');
			}
		}

		$query->from($db->qn('#__tj_ucm_data', 'a'));

		// Join over fields value table
		if($client == 'com_tjucm.rop'){
			$query->join(
				"LEFT", $db->qn('#__tjfields_fields_value_flat', 'fv') . ' ON (' . $db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ')'
			);
		}else{
			$query->join(
				"LEFT", $db->qn('#__tjfields_fields_value', 'fv') . ' ON (' . $db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ')'
			);
		}
		


		if (!empty($client))
		{
			$query->where($db->qn('a.client') . ' = ' . $db->q($db->escape($client)));
		}

		$ucmTypeId = $this->getState('ucmType.id', '', 'INT');

		if (!empty($ucmTypeId))
		{
			$query->where($db->qn('a.type_id') . ' = ' . (INT) $ucmTypeId);
		}

		// DPE hack - Can Go in Core - Filter By UCM Data ID
		$ucmDataId = $this->getState('ucm.id', '', 'INT');

		if ($ucmDataId)
		{
			$query->where($db->quoteName('a.id') . ' = ' . (INT) $ucmDataId);
		}
		// DPE hack ends here

		$createdBy = $this->getState('created_by', '', 'INT');

		if (!empty($createdBy))
		{
			$query->where($db->qn('a.created_by') . ' = ' . (INT) $createdBy);
		}

		// Filter for parent record
		$parentId = $this->getState('parent_id');

		if (is_numeric($parentId))
		{
			$query->where($db->qn('a.parent_id') . ' = ' . $parentId);
		}

		// Show records belonging to users cluster if com_cluster is installed and enabled - start
		$clusterExist = ComponentHelper::getComponent('com_cluster', true)->enabled;

		if ($clusterExist)
		{
			JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
			$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
			$fieldTable->load(array('client' => $client, 'type' => 'cluster', 'state' => '1'));

			if ($fieldTable->id)
			{
				// DPE - hack - start
				$filterProcess = $this->getState('filter.process');
				$filterCoredata = Factory::getApplication()->input->get('filter_coredata', '', 'string');
				$filterGenericlist = Factory::getApplication()->input->get('filter_genericlist', '', 'string');
				$filterGenericlist = ($filterGenericlist)?$filterGenericlist:Factory::getApplication()->input->get('filter_genericlist1', '', 'string');


				if (($filterProcess == 'generic') && (($filterCoredata) || $filterGenericlist))
				{
					$dpeParam         = JComponentHelper::getParams('com_dpe');

					if ($dpeParam->get('cluster_id', '0', 'INT') > 0)
					{
						$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $dpeParam->get('cluster_id', '0', 'INT'));
					}
				}
				else
				{
					JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
					$cluster            = JFormHelper::loadFieldType('cluster', false);
					$clusterList        = $cluster->getOptionsExternally();
					$user               = Factory::getUser();
					$usersClusters      = array();
					$usersStaffClusters = array();

					if (!empty($clusterList))
					{
						JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

						foreach ($clusterList as $clusterList)
						{
							if (!empty($clusterList->value))
							{
								// Get orgs where user don't have ucm list access
								if (!RBACL::check($user->id, 'com_cluster', 'core.viewitemlist.' . $ucmTypeId, 'com_tjucm', (int) $clusterList->value))
								{
									$usersStaffClusters[] = $clusterList->value;
								}
								else
								{
									$usersClusters[] = $clusterList->value;
								}
							}
						}
					}

					$addedBy = '';

					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						$extraQuery = '';

						// If user having org where user don't have list view access then get assigned records
						if (! empty($usersStaffClusters) && empty($this->getState('assigned')))
						{
							Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
							$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
							$tjFieldFieldTable->load(array('client' => $client, 'type' => 'assignee', 'state' => 1));

							$subQuery  = $db->getQuery(true);
							$subQuery->select(1);
							if($client == 'com_tjucm.rop'){
								$subQuery->from($db->qn('#__tjfields_fields_value_flat', 'fva'));
								$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value_flat', 'fva1') .
									' ON (' . $db->qn('fva.content_id') . ' = ' . $db->qn('fva1.content_id') . ')');
							}else
							{
								$subQuery->from($db->qn('#__tjfields_fields_value', 'fva'));
								$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value', 'fva1') .
									' ON (' . $db->qn('fva.content_id') . ' = ' . $db->qn('fva1.content_id') . ')');
							}
							
							
							$subQuery->where($db->qn('fva1.field_id') . ' = ' . (int) $tjFieldFieldTable->id);
							$subQuery->where('fva1.value' . " = " . (int) $user->id);
							$subQuery->where($db->qn('fva.content_id') . '=' . $db->qn('a.id').')');
							$subQuery->where("(" . $db->qn('a.cluster_id') . " IN ('" . implode("','", $usersStaffClusters) . "')");
							$extraQuery = " OR EXISTS (" . $subQuery . ")";

							// Don't show draft records to assignee
							$this->setState('filter.draft', 0);
						}

						if ($this->getState('assigned'))
						{
							// If user is only staff then show assigned records from staff org
							if ($usersStaffClusters && empty($usersClusters))
							{
								$usersClusters = $usersStaffClusters;
							}
							elseif (!empty($usersClusters) && !empty($usersStaffClusters))
							{
								// If user is  staff + admin then show assigned records from both org
								$usersClusters = array_merge($usersClusters, $usersStaffClusters);
							}
						}

						// If cluster array empty then we set 0 in whereclause query
						if (empty($usersClusters))
						{
							$usersClusters[] = 0;
						}

						$addedBy = $db->qn('a.created_by') . ' = ' . $user->id . ' AND ';

						// DPE Hack to bypass this query only for generate document . 
						if(!Factory::getApplication()->input->get('generate_doc', "", "INT")) 
						{
							$query->where("(" . $db->qn('a.cluster_id') . " IN ('" . implode("','", $usersClusters) . "') OR ( " . $addedBy .
								$db->qn('a.cluster_id') . " IS NULL )) $extraQuery ");
						}
						

						// This code will show only assigned records
						if ($this->getState('assigned'))
						{
							
							PluginHelper::importPlugin('system', 'dpe_tjlms_cluster');
							Factory::getApplication()->triggerEvent('onTjucmModelItemsGetListQuery', array($query, $client, $this->context));


							// Don't show draft records to assignee
							$this->setState('filter.draft', 0);
						}
					}
				}
				// DPE - hack - END
			/*
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clustersModel = ClusterFactory::model('Clusters', array('ignore_request' => true));
				$clusters = $clustersModel->getItems();
				$usersClusters = array();

				if (!empty($clusters))
				{
					foreach ($clusters as $clusterList)
					{
						if (!empty($clusterList->id))
						{
							if (TjucmAccess::canView($ucmTypeId, $clusterList->id))
							{
								$usersClusters[] = $clusterList->id;
							}
						}
					}
				}

				// If cluster array empty then we set 0 in whereclause query
				if (empty($usersClusters))
				{
					$usersClusters[] = 0;
				}

				$query->where($db->qn('a.cluster_id') . ' IN (' . implode(",", $usersClusters) . ')');
				*/
			}
		}

		// Filter by published state
		// DPE hack to show published records - This can get removed once we fix issue in core aboute state field value
		$published = $this->getState('filter.published');

		if(!$published)
		{
			$published = $this->getState('filter.state', '');

			if (is_numeric($published))
			{
				$query->where($db->qn('a.state') . ' = ' . (INT) $published);
			}
			elseif ($published === '')
			{
				$query->where(($db->qn('a.state') . ' IN (0, 1)'));
			}

			// Filter by draft status
			$draft = $this->getState('filter.draft');

			if (in_array($draft, array('0', '1')))
			{
				$query->where($db->qn('a.draft') . ' = ' . $draft);
			}
		}
		else
		{
			$query->where($db->qn('a.draft') . ' = 0');
			$query->where($db->qn('a.state') . ' = 1');
		}

		// Search by content id
		$search = $this->getState($client . '.filter.search');

		if (!empty($search))
		{
			$search = $db->escape(trim($search), true);

			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('a.id') . ' = ' . (int) str_replace('id:', '', $search));
			}
		}

		$fromDate = $this->getState($client . '.filter.fromDate');
		$toDate = $this->getState($client . '.filter.toDate');

		if (!empty($fromDate) || !empty($toDate))
		{
			$query->where('DATE(' . $db->qn('a.created_date') . ') ' . ' BETWEEN ' . $db->q($fromDate) . ' AND ' . $db->q($toDate));
		}
		// Search on fields data
		$this->filterContent($client, $query);

		// Filter by tags

		$agencyTag = array_filter((array) $this->getState('filter.tags'));  // DPE HACK

		if (is_array($agencyTag))
		{
			foreach($agencyTag as $key => $agencyTags)
			{

				if (!is_int($agencyTags))
				{ 
					$agencyTag[$key] = (int) $agencyTags;
				}
			}
		}

		if (!empty($agencyTag) && $user->authorise('core.manageall', 'com_cluster'))
		{	
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
			$clusterIdsByTags = $dashBoardModel->getClusterIdsByTags($agencyTag);
		}
		else
		{
			// Filter by cluster
			$clusterId = (int) $this->getState($client . '.filter.cluster_id');
		}
		
		if ($clusterId || $clusterIdsByTags )
		{	
			if (is_array($clusterIdsByTags))
			{ 
				$clusterId = ($clusterIdsByTags) ? $clusterIdsByTags : $clusterId;	
				$query->where($db->qn('a.cluster_id') . ' IN (' . implode(',', $clusterId) .')');
			}
			else
			{
				$clusterId = ($clusterIdsByTags) ? $clusterIdsByTags : $clusterId;	
				$query->where($db->qn('a.cluster_id') . '=' . (INT) $clusterId );
			}

		}

		//Filter By numeric calculation
		if ($this->getState($client . '.filter.numeirccalculation'))
		{
			$numericCalcualtion = explode('-',$this->getState($client . '.filter.numeirccalculation'));
			$numericdb    = $this->getDbo();
			$numericSubQuery  = $numericdb->getQuery(true);							
			$calcualtionId = array();

			foreach ($this->fields as $fieldId => $field)
			{ 
				if ($field->type == 'numericcalculation')
				{	
					$numericSubQuery->select('content_id');
					
					if($client == 'com_tjucm.rop'){
						$numericSubQuery->from($numericdb->qn('#__tjfields_fields_value_flat', 'fvs'));
					}else
					{
						$numericSubQuery->from($numericdb->qn('#__tjfields_fields_value', 'fvs'));
					}
					$numericSubQuery->where($numericdb->qn('fvs.field_id') . '='.(int) $fieldId);
					$numericSubQuery->where($numericdb->qn('fvs.value') . '>='. (int) $numericCalcualtion[0]);
					$numericSubQuery->where($numericdb->qn('fvs.value') . '<='.(int) $numericCalcualtion[1]);
					$numericdb->setQuery($numericSubQuery);
					$calcualtionId = $numericdb->loadColumn(); 				
				}
			}

			$calcualtionId = implode(',',$calcualtionId);
			($calcualtionId)?$query->where($db->qn('a.id') . 'IN (' .$calcualtionId.')' ):$query->where($db->qn('a.id') . '= 0' );
		}

		// Filter by category
		$categoryId = (int) $this->getState($client . '.filter.category_id');

		if ($categoryId)
		{
			$query->where($db->qn('a.category_id') . ' = ' . $categoryId);
		}

		$query->group($db->qn('a.id'));

		// Sort data
		$this->sortContent($query);
	    $query->order('a.id DESC'); //DPE hack
// echo $query->dump();
	return $query;
}

	/**
	 * Get list items
	 *
	 * @return  ARRAY
	 *
	 * @since    1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();

		// Get id of multi-select fields
		$contentIds = array_column($items, 'id');
		$fieldValues = $this->getFieldsData($contentIds);

		$client = htmlentities($this->getState('ucm.client'));

		if (!empty($contentIds) && !empty($client))
		{
			// Get fields which can have multiple values
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn('id'));
			$query->from($db->qn('#__tjfields_fields'));
			$query->where($db->qn('client') . ' = ' . $db->q($client));

			// DPE Hack to add assignee field type in "IN clause"
			$query->where($db->qn('type') . ' IN("multi_select", "tjlist", "assignee")');
			$query->where('(' . $db->qn('params') . ' LIKE ' . $db->q('%multiple":"true%') . ' OR ' . $db->qn('params') . ' LIKE ' . $db->q('%multiple":"1%') . ')');
			$db->setQuery($query);
			$fieldsList = $db->loadColumn();

			if (!empty($fieldsList) && ($client != 'com_tjucm.rop'))			{
				// Get fields which can have multiple values
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->qn(array('content_id', 'field_id', 'value')));
				if($client == 'com_tjucm.rop'){
					$query->from($db->qn('#__tjfields_fields_value_flat'));
				}else
				{
					$query->from($db->qn('#__tjfields_fields_value'));
				} 				
				$query->where($db->qn('content_id') . ' IN(' . implode(', ', $contentIds) . ')');
				$query->where($db->qn('field_id') . ' IN(' . implode(', ', $fieldsList) . ')');
				$db->setQuery($query);
				$multiSelectValues = $db->loadObjectList();

				$mappedData = array();

				foreach ($multiSelectValues as $multiSelectValue)
				{
					$mappedData[$multiSelectValue->content_id][$multiSelectValue->field_id][] = $multiSelectValue->value;
				}

				foreach ($items as $k => &$item)
				{
					$item = (ARRAY) $item;

					foreach ($mappedData as $contentId => $mappedContentData)
					{
						if ($contentId == $item['id'])
						{
							foreach ($mappedContentData as $fieldId => $value)
							{
								$item[$fieldId] = $value;
							}

							unset($mappedContentData[$contentId]);
						}
					}

					$item = (OBJECT) $item;
				}

			}
		}

		return $items;
	}

	/**
	 * Function to sort content as per field values
	 *
	 * @param   OBJECT  &$query  query object
	 *
	 * @return  VOID
	 *
	 * @since    1.2.1
	 */
	private function sortContent(&$query)
	{
		$db = Factory::getDbo();

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn && (!is_numeric($orderCol) || array_key_exists($orderCol, $this->fields)))
		{
			if ($this->specialSortableFields[$orderCol]->type == 'itemcategory')
			{
				$query->select($db->qn('c.title', 'itemcategorytitle'));

				// Join over category table
				$query->join(
					"LEFT", $db->qn('#__categories', 'c') . ' ON (' . $db->qn('a.category_id') . ' = ' . $db->qn('c.id') . ')'
				);

				$query->order($db->escape($db->qn('itemcategorytitle') . ' ' . $orderDirn));
			}
			elseif ($this->specialSortableFields[$orderCol]->type == 'cluster')
			{
				$query->select($db->qn('cl.name', 'clustertitle'));

				// Join over cluster table
				$query->join(
					"LEFT", $db->qn('#__tj_clusters', 'cl') . ' ON (' . $db->qn('a.cluster_id') . ' = ' . $db->qn('cl.id') . ')'
				);

				$query->order($db->escape($db->qn('clustertitle') . ' ' . $orderDirn));
			}
			elseif ($this->specialSortableFields[$orderCol]->type == 'ownership')
			{
				$query->select($db->qn('u.name', 'ownershiptitle'));

				// Join over user table
				$query->join(
					"LEFT", $db->qn('#__users', 'u') . ' ON (' . $db->qn('a.created_by') . ' = ' . $db->qn('u.id') . ')'
				);

				$query->order($db->escape($db->qn('ownershiptitle') . ' ' . $orderDirn));
			}
			else
			{
				$query->order($db->escape($db->qn($orderCol) . ' ' . $orderDirn));
			}
		}
	}

	/**
	 * Function to filter content as per field values
	 *
	 * @param   string  $client  Client
	 * 
	 * @param   OBJECT  &$query  query object
	 *
	 * @return  VOID
	 *
	 * @since    1.2.1
	 */
	private function filterContent($client, &$query)
	{
		$app  = Factory::getApplication();
		$db = $this->getDbo();
		$subQuery = $db->getQuery(true);
		$subQuery->select(1);
		if($client == 'com_tjucm.rop'){
			$subQuery->from($db->qn('#__tjfields_fields_value_flat', 'v'));

		}else
		{
			$subQuery->from($db->qn('#__tjfields_fields_value', 'v'));
		}
		$filterProcessCategory = $app->input->get('process_category');

		// Flag to mark if field specific search is done from the search box
		$filterFieldFound = 0;

		// Variable to store count of the self joins on the fields_value table
		$filterFieldsCount = 0;

		// Filter by field value
		$search = $this->getState($client . '.filter.search');

		if (!empty($this->fields) && (stripos($search, 'id:') !== 0))
		{
			foreach ($this->fields as $fieldId => $field)
			{
				// For field specific search
				if (stripos($search, $field->label . ':') === 0)
				{
					$filterFieldsCount++;

					$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value_flat', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
						'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');

					if($client == 'com_tjucm.rop'){
						$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value_flat', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
							'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
					}else{
						$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
							'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
					}

					$search = trim(str_replace($field->label . ':', '', $search));
					$subQuery->where($db->qn('v' . $filterFieldsCount . '.field_id') . ' = ' . $fieldId);
					$subQuery->where($db->qn('v' . $filterFieldsCount . '.value') . ' LIKE ' . $db->q('%' . $search . '%'));
					$filterFieldFound = 1;

					break;
				}
			}
		}

		// For generic search
		if ($filterFieldFound == 0 && !empty($search)  && (stripos($search, 'id:') !== 0))
		{
			$filterFieldsCount++;

			if($client == 'com_tjucm.rop'){
				$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value_flat', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
					'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
			}else{
				$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
					'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
			}
			$subQuery->where($db->qn('v' . $filterFieldsCount . '.value') . ' LIKE ' . $db->q('%' . $search . '%'));
		}

		// For filterable fields
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$fieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$fieldsModel->setState('filter.client', $client);
		$fieldsModel->setState('filter.filterable', 1);
		$fields = $fieldsModel->getItems();

		// DPE Hack
		$params              = ComponentHelper::getParams('com_dpe');
		$sarrRquestStatus = json_decode($params->get('sarrequestStatus'), true);
		$foiRquestStatus = json_decode($params->get('foirequestStatus'), true);
		$breachRquestStatus = json_decode($params->get('breachStatus'), true);

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$sarfieldTable = Table::getInstance('field', 'TjfieldsTable');
		$sarfieldTable->load(array('id'=>$sarrRquestStatus));

		$foifieldTable = Table::getInstance('field', 'TjfieldsTable');
		$foifieldTable->load(array('id'=>$foiRquestStatus));

		$breachfieldTable = Table::getInstance('field', 'TjfieldsTable');
		$breachfieldTable->load(array('id'=>$breachRquestStatus));


		$sarrRquestStatusName = $sarfieldTable->name;
		$foiRquestStatusName = $foifieldTable->name;
		$breachRquestStatusName = $breachfieldTable->name;


		foreach ($fields as $field)
		{
			$filterValue = $this->getState('filter.field.' . $field->name);
			
			// DPE hack can't go core
			if($field->name == $sarrRquestStatusName || $field->name == $foiRquestStatusName || $field->name == $breachRquestStatusName )	{
				$filterValue = $this->getState('filter.field.' . $field->name.'[]');
			}

			$filteroptionId = $this->getState('filter.field.' . $field->name . '.optionId');

			if ($filterValue != '' || $filteroptionId)
			{
				$filterFieldsCount++;

				

				if($client == 'com_tjucm.rop'){
					$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value_flat', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
						'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
				}else{
					$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value', 'v' . $filterFieldsCount) . ' ON (' . $db->qn('v' .
						'.content_id') . ' = ' . $db->qn('v' . $filterFieldsCount . '.content_id') . ')');
				}
				$subQuery->where($db->qn('v' . $filterFieldsCount . '.field_id') . ' = ' . $field->id);

				if ($filteroptionId)
				{
					// Check option id blank or null
					if ($filteroptionId == 'other')
					{
						$subQuery->where('(' . $db->qn('v' . $filterFieldsCount . '.option_id') .
							' is null OR ' . $db->qn('v' . $filterFieldsCount . '.option_id') . ' = 0 )');
					}
					else
					{
						$subQuery->where($db->qn('v' . $filterFieldsCount . '.option_id') . ' = ' . $db->q($filteroptionId));
					}
				}
				else
				{
					
					if(is_array($filterValue))
					{
						// DPE hack
						if($field->name == 'com_tjucm_breachlog_breachstatus' || $field->name == 'com_tjucm_sarlog_requeststatus' || $field->name == 'com_tjucm_FOIlog_requeststatus' ){
						}else{
							$filterValue = implode(" ",  $filterValue);
						}
					}
					

					// DPE Hack to show all view of roplist
					if (!is_array($filterValue) && str_contains( $filterValue, 'AllProcess')) 
					{
						$filterValue = explode(',',$filterValue);

						Unset($filterValue[0]);
					}

					// DPE Hack - Can go in core

					if (is_array($filterValue))
					{
						// @README https://stackoverflow.com/questions/9618277/how-to-use-php-array-with-sql-in-operator
						$filterValue = array_map(
							function ($a){
								return Factory::getDbo()->escape($a);
							}, $filterValue
						);

						$filterValue = implode("','", $filterValue);

						if (!empty($filterValue))
						{
							$subQuery->where($db->qn('v' . $filterFieldsCount . '.value') . ' IN (\'' . $filterValue . '\')');
						}
					}
					else
					{
						if (substr($filterValue, 0, 1) === ' ') {
							$filterValue = ltrim($filterValue);
						}
						$subQuery->where($db->qn('v' . $filterFieldsCount . '.value') . ' = ' . $db->q($filterValue));
					}
				}
			}
		}

		// DPE Hack to show the High Level process
		if ($filterProcessCategory)
		{
			$subQueryCategory = $db->getQuery(true)
			->select('1');

			if ($client == 'com_tjucm.rop') {
				$subQueryCategory
				->from($db->quoteName('#__tjfields_fields_value_flat', 'v'))
				->leftJoin(
					$db->quoteName('#__tjfields_fields_value_flat', 'v1') .
					' ON (' . $db->quoteName('v.content_id') . ' = ' . $db->quoteName('v1.content_id') . ')'
				);
			} else {
				$subQueryCategory
				->from($db->quoteName('#__tjfields_fields_value', 'v'))
				->leftJoin(
					$db->quoteName('#__tjfields_fields_value', 'v1') .
					' ON (' . $db->quoteName('v.content_id') . ' = ' . $db->quoteName('v1.content_id') . ')'
				);
			}

			$subQueryCategory
			->where($db->quoteName('v1.field_id') . ' = 182')
			->where($db->quoteName('v1.value') . ' = ' . $db->quote('High Level'))
			->where($db->quoteName('v.content_id') . ' = ' . $db->quoteName('a.id'));


			$query->where('EXISTS (' . $subQueryCategory . ')');
		}
		// DPE Hack End

		if ($filterFieldsCount > 0)
		{
			$subQuery->where($db->qn('v.content_id') . '=' . $db->qn('a.id'));
			
			$query->where("EXISTS (" . $subQuery . ")");
			
			
		}


	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getFields()
	{
		
		// Load fields model
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$fieldsModel = JModelLegacy::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));

		$fieldsModel->setState('filter.showonlist', 1);
		$fieldsModel->setState('filter.state', 1);
		$fieldsModel->setState('filter.showonlist', 1);
		$fieldsModel->setState('list.ordering', 'ordering');
		$fieldsModel->setState('list.direction', 'ASC');
		$client = htmlentities($this->getState('ucm.client'));

		if (!empty($client))
		{
			$fieldsModel->setState('filter.client', $client);
		}

		$items = $fieldsModel->getItems();

		foreach ($items as $item)
		{
			if (in_array($item->type, array('itemcategory', 'cluster', 'ownership')))
			{
				$this->specialSortableFields[$item->id] = $item;
			}

			$this->fields[$item->id] = $item;
		}

		return $this->fields;
	}

	/**
	 * Method to fields data for given content Ids
	 *
	 * @param   array  $contentIds  An array of record ids.
	 *
	 * @return  ARRAY  Fields data if successful, false if an error occurs.
	 *
	 * @since   1.2.1
	 */
	private function getFieldsData($contentIds)
	{
		$contentIds = implode(',', $contentIds);

		if (empty($contentIds))
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		if ($client == 'com_tjucm.rop'){
			$query->from($db->qn('#__tjfields_fields_value_flat', 'fv'));

		}else{
			$query->from($db->qn('#__tjfields_fields_value', 'fv'));

		}

		$query->join('INNER', $db->qn('#__tjfields_fields', 'f') . ' ON (' . $db->qn('f.id') . ' = ' . $db->qn('fv.field_id') . ')');
		$query->where($db->qn('f.state') . '=1');
		$query->where($db->qn('fv.content_id') . ' IN (' . $contentIds . ')');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Check if there are fields to show in list view
	 *
	 * @param   string  $client  Client
	 *
	 * @return boolean
	 */
	public function showListCheck($client)
	{
		if (!empty($client))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select("count(" . $db->qn('id') . ")");
			$query->from($db->qn('#__tjfields_fields'));
			$query->where($db->qn('client') . '=' . $db->q($client));
			$query->where($db->qn('showonlist') . '=1');
			$db->setQuery($query);

			$result = $db->loadResult();

			return $result;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to check the compatibility between ucm types
	 *
	 * @param   string  $client  Client
	 * 
	 * @return  mixed
	 * 
	 * @since    __DEPLOY_VERSION__
	 */
	public function canCopyToSameUcmType($client)
	{
		JLoader::import('components.com_tjucm.models.types', JPATH_ADMINISTRATOR);
		$typesModel = BaseDatabaseModel::getInstance('Types', 'TjucmModel');
		$typesModel->setState('filter.state', 1);
		$ucmTypes 	= $typesModel->getItems();

		JLoader::import('components.com_tjucm.models.type', JPATH_ADMINISTRATOR);
		$typeModel = BaseDatabaseModel::getInstance('Type', 'TjucmModel');

		$checkUcmCompatability = false;

		foreach ($ucmTypes as $key => $type)
		{
			if ($client != $type->unique_identifier)
			{
				$result = $typeModel->getCompatibleUcmTypes($client, $type->unique_identifier);

				if ($result)
				{
					$checkUcmCompatability = true;
				}
			}
		}

		JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
		$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', Factory::getDbo()));
		$fieldTable->load(array('client' => $client, 'type' => 'cluster', 'state' => '1'));

		if (!$checkUcmCompatability && !$fieldTable->id)
		{
			return true;
		}

		return false;
	}
}
