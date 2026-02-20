<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_subusers'))
{
	throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

\JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

$input = Factory::getApplication()->getInput();
$task = $input->get('task');

// Get controller name from task or default to display
$controllerName = 'Subusers';
if (strpos($task, '.') !== false)
{
	list($controllerName, $task) = explode('.', $task, 2);
	$controllerName = ucfirst($controllerName);
}

// Load the controller
$controllerClass = 'SubusersController' . $controllerName;
$controllerPath = JPATH_ADMINISTRATOR . '/components/com_subusers/controllers/' . strtolower($controllerName) . '.php';

if (file_exists($controllerPath))
{
	require_once $controllerPath;
	$controller = new $controllerClass;
}
else
{
	$controller = new BaseController;
}

$controller->execute($task);
$controller->redirect();
