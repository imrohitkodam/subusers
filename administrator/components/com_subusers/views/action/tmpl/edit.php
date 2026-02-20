<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

Factory::getDocument()->addScriptDeclaration(
		'
	Joomla.submitbutton = function(task)
	{
		if (task == "action.cancel" || document.formvalidator.isValid(document.getElementById("action-form")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("action-form"));
		}
	};
');
?>

<form
	action="<?php echo Route::_('index.php?option=com_subusers&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm"
	id="action-form" class="form-validate">
	<div class="form-horizontal">
		<div class="row-fluid">
			<div class="span9">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('name'); ?>
				<?php echo $this->form->renderField('code'); ?>
				<?php echo $this->form->renderField('client'); ?>
				<?php echo $this->form->getInput('created_date'); ?>
			</div>
		</div>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
