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
HTMLHelper::_('behavior.keepalive');

Factory::getDocument()->addScriptDeclaration(
		'
	Joomla.submitbutton = function(task)
	{
		if (task == "user.cancel" || document.formvalidator.isValid(document.getElementById("user-form")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("user-form"));
		}
	};
');
?>

<form
	action="<?php echo Route::_('index.php?option=com_subusers&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm"
	id="user-form" class="form-validate">
	<div class="form-horizontal">
		<div class="row-fluid">
			<div class="span9">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('user_id'); ?>
				<?php echo $this->form->renderField('role_id'); ?>
				<?php echo $this->form->renderField('client'); ?>
				<?php echo $this->form->renderField('client_id'); ?>
				<?php echo $this->form->getInput('created_by'); ?>
				<?php echo $this->form->getInput('created_date'); ?>
				<?php echo $this->form->getInput('modified_by'); ?>
				<?php echo $this->form->getInput('modified_date'); ?>
			</div>
		</div>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
