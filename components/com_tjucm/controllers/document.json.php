<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * Dpe document Controller
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmControllerDocument extends TjucmController
{
	/**
	 * Method to get document field values
	 *
	 * @return  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getDocumentFieldValues()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$contentId = $app->input->getInt('contentId', 0);
		$client = $app->input->get('client');
		$clusterId = $app->input->getInt('clusterId');

		if (!$contentId && !$client && !$clusterId)
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		$data['content_id'] = $contentId;
		$data['client'] = $client;
		$data['clusterId'] = $clusterId;

		$model = $this->getModel('document');
		$fieldValues = $model->getDocumentFieldValues($data);

		if (!$fieldValues)
		{
			echo new JsonResponse(null, Text::_('COM_TJUCM_INVALID_REQUEST'), true);
			$app->close();
		}

		echo new JsonResponse($fieldValues);
		$app->close();
	}
}
