var tjucm = {
    documents: {
        showHide: function(divId) {
            var divIdArr = divId.split("_");
            var linkDiv = jQuery("#" + divId);
            var moreLess = linkDiv.css("display");

            if (moreLess == 'none' || moreLess == '') {
                linkDiv.css("display", "block");
                jQuery(".document-more_" + divIdArr[1]).hide();
                jQuery(".document-less_" + divIdArr[1]).show();
            } else {
                linkDiv.css("display", "none");
                jQuery(".document-more_" + divIdArr[1]).show();
                jQuery(".document-less_" + divIdArr[1]).hide();
            }
        }
    }
}

jQuery(document).on('click', '.more-less', function() {
    tjucm.documents.showHide(jQuery(this).data("div"))
});


jQuery(document).on('change', '#jform_ucm_type', function(e) {
    var typeId = jQuery(this).val();
    jQuery('.tags .tag-list').html('');
    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
        type: 'POST',
        data: {
            typeId: typeId,
            task: 'document.getSupportedTags',
            format: 'json'
        },
        dataType: "json"
    }).done(function(data) {
        console.log(data);
        jQuery.each(data.data, function(key, value) {
            jQuery('.tags .tag-list').append("<li title='" + value.type + "'>" + "<strong>" + value.title + "</strong>" + ": " + '{{' + value.name + '}}' + "</li>");
        });
    });

});

jQuery(document).on('change', '#jform_ucm_type', function(e) {
    var typeId = jQuery(this).val();
    jQuery('.filters').html('');
    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
        type: 'POST',
        data: {
            typeId: typeId,
            task: 'document.getFilters',
            format: 'json',
            data: jQuery('#params-value').val()
        },
        dataType: "json"
    }).done(function(response) {
        if (response.success == true) {
            response.data.html = response.data.html.replace(/jform\[/g, "jform[params][filters][");


            jQuery('#ucm-document-edit .filters').html(response.data.html);
            jQuery('#ucm-document-edit').trigger('subform-row-add', jQuery('.filters'));
            jQuery('#ucm-document-edit .filters').find('.required').removeClass("required");
            //jQuery('#ucm-document-edit .filters').find("span.star").remove();

            try {
                eval(response.data.script);
            } catch (err) {
                console.log(err);
            }
        } else {
            alert(response.message);
        }
    });
});

jQuery(document).ready(function() {
    var type = jQuery('#jform_ucm_type');

    if (type.val()) {
        type.trigger("change");
    }
})
