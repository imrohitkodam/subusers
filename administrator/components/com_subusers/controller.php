<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Filter\InputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

\JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * Class SubusersController
 *
 * @since  1.0.0
 */
class SubusersController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return   JController This object to support chaining.
	 *
	 * @since    1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_subusers/helpers/subusers.php';

		$app = Factory::getApplication();
		$view = $app->getInput()->getCmd('view', 'roles');
		$app->getInput()->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}
}
