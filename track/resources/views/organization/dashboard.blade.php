@extends('layouts.main')
@section('title')
    Dashboard
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0 d-flex align-items-center"><i class="tim-icons icon-chart-pie-36"></i> Company Dashboard</h1>
            </div>
            <div class="col-sm-6">
                {{-- <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard v1</li>
                </ol> --}}
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button class="btn btn-primary float-right" id="exportButtonCSV">
                    <i class="tim-icons icon-single-copy-04"></i>&nbsp; Export</button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2 text-nowrap">Total de Departamentos</h3>
                            <h2 class="display-2 mb-2">{{ $org_stats->total_departments }}</h2>
                            <p>{{ $org_stats->active_departments }} Active</p>
                        </div>
                        <div class="right card-icon">
                            <i class="fa-solid fa-4x fa-building position-absolute text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2">Total de Docas</h3>
                            <h2 class="display-2 mb-2">{{ $org_stats->total_docks }}</h2>
                            <p>{{ $org_stats->active_docks }} Active</p>
                        </div>
                        <div class="right card-icon">
                            <i class="fa-regular fa-4x fa-hard-drive position-absolute"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2">Total de Dispositivos</h3>
                            <h2 class="display-2 mb-2">{{ $org_stats->total_devices }}</h2>
                            <p>{{ $org_stats->active_devices }} Active</p>
                        </div>
                        <div class="right card-icon">
                            <i class="fa-solid fa-4x fa-mobile-screen-button position-absolute text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2">Em Uso</h3>
                            <h2 class="display-2 mb-2">{{ $org_stats->inuse_devices }}</h2>
                            <p>Atualmente em uso</p>
                        </div>
                        <div class="right card-icon">
                            <i class="fa-regular fa-4x fa-clock position-absolute text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2">Falha ou Atrasado</h3>
                            <h2 class="display-2 mb-2">{{ $org_stats->overdue_devices }}</h2>
                            <p>Necessita atenção</p>
                        </div>
                        <div class="right card-icon">
                            <i class="bi bi-exclamation-triangle fa-4x position-absolute text-danger" style="top:20%;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-stretch">
            <div class="col-md-4 ">
                <div class="card rounded-5 h-100">
                    <div class="card-header">
                        <h4 class="card-title"><i class="bi bi-graph-up-arrow"></i> Device Status Distribution</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">

                            <canvas id="device_status_chart" style="min-height: 300px"></canvas>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 ">
                <div class="card rounded-5 h-100">
                    <div class="card-header">
                        <h4 class="card-title"><i class="fa-regular fa-hard-drive"></i> Dock Status Distribution</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">

                            <canvas id="dock_status_chart" style="min-height: 300px"></canvas>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 ">
                <div class="card rounded-5 h-100">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa-solid fa-building"></i>

                            Department Overview
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">

                            <canvas id="department_overview_chart" style="min-height: 300px"></canvas>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="row mt-3">
            <h2 class="m-0 d-flex align-items-center"><i class="bi bi-clock-history mr-1"></i>Atividade Recente</h2>
            <div class="col-md-12 mt-3">
                <div class="card rounded-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-2">
                                <div class="py-1 rounded-5 bg-primary text-center text-white">CHECKOUT</div>
                            </div>
                            <div class="col-10">
                                <p class="mb-0">VR Headset checked out for training session</p>
                                <p class="mb-0">trainer@example.com • 24/06/2025, 14:24:44</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card rounded-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-2">
                                <div class="py-1 rounded-5 bg-success text-center text-white">CHECK-IN</div>
                            </div>
                            <div class="col-10">
                                <p class="mb-0">Device returned to dock 1231</p>
                                <p class="mb-0">user@example.com • 24/06/2025, 14:24:44</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card rounded-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-2">
                                <div class="py-1 rounded-5 bg-success text-center text-white">CREATE</div>
                            </div>
                            <div class="col-10">
                                <p class="mb-0">Created dock: Main Storage Dock</p>
                                <p class="mb-0">admin@example.com • 24/06/2025, 14:24:44</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card rounded-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-2">
                                <div class="py-1 rounded-5 bg-success text-center text-white">ASSIGN</div>
                            </div>
                            <div class="col-10">
                                <p class="mb-0">Device assigned to user for project work</p>
                                <p class="mb-0">user@example.com • 23/06/2025, 13:40:52 </p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
@endsection

@section('scripts')
    <script>
        const dsc = document.getElementById('device_status_chart').getContext('2d');
        var devices = [
            "{{ $org_stats->inuse_devices }}",
            "{{ $org_stats->available_devices }}",
            "{{ $org_stats->overdue_devices }}",
        ];


        new Chart(dsc, {
            type: 'doughnut', // or 'line', 'pie', etc.
            data: {
                labels: ['In use', 'Available', 'On alert', ],
                datasets: [{
                    label: '',
                    data: devices,
                    backgroundColor: ['#0c5389 ', '#199f85', '#dc3545'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });
    </script>

    <script>
        const orgStatsGraphUrl = "{{ route('admin.dashboard.graphStats') }}";


        $(document).ready(function() {
            $.ajax({
                url: orgStatsGraphUrl,
                method: "GET",
                success: function(resp) {
                    let ctx = document.getElementById('dock_status_chart').getContext('2d');
                    let ctx2 = document.getElementById('department_overview_chart').getContext('2d');

                    if (resp.status == 1) {
                        var data = resp.data;

                        if (data.hasOwnProperty('dock') && data.dock.length > 0) {
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: data.dock.map(d => d.name),
                                    datasets: [{
                                            label: 'Available',
                                            data: data.dock.map(d => d.available_devices),
                                            backgroundColor: '#199f85',
                                        },
                                        {
                                            label: 'In Use',
                                            data: data.dock.map(d => d.inuse_devices),
                                            backgroundColor: '#0c5389',
                                        },
                                        {
                                            label: 'Overdue',
                                            data: data.dock.map(d => d.overdue_devices),
                                            backgroundColor: '#dc3545',
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top'
                                        },
                                        tooltip: {
                                            enabled: true
                                        }
                                    }
                                }
                            });
                        }

                        if (data.hasOwnProperty('department') && data.department.length > 0) {
                            new Chart(ctx2, {
                                type: 'bar',
                                data: {
                                    labels: data.department.map(d => d.name),
                                    datasets: [{
                                            label: 'Available',
                                            data: data.department.map(d => d
                                                .available_devices),
                                            backgroundColor: '#199f85',
                                        },
                                        {
                                            label: 'In Use',
                                            data: data.department.map(d => d.inuse_devices),
                                            backgroundColor: '#0c5389',
                                        },
                                        {
                                            label: 'Overdue',
                                            data: data.department.map(d => d
                                                .overdue_devices),
                                            backgroundColor: '#dc3545',
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top'
                                        },
                                        tooltip: {
                                            enabled: true
                                        }
                                    }
                                }
                            });
                        }
                    }
                }
            });
        });
    </script>

    <script>
        $('#exportButtonCSV').on('click', function() {

            let csvContent = "data:text/csv;charset=utf-8,";

            // Define CSV header
            const headers = ['Total Departments', 'Total Docks', 'Total Devices', 'Devices In Use',
                'Failed or Overdue Devices'
            ];
            csvContent += headers.join(",") + "\r\n";

            // Extract values from the `row.details` HTML (you can adjust this structure)

            const totalDepartments = "{{ $org_stats->total_departments }}";
            const totalDocks = "{{ $org_stats->total_docks }}";
            const totalDevices = "{{ $org_stats->total_devices }}";
            const devicesInUse = "{{ $org_stats->inuse_devices }}";
            const failedOrOverdueDevices = "{{ $org_stats->overdue_devices }}";
            csvContent += [totalDepartments, totalDocks, totalDevices, devicesInUse, failedOrOverdueDevices].join(
                ",") + "\r\n";

            // Create and trigger download
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "dashboard_export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
@endsection
