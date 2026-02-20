<?php

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Custom Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @subpackage	        com_my
 * @since		1.6
 */
class JFormFieldDpetags extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'DpeTags';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	public function getOptions()
	{
		// Initialize variables.
		$options = array();

		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('Distinct tags.id As value, tags.title As text');
		$query->from('#__tags AS tags');
		$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagMap') . ' ON ' . $db->quoteName('tagMap.tag_id') . ' = ' . $db->quoteName('tags.id'));
		$query->order('tags.title');
		$query->where('type_alias="com_multiagency.multiagency"');

		// Check for a database error.
			try 
			{
			    // Get the options.
				$db->setQuery($query);
				$options = $db->loadObjectList();
			}
			catch (Exception $e)
			{
			    echo $e->getMessage();
			}

		return $options;
	}
}