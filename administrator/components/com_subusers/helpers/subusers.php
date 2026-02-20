<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Subusers
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;

/**
 * Subusers helper.
 *
 * @since  1.6
 */
class SubusersHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  string
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		Sidebar::addEntry(
					Text::_('COM_SUBUSERS_TITLE_ROLES'),
					'index.php?option=com_subusers&view=roles',
					$vName == 'roles'
				);
		Sidebar::addEntry(
					Text::_('COM_SUBUSERS_TITLE_ACTIONS'),
					'index.php?option=com_subusers&view=actions',
					$vName == 'actions'
				);
		Sidebar::addEntry(
					Text::_('COM_SUBUSERS_TITLE_USERS'),
					'index.php?option=com_subusers&view=users',
					$vName == 'users'
				);
		Sidebar::addEntry(
			Text::_('COM_SUBUSERS_TITLE_MAPPINGS'),
			'index.php?option=com_subusers&view=mappings',
			$vName == 'mappings'
		);
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    CMSObject
	 *
	 * @since    1.6
	 */
	public static function getActions()
	{
		$user   = Factory::getUser();
		$result = new CMSObject;

		$assetName = 'com_subusers';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
