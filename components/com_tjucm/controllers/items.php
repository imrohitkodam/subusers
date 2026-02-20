<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Filter\InputFilter;

/**
 * Items list controller class.
 *
 * @since  1.6
 */
class TjucmControllerItems extends TjucmController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Items', $prefix = 'TjucmModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Function to import records in specifed UCM type from CSV.
	 *
	 * @return null
	 *
	 * @since	1.2.4
	 */
	public function importCsv()
	{
		Session::checkToken() or die('Invalid Token');

		$app = Factory::getApplication();
		$importFile = $app->input->files->get('csv-file-upload');

		$client = $app->input->get("client", '', 'STRING');

		if (empty($client))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_SOMETHING_WENT_WRONG'), 'error');
			$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component');
		}

		// Check if the file is a CSV file
		if (!in_array($importFile['type'], array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv')))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_ITEMS_INVALID_CSV_FILE'), 'error');
			$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $client);
		}

		// Load required files
		JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		JLoader::import('components.com_tjfields.models.options', JPATH_ADMINISTRATOR);

		$uploadPath = Factory::getConfig()->get('tmp_path') . '/' . File::makeSafe($importFile['name']);

		// Upload the JSON file
		if (!File::upload($importFile['tmp_name'], $uploadPath))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_ITEMS_CSV_FILE_UPLOAD_ERROR'), 'error');
			$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $client);
		}

		// Get all fields in the given UCM type
		$tjFieldsFieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjFieldsFieldsModel->setState("filter.client", $client);
		$tjFieldsFieldsModel->setState("filter.state", 1);
		$tjFieldsFieldsModel->setState('list.ordering', 'a.ordering');
		$tjFieldsFieldsModel->setState('list.direction', 'asc');
		$fields = $tjFieldsFieldsModel->getItems();

		// Map the field names as per field labels in the uploaded CSV file
		$fieldsArray = array();
		$requiredFieldsName = array();
		$requiredFieldsLabel = array();
		$fieldsName = array_column($fields, 'name');
		$fieldsLabel = array_column($fields, 'label');
		$fieldHeaders = array_combine($fieldsName, $fieldsLabel);

		foreach ($fields as $field)
		{
			// Get the required fields for the UCM type
			if ($field->required == 1)
			{
				$requiredFieldsName[$field->name] = $field->name;
				$requiredFieldsLabel[] = $field->label;
			}

			// Add options data the radio and list type fields
			if (in_array($field->type, array('radio', 'single_select', 'multi_select', 'tjlist')))
			{
				$tjFieldsOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
				$tjFieldsOptionsModel->setState("filter.field_id", $field->id);
				$field->options = $tjFieldsOptionsModel->getItems();
			}

			$fieldsArray[$field->name] = $field;
		}

		// Read the CSV file
		$file = fopen($uploadPath, 'r');
		$headerRow = true;
		$invalidRows = 0;
		$validRows = 0;
		$errors = array();

		// Loop through the uploaded file
		while (($data = fgetcsv($file)) !== false)
		{
			if ($headerRow)
			{
				$headers = $data;
				$headerRow = false;

				// Check if all the required fields headers are present in the CSV file to be imported
				$isValid = (count(array_intersect($requiredFieldsLabel, $headers)) == count($requiredFieldsLabel));

				if (!$isValid)
				{
					$app->enqueueMessage(Text::_('COM_TJUCM_ITEMS_INVALID_CSV_FILE_REQUIRED_COLUMN_MISSING'), 'error');
					$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $client);
				}
			}
			elseif (count($headers) == count($data))
			{
				$itemData   = array();
				$parentId   = 0;
				$categoryId = 0;

				// Prepare item data for item creation
				foreach ($data as $key => $value)
				{
					if ($headers[$key] === 'parent_id')
					{
						$parentId = $value;
						continue;
					}

					$fieldName = array_search($headers[$key], $fieldHeaders);
					$value = trim($value);

					if ($fieldName !== false && $value != '')
					{
						if (isset($fieldsArray[$fieldName]->options) && !empty($fieldsArray[$fieldName]->options))
						{
							$fieldParams = new Registry($fieldsArray[$fieldName]->params);
							$fieldOptions = array_column($fieldsArray[$fieldName]->options, 'options');

							// If there are multiple values for a field then we need to send those as array
							if (strpos($value, '||') !== false && $fieldParams->get('multiple'))
							{
								$optionValue = array_map('trim', explode("||", $value));
								$multiSelectValues = array();
								$otherOptionsValues = array();

								foreach ($optionValue as $option)
								{
									if (in_array($option, $fieldOptions))
									{
										$multiSelectValues[] = $option;
									}
									else
									{
										if ($fieldParams->get('other'))
										{
											$otherOptionsValues[] = $option;
										}
									}
								}

								if (!empty($otherOptionsValues))
								{
									$multiSelectValues[] = 'tjlistothervalue';
									$multiSelectValues[] = implode(',', $otherOptionsValues);
								}

								$itemData[$fieldName] = $multiSelectValues;
							}
							else
							{
								if (in_array($value, $fieldOptions))
								{
									$itemData[$fieldName] = $value;
								}
								else
								{
									if ($fieldParams->get('other'))
									{
										$itemData[$fieldName] = array('tjlistothervalue', $value);
									}
								}
							}
						}
						elseif ($fieldsArray[$fieldName]->type == 'cluster')
						{
							if (JLoader::import('components.com_cluster.tables.clusters', JPATH_ADMINISTRATOR))
							{
								$clusterTable = Table::getInstance('Clusters', 'ClusterTable');
								$clusterTable->load(array("name" => $value));
								$itemData[$fieldName] = $clusterTable->id;
							}
						}
						elseif ($fieldsArray[$fieldName]->type == 'itemcategory')
						{
							if (JLoader::import('components.com_categories.tables.category', JPATH_ADMINISTRATOR))
							{
								$categoryTable = Table::getInstance('Category', 'CategoriesTable');
								$categoryTable->load(array('title' => $value, 'extension' => $client, 'published' => 1));

								if (property_exists($categoryTable, 'id'))
								{
									$itemData[$fieldName] = $categoryId = $categoryTable->id;
								}
							}
						}
						else
						{
							$itemData[$fieldName] = trim($value);
						}
					}
				}

				// Check if all the required values are present in the row
				$isValid = (count(array_intersect_key($itemData, $requiredFieldsName)) == count($requiredFieldsName));

				if (!$isValid || empty($itemData))
				{
					$invalidRows++;
				}
				else
				{
					$tjucmItemFormModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');

					$fieldsData = array();
					$fieldsData['client'] = $client;

					$form = $tjucmItemFormModel->getTypeForm($fieldsData);
					$data = $tjucmItemFormModel->validate($form, $itemData);

					if ($data !== false)
					{
						// Save the record in UCM
						if ($tjucmItemFormModel->save(array('client' => $client, 'parent_id' => $parentId, 'category_id' => $categoryId)))
						{
							$contentId = (int) $tjucmItemFormModel->getState($tjucmItemFormModel->getName() . '.id');

							$fieldsData['content_id']  = $contentId;
							$fieldsData['fieldsvalue'] = $data;

							if ($tjucmItemFormModel->saveFieldsData($fieldsData))
							{
								$validRows++;

								continue;
							}
						}
					}

					$invalidRows++;

					$errors = array_merge($errors, $tjucmItemFormModel->getErrors());
				}
			}
			else
			{
				$invalidRows++;
			}
		}

		if (!empty($errors))
		{
			$this->processErrors($errors);
		}

		if ($validRows)
		{
			$app->enqueueMessage(Text::sprintf('COM_TJUCM_ITEMS_IMPORTED_SCUUESSFULLY', $validRows), 'success');
			$msg = array('msg'=>Text::sprintf('COM_TJUCM_ITEMS_IMPORTED_SCUUESSFULLY', $validRows), 'type'=>'success');
		}

		if ($invalidRows)
		{
			$app->enqueueMessage(Text::sprintf('COM_TJUCM_ITEMS_IMPORT_REJECTED_RECORDS', $invalidRows), 'warning');
			$msg = array('msg'=>Text::sprintf('COM_TJUCM_ITEMS_IMPORT_REJECTED_RECORDS', $invalidRows), 'type'=>'warning');
		}

		if (empty($validRows) && empty($invalidRows))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_ITEMS_NO_RECORDS_TO_IMPORT'), 'error');
			$msg = array('msg'=>Text::sprintf('COM_TJUCM_ITEMS_NO_RECORDS_TO_IMPORT'), 'type'=>'error');
		}
		//DPE Hack
		?>

		 <script type="text/javascript" src="<?php echo Uri::root().'/media/vendor/jquery/js/jquery.min.js'?>"></script>
					<script type="text/javascript">

						var msg = '<?php echo $msg["msg"];?>';
						var type = '<?php echo $msg["type"];?>';

						jQuery('<div id="system-message-container"></div>').appendTo('.com-tjgophish');
						Joomla.renderMessages({type : [msg]});				

						setTimeout(function() {
							window.location.href= '<?php echo 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $client; ?>';
						}, 2000);
					</script>
	<?php	//$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component&client=' . $client);
	}

	/**
	 * Function to generate schema of CSV file for importing the records in specifed UCM type.
	 *
	 * @return null
	 *
	 * @since	1.2.4
	 */
	public function getCsvImportFormat()
	{
		Session::checkToken('get') or die('Invalid Token');

		$app = Factory::getApplication();
		$client = $app->input->get("client", '', 'STRING');

		if (empty($client))
		{
			$app->enqueueMessage(Text::_('COM_TJUCM_SOMETHING_WENT_WRONG'), 'error');
			$app->redirect(Uri::root() . 'index.php?option=com_tjucm&view=items&layout=importitems&tmpl=component');
		}

		// Get UCM Type data
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$ucmTypeTable = Table::getInstance('Type', 'TjucmTable');
		$ucmTypeTable->load(array("unique_identifier" => $client));

		// Check if UCM type is subform
		$ucmTypeParams = new Registry($ucmTypeTable->params);
		$isSubform     = $ucmTypeParams->get('is_subform');

		// Get fields in the given UCM type
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$tjFieldsFieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjFieldsFieldsModel->setState("filter.client", $client);
		$tjFieldsFieldsModel->setState("filter.state", 1);
		$tjFieldsFieldsModel->setState('list.ordering', 'a.ordering');
		$tjFieldsFieldsModel->setState('list.direction', 'asc');
		$fields = $tjFieldsFieldsModel->getItems();
		$fieldsLabel = array_column($fields, 'label');

		if ($isSubform)
		{
			// Add parentid in colunm
			array_push($fieldsLabel, 'parent_id');
		}

		// Generate schema CSV file with CSV headers as label of the fields for given UCM type and save it in temp folder
		$fileName = preg_replace('/[^A-Za-z0-9\-]/', '', $ucmTypeTable->title) . '.csv';
		$csvFileTmpPath = Factory::getConfig()->get('tmp_path') . '/' . $fileName;
		$output = fopen($csvFileTmpPath, 'w');
		fputcsv($output, $fieldsLabel);
		fclose($output);

		// Download the CSV file
		header("Content-type: text/csv");
		header("Content-disposition: attachment; filename = " . $fileName);
		readfile($csvFileTmpPath);

		jexit();
	}

	/**
	 * Method to procees errors
	 *
	 * @param   ARRAY  $errors  ERRORS
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	private function processErrors($errors)
	{
		$app = Factory::getApplication();

		if (!empty($errors))
		{
			$code = 500;
			$msg  = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$code  = $errors[$i]->getCode();
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			$app->enqueueMessage(implode("<br>", $msg), 'error');
		}
	}

	/**
	 * Function to get Items using Ajax
	 *
	 * @return null
	 *
	 * @since	1.2.4
	 */
	public function getAjaxItems()
	{
		$jinput       = Factory::getApplication()->input;
		$ucmTypeId    = $jinput->post->get('typeId', '', 'int');
		$limit        = $jinput->post->get('limit', '', 'int');
		$clusterValue = $jinput->post->get('cluster_id', 0, 'int');
		$client       = $jinput->post->get('client', '', 'String');
		$filterSearch = $jinput->post->get('filterSearch', '', 'String');
		$ucmfields    = $jinput->post->get('ucmfields', '', 'JSON');
		$ucmfields    = json_decode(base64_decode($ucmfields));
		$inputFilter  = InputFilter::getInstance();
		$newLimit     = $jinput->post->get('paginationIndexAjax', 0, 'int');
		$paramEdit    = $jinput->post->get('paramEdit', '', 'int'); // DPE Hack

		$fieldsData   = array();

		$model = $this->getModel('items');
		$model = BaseDatabaseModel::getInstance('Items', 'TjucmModel', array('ignore_request' => true));
		$model->setState('filter.state', 1);
		$model->setState('filter.draft', 0);

		if ($clusterValue)
		{
			$model->setState($client . '.filter.cluster_id', $clusterValue);
		}

		if ($filterSearch)
		{
			$model->setState($client . '.filter.search', $filterSearch);
		}

		$model->setState('showall', 1);
		$model->setState('ucm.client', $client);
		$model->setState('ucmType.id', (int) $ucmTypeId);
		$model->setState('list.ordering', 'a.id');
		$model->setState('list.direction', 'DESC');

		if (isset($newLimit))
		{
			$model->setState('list.start', $inputFilter->clean($newLimit, 'int'));
		}

		if (isset($limit))
		{
			$model->setState('list.limit', $inputFilter->clean((int) $limit, 'int'));
		}

		$items = $model->getItems();

		$fieldsColumn = $model->getFields();

		$columnsToShow = array();

		foreach ($ucmfields as $field)
		{
			foreach ($fieldsColumn as $key => $col)
			{
				if ($field == $col->id)
				{
					$columnsToShow[$key] = $col;
				}
			}
		}

		foreach ($columnsToShow as $fieldId => $col_name)
		{
			if (isset($fieldsData[$fieldId]))
			{
				$tjFieldsFieldTable = $fieldsData[$fieldId];
			}
			else
			{
				JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
				$tjFieldsFieldTable = JTable::getInstance('field', 'TjfieldsTable');
				$tjFieldsFieldTable->load($fieldId);
				$fieldsData[$fieldId] = $tjFieldsFieldTable;
			}
		}

		$html = "";
		jimport('joomla.application.module.helper');
		$layoutpath = JModuleHelper::getLayoutPath('mod_ucm_data', $layout = 'default_list');

		foreach ($items as $item) 
		{ // DPE Hack
			$html .= LayoutHelper::render( 
				'default_list',
				array('item' => $item, 'fieldsData' => $fieldsData,'param'=>$paramEdit,
					'columnsToShow' => $columnsToShow, 'ucmType' => $ucmTypeId),
					JPATH_SITE . '/' . 'modules/mod_ucm_data/tmpl');

			$newLimit++;
		}

		// Get the data needed in ajax format
		$result                        = array();
		$result['paginationIndexAjax'] = $newLimit;
		/** @scrutinizer ignore-call */
		$result['total']               = $model->getTotal();
		$result['records']             = $html;

		echo new JsonResponse($result);

		jexit();
	}



	public function setFilterFromDashboard()
	{	
		$app = Factory::getApplication();
		$data       = $app->input->getArray();

		$model = $this->getModel('items');
		// DPE HACK CAN GO IN CORE
		if (empty($data['tags']))
		{	
			$model->setState('filter.tags', '');
		}

		$app->close();
	}
}
