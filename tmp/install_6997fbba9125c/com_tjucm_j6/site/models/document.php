<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Document model.
 *
 * @since  __DEPLOY__VERSION__
 */
class TjucmModelDocument extends AdminModel
{
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
		return true;
	}

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
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function getItem($id = null)
	{
		$item = parent::getItem($id);

		$item->fieldData['records'] = array();

		$clusterId = $this->getState("cluster_id");
		/** @var $itemsModel TjucmModelItems */
		$itemsModel = Tjucm::model('Items', array('ignore_request' => true));

		/** @var $itemTable TjucmTableitem */
		$itemTable = Tjucm::table('type');
		$itemTable->load(array('id' => $item->ucm_type));

		$itemsModel->setState("ucm.client", $itemTable->unique_identifier);
		$itemsModel->setState("ucmType.id", $item->ucm_type);
		$itemsModel->setState($itemTable->unique_identifier . ".filter.cluster_id", $clusterId);

		// Filter By UCM Data ID
		$input     = Factory::getApplication()->input;
		$ucmDataID = $input->get('ucm_id', 0, 'INT');

		if ($ucmDataID)
		{
			$itemsModel->setState('ucm.id', $ucmDataID);
		}

		$filters = array_filter( (array) $item->params['filters']); // DPE HACK

		foreach ($filters as $key => $value)
		{
			if (is_string($value))
			{
				$value = StringHelper::trim($value);
			}

			$itemsModel->setState('filter.field.' . $key, $value);
		}

		$records = $itemsModel->getItems();

		if (empty($records))
		{
			return $item;
		}

		$data = array();

		// Support for multiple product
		foreach ($records as $record)
		{
			$data[] = array(
				"content_id" => $record->id,
				"client" => $itemTable->unique_identifier,
				"clusterId" => $clusterId
				);
		}

		PluginHelper::importPlugin('tjucm');

		// Get values from plugin
		$item->fieldData['records'] = $this->getFieldData($data);

		$pluginData = array(
				"client" => $itemTable->unique_identifier,
						"clusterId" => $clusterId
						);

		$pluginData = Factory::getApplication()->triggerEvent('onDocumentBeforeDisplay', array($pluginData))['0'];
		$item->fieldData = array_merge($item->fieldData, $pluginData);

		return $item;
	}

	/**
	 * This function provides the field value array of tjucm
	 *
	 * @param   array  $data  field data
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFieldData($data)
	{ 
		JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
		$itemModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel', array("ignore_request" => true));
		$tjFieldsHelper = new TjfieldsHelper;
		$allRecords = array();

		// Get document ID. This is available if you are getting data for single record
		$input = Factory::getApplication()->input;
		$ucmId = $input->get('ucm_id', 0, "INT");  // DPE HACK

		foreach ($data as $singleRecord)
		{
			$ucmSubFormFieldValues = $tjFieldsHelper->FetchDatavalue($singleRecord);

			$fieldValueArray = array();

			foreach ($ucmSubFormFieldValues as $ucmSubFormFieldValue)
			{
				if ($ucmSubFormFieldValue->type === 'ucmsubform')
				{
					if ($ucmId)
					{
						// Format if you are getting data for single UCM record
						$allvalues = $itemModel->getUcmSubFormFieldDataJson($singleRecord['content_id'], $ucmSubFormFieldValue);
						$values = new Registry($allvalues);

						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
						$fieldInstance = Table::getInstance('field', 'TjfieldsTable');


						foreach ($values as $value)
						{
							$multipleRecord = array();
							$allSubformRecords = array();

							foreach ($value as $key => $fvalue)
							{
								$fieldInstance->load(array('name' => $key));

								if ($fieldInstance->type === 'related')
								{
									$fieldData = new Registry($fieldInstance->params);

									// Array flatten i.e array_flatten
									$fieldDataArray = array();

									array_walk_recursive($fieldData['fieldName'], function($x) use (&$fieldDataArray) { $fieldDataArray[] = $x; });

									$fieldValues = array();
									$relatedValueArray = array();

									if (is_array($fvalue))
									{
										$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

										foreach ($fieldValues as $fieldValue)
										{
											$relatedValueArray[] = $fieldValue->value;
										}

										$allSubformRecords[$key] = implode(', ', $relatedValueArray);
									}
									else
									{
										$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

										foreach ($fieldValues as $fieldValue)
										{
											$relatedValueArray[] = $fieldValue->value;
										}

										$allSubformRecords[$key] = implode(', ', $relatedValueArray);
									}
								}
								elseif($fieldInstance->type === 'tjlist' || $fieldInstance->type === 'single_select')
								{
									if (!is_array($fvalue))
									{
										$fvalue = array('value' => $fvalue);
									}

									$fieldValues = $this->getFieldOptions($fieldInstance->id, $fvalue);
									$tjlistValueArray = array();

									if (count($fieldValues) == 1)
									{
										$tjlistValueArray[] = ($fieldValues['0']->feedback)?$fieldValues['0']->options." (". $fieldValues['0']->feedback.")" : $fieldValues['0']->options;
									}
									else
									{
										foreach ($fieldValues as $fieldValue)
										{
											$tjlistValueArray[] = ($fieldValue->feedback)?$fieldValue->options." (". strip_tags($fieldValues['0']->feedback).")" : $fieldValue->options;
										}
									}

									$fieldData = new Registry($fieldInstance->params);

									if ($fieldData->get('other') == 1)
									{
										foreach ($fvalue as $default_option)
										{
											if (strpos($default_option, 'tjlist:-') !== false)
											{
												$tjlistValueArray[] = str_replace('tjlist:-', '', $default_option);
											}
										}
									}
									
									$allSubformRecords[$key] = implode(', ', $tjlistValueArray);

									}
								else
								{
									if (is_string($fvalue))
									{
										$allSubformRecords[$key] = $fvalue;
									}
									else
									{
										$allSubformRecords[$key] = $fvalue[0]->value ? $fvalue[0]->value : $fvalue;
									}
								}
							}

							if (!empty($allSubformRecords))
							{
								$fieldValueArray[$ucmSubFormFieldValue->name]['subFormrecords'][] = $allSubformRecords;
							}
						}
					}
					else
					{
						// Format for multiple records
						$allvalues = $itemModel->getUcmSubFormFieldDataJson($singleRecord['content_id'], $ucmSubFormFieldValue);
						$values = new Registry($allvalues);

						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
						$fieldInstance = Table::getInstance('field', 'TjfieldsTable');

						foreach ($values as $value)
						{
							$multipleRecord = array();

							foreach ($value as $key => $fvalue)
							{
								$fieldInstance->load(array('name' => $key));

								if ($fieldInstance->type === 'related')
								{
									$fieldData = new Registry($fieldInstance->params);

									// Array flatten i.e array_flatten
									$fieldDataArray = array();

									array_walk_recursive($fieldData['fieldName'], function($x) use (&$fieldDataArray) { $fieldDataArray[] = $x; });

									$fieldValues = array();
									$relatedValueArray = array();

									if (is_array($fvalue))
									{
										$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

										foreach ($fieldValues as $fieldValue)
										{
											$relatedValueArray[] = $fieldValue->value;
										}

										if (!empty($fieldValueArray[$ucmSubFormFieldValue->name][0][$key]))
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= ", " . implode(', ', $relatedValueArray);
										}
										else
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= implode(', ', $relatedValueArray);
										}
									}
									else
									{
										$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

										foreach ($fieldValues as $fieldValue)
										{
											$relatedValueArray[] = $fieldValue->value;
										}

										if (!empty($fieldValueArray[$ucmSubFormFieldValue->name][0][$key]))
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= ", " . implode(', ', $relatedValueArray);
										}
										else
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= implode(', ', $relatedValueArray);
										}
									}
								}
								elseif($fieldInstance->type === 'tjlist' || $fieldInstance->type === 'single_select')
								{
									if (!is_array($fvalue))
									{
										$fvalue = array('value' => $fvalue);
									}

									
									$fieldValues  = $this->getFieldOptions($fieldInstance->id, $fvalue);
									$tjlistValueArray = array();

									if (count($fieldValues) == 1)
									{
										$tjlistValueArray[] = ($fieldValues['0']->feedback)?$fieldValues['0']->options." (". strip_tags($fieldValues['0']->feedback).")" : $fieldValues['0']->options;
									}
									else
									{
										foreach ($fieldValues as $fieldValue)
										{
											$tjlistValueArray[] = ($fieldValue->feedback)?$fieldValue->options." (". strip_tags($fieldValues['0']->feedback).")" : $fieldValue->options;
										}
									}

									$fieldData = new Registry($fieldInstance->params);

									if ($fieldData->get('other') == 1)
									{
										foreach ($fvalue as $default_option)
										{
											if (strpos($default_option, 'tjlist:-') !== false)
											{
												$tjlistValueArray[] = str_replace('tjlist:-', '', $default_option);
											}
										}
									}

									if (!empty($fieldValueArray[$ucmSubFormFieldValue->name][0][$key]))
									{
										$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= ", " . implode(', ', $tjlistValueArray);
									}
									else
									{
										$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= implode(', ', $tjlistValueArray);
									}
								}
								else
								{
									if (is_string($fvalue))
									{
										if (!empty($fieldValueArray[$ucmSubFormFieldValue->name][0][$key]))
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= ", " . $fvalue;
										}
										else
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= $fvalue;
										}
									}
									else
									{
										if (!empty($fieldValueArray[$ucmSubFormFieldValue->name][0][$key]))
										{
											$fvalueOtherField = $fvalue[0]->value ? $fvalue[0]->value : $fvalue;
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= ", " . $fvalueOtherField;
										}
										else
										{
											$fieldValueArray[$ucmSubFormFieldValue->name][0][$key] .= $fvalue[0]->value ? $fvalue[0]->value : $fvalue;
										}
									}
								}
							}
						}
					} // End else for multiple records format
				}
				elseif ($ucmSubFormFieldValue->type === 'related')
				{
					$fieldData = new Registry($ucmSubFormFieldValue->params);

					// Array flatten i.e array_flatten $fieldDataArray = array_flatten($fieldData['fieldName']);

					$fieldDataArray = array();

					array_walk_recursive($fieldData['fieldName'], function($x) use (&$fieldDataArray) { $fieldDataArray[] = $x; });

					$fieldValues = array();
					$relatedValueArray = array();

					if (is_array($fvalue))
					{
						$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

						foreach ($fieldValues as $fieldValue)
						{
							$relatedValueArray[] = $fieldValue->value;
						}

						if (count($relatedValueArray) > 1)
						{
							$fieldValueArray[$key] = implode(', ', $relatedValueArray);
						}
						else
						{
							$fieldValueArray[$key] = $relatedValueArray;
						}
					}
					else
					{
						$fieldValues = $this->getFieldValues($fvalue, $fieldDataArray[0]->fieldIds);

						foreach ($fieldValues as $fieldValue)
						{
							$relatedValueArray[] = $fieldValue->value;
						}

						if (count($relatedValueArray) > 1)
						{
							$fieldValueArray[$key] = implode(', ', $relatedValueArray);
						}
						else
						{
							$fieldValueArray[$key] = $relatedValueArray;
						}
					}
				}
				elseif($ucmSubFormFieldValue->type === 'tjlist' || $ucmSubFormFieldValue->type === 'single_select')
				{
					$fieldData = new Registry($ucmSubFormFieldValue->params);

					if (!is_array($ucmSubFormFieldValue->value))
					{
						$ucmSubFormFieldValue->value = array('value' => $ucmSubFormFieldValue->value);
					}

					// Get the label of the dropdown from it's value value
					$fieldValues = $this->getFieldOptions($ucmSubFormFieldValue->field_id, $ucmSubFormFieldValue->value);

					$tjlistValueArray = array();

					if (count($fieldValues) == 1)
					{
						$tjlistValueArray[] = (strip_tags($fieldValues['0']->feedback))?$fieldValues['0']->options." (". strip_tags($fieldValues['0']->feedback) ." )" : $fieldValues['0']->options;
					}
					else
					{
						foreach ($fieldValues as $fieldValue)
						{
							
							$tjlistValueArray[] = ($fieldValue->feedback)?$fieldValue->options." (". strip_tags($fieldValues['0']->feedback).")" : $fieldValue->options;
						}
					}

					if ($fieldData->get('other') == 1)
					{
						foreach ($ucmSubFormFieldValue->value as $default_option)
						{
							if (strpos($default_option->value, 'tjlist:-') !== false)
							{
								$tjlistValueArray[] = str_replace('tjlist:-', '', $default_option->value);
							}
						}
					}

					if (count($tjlistValueArray) > 1)
					{
						$fieldValueArray[$ucmSubFormFieldValue->name] = implode(', ', $tjlistValueArray);
					}
					else
					{
						$fieldValueArray[$ucmSubFormFieldValue->name] = implode(', ', array_unique($tjlistValueArray));
					}
				}
				else if($ucmSubFormFieldValue->type === 'radio')
									{
										 $fieldValueArray[$ucmSubFormFieldValue->name] = $ucmSubFormFieldValue->value[0]->options;
							
										
										}
				elseif ($ucmSubFormFieldValue->type === 'ownership')
				{
					$userData = Factory::getUser($ucmSubFormFieldValue->value);
					$fieldValueArray[$ucmSubFormFieldValue->name] = $userData->name . ' (' . $userData->email . ')';
				}
				else
				{
					$fieldValueArray[$ucmSubFormFieldValue->name] = $ucmSubFormFieldValue->value;
				}
			}

			if (!empty($fieldValueArray))
			{
				$allRecords[] = $fieldValueArray;
			}
		}

		return $allRecords;
	}

	/**
	 * This function provides the Field Values
	 *
	 * @param   int    $contentId  contentId
	 * @param   array  $fieldIds   field ids
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFieldValues($contentId, $fieldIds = array())
	{
		$db = Factory::getDbo();
		$query	= $db->getQuery(true);
		$query->select('value FROM #__tjfields_fields_value');
		$query->where($db->quoteName('field_id') . ' IN (' . implode(',', $fieldIds) . ')');

		if (is_array($contentId))
		{
			$query->where($db->quoteName('content_id') . ' IN (' . implode(',', $contentId) . ')');
		}
		else
		{
			$query->where($db->quoteName('content_id') . ' = ' . $db->quote($contentId));
		}

		$db->setQuery($query);

		return $db->loadObjectlist();
	}

	/**
	 * This function provides the Field options
	 *
	 * @param   int    $fieldId  fieldId
	 * @param   array  $values   field values
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFieldOptions($fieldId, $values = array())
	{
		$showFeedback = $this->getFeedBackValue($fieldId);
		$multiple     = json_decode($showFeedback[0]->params);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if ($showFeedback[0]->showFeedback && ($multiple->multiple == 0))
		{
			$query->select('options , feedback FROM #__tjfields_options');
	
		}else
		{
			$query->select('options  FROM #__tjfields_options');	
		}
		
		$query->where($db->quoteName('field_id') . ' = ' . $db->quote($fieldId));

		foreach ($values as $value)
		{
			if (is_array($value) || is_object($value))
			{
				$value = new Registry($value);

				$querysubArr[] = $db->quoteName('value') . ' = ' . $db->quote($value->get('value'));
			}
			else
			{
				$querysubArr[] = $db->quoteName('value') . ' = ' . $db->quote($value);
			}
		}

		$querysub = implode(" OR ", $querysubArr);

		if ($querysubArr)
		{
			$query->where('(' . $querysub . ')');
		}

		$db->setQuery($query);
		return  $db->loadObjectlist();
	}

	/**
	 * This function provides the FeedBack Data of fields
	 *
	 * @param   int    $fieldId  fieldId
	 * @param   string  $value    field values
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFeedBackValue($fieldId)
	{
		if (!$fieldId )
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('showFeedback, params FROM #__tjfields_fields');
		$query->where($db->quoteName('id') . ' = ' . $db->quote($fieldId));
		$db->setQuery($query);

		return  $db->loadObjectlist();
	}
}
