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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;

jimport('joomla.application.component.view');

/**
 * View class for a list of Subusers.
 *
 * @since  1.6
 */
class SubusersViewRoles extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		SubusersHelper::addSubmenu('roles');

		$this->addToolbar();

		$this->sidebar = Sidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_subusers/helpers/subusers.php';

		$state = $this->get('State');
		$canDo = SubusersHelper::getActions($state->get('filter.category_id'));

		ToolbarHelper::title(Text::_('COM_SUBUSERS_TITLE_ROLES'), 'roles.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_ADMINISTRATOR . '/components/com_subusers/views/role';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::addNew('role.add', 'JTOOLBAR_NEW');
				ToolbarHelper::custom('roles.duplicate', 'copy.png', 'copy_f2.png', 'JTOOLBAR_DUPLICATE', true);
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				ToolbarHelper::editList('role.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('roles.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('roles.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				ToolbarHelper::deleteList('', 'roles.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->state))
			{
				ToolbarHelper::divider();
				ToolbarHelper::archiveList('roles.archive', 'JTOOLBAR_ARCHIVE');
			}

			if (isset($this->items[0]->checked_out))
			{
				ToolbarHelper::custom('roles.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				ToolbarHelper::deleteList('', 'roles.delete', 'JTOOLBAR_EMPTY_TRASH');
				ToolbarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				ToolbarHelper::trash('roles.trash', 'JTOOLBAR_TRASH');
				ToolbarHelper::divider();
			}
		}

		if ($canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'roles.delete', 'JTOOLBAR_DELETE');
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_subusers');
		}

		// Set sidebar action - New in 3.0
		Sidebar::setAction('index.php?option=com_subusers&view=roles');

		$this->extra_sidebar = '';
		Sidebar::addFilter(

			Text::_('JOPTION_SELECT_PUBLISHED'),

			'filter_published',

			HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), "value", "text", $this->state->get('filter.state'), true)

		);
	}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`name`' => Text::_('COM_SUBUSERS_ROLES_NAME')
		);
	}
}
