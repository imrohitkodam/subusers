<?php
/**
 * @package    Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

/**
 * Document Table class
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmTableDocument extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_ucm_documents', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate()->toSql();
		$user = Factory::getUser();

		if ($this->id)
		{
			$this->modified_by = $user->id;
			$this->modified_date = $date;
		}
		else
		{
			$this->created_date = $date;
			$this->created_by = $this->modified_by = $user->id;
		}

		// Attempt to store the data.
		return parent::store($updateNulls);
	}
}
