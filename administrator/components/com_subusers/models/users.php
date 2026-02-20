<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Data\DataObject;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.0.0
 */
class SubusersModelUsers extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.`id`',
				'user_id', 'a.`user_id`',
				'role_id', 'a.`role_id`',
				'client', 'a.`client`',
				'client_id', 'a.`client_id`',
				'created_by', 'a.`created_by`',
				'uc.name'
			);
		}

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
	 */
	protected function populateState($ordering = 'uc.name', $direction = 'ASC')
	{
		$app = Factory::getApplication('administrator');

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$this->setState('filter.user_id', $app->getUserStateFromRequest($this->context . ' . filter.user_id', 'filter_user_id', '', 'string'));

		$params = ComponentHelper::getParams('com_subusers');
		$this->setState('params', $params);

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return   string A store id.
	 *
	 * @since    1.0.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(array('a.*', 'uc.name','uc.email', 'rl.name as rolename'));
		$query->from('`#__tjsu_users` AS a');
		$query->join('INNER', $db->quoteName('#__users', 'uc') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('uc.id') . ')');
		$query->join('INNER', $db->quoteName('#__tjsu_roles', 'rl') . ' ON (' . $db->quoteName('rl.id') . ' = ' . $db->quoteName('a.role_id') . ')');
		$search = $this->getState('filter.search');

		// If the model is set to check item state, add to the query.
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('uc.block = ' . (int) $state);
		}

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( uc.`name` LIKE ' . $search . '  OR  a.`user_id` LIKE ' . $search . '  OR  a.`client_id` LIKE ' . $search . ' )');
			}
		}

		$roleId = $this->getState('filter.role_id');

		if (!empty($roleId))
		{
			if (is_array($roleId))
			{
				$query->where($db->quoteName('a.role_id') . 'IN (' . implode(',', $db->quote($roleId)) . ')');
			}
			else
			{
				$query->where($db->quoteName('a.role_id') . " = " . (int) $roleId);
			}
		}

		$client = $this->getState('filter.client');

		if (!empty($client))
		{
			$query->where($db->quoteName('a.client') . " = " . $db->quote($client));
		}

		$clientId = $this->getState('filter.client_id');

		if (!empty($clientId))
		{
			if (is_array($clientId))
			{
				$query->where($db->quoteName('a.client_id') . 'IN (' . implode(',', $db->quote($clientId)) . ')');
			}
			else
			{
				$query->where($db->quoteName('a.client_id') . " = " . (int) $clientId);
			}
		}

		// Filter will send single user_id or array of user_ids
		$userId = $this->getState('filter.user_id');

		if (!empty($userId))
		{
			if (is_array($userId))
			{
				$query->where($db->quoteName('a.user_id') . 'IN (' . implode(',', $db->quote($userId)) . ')');
			}
			else
			{
				$query->where($db->quoteName('a.user_id') . " = " . (int) $userId);
			}
		}

		$groupBy = $this->getState('group_by');

		if (!empty($groupBy))
		{
			$query->group($db->quoteName('a.' . $groupBy));
		}

		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
