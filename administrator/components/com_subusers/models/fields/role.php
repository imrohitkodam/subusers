<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of Roles
 *
 * @since  1.0.0
 */
class JFormFieldRole extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since    1.0.0
	 */
	protected $type = 'role';

	/**
	 * Method to get list of role options.
	 *
	 * @return   array role An array of HTMLHelper options
	 *
	 * @since    1.0.0
	 */
	protected function getOptions()
	{
		$jinput = Factory::getApplication()->getInput();
		$client = $jinput->get('client');

		// Include models
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/models');

		// Get instance of model class, where class name will be SubusersModel
		$subuserModelType = BaseDatabaseModel::getInstance('Roles', 'SubusersModel');
		$subuserModelType->setState('filter.client', $client);

		$results = $subuserModelType->getItems();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_SUBUSERS_FORM_LBL_SELECT_ROLE'));

		if ($results)
		{
			foreach ($results as $result)
			{
				$options[] = HTMLHelper::_('select.option', $result->id, $result->name);
			}
		}

		return $options;
	}
}
