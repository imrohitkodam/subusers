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
class SubusersModelUser extends AdminModel
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
	public function getTable($type = 'User', $prefix = 'SubusersTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');

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
			'com_subusers.user', 'user',
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
		$data = Factory::getApplication()->getUserState('com_subusers.edit.user.data', array());

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
	 * This method will return the role id of given client content
	 *
	 * @param   integer  $userId     Id of the user for which to check authorisation.
	 * @param   string   $client     The name of the client to authorise. com_content
	 * @param   integer  $contentId  The content key. null check with role and allowed actions.
	 *
	 * @return  integer  The role id
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAssociatedContentRole($userId, $client, $contentId = null)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('role_id');
		$query->from($db->quoteName('#__tjsu_users'));
		$query->where($db->quoteName('user_id') . " = " . (int) $userId);
		$query->where($db->quoteName('client') . " = " . $db->q($client));

		if (!is_null($contentId))
		{
			$query->where($db->quoteName('client_id') . " = " . $db->quote($contentId));
		}

		$db->setQuery($query);

		return $db->loadColumn();
	}
}
