var gcwp_columns;
var gcwp_rows;

jQuery('.gcwp-add-row').live('click', function(event) {
        gcwpFadeColumns();
        gcwp_columns = jQuery('#gcwp-data tr:last td').length;
        gcwp_rows = jQuery('#gcwp-data tr').length;
        jQuery('#gcwp-data tr:last').each(function() {
                var html = '<tr>';
                for (var i = 0; i < gcwp_columns; i++) {
                    html += '<td><input type="text" name="gcwp[data_table][columns]['+i+'][]" /></td>';
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
            jQuery(this).find('th:last,td:last').each(function() {
                var html = '';
                if (i == 0 || i == gcwp_rows-1) {
                    html += '<td class="action"><a href="#" class="gcwp-delete-column submitdelete">Delete</a></td>';
                } else {
                    html += '<' + this.tagName + '>';
                    html += '<input type="text" name="gcwp[data_table][columns]['+gcwp_columns+'][]" />';
                    html += '</' + this.tagName + '>';
                }
                i++;
                jQuery(this).after(html);
            });
        });
        gcwpFadeColumns();
        event.preventDefault();
});

function gcwpFadeColumns() {
    jQuery('#gcwp-format input[type="radio"]:checked').each(function() { 
        if (jQuery(this).val() == 'pie-chart') {
            jQuery('#gcwp-data tr').each(function() {
                var i = 0;
                jQuery(this).find('th input,td input').each(function() {
                    if (i > 1) {
                        jQuery(this).fadeTo('fast', 0.5);
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
    jQuery('#gcwp-format input[type="radio"]').bind('change', function() {
        gcwpFadeColumns();
    });
    
    jQuery("a.gcwp-delete-column").live("click", function() {
        /* Better index-calculation from @activa */
        var myIndex = jQuery(this).closest("td").prevAll("td").length;
        jQuery(this).parents("table").find("tr").each(function(){
            jQuery(this).find("td:eq("+myIndex+"),th:eq("+myIndex+")").remove();
            fixIndices();
        });
        event.preventDefault();
    });
});