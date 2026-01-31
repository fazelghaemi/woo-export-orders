jQuery(document).ready(function ($) {
    $("#start_date, #end_date").datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Initialize the DataTable
    var oTable = $("#order_history").DataTable({
        "bSortClasses": false,
        "aaSorting": [[0, 'desc']],
        "bAutoWidth": true,
        "bInfo": true,
        "bScrollCollapse": true,
        "sPaginationType": "full_numbers",
        "bRetrieve": true,
        "pageLength": 5,
        "oLanguage": {
            "sSearch": "جستجو:",
            "sInfo": "نمایش _START_ تا _END_ از _TOTAL_ مورد",
            "sInfoEmpty": "نمایش 0 تا 0 از 0 مورد",
            "sZeroRecords": "هیچ موردی یافت نشد",
            "sInfoFiltered": "(فیلتر شده از مجموع _MAX_ مورد)",
            "sEmptyTable": "اطلاعاتی موجود نیست",
            "sLengthMenu": "نمایش _MENU_ مورد",
            "oPaginate": {
                "sFirst": "ابتدا",
                "sPrevious": "قبلی",
                "sNext": "بعدی",
                "sLast": "انتها"
            }
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'دانلود فایل به صورت اکسل',
                className: 'ui button'
            },
        ]
    });

    // Event handler for filter button
    $('#filter_orders').on('click', function (e) {
        e.preventDefault();

        var start_date = $('#start_date').val();
        var start_time = $('#start_time').val();
        var end_date = $('#end_date').val();
        var end_time = $('#end_time').val();

        var page_length = $('#page_length').val();

        $.ajax({
            url: ajax_obj.ajax_url,
            type: 'post',
            data: {
                action: 'export_orders_by_date',
                start_date: start_date,
                start_time: start_time,
                end_date: end_date,
                end_time: end_time,
                security: ajax_obj.nonce
            },
            success: function (response) {
                oTable.clear().draw();
                oTable.rows.add($(response)).draw();
                oTable.page.len(page_length).draw();
            }
        });
    });

    // Event handler for page length change
    $('#page_length').on('change', function () {
        var page_length = $(this).val();
        oTable.page.len(page_length).draw();
    });
});
