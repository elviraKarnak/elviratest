jQuery(document).ready(function($) {
    'use strict';
    
    // Add agent
    $('#ewqc-add-agent').on('click', function() {
        var $tbody = $('#ewqc-agents-list');
        var index = $tbody.find('tr').length;
        
        var row = '<tr>' +
            '<td><input type="text" name="ewqc_agents[' + index + '][name]" class="regular-text" /></td>' +
            '<td><input type="text" name="ewqc_agents[' + index + '][title]" class="regular-text" /></td>' +
            '<td><input type="text" name="ewqc_agents[' + index + '][phone]" class="regular-text" /></td>' +
            '<td><input type="checkbox" name="ewqc_agents[' + index + '][active]" value="1" checked /></td>' +
            '<td><button type="button" class="button ewqc-remove-agent">Remove</button></td>' +
            '</tr>';
        
        $tbody.append(row);
    });
    
    // Remove agent
    $(document).on('click', '.ewqc-remove-agent', function() {
        $(this).closest('tr').remove();
    });
});