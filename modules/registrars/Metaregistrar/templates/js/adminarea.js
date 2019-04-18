$(document).ready(function() {
    var datatable = $('#poll-data-table').DataTable({
        "aoColumns": [
            null,
            { "bSortable": false }
        ]
    });
   
    $('#poll-data-table_filter').prepend('<label>To:<input type=\'text\' id=\'to\' name=\'to\' class=\'form-control input-sm datepicker\'></label> ');
    $('#poll-data-table_filter').prepend('<label>From:<input type=\'text\' id=\'from\' name=\'from\' class=\'form-control input-sm datepicker\'></label> ');
    $('.datepicker').datepicker({dateFormat: "dd/mm/yy",});
    
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var from    = $('#from').val();
            var to      = $('#to').val();
            var date    = data[0] || 0; 
            
            from = $.datepicker.parseDate('dd/mm/yy', from);
            to = $.datepicker.parseDate('dd/mm/yy', to);
            date = $.datepicker.parseDate('dd/mm/yy', date);
            
            if  (
                    (from == null && to == null) ||
                    (from == null && date <= to) ||
                    (to == null && date >= from) ||
                    (date <= to && date >= from)
                )
            {
                return true;
            }
            return false;
        }
    );

    $('.datepicker').change( function() {
        datatable.draw();
    });
});
