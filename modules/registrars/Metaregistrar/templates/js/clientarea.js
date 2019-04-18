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
    
    $('#from').css("cssText", "padding-left: 10px !important;").css('background-image', 'none');
    $('#to').css("cssText", "padding-left: 10px !important;").css('background-image', 'none');
    
    var bottomRow = $('#poll-data-table').parent().parent().next();
    var midRow  = $('#poll-data-table').parent().parent();
    var topRow = $('#poll-data-table').parent().parent().prev();
    
    var lengthDiv = $('.dataTables_length').parent();
    var infoDiv   = $('#poll-data-table_info').parent();
    var paginateDiv = $('#poll-data-table_paginate').parent();
    var filterDiv = $('#poll-data-table_filter').parent();
    
    paginateDiv.css('padding-bottom', 15);
    lengthDiv.removeClass("col-sm-6" ).addClass("col-sm-5").css('padding-bottom', 15);
    filterDiv.removeClass("col-sm-6" ).addClass("col-sm-12").css('padding-top', 15);
    infoDiv.removeClass("col-sm-5" ).addClass("col-sm-12");
    
    bottomRow.prepend(lengthDiv);
    
    topRow.css('background-color', '#f6f6f6');
    midRow.css('background-color', '#f6f6f6');
    bottomRow.css('background-color', '#f6f6f6');
    
    infoDiv.children().css("cssText", "padding: 12px !important;").css('background-color', '#f6f6f6').css('color', '#000000').css('border', 'none');
    filterDiv.children().css('float', 'left');
    
    topRow.append(infoDiv);
    
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
