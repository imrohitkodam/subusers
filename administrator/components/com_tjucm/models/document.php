<?php
/**
 * @package    Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
/**
 * Document model.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmModelDocument extends AdminModel
{
	/**
	 * @var null  Item data
	 * @since  __DEPLOY__VERSION__
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    Table    A database object
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function getTable($type = 'Document', $prefix = 'TjucmTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form  A Form object on success, false on failure
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_tjucm.document', 'document',
			array('control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjucm.edit.document.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;
		}

		$data->com_tjucm_rop_Businessfunction = 1;

		return $data;
	}

	/**
	 * This function provide the list of fields
	 *
	 * @param   Array  $data  data
	 *
	 * @return  Array  array of fields
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getSupportedTags($data)
	{
		$typeTable = Tjucm::table("type");

		$typeTable->load(array('id' => $data['typeId']));

		PluginHelper::importPlugin('tjucm');

		$data['unique_identifier'] = $typeTable->unique_identifier;
		$data['context'] = 'com_multiagency.multiagency';

		// Get values from plugin
		$fields = Factory::getApplication()->triggerEvent('onDocumentBeforeCreate', array($data));

		return array_reduce($fields, 'array_merge', array());
	}

	/**
	 * This function provide the fields and values
	 *
	 * @param   Array  $data  data
	 *
	 * @return  Array  array of fields
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getDocumentFieldValues($data)
	{
		
		PluginHelper::importPlugin('tjucm');

		// Get values from plugin
		$fieldValues = Factory::getApplication()->triggerEvent('onDocumentBeforeDisplay', array($data));

		// Make single array from multidimensional array
		$fieldValues = array_reduce($fieldValues, 'array_merge', array());

		return $fieldValues;
	}

	/**
	 * This function provide the list of fields (list, radio, checkbox)
	 * which is used as a filters
	 *
	 * @param   Array  $data  data
	 *
	 * @return  string  The field html
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function getFilters($data)
	{
		$typeTable = Tjucm::table("type");
		$typeTable->load(array('id' => $data['typeId']));

		/** @var $itemFormModel TjucmModelItemForm */
		$itemFormModel = Tjucm::model('ItemForm',  array("ignore_request" => true));

		$ucmForm = $itemFormModel->getFormExtra(
		array(
			"clientComponent" => 'com_tjucm',
			"client" => $typeTable->unique_identifier,
			"view" => str_replace('com_tjucm.', '', $typeTable->unique_identifier),
			"layout" => 'default'
			)
			);

		$html = '';

		$clients = [];

		$formData      = new Registry(stripslashes($data['data']));
		$formDataArray = $formData->toArray();
		$ucmForm->bind($formData->toArray());

		if (!empty($ucmForm))
		{
			$fieldSets = $ucmForm->getFieldsets();

			foreach ($fieldSets as $fieldset)
			{
				foreach ($ucmForm->getFieldset($fieldset->name) as $field)
				{
					if ($field->type === 'list' || $field->type === 'Radio' || $field->type === 'Checkbox')
					{
						$field->__set('type', 'tjlist');
					}

					if ($field->type === 'tjlist' || $field->type === 'list' || $field->type === 'Radio' || $field->type === 'Checkbox')
					{
						$field->__set('required', false);

						if (empty($formDataArray[$field->fieldname]))
						{
							$field->__set('value', '');
						}

						$html .= $field->renderField();
					}
					elseif ($field->type == 'Ucmsubform')
					{
						$formsource = explode('/', $field->formsource);

						// Disable subform fields from Filter
						// $clients[] = 'com_tjucm.' . str_replace('form_extra.xml', '', $formsource[count($formsource) - 1]);
					}
				}
			}

			foreach ($clients as $client)
			{
				$ucmSubForm = $itemFormModel->getFormExtra(
					array(
						"clientComponent" => 'com_tjucm',
						"client" => $client,
						"view" => str_replace('com_tjucm.', '', $client),
						"layout" => 'default'
						)
					);

				$subFieldSets = $ucmSubForm->getFieldsets();
				$ucmSubForm->bind($formData->toArray());

				foreach ($subFieldSets as $subFieldSet)
				{
					foreach ($ucmSubForm->getFieldset($subFieldSet->name) as $field)
					{
						if ($field->type == 'tjlist' || $field->type == 'list' || $field->type == 'Checkbox' || $field->type == 'Radio')
						{
							$field->__set('required', false);
							$html .= $field->renderField();
						}
					}
				}
			}
		}

		return $html;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function save($data)
	{
		// @TODO this is not the correct way to pass values to the function
		$input = Factory::getApplication()->input;
		$params = $input->get('jform', array(), 'array')['params'];
		$data['params'] = json_encode($params);
		$data['checked_out'] = ($data['checked_out'])?$data['checked_out']:0;
		$data['modified_date'] = ($data['modified_date'])?$data['modified_date']:Factory::getDate()->toSql();



		return parent::save($data);
	}

	/**
	 * Reset All filters for the document
	 *
	 * @return    boolean  True on success.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function resetParams()
	{
		$app        = Factory::getApplication();
		$documentId = $app->input->get('id', 0, 'INT');

		if (!$documentId)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		$documentTable = $this->getTable();

		if ($documentTable->load($documentId, true))
		{
			// Reset Filters i.e params for the document
			$documentTable->params = '';

			if (!$documentTable->store())
			{
				throw new Exception($documentTable->getError());
			}
		}
		else
		{
			throw new Exception($documentTable->getError());
		}

		return true;
	}
}
