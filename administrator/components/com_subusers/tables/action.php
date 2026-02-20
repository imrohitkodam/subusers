<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * role Table class
 *
 * @since  1.0.0
 */
class SubusersTableaction extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjsu_actions', 'id', $db);
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();

		if (!$this->id)
		{
			$this->created_date = $date->toSql();
		}

		return parent::store($updateNulls);
	}
}
