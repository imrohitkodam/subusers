<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
/**
 * View class for a list of Documents.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmViewDocuments extends HtmlView
{
	/**
	 * The item data.
	 *
	 * @var   object
	 * @since __DEPLOY_VERSION__
	 */
	protected $items;

	/**
	 * The pagination object.
	 *
	 * @var   Pagination
	 * @since __DEPLOY_VERSION__
	 */
	protected $pagination;

	/**
	 * The model state.
	 *
	 * @var   CMSObject
	 * @since __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * A Form instance with filter fields.
	 *
	 * @var    Form
	 * @since  __DEPLOY_VERSION__
	 */
	public $filterForm;

	/**
	 * An array with active filters.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	public $activeFilters;

	public $clusterList;

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
		$app         = Factory::getApplication();
		$user        = Factory::getUser();
		$this->state = $this->get('State');

		// DPE Hack to load the clusters and check permissions
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
			$cluster           = FormHelper::loadFieldType('cluster', false);
			$this->clusterList = $cluster->getOptionsExternally();

			// Check if don't have organisation
			if (!$this->clusterList['1']->value)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

				return;
			}

			if (empty($this->clusterList['0']->value))
			{
				unset($this->clusterList['0']);
			}

			// To set state of filter
			if (!$this->state->get('filter.cluster_id'))
			{
				$this->state->set('filter.cluster_id', $this->clusterList['1']->value);
			}
		}


		$orgAdmin      = ComponentHelper::getParams('com_multiagency')->get('multiagency_school_admin_group');

		// Check document generate permission and also check the user is school admin or not
		$isDocumentGenerate = true;

		if ((!$user->authorise('core.manageall', 'com_cluster')) && (!in_array($orgAdmin,$user->groups)))
		{
			$isDocumentGenerate = RBACL::check($user->id, 'com_cluster', 'document.generate', 'com_multiagency', $this->state->get('filter.cluster_id'));
		}

		if (!$isDocumentGenerate)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		//  Get the documents of type Multiple
		$model               = $this->getmodel();
		$model->setState('filter.document_type', 1);

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
