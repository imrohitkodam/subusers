<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tuucm
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\Data\DataObject;
use Joomla\CMS\Table\Table;

/**
 * TJUCM_Data Table class
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmTabledata extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbaseDriver  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_ucm_data', 'id', $db);
	}
}
