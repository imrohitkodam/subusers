<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Version;

/**
 * Updates the structure of the component
 *
 * @since  1.0.0
 */
class Com_SubusersInstallerScript
{
	/**
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string $type   Type of process [install | update]
	 * @param   mixed  $parent Object who called this method
	 *
	 * @return boolean True if the process should continue, false otherwise
	 */
	public function preflight($type, $parent)
	{
		$jversion = new Version;

		// Installing component manifest file version
		$manifest = $parent->getManifest();
		$release  = (string) $manifest['version'];

		// Abort if the component wasn't build for the current Joomla version
		if (!$jversion->isCompatible($release))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('This component is not compatible with installed Joomla version'),
				'error'
			);

			return false;
		}

		return true;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function install($parent)
	{
		$this->installPlugins($parent);
		$this->installModules($parent);
	}


	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installPlugins($parent)
	{
		$installationFolder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		$manifest = $parent->getManifest();
		$plugins = $manifest->plugins ?? null;
		
		if (!$plugins)
		{
			return;
		}

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
			{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$path        = $installationFolder . '/plugins/' . $pluginGroup;
				$installer   = new Installer;

				if (!$this->isAlreadyInstalled('plugin', $pluginName, $pluginGroup))
				{
					$result = $installer->install($path);
				}
				else
				{
					$result = $installer->update($path);
				}

				if ($result)
				{
					$app->enqueueMessage('Plugin ' . $pluginName . ' was installed successfully');
				}
				else
				{
					$app->enqueueMessage('There was an issue installing the plugin ' . $pluginName, 'error');
				}

				$query
					->clear()
					->update('#__extensions')
					->set('enabled = 1')
					->where(
						array(
							'type LIKE ' . $db->quote('plugin'),
							'element LIKE ' . $db->quote($pluginName),
							'folder LIKE ' . $db->quote($pluginGroup)
						)
					);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Check if an extension is already installed in the system
	 *
	 * @param   string $type   Extension type
	 * @param   string $name   Extension name
	 * @param   mixed  $folder Extension folder(for plugins)
	 *
	 * @return boolean
	 */
	private function isAlreadyInstalled($type, $name, $folder = null)
	{
		$result = false;

		switch ($type)
		{
			case 'plugin':
				$result = file_exists(JPATH_PLUGINS . '/' . $folder . '/' . $name);
				break;
			case 'module':
				$result = file_exists(JPATH_SITE . '/modules/' . $name);
				break;
		}

		return $result;
	}

	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installModules($parent)
	{
		$installationFolder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		$manifest = $parent->getManifest();
		$modules = $manifest->modules ?? null;
		
		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				foreach ($modules->children() as $module)
				{
					$moduleName = (string) $module['module'];
					$path       = $installationFolder . '/modules/' . $moduleName;
					$installer  = new Installer;

					if (!$this->isAlreadyInstalled('module', $moduleName))
					{
						$result = $installer->install($path);
					}
					else
					{
						$result = $installer->update($path);
					}

					if ($result)
					{
						$app->enqueueMessage('Module ' . $moduleName . ' was installed successfully');
					}
					else
					{
						$app->enqueueMessage('There was an issue installing the module ' . $moduleName, 'error');
					}
				}
			}
		}
	}

	/**
	 * Method to update the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->installPlugins($parent);
		$this->installModules($parent);
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		$this->uninstallPlugins($parent);
		$this->uninstallModules($parent);
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallPlugins($parent)
	{
		$app     = Factory::getApplication();
		$manifest = $parent->getManifest();
		$plugins = $manifest->plugins ?? null;
		
		if (!$plugins)
		{
			return;
		}

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
			{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$query
					->clear()
					->select('extension_id')
					->from('#__extensions')
					->where(
						array(
							'type LIKE ' . $db->quote('plugin'),
							'element LIKE ' . $db->quote($pluginName),
							'folder LIKE ' . $db->quote($pluginGroup)
						)
					);
				$db->setQuery($query);
				$extension = $db->loadResult();

				if (!empty($extension))
				{
					$installer = new Installer;
					$result    = $installer->uninstall('plugin', $extension);

					if ($result)
					{
						$app->enqueueMessage('Plugin ' . $pluginName . ' was uninstalled successfully');
					}
					else
					{
						$app->enqueueMessage('There was an issue uninstalling the plugin ' . $pluginName, 'error');
					}
				}
			}
		}
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallModules($parent)
	{
		$app = Factory::getApplication();

		$manifest = $parent->getManifest();
		$modules = $manifest->modules ?? null;
		
		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);

				foreach ($modules->children() as $plugin)
				{
					$moduleName = (string) $plugin['module'];
					$query
						->clear()
						->select('extension_id')
						->from('#__extensions')
						->where(
							array(
								'type LIKE ' . $db->quote('module'),
								'element LIKE ' . $db->quote($moduleName)
							)
						);
					$db->setQuery($query);
					$extension = $db->loadResult();

					if (!empty($extension))
					{
						$installer = new Installer;
						$result    = $installer->uninstall('module', $extension);

						if ($result)
						{
							$app->enqueueMessage('Module ' . $moduleName . ' was uninstalled successfully');
						}
						else
						{
							$app->enqueueMessage('There was an issue uninstalling the module ' . $moduleName, 'error');
						}
					}
				}
			}
		}
	}
}
