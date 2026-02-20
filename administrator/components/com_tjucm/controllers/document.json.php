<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

JLoader::import('components.com_tjucm.includes.tjucm', JPATH_SITE);

/**
 * Document controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmControllerDocument extends AdminController
{
	/**
	 * This function provide the list of fields
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getSupportedTags()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$data = $app->input->getArray();

		/** @var $model TjucmModelDocument */
		$model = $this->getModel('document');

		if (!$data['typeId'])
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		$fields = $model->getSupportedTags($data);

		if (!$fields)
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		echo new JsonResponse($fields);
		$app->close();
	}

	/**
	 * This function provide the list of fields (list, radio, checkbox)
	 * which is used as a filters
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getFilters()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$data = $app->input->getArray();

		/** @var $model TjucmModelDocument */
		$model = $this->getModel('document');

		if (!$data['typeId'])
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		$fields = $model->getFilters($data);

		if (empty($fields))
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		$response = array('html' => $fields, 'script' => Factory::getDocument()->_script['text/javascript']);
		echo new JsonResponse($response);
		$app->close();
	}
}
