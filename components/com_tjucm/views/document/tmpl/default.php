<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
/** @var $this TjucmViewItemform */
?>
<script>
function copyToClipboard(element) {
	str = jQuery(element).html();
    function listener(e) {
        e.clipboardData.setData("text/html", str);
        e.clipboardData.setData("text/plain", str);
        e.preventDefault();
      }
      document.addEventListener("copy", listener);
      document.execCommand("copy");
      document.removeEventListener("copy", listener);
      alert("<?php echo Text::_('COM_TJUCM_DOCUMENT_COPY_SUCCESS_TEXT');?>");
   };

</script>

<div class="tj-ucm-document-container">
	<form action="" method="post" enctype="multipart/form-data" name="document-generate-form" id="document-generate-form">
		<input type="hidden" name="task" value="document.download"/>
		<input type="hidden" name="view" value="document"/>
		<input type="hidden" name="option" value="com_tjucm"/>
		<input type="hidden" name="id" value="<?php echo $this->item->id; ?>"/>
		<input type="hidden" name="cluster_id" value="<?php echo $this->clusterId;?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>

	<div class="sticky-btn-cover text-right">
		<button type="button" class="btn btn-primary tj-ucm-document-download ml-10" onclick="document.getElementById('document-generate-form').submit();"><i class="fa fa-download mr-10"></i> <?php echo Text::_('COM_TJUCM_DOCUMENT_DOWNLOAD_PDF');?></button>
		<button type="button" class="btn btn-primary tj-ucm-document-download ml-10" onclick="exportHTML();"><i class="fa fa-download mr-10"></i> <?php echo Text::_('COM_TJUCM_DOCUMENT_DOWNLOAD_DOCX');?></button>
		<button type="button" class="btn btn-primary tj-ucm-document-download" onclick="copyToClipboard('.tj-ucm-document')"><i class="fa fa-clipboard mr-10" aria-hidden="true"></i><?php echo Text::_('COM_TJUCM_DOCUMENT_COPY');?></button>

	</div>

	<div class="tj-ucm-document" id="tj-ucm-document">
		<?php echo $this->loadTemplate('body');?>
	</div>

	<div class="alert alert-primary hide document-generate-failed" role="alert"><?php echo Text::sprintf('COM_TJUCM_DOCUMENT_GENERATE_FAILED_MESSAGE', '<a href="javascript:void(0)"  class="" onclick="generateDocument();">' .  Text::_("COM_TJUCM_DOCUMENT_GENERATE_RETRY") . '</a>');?> </div>
</div>
<script>
    function exportHTML(){
       var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' "+
            "xmlns:w='urn:schemas-microsoft-com:office:word' "+
            "xmlns='http://www.w3.org/TR/REC-html40'>"+
            "<body>";
       var footer = "</body></html>";
       var sourceHTML = header+document.getElementById("tj-ucm-document").innerHTML+footer;

       var source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
       var fileDownload = document.createElement("a");
       document.body.appendChild(fileDownload);
       fileDownload.href = source;
       fileDownload.download = 'document.docx';
       fileDownload.click();
       document.body.removeChild(fileDownload);
    }
</script>
