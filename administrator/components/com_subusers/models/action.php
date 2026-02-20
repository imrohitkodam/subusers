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
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;

/**
 * Subusers model.
 *
 * @since  1.0.0
 */
class SubusersModelAction extends AdminModel
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
	public function getTable($type = 'Action', $prefix = 'SubusersTable', $config = array())
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
			'com_subusers.action', 'action',
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
		$data = Factory::getApplication()->getUserState('com_subusers.edit.action.data', array());

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
	 * Method to load the all role id that are allowed to perform given action
	 *
	 * @param   integer  $actionId  The action id
	 *
	 * @return  array    The array Of role ids
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAssignedRoles($actionId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT role_id');
		$query->from($db->quoteName('#__tjsu_role_action_map'));

		if (is_array($actionId))
		{
			$query->where($db->quoteName('action_id') . 'IN (' . implode(',', $db->quote($actionId)) . ')');
		}
		else
		{
			$query->where($db->quoteName('action_id') . " = " . (int) $actionId);
		}

		$query->where($db->quoteName('state') . " = " . 1);

		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   array  $data  The id of the actionId.
	 *
	 * @return  boolean    true on success, false on failure.
	 *
	 * @since    1.0.0
	 */
	public function save($data)
	{
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('action.id');

		$data['created_by'] = Factory::getUser()->id;
		$data['checked_out'] = Factory::getUser()->id;
        $data['checked_out_time'] = new Date('now').'.000000'; 
        $data['ordering']= 0 ;
        $data['state']= 1 ;

		$action = SubusersAction::getInstance($pk);

		// Bind the data.
		if (!$action->bind($data))
		{
			$this->setError($action->getError());

			return false;
		}

		$result = $action->save();


		// Store the data.
		if (!$result)
		{
			$this->setError($action->getError());

			return false;
		}

		$this->setState('action.id', $action->id);

		return true;
	}
}
