<?php
/**
 * @package    Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Document controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmControllerDocument extends FormController
{
	/**
	 * Reset All filters for the document
	 *
	 * @return    boolean  True on success.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function resetParams()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$canDo      = TjucmHelper::getActions();
		$documentId = $app->input->get('id', 0, 'INT');

		if (!$documentId || !$canDo->get('core.create'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$model = $this->getModel();

		if ($model->resetParams())
		{
			$this->setMessage(Text::_('COM_TJUCM_DOCUMENT_RESET_PARAMS'));
			$this->setRedirect(Route::_('index.php?option=com_tjucm&view=document&layout=edit&id=' . $documentId, false));

			return true;
		}

		$this->setMessage(Text::_('COM_TJUCM_DOCUMENT_UNABLE_TO_RESET_PARAMS', 'error'));
		$this->setRedirect(Route::_('index.php?option=com_tjucm&view=document&layout=edit&id=' . $documentId, false));

		return false;
	}
}
