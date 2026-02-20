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

// Get application and input
$app = Factory::getApplication();
$input = $app->getInput();

// Get controller from MVCFactory
$controller = $app->bootComponent('com_tjqueue')
    ->getMVCFactory()
    ->createController(
        $input->getCmd('view', 'entries'),
        'Administrator',
        [],
        $app,
        $input
    );

// Perform the Request task
$controller->execute($input->get('task'));

// Redirect if set by the controller
$controller->redirect();
