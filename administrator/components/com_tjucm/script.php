<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Updates the database structure of the component
 *
 * @version  Release: 0.2b
 * @author   Component Creator <support@component-creator.com>
 * @since    0.1b
 */
class Com_TjucmInstallerScript
{
	/**
	 * Minimum Joomla version required to install the extension
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $minimumJoomla = '6.0';

	/**
	 * Minimum PHP version required to install the extension
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $minimumPhp = '7.4';

	/**
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string  $type    Type of process [install | update]
	 * @param   mixed   $parent  Object who called this method
	 *
	 * @return boolean True if the process should continue, false otherwise
	 */
	public function preflight($type, $parent)
	{
		// Check minimum Joomla version
		if (!version_compare(JVERSION, $this->minimumJoomla, 'ge'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'JLIB_INSTALLER_MINIMUM_JOOMLA',
					$this->minimumJoomla
				),
				'error'
			);

			return false;
		}

		// Check minimum PHP version
		if (version_compare(PHP_VERSION, $this->minimumPhp, 'lt'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'JLIB_INSTALLER_MINIMUM_PHP',
					$this->minimumPhp
				),
				'error'
			);

			return false;
		}

		return true;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 *
	 * @since 0.2b
	 */
	public function install($parent)
	{
	}

	/**
	 * Method to update the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
	}
}
