<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Subusers model.
 *
 * @since  1.0.0
 */
class SubusersModelRole extends AdminModel
{
	/**
	 * @var null  Item data
	 * @since  1.0.0
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    Table    A database object
	 *
	 * @since    1.0.0
	 */
	public function getTable($type = 'Role', $prefix = 'SubusersTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form  A Form object on success, false on failure
	 *
	 * @since    1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_subusers.role', 'role',
			array('control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since    1.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_subusers.edit.role.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $roleId  The id of the roleId.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since    1.0.0
	 */
	public function getActions($roleId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('action');
		$query->from($db->quoteName('#__tjsu_role_action_map'));
		$query->where($db->quoteName('role_id') . " = " . $db->quote($roleId));
		$db->setQuery($query);
		$actions = $db->loadColumn();

		return $actions;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   array  $data  The id of the roleId.
	 *
	 * @return  boolean    true on success, false on failure.
	 *
	 * @since    1.0.0
	 */
	public function save($data)
	{

		$data['modified_by'] = Factory::getUser()->id;
		
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('role.id');

		$role = SubusersRole::getInstance($pk);

// Joomla 4 changes for dpe
		$data['checked_out'] = ($data['checked_out'])?$data['checked_out']:0;
		$data['checked_out_time'] = ($data['checked_out_time'])?$data['checked_out']:'0000-00-00 00:00:00';
		$data['state'] = ($data['state'])?$data['state']:0;



		// Bind the data.
		if (!$role->bind($data))
		{
			$this->setError($role->getError());

			return false;
		}

		$result = $role->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($role->getError());

			return false;
		}

		$this->setState('role.id', $role->id);

		return true;
	}

	/**
	 * Method to get a roles by actions.
	 *
	 * @param   array   $actions   array of action code.
	 * @param   string  $client    name of action client.
	 * @param   int     $clientId  client id.
	 *
	 * @return  array  indexed array of associated arrays.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getAuthorizeRoles($actions = array(), $client = null, $clientId = null)
	{
		if (!empty($actions))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(('id')));
			$query->from($db->quoteName('#__tjsu_actions'));

			if ($client)
			{
				$query->where($db->quoteName('client') . ' = ' . $db->quote($client));
			}

			foreach ($actions as $action)
			{
				$query->where($db->quoteName('code') . ' = ' . $db->quote($action));
			}

			$db->setQuery($query);

			$actionIds = $db->loadColumn();

			// Get role ids by providing action ids
			$actionModel = RBACL::model("action");

			return $actionModel->getAssignedRoles($actionIds);
		}
	}
}
