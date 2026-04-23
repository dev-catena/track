

var table = $('#notification-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url:notificationListUrl,
        data: function (d) {
            d.action = $('#actionFilter').val();
            d.entity = $('#entityTypeFilter').val();
        },
        // beforeSend: function () {
        //     StartLoading();
        // },
        // complete: function () {
        //     StopLoading();
        // }
    } ,
    columns: [
        { data: 'created_at', name: 'created_at' },
        { data: 'type', name: 'type' },
        { data: 'description', name: 'description' },
        { data: 'device', name: 'device' },
        { data: 'operator', name: 'operator' },
        { data: 'status', name: 'status' },
    ],
    paging: true,
    searching: true,
    ordering: false,
    dom: 'tip',
    //pageLength: 9,
    drawCallback: function (settings) {
        let data = table.rows().data();
        let wrapper = $('#table-data');
        wrapper.empty();

        if (data.length === 0) {
            wrapper.append(`
                <div class="col-12">
                    <div class="alert text-center m-3">
                        No data available.
                    </div>
                </div>
            `);
            return;
        }

        data.each(function (row) {
            wrapper.append(row.details);
        });
    }
});

$('#exportButtonCSV').on('click', function () {
    const data = table.rows().data();
    if (!data.length) {
        showAlert('warning', 'No data available to export.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";

    // Define CSV header
    const headers = ['Timestamp', 'Type', 'Details', 'Device','User','Status'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const timestamp = escapeCSV(row.created_at) || '';
        const type = escapeCSV(row.type) || '';
        const details = $('<div>').html(row.description).text();
        const device = escapeCSV(row.device) || '';
        const user = escapeCSV(row.operator) || '';
        const status = escapeCSV($(row.status).text()) || '';
        csvContent += [timestamp, type, details, device, user, status].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "notification_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

$('#entityTypeFilter','#actionFilter').on('change', function () {
    table.ajax.reload();
});

let debounceSearchTimer;
$('#searchInput').on('input', function () {
    var searchTerm = $(this).val().trim();

    clearTimeout(debounceSearchTimer);

    debounceSearchTimer = setTimeout(function () {
        table.search(searchTerm).draw();
    }, 500);
});
