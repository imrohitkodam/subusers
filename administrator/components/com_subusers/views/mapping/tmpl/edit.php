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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;

HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet(JPATH_ROOT . 'media/com_subusers/css/edit.css');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {
		
	});

	Joomla.submitbutton = function (task) {
		if (task == 'mapping.cancel') {
			Joomla.submitform(task, document.getElementById('mapping-form'));
		}
		else {
			
			if (task != 'mapping.cancel' && document.formvalidator.isValid(document.getElementById('mapping-form'))) {
				
				Joomla.submitform(task, document.getElementById('mapping-form'));
			}
			else {
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>

<form
	action="<?php echo Route::_('index.php?option=com_subusers&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="mapping-form" class="form-validate">

	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_SUBUSERS_TITLE_MAPPING', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

									<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('role_id'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('role_id'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('action_id'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('action_id'); ?></div>
			</div>
				<input type="hidden" name="jform[client]" value="<?php echo $this->item->client; ?>" />

				<?php if(empty($this->item->created_by)){ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo Factory::getUser()->id; ?>" />

				<?php } 
				else{ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />

				<?php } ?>				
				<?php if(empty($this->item->checked_out)){ ?>
				<input type="hidden" name="jform[checked_out]" value="<?php echo Factory::getUser()->id; ?>" />
				<?php } 
				else{ ?>
					<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
				<?php }?>

				<?php if(empty($this->item->checked_out_time)){ ?>
				<input type="hidden" name="jform[checked_out_time]" value="<?php echo new Date('now').'.000000'; ?>" />
				<?php } 
				else{ ?>
					<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
				<?php }?>
				<?php if(empty($this->item->ordering)){ ?>
				<input type="hidden" name="jform[ordering]" value="<?php echo '0'; ?>" />
				<?php } 
				else{ ?>
					<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
				<?php }?>

				<?php if(empty($this->item->state)){ ?>
				<input type="hidden" name="jform[state]" value="<?php echo '1'; ?>" />
				<?php } 
				else{ ?>
					<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
				<?php }?>

				


				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>
