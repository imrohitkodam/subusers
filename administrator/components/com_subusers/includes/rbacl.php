<?php
/**
 * @package     Subusers
 * @subpackage  com_subusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

JLoader::discover("Subusers", JPATH_ADMINISTRATOR . '/components/com_subusers/libraries');

/**
 * Subusers factory class.
 *
 * This class perform the helpful operation for subuser
 *
 * @since  __DEPLOY_VERSION__
 */
class RBACL
{
	/**
	 * Array of loaded user roles
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $roles = array();

	/**
	 * Retrieves a table from the table folder
	 *
	 * @param   string  $name  The table file name
	 *
	 *
	 * @since   __DEPLOY_VERSION__
	 **/
	public static function table($name)
	{
		// @TODO Improve file loading with specific table file.

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');

		// @TODO Add support for cache
		return Table::getInstance($name, 'SubusersTable');
	}

	/**
	 * Retrieves a model from the model folder
	 *
	 * @param   string  $name    The model name to instantiate
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 *
	 * @since   __DEPLOY_VERSION__
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/models', 'SubusersModel');

		// @TODO Add support for cache
		return BaseDatabaseModel::getInstance($name, 'SubusersModel', $config);
	}

	/**
	 * Method to check if a user is authorised to perform an action, optionally on an content.
	 *
	 * @param   integer  $userId        Id of the user for which to check authorisation.
	 * @param   string   $client        The name of the client to authorise. com_content
	 * @param   string   $action        The name of the action to authorise. Eg. core.edit
	 * @param   string   $actionClient  The name of the client of action to authorise. Eg. com_content
	 * @param   integer  $contentId     The content key. null check with role and allowed actions.
	 *
	 * @return  boolean         True if allowed, false for an explicit deny, null for an implicit deny.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function check($userId, $client, $action, $actionClient = null, $contentId = null)
	{
		$action = strtolower(preg_replace('#[\s\-]+#', '.', trim($action)));

		$user = Factory::getUser($userId);

		if ($user->id)
		{
			if (empty(trim($actionClient)))
			{
				$actionClient = $client;
			}

			/*
			 * Step 1. Check the action is exist
			 */
			$actionObj = SubusersAction::loadActionByCode($action, $actionClient);

			if ($actionObj->id)
			{
				/*
				 * Step 2. check allowed roles for the action
				 * It is may be more than one
				 */
				$authorizedRoles = $actionObj->getAuthorizedRoles();

				/*
				 * Step 3. Load user assigned roles
				 * It is may be more than one
				 */
				if (!isset(self::$roles[$client][$user->id]))
				{
					self::$roles[$client][$userId] = self::getRoleByUser($user->id, $client);
				}

				/*
				 * Step 4. Is user have right authority to perform this action
				 */
				$allowedRoles = array_intersect($authorizedRoles, self::$roles[$client][$userId]);

				if (!empty($allowedRoles))
				{
					/*
					 * Step 5. If the content id is provided and check for the associated role to it
					 */
					if (empty($contentId))
					{
						return true;
					}

					$userModel = self::model("user");
					$contentRoleId = $userModel->getAssociatedContentRole($userId, $client, $contentId);

					if (!empty(array_intersect($contentRoleId, $allowedRoles)))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * This method will check the core Joomla authorisatoion and RBACL authorisation
	 *
	 * @param   integer  $userId        Id of the user for which to check authorisation.
	 * @param   string   $client        The name of the client to authorise. com_content
	 * @param   string   $action        The name of the action to authorise. Eg. core.edit
	 * @param   string   $actionClient  The name of the client of action to authorise. Eg. com_content
	 * @param   integer  $contentId     The content key. null check with role and allowed actions.
	 *
	 * @return  boolean  True if authorised
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function authorise($userId, $client, $action, $actionClient = null, $contentId = null)
	{
		$client = (string) $client;
		$action = (string) $action;
		$actionClient = (string) $actionClient;

		if (empty(trim($actionClient)))
		{
			$actionClient = $client;
		}

		$user = Factory::getUser($userId);
		$result = $user->authorise($action, $actionClient);

		return $result && self::check($userId, $client, $action, $actionClient, $contentId);
	}

	/**
	 * Get user roles by user id and client id
	 *
	 * @param   integer  $userId           userId
	 * @param   string   $client           client for role
	 * @param   mixed    $clientContentId  content id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getRoleByUser($userId, $client = '', $clientContentId = null)
	{
		$roles = array();

		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT role_id');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->quoteName('user_id') . " = " . $db->quote($userId));

			if (!empty($client))
			{
				$query->where($db->quoteName('client') . " = " . $db->quote($client));
			}

			if (is_array($clientContentId) && !empty($clientContentId))
			{
				$query->where($db->qn('client_id') . ' IN (' . implode(',', $db->q($clientContentId)) . ')');
			}
			elseif (!is_null($clientContentId))
			{
				$query->where($db->quoteName('client_id') . " = " . $db->quote($clientContentId));
			}


			$db->setQuery($query);
			$roles = $db->loadColumn();
		}

		return $roles;
	}
	/**
	 * Get user roles by user id and client id
	 *
	 * @param   integer  $userId           userId
	 * @param   string   $client           client for role
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getAgencyByUser($userId, $client = '')
	{
		$roles = array();

		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT client_id');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->quoteName('user_id') . " = " . $db->quote($userId));

			if (!empty($client))
			{
				$query->where($db->quoteName('client') . " = " . $db->quote($client));
			}

			$db->setQuery($query);
			$roles = $db->loadColumn();
		}

		return $roles;
	}

	/**
	 * Get user roles by user id and client id
	 *
	 * @param   integer  $userId           userId
	 * @param   string   $client           client for role
	 * @param   mixed    $clientContentId  content id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getCoreRoleByUser($userId, $client = '', $clientContentId = null)
	{
		$subusersModelRole = self::model('roles', array('ignore_request' => true));
		$subusersModelRole->setState('filter.client', 'com_multiagency');
		$subusersModelRole->setState('filter.state', 1);
		$coreRoles = $subusersModelRole->getItems();
		$coreRoles = array_column($coreRoles, 'id');

		$roles = array();

		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT role_id');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->quoteName('user_id') . " = " . $db->quote($userId));

			if (!empty($client))
			{
				$query->where($db->quoteName('client') . " = " . $db->quote($client));
			}

			if (is_array($clientContentId) && !empty($clientContentId))
			{
				$query->where($db->qn('client_id') . ' IN (' . implode(',', $db->q($clientContentId)) . ')');
			}
			elseif (!is_null($clientContentId))
			{
				$query->where($db->quoteName('client_id') . " = " . $db->quote($clientContentId));
			}

			if (!empty($coreRoles))
			{
				$query->where($db->qn('role_id') . ' IN (' . implode(',', $db->q($coreRoles)) . ')');
			}

			$db->setQuery($query);
			$roles = $db->loadColumn();
		}

		return $roles;
	}
}
