@extends('layouts.main')
@section('title')
    Gestão de Dispositivos
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
                <h1 class="m-0 d-flex align-items-center" style="text-wrap: nowrap;">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <span class="heading_title ml-2">Gestão de Dispositivos</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">
                    <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
                        <i class="fa fa-download"></i>&nbsp; Exportar</button>

                    <button class="btn btn-primary float-right " onclick="showModal(1);">
                        <i class="fa fa-plus"></i>&nbsp; Add Device</button>
                </div>

                <div class="form_data" style="display: none;">
                    <button class="btn btn-primary float-right form_data" onclick="resetForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Back</button>
                </div>

            </div>
        </div>

        <div class="data_list">
            @include('common.device.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('common.device.form')
        </div>

        <div class="modal fade" id="smallModal" tabindex="-1" aria-labelledby="deviceLabel" aria-hidden="true">


            <div class="modal-dialog modal-sm">
                <div class="modal-content text-center">
                    <div class="modal-header">
                        <h4 class="modal-title fw-bold">Adicionar Novo Dispositivo</h5>
                            <button type="button" class="btn-close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body " style="padding: 20px;">
                        <div class="svg-icon mx-auto mt-4" style="width:150px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg"
                                xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 256 256"
                                enable-background="new 0 0 256 256" xml:space="preserve">
                                <metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>
                                <g>
                                    <g>
                                        <g>
                                            <path fill="#0c5389"
                                                d="M87,10.7c-2.5,0.9-5.1,3.2-6.4,5.6l-1.1,2.2v86.3c0,79.9,0.1,86.4,0.9,87.8c1,1.9,4.6,5.2,6.3,5.8c0.8,0.3,17.2,0.5,41.5,0.5c36,0,40.3-0.1,41.8-0.8c2.3-1.2,4.4-3.2,5.5-5.4c1-1.9,1-4,0.9-88.7l-0.2-86.7l-1.6-2.3c-1-1.3-2.7-2.8-4.3-3.6l-2.7-1.4l-39.5,0.1C99.2,10.1,88.2,10.3,87,10.7z M140.7,22.2c0.9,0.9,1.5,2,1.5,2.9c0,0.8-0.6,1.9-1.5,2.9l-1.5,1.5h-11h-11l-1.6-1.3c-2.1-1.7-2.2-3.8-0.1-5.9l1.5-1.5h11.1h11.1L140.7,22.2z M169.2,102.7c0.1,58.5,0.1,62.8-0.8,63.3c-0.7,0.5-9.6,0.7-40.4,0.7c-38.9,0-39.6,0-40.6-1c-1-1-1-1.7-1-62.5c0-41.5,0.2-61.8,0.5-62.5c0.6-1,1.4-1,41.4-0.9l40.8,0.2L169.2,102.7z M132.5,174.8c5.4,2.9,5.3,11.9-0.2,14.7c-2.3,1.2-6.4,1.1-8.5-0.1c-2.7-1.6-3.8-3.4-4-6.8c-0.4-5.4,2.9-8.9,8.3-8.9C129.7,173.8,131.4,174.2,132.5,174.8z"
                                                data-title="Layer 0" xs="0"></path>
                                            <path fill="#0c5389"
                                                d="M45.6,196.3c-5.6,1.1-12,5.8-14.7,10.8c-2.7,4.9-2.9,6.6-2.9,23.4V246l100-0.1l99.9-0.2v-15.9c0-14.1-0.1-16.2-0.9-18.6c-2.2-6.3-7.2-11.5-13.6-14c-2.8-1.1-3.6-1.2-20.6-1.3l-17.8-0.2l-1.4,1.4c-0.8,0.8-2.4,1.8-3.5,2.3c-1.9,0.9-4.2,1-41.7,1c-24.7,0-40.4-0.2-41.5-0.5c-1-0.3-2.8-1.3-3.9-2.3l-2.1-1.8l-16.6,0.1C55.2,195.9,46.8,196.1,45.6,196.3z M213.7,210.4c1.3,1.3,1.7,2.2,1.7,3.6c0,1.5-0.4,2.3-1.7,3.6c-1.3,1.3-2.2,1.7-3.6,1.7s-2.3-0.4-3.6-1.7c-1.3-1.3-1.7-2.2-1.7-3.6c0-2.7,2.7-5.4,5.4-5.4C211.5,208.6,212.3,209,213.7,210.4z"
                                                data-title="Layer 1" xs="1"></path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <p class="mt-2 pb-5">Approach the device to the dock</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const deviceListUrl = "{{ url(request()->segment(1) . '/device') }}";
        const deviceSaveUrl = "{{ route('device.store') }}";
        const deviceDetailUrl = "{{ route('device.detail', ':id') }}";
        const deviceUpdateUrl = "{{ route('device.update', ':id') }}";
        const deviceDeleteUrl = "{{ route('device.destroy', ':id') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/device.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/device.js')) }}">
    </script>
@endsection
