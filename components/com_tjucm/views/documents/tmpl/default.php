<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('script', 'media/com_dpe/js/tjucm.js');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('script', 'media/com_tjucm/js/document.min.js');

/** @var $this TjucmViewDocuments */
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$clusterId = 0;
?>

<form action="<?php echo Uri::getInstance()->toString(); ?>" method="post"
	name="adminForm" id="adminForm">
		<div class="dp-search-filter">
			<div id="filter-progress-bar" class="col-sm-12 col-md-4 pl-0 dp-search-filter">
					<div class="input-group">
							<input type="text" class="w-100" name="filter[search]" id="filter_search"
									title="<?php echo empty($firstListColumn) ? Text::_('JSEARCH_FILTER') : Text::sprintf('COM_TJUCM_ITEMS_SEARCH_TITLE', $this->listcolumn[$firstListColumn]); ?>"
									value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
									placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
									<span class="input-group-btn">
											<button class="btn btn-default" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
											<button class="btn btn-basic qtc-hasTooltip br-4 mx-10" id="clear-search-button" onclick="document.getElementById('filter_search').value='';this.form.submit();" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">Clear</button>
								</span>
					</div>
			</div>
			<?php
				// Check if com_cluster component is installed
				if (ComponentHelper::getComponent('com_cluster', true)->enabled)
				{
					FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
					$cluster           = FormHelper::loadFieldType('cluster', false);
					$this->clusterList = $cluster->getOptionsExternally();

					if (empty($this->clusterList['0']->value))
					{
						unset($this->clusterList['0']);
					}

					$clusterId = $this->state->get('filter.cluster_id', $this->clusterList['1']->value);

					?>
					<div class="btn-group md-w-300px text-left">
						<?php
						echo HTMLHelper::_('select.genericlist', $this->clusterList, "filter[cluster_id]", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get('filter.cluster_id', $this->clusterList['0']->value));
						?>
					</div>
					<?php
				}
			?>
			<div class="btn-group pull-right ml-20">
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
		<div class="table-responsive mt-20px">
			<?php
			if (empty($this->items))
			{
			?>
				<div class="alert alert-no-items">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php
			}
			else
			{?>
				<table class="table table-striped" id="activityList">
					<thead class="thead-light">
						<tr>
							<th width="30%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJUCM_DOCUMENT_TITLE', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th width="50%">
							<?php echo Text::_('COM_TJUCM_DOCUMENT_DESCRIPTION'); ?>
							</th>
							<th width="20%">
							<?php echo Text::_('COM_TJUCM_DOCUMENT_ACTION_HEADING'); ?>
							</th>
						</tr>
					</thead>
					<tfoot>
						<tr class="pagers">
							<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 5; ?>">
								<div class="pager" id="pagination">
									<?php echo $this->pagination->getPagesLinks(); ?>
									<hr class="hr hr-condensed"/>
								</div>
							</td>
						</tr>
					</tfoot>
					<tbody>
					<?php foreach ($this->items as $i => $item)
					{
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td>
							<div class="break-word doc-title">
								<?php echo $this->escape($item->title); ?>
							</div>
						</td>
						<td>
							<div>
								<?php
								$lessonDescCharLimit = 100;

								if (strlen($item->description) > $lessonDescCharLimit)
								{
									echo substr(strip_tags($item->description), 0, $lessonDescCharLimit);?>

									<div class="mid" id="HiddenDiv_<?php echo $i ?>" style="">
										<?php echo substr(strip_tags($item->description), $lessonDescCharLimit, strlen($item->description));?>
									</div>
									<a class="more-less document-more_<?php echo $i ?>" data-div="HiddenDiv_<?php echo $i ?>">
										<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_MORE');?>
									</a>
									<a class="more-less document-less_<?php echo $i ?>" style="display:none" data-div="HiddenDiv_<?php echo $i ?>">
										<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_LESS');?>
									</a>
								<?php
								}
								else
								{
									echo $this->escape($item->description);
								}
								?>
							</div>
						</td>

						<td>
						<?php
						$link = 'index.php?option=com_tjucm&view=document&id=' . $item->id . '&tmpl=component&cluster_id=' . $clusterId;
						?>
							<a class="d-inline-block mr-4" href="javascript:void(0);" onclick="openDocumentPopup('<?php echo Route::_($link, false);?>')" title="<?php echo JTEXT::_('COM_TJUCM_DOCUMENT_PREVIEW');?>" >
								<i class="fa fa-file-text" aria-hidden="true"></i>
							</a>
	
						</td>
					</tr>
					<?php
					}
					?>
					</tbody>
				</table>
		<?php
			}
			?>
		</div>
		<div class="col-xs-12">
			<div class="pull-right">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
		</div>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
