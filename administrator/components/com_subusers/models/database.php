<?php
/**
 * @package     Subusers
 * @subpackage  com_subusers
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Schema\ChangeSet;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/database.php';

/**
 * Jlike Manage Model
 *
 * @since  1.6
 */
class SubusersModelDatabase extends InstallerModelDatabase
{
	/**
	 * Gets the changeset object.
	 *
	 * @return  ChangeSet
	 */
	public function getItems()
	{
		$folder = JPATH_ADMINISTRATOR . '/components/com_subusers/sql/updates/';

		try
		{
			$changeSet = ChangeSet::getInstance($this->getDbo(), $folder);
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return false;
		}
		return $changeSet;
	}
}
