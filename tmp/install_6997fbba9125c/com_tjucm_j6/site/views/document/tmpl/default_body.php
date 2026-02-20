<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var $this TjucmViewItemform */

jimport('libraries.tjmustache.Mustache.Autoloader', JPATH_SITE);

Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'helpers' => array('toString' => function($text, $Mustache_LambdaHelper) {
    // do something translatey here...
      /** @var $Mustache_LambdaHelper Mustache_LambdaHelper*/
    }),
    'escape' => function($value) {
    return $this->escape($value);
    },
    ));
echo $m->render($this->item->document_body, $this->item->fieldData);
?>