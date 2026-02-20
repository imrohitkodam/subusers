<?php
/**
 * @package    Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Documents records.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmModelDocuments extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      __DEPLOY__VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title', 'a.title',
				'ucm_type', 'a.ucm_type',
				'b.title',
				'state', 'a.state'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		parent::populateState($ordering, $direction);

		if ($app->isClient('site'))
		{
			$this->setState('filter.state', 1);
		}

		$start = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', 0, 'int');

		if ($limit == 0)
		{
			$limit = $app->get('list_limit', 0);
		}

		$this->setState('list.limit', $limit);
		$this->setState('list.start', $start);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->select($db->quoteName('b.title', 'ucmname'));
		$query->from('`#__tj_ucm_documents` AS a');
		$query->join('LEFT', $db->quoteName('#__tj_ucm_types', 'b') . ' ON (' . $db->quoteName('a.ucm_type') . ' = ' . $db->quoteName('b.id') . ')');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('( a.`title` LIKE ' . $search . ' )');
			}
		}

		// Filter by ucm type
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('a.state = ' . (int) $state);
		}

		// Filter by ucm type
		$ucmType = $this->getState('filter.ucm_type');

		if (!empty($ucmType))
		{
			$query->where('a.ucm_type = ' . (int) $ucmType);
		}

		// Filter by ucm type
		$documentType = $this->getState('filter.document_type');

		if (is_numeric($documentType))
		{
			$query->where('a.document_type = ' . (int) $documentType);
		}

		$query->order($db->qn($db->escape($this->getState('list.ordering', 'a.id'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		return $query;
	}
}
