

var table = $('#logs-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url:logListUrl,
        data: function (d) {
            d.organization = $('#companyFilter').val();
            d.department = $('#departmentFilter').val();
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
        { data: 'action', name: 'action' },
        { data: 'entity', name: 'entity' },
        { data: 'description', name: 'description' },
        { data: 'createdBy', name: 'createdBy' },
        { data: 'ip_address', name: 'ip_address' },
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
    const headers = ['Timestamp', 'Action', 'Entity', 'Details','User','IP Address'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const timestamp = escapeCSV(row.created_at) || '';
        //get only text from action which is html
        const action = escapeCSV($(row.action).text()) || '';
        const entity = escapeCSV(row.entity) || '';
        const details = escapeCSV(row.description) || '';
        const user = escapeCSV(row.createdBy) || '';
        const ipAddress = escapeCSV(row.ip_address) || '';
        csvContent += [timestamp, action, entity, details, user, ipAddress].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "logs_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});


$('#departmentFilter,#entityTypeFilter, #actionFilter').on('change', function () {
    table.ajax.reload();
});

$('#companyFilter').on('change', function () {
    let companyId = $(this).val();
    $('#departmentFilter').html('<option value="">Select Department</option>');

    if (!companyId) {
        table.ajax.reload();
        return;
    }

    getDepartmentListByCompanyId(companyId,'logs',table);
});

let debounceSearchTimer;
$('#searchInput').on('input', function () {
    var searchTerm = $(this).val().trim();

    clearTimeout(debounceSearchTimer);

    debounceSearchTimer = setTimeout(function () {
        table.search(searchTerm).draw();
    }, 500);
});
