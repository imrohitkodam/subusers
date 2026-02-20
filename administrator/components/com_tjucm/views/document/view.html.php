<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
/**
 * View to edit
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmViewDocument extends HtmlView
{
	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The Form object
	 *
	 * @var  Form
	 */
	protected $form;

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
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = TjucmHelper::getActions();
		$toolbar = Toolbar::getInstance('toolbar');

		ToolBarHelper::title(Text::_('COM_TJUCM_TITLE_TYPE'), 'type.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolBarHelper::apply('document.apply', 'JTOOLBAR_APPLY');
			ToolBarHelper::save('document.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			ToolBarHelper::custom('document.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			$button = '<button onclick="Joomla.submitbutton(' . "'document.resetParams'" . ');"
class="btn btn-small button-list"><span class="icon-undo" aria-hidden="true"></span>' .
Text::_('COM_TJUCM_DOCUMENT_RESET_BUTTON_TEXT') . '</button>';
			$toolbar->appendButton('Custom', $button);
		}

		if (empty($this->item->id))
		{
			ToolBarHelper::cancel('document.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolBarHelper::cancel('document.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
