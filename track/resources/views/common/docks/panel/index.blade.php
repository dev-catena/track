@extends('layouts.main')
@section('title')
    Docks Panel
@endsection
@section('style')
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #fff !important;
        }


        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #0c5389 !important;
            border-color: #0c5389 !important;
            color: #fff !important;
            opacity: 0.5;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0c5389 !important;
            border-color: #0c5389 !important;
            color: #fff !important;
        }

        .dataTables_length,
        .dataTables_filter {
            display: none !important;
        }

        table.dataTable tbody th,
        table.dataTable tbody td,
        table.dataTable>thead>tr>th,
        table.dataTable>thead>tr>td {
            background-color: transparent !important;
        }

        table .dropdown-menu:before {
            display: none;
        }

        table .dropdown-menu {
            min-width: 7rem;
        }
    </style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="fa-regular fa-hard-drive"></i>
                    <span class="heading_title ml-2">Docks Panel</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4 col-6">
                            <select class="form-control select-control mt-1" id="dockDropdown">
                                <option value="">Select Dock</option>
                                @foreach ($docks as $dock)
                                    <option value="{{ $dock->id }}">{{ $dock->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-6 pl-0">
                            <button class="btn btn-primary float-right" id="refreshButtonCSV">
                                <i class="fa fa-refresh"></i>&nbsp; Refresh</button>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="data_list">
            <div class="row mt-3">
                <div class="col-md-2">
                    <div class="card rounded-4">
                        <div class="card-body position-relative">
                            <div class="left text-center">
                                <div class="d-inline-flex">
                                    <i class="bi bi-check2-circle fa-2x  text-success position-absolute"
                                        style="left: 10%; top: 16%;"></i>
                                    <h2 class="display-2 mb-2 text-center text-success ">{{ $dockStats->available_count }}
                                    </h2>
                                </div>

                                <p class="mb-2 text-nowrap text-center">Available</p>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card rounded-4">
                        <div class="card-body position-relative">
                            <div class="left text-center">
                                <div class="d-inline-flex">
                                    <i class="bi bi-lightning-charge fa-2x  text-warning position-absolute"
                                        style="left: 10%; top: 16%;"></i>
                                    <h2 class="display-2 mb-2 text-center text-warning">{{ $dockStats->inuse_count }}</h2>
                                </div>

                                <p class="mb-2 text-nowrap text-center">In Use</p>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card rounded-4">
                        <div class="card-body position-relative">
                            <div class="left text-center">
                                <div class="d-inline-flex">
                                    <i class="bi bi-exclamation-triangle fa-2x  text-danger position-absolute"
                                        style="left: 10%; top: 16%;"></i>
                                    <h2 class="display-2 mb-2 text-center text-danger">{{ $dockStats->maintenance_count }}
                                    </h2>
                                </div>

                                <p class="mb-2 text-nowrap text-center">Maintenance</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row-mt-3">
                <div class="card card-body">
                    <div class="d-flex justify-content-between">
                        <h3 class="fw-bold"><i class="fa-solid fa-mobile-screen-button"></i> Dock Store (10 slots)</h3>
                        <p class="float-end"><i class="fa-regular fa-clock"></i> Last updated: 13:05:26
                        </p>
                    </div>
                    <div class="row mt-3 dock-slot">
                        <div class="col">
                            <div class="card card-body  text-warning bg-warning-opacity">
                                <h3 class="text-center text-warning mb-2">1</h3>
                                <i class="bi bi-lightning-charge text-center "></i>
                                <p class="text-center text-warning">Medium Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-success bg-success-opacity">
                                <h3 class="text-center text-success mb-2">1</h3>
                                <i class="bi bi-stopwatch text-center "></i>
                                <p class="text-center text-success">High Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-danger bg-danger-opacity">
                                <h3 class="text-center text-danger mb-2">1</h3>
                                <i class="bi bi-exclamation-triangle text-center "></i>
                                <p class="text-center text-danger">Low Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body  text-warning bg-warning-opacity">
                                <h3 class="text-center text-warning mb-2">1</h3>
                                <i class="bi bi-lightning-charge text-center "></i>
                                <p class="text-center text-warning">Medium Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-success bg-success-opacity">
                                <h3 class="text-center text-success mb-2">1</h3>
                                <i class="bi bi-stopwatch text-center "></i>
                                <p class="text-center text-success">High Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-danger bg-danger-opacity">
                                <h3 class="text-center text-danger mb-2">1</h3>
                                <i class="bi bi-exclamation-triangle text-center "></i>
                                <p class="text-center text-danger">Low Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body  text-warning bg-warning-opacity">
                                <h3 class="text-center text-warning mb-2">1</h3>
                                <i class="bi bi-lightning-charge text-center "></i>
                                <p class="text-center text-warning">Medium Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-success bg-success-opacity">
                                <h3 class="text-center text-success mb-2">1</h3>
                                <i class="bi bi-stopwatch text-center "></i>
                                <p class="text-center text-success">High Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-danger bg-danger-opacity">
                                <h3 class="text-center text-danger mb-2">1</h3>
                                <i class="bi bi-exclamation-triangle text-center "></i>
                                <p class="text-center text-danger">Low Charge</p>

                            </div>

                        </div>
                        <div class="col">
                            <div class="card card-body text-danger bg-danger-opacity">
                                <h3 class="text-center text-danger mb-2">1</h3>
                                <i class="bi bi-exclamation-triangle text-center "></i>
                                <p class="text-center text-danger">Low Charge</p>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const dockListUrl = "{{ route('superadmin.manage.dock.panel') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/dock.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/dock.js')) }}">
    </script>
@endsection
