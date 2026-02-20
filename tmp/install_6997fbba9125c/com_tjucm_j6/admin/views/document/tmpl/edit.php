<?php
/**
 * @package    Com_Tjucm
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('script', 'administrator/components/com_tjucm/assets/js/document.js');
HTMLHelper::_('jquery.token');

Factory::getDocument()->addScriptDeclaration(
		'
	Joomla.submitbutton = function(task)
	{
		if (task == "document.cancel" || document.formvalidator.isValid(document.getElementById("document-form")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("document-form"));
		}
	};
');

/** @var $this TjucmViewDocument */
?>

<form
	action="<?php echo Route::_('index.php?option=com_tjucm&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm"
	id="document-form" class="form-validate">
	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'ucm-document-edit')); ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'ucm-document-edit', Text::_('COM_TJUCM_TITLE_TYPE', true)); ?>
		<div class="row-fluid">
			<div class="span8">
			<?php
				echo $this->form->renderField('title');
				echo $this->form->renderField('document_type');
				echo $this->form->renderField('ucm_type');
				echo $this->form->renderField('state');
				echo $this->form->renderField('description');
				echo $this->form->renderField('ropprocess');
				echo $this->form->renderField('document_body');
				$paramsData = new Registry($this->form->getValue('params')['filters']);
				?>
				<div class="control-group">
					<div class = "control-label"><?php echo Text::_('COM_TJUCM_DOCUMENT_TJUCM_TYPE_FILTER'); ?></div>
					<input id="params-value" name="params-value" type='hidden' value='<?php echo addslashes($paramsData->toString());?>'>
					<div class="controls filters">
					</div>
				</div>
			</div>
			<h3><?php echo Text::_('COM_TJUCM_TAGS');?></h3>
			<div class="tags span4">
				<ol class="tag-list">
				</ol>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

				<?php
				$this->set('ignore_fieldsets', array('permissions'));
				// Loading joomla's params layout to show the fields and field group added in params layout.
				echo LayoutHelper::render('joomla.edit.params', $this);
				?>


			<?php if (Factory::getUser()->authorise('core.admin', 'tjucm')) : ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php endif; ?>


		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
