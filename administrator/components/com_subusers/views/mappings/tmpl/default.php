<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Subusers
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2015. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Layout\LayoutHelper;

JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet(JPATH_ROOT . 'media/com_subusers/css/list.css');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_subusers');
$saveOrder = $listOrder == 'a.`ordering`';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_subusers&task=mappings.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'mappingList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.orderTable = function () {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	};

	jQuery(document).ready(function () {
		jQuery('#clear-search-button').on('click', function () {
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});
</script>

<?php

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

?>

<form action="<?php echo Route::_('index.php?option=com_subusers&view=mappings'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>
		<?php
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
			<div class="clearfix"></div>
			<table class="table table-striped" id="mappingList">
				<thead>
				<tr>
					<?php if (isset($this->items[0]->ordering)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.`ordering`', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
						</th>
					<?php endif; ?>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value=""
							   title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
					</th>
					<?php if (isset($this->items[0]->state)): ?>
					<th width="1%" class="nowrap center">
						<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.`state`', $listDirn, $listOrder); ?>
					</th>
					<?php endif; ?>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_SUBUSERS_MAPPINGS_ROLE_NAME', 'roles.name', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_SUBUSERS_MAPPINGS_ROLE_ID', 'a.`role_id`', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_SUBUSERS_MAPPINGS_ACTION_NAME', 'roles.name', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_SUBUSERS_MAPPINGS_ACTION_ID', 'a.`action_id`', $listDirn, $listOrder); ?>
					</th>
					<?php if (isset($this->items[0]->id)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.`id`', $listDirn, $listOrder); ?>
						</th>
					<?php endif; ?>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering   = ($listOrder == 'a.ordering');
					$canCreate  = $user->authorise('core.create', 'com_subusers');
					$canEdit    = $user->authorise('core.edit', 'com_subusers');
					$canCheckin = $user->authorise('core.manage', 'com_subusers');
					$canChange  = $user->authorise('core.edit.state', 'com_subusers');
					?>
					<tr class="row<?php echo $i % 2; ?>">

						<?php if (isset($this->items[0]->ordering)) : ?>
							<td class="order nowrap center hidden-phone">
								<?php if ($canChange) :
									$disableClassName = '';
									$disabledLabel    = '';

									if (!$saveOrder) :
										$disabledLabel    = Text::_('JORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									endif; ?>
									<span class="sortable-handler hasTooltip <?php echo $disableClassName ?>"
										  title="<?php echo $disabledLabel ?>">
							<i class="icon-menu"></i>
						</span>
									<input type="text" style="display:none" name="order[]" size="5"
										   value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
								<?php else : ?>
									<span class="sortable-handler inactive">
							<i class="icon-menu"></i>
						</span>
								<?php endif; ?>
							</td>
						<?php endif; ?>
						<td class="hidden-phone">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<?php if (isset($this->items[0]->state)): ?>
						<td class="center">
							<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'mappings.', $canChange, 'cb'); ?>
						</td>
						<?php endif; ?>
						<td>
							<?php echo $item->role_name; ?>
						</td>
						<td>
							<?php echo $item->role_id; ?>
						</td>
						<td>
							<?php echo $item->action_name; ?>
						</td>
						<td>
							<?php echo $item->action_id; ?>
						</td>
						<?php if (isset($this->items[0]->id)): ?>
							<td class="center hidden-phone">
								<?php echo (int) $item->id; ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
</form>        
