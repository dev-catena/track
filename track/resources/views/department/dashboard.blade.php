@extends('layouts.main')
@section('title')
    Dashboard
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0 d-flex align-items-center"><i class="tim-icons icon-chart-pie-36"></i> Manager Dashboard</h1>
            </div>
            <div class="col-sm-6">

            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button class="btn btn-primary float-right">
                    <i class="tim-icons icon-single-copy-04"></i>&nbsp; Export</button>
            </div>
        </div>
        <div class="row mt-3">

            <div class="col">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h3 class="display-5 mb-2">Total Docks</h3>
                            <h2 class="display-2 mb-2">5</h2>
                            <p>3 Active</p>
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
                            <h3 class="display-5 mb-2">Total Devices</h3>
                            <h2 class="display-2 mb-2">70</h2>
                            <p>62 Active</p>
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
                            <h3 class="display-5 mb-2">In Use</h3>
                            <h2 class="display-2 mb-2">70</h2>
                            <p>Currently in use</p>
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
                            <h3 class="display-5 mb-2">Failed or Overdue</h3>
                            <h2 class="display-2 mb-2">6</h2>
                            <p>Need atention</p>
                        </div>
                        <div class="right card-icon">
                            <i class="bi bi-exclamation-triangle fa-4x position-absolute text-danger" style="top:20%;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
@endsection
