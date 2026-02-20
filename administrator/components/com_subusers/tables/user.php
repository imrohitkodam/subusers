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
use Joomla\CMS\Date\Date;

/**
 * user Table class
 *
 * @since  1.0.0
 */
class SubusersTableuser extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjsu_users', 'id', $db);
	}

	/**
	 * Overloaded check function
	 *
	 * @return boolean
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		return parent::check();
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
		$user = Factory::getUser();
		$checkoutDate = new Date('now');		
		$checkoutDate = $checkoutDate->toSQL();
		
		if ($this->id)
		{
			$this->modified_by = $user->id;
			$this->modified_date = $date->toSql();
			$this->modified_by = $user->id;
			$this->modified_date = $checkoutDate;
		}
		else
		{
			$this->created_by = $user->id;
			$this->created_date = $date->toSql();
			$this->modified_by = 0;
			$this->modified_date = '0000-00-00 00:00:00';
		}
		

		$this->checked_out = $user->id;
        $this->checked_out_time  = $checkoutDate; 
        $this->state = 1 ;

		return parent::store($updateNulls);
	}
}
