var gcwp_columns;
var gcwp_rows;

function gcwpFadeColumns() {
    jQuery('#gcwp-format input[type="radio"]:checked').each(function() {

        if (jQuery(this).val() == 'pie-chart') {
            jQuery('#gcwp-data tr').each(function() {
                var i = 0;
                jQuery(this).find('th input,td input').each(function() {
                    if (i > 1) {
                        jQuery(this).fadeTo('fast', 0.3);
                    }
                    i++;
                });
            });
        } else {
            jQuery('#gcwp-data tr').each(function() {
                var i = 0;
                jQuery(this).find('th input,td input').each(function() {
                    if (i > 1) {
                        jQuery(this).fadeTo('fast', 1);
                    }
                    i++;
                });
            });
        }
    });
}

function fixIndices() {
    jQuery("tr").each(function(){
        var index = 0;
        jQuery(this).find('td input,th input').each(function() {
            jQuery(this).attr('name', 'gcwp[data_table][columns][' + (index) + '][]');
            index++;
        });
    });
}

jQuery(document).ready(function() {
    gcwpFadeColumns();

    jQuery('.gcwp-add-row').live('click', function(event) {
            gcwpFadeColumns();
            gcwp_columns = jQuery('#gcwp-data tr:last td').length;
            gcwp_rows = jQuery('#gcwp-data tr').length;
            jQuery('#gcwp-data tr:last').each(function() {
                    var html = '<tr>';
                    for (var i = 0; i < gcwp_columns; i++) {
                        if (i == 0 || i == gcwp_columns-1) {
                            html += '<td class="action"><a href="#" class="gcwp-delete-row submitdelete">'+gcwp_delete+'</a></td>';
                        } else {
                            html += '<td><input type="text" name="gcwp[data_table][columns]['+(i-1)+'][]" /></td>';
                        }
                    }
                    html += '</tr>';
                    jQuery(this).before(html);
            });
            gcwpFadeColumns();
            event.preventDefault();
    });

    jQuery('.gcwp-add-column').live('click', function(event) {
            gcwp_columns = jQuery('#gcwp-data tr:last td').length;
            gcwp_rows = jQuery('#gcwp-data tr').length;

            var i = 0;
            jQuery('#gcwp-data tr').each(function() {
                jQuery(this).find('td:last').each(function() {
                    var html = '';
                    var tagName = 'td';
                    if (i == 0 || i == gcwp_rows-1) {
                        html += '<td class="action"><a href="#" class="gcwp-delete-column submitdelete">'+gcwp_delete+'</a></td>';
                    } else {
                        if (i == 1) {
                            tagName = 'th';
                        }
                        html += '<' + tagName + '>';
                        html += '<input type="text" name="gcwp[data_table][columns][' + (gcwp_columns-2) + '][]" />';
                        html += '</' + tagName + '>';
                    }
                    i++;
                    jQuery(this).before(html);
                });
            });
            gcwpFadeColumns();
            event.preventDefault();
    });

    jQuery('#gcwp-format input[type="radio"]').bind('change', function() {
        gcwpFadeColumns();
        jQuery('.gcwp-chart-information').each(function() {
            jQuery(this).toggle();
        });
    });

    jQuery("a.gcwp-delete-column").live("click", function() {
        /* Better index-calculation from @activa */
        var myIndex = jQuery(this).closest("td").prevAll("td").length;

        jQuery(this).parents("table").find("tr").each(function() {

                jQuery(this).find("td:eq("+myIndex+"),th:eq("+(myIndex-1)+")").remove();

            fixIndices();
        });
        event.preventDefault();
    });

    jQuery("a.gcwp-delete-row").live("click", function() {
        var myIndex = jQuery(this).closest("tr").remove();
        event.preventDefault();
    });
});