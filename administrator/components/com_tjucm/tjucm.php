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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tjucm'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}


// Load backend helper
$path = JPATH_ADMINISTRATOR . '/components/com_tjucm/helpers/tjucm.php';

if (!class_exists('TjucmHelper'))
{
	JLoader::register('TjucmHelper', $path);
	JLoader::load('TjucmHelper');
}

JLoader::registerPrefix('Tjucm', JPATH_COMPONENT_ADMINISTRATOR);

$app = Factory::getApplication();
$mvcFactory = $app->bootComponent('com_tjucm')->getMVCFactory();
$controller = $mvcFactory->createController(
	$app->getInput()->get('task', 'display'),
	'Administrator',
	array(),
	$app,
	$app->getInput()
);
$controller->execute($app->getInput()->get('task'));
$controller->redirect();
