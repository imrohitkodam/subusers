<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJQueue
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Get application and input
$app = Factory::getApplication();
$input = $app->getInput();

// Require the base controller
require_once JPATH_COMPONENT . '/controller.php';

// Get an instance of the controller prefixed by Tjqueue
$controller = BaseController::getInstance('Tjqueue');

// Perform the Request task
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
