var gcwp_columns;
var gcwp_rows;

jQuery('.gcwp-add-row').live('click', function(event) {
        gcwp_columns = jQuery('#gcwp-data tr:last td').length;
        gcwp_rows = jQuery('#gcwp-data tr').length;
        jQuery('#gcwp-data tr:last').each(function() {
                var html = '<tr>';
                for (var i = 0; i < gcwp_columns; i++) {
                    html += '<td><input type="text" name="gcwp[data_table][columns]['+i+'][]" /></td>';
                }
                html += '</tr>';
                jQuery(this).after(html);
        });
        event.preventDefault();
});

jQuery('.gcwp-add-column').live('click', function(event) {
        gcwp_columns = jQuery('#gcwp-data tr:last td').length;
        gcwp_rows = jQuery('#gcwp-data tr').length;
        jQuery('#gcwp-data tr').each(function() {
            jQuery(this).find('th:last,td:last').each(function() {
                var html = '<' + this.tagName + '>';
                html += '<input type="text" name="gcwp[data_table][columns]['+gcwp_columns+'][]" />';
                html += '</' + this.tagName + '>';
                jQuery(this).after(html);
            });
        });
        event.preventDefault();
});