@extends('layouts.main')
@section('title')
    Dashboard
@endsection
@section('content')
    <div class="content">
        <div class="row mb-3">
            <div class="col-sm-6">
                <h1 class="m-0 d-flex align-items-center"><i class="tim-icons icon-chart-pie-36"></i> Dashboard</h1>
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
                    <i class="fa fa-download"></i>&nbsp; Export</button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h2 class="display-2 mb-2">{{ $organization_count }}</h2>
                        <p>Total de Empresas Ativas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h2 class="display-2 mb-2">{{ $inuse_device_count }}</h2>
                        <p>Dispositivos em uso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h2 class="display-2 mb-2">{{ $idle_device_count }}</h2>
                        <p>Dispositivos ociosos</p>
                    </div>
                </div>
            </div>
        </div>


    </div>
@endsection
@section('scripts')
    <script>
        $('#exportButtonCSV').on('click', function() {

            let csvContent = "data:text/csv;charset=utf-8,";

            // Define CSV header
            const headers = ['Active Companies', 'Devices in use', 'Idle Devices'];
            csvContent += headers.join(",") + "\r\n";

            // Extract values from the `row.details` HTML (you can adjust this structure)

            const activeCompanies = "{{ $organization_count }}";
            const devicesInUse = "{{ $inuse_device_count }}";
            const idleDevices = "{{ $idle_device_count }}";

            csvContent += [activeCompanies, devicesInUse, idleDevices].join(",") + "\r\n";

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
