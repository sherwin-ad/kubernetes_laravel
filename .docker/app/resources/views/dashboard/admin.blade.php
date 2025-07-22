@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Dashboard</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->
        </div>
        <div class="app-content">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header border-0">
                                <div class="d-flex justify-content-between">
                                    <h3 class="card-title">Aggregators Performance</h3>
                                    {{-- <a href="javascript:void(0);" class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">View Report</a> --}}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex">
                                    <p class="d-flex flex-column">
                                        <span class="fw-bold fs-5">₱{{ number_format($aggregators->sum(fn($aggregator) => $aggregator->transactions->sum('amount')), 2) }}</span> <span>Total Trx</span>
                                    </p>
                                    <p class="ms-auto d-flex flex-column text-end">
                                        {{-- <span class="text-success"> <i class="bi bi-arrow-up"></i> 12.5% </span> --}}
                                        {{-- <span class="text-secondary">Since last week</span> --}}
                                    </p>
                                </div>
                                <!-- /.d-flex -->
                                <div class="position-relative mb-4">
                                    <div id="visitors-chart"></div>
                                </div>
                                <div class="d-flex flex-row justify-content-end">
                                    <span class="me-2">
                                        <i class="bi bi-square-fill text-primary"></i> This Week
                                    </span>
                                    <span> <i class="bi bi-square-fill text-secondary"></i> Last Week </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <!-- /.card -->
                        <div class="card mb-4">
                            <div class="card-header border-0">
                                <h3 class="card-title">Aggregators</h3>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-tool btn-sm"> <i class="bi bi-download"></i> </a>
                                    <a href="#" class="btn btn-tool btn-sm"> <i class="bi bi-list"></i> </a>
                                </div>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>No. Merchant</th>
                                            <th>Trx</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($aggregators as $aggregator)
                                            <tr>
                                                <td>
                                                    {{-- <img src="../../dist/assets/img/default-150x150.png" alt="Product 1" class="rounded-circle img-size-32 me-2" /> --}}
                                                    {{ $aggregator->name }}
                                                </td>
                                                <td>{{ $aggregator->merchants->count() }}</td>
                                                <td>
                                                    {{-- <small class="text-success me-1">
                                                <i class="bi bi-arrow-up"></i>
                                                12%
                                            </small> --}}
                                                    ₱{{ number_format($aggregator->transactions->sum('amount'), 2) }}
                                                </td>
                                                <td>
                                                    <a href="#" class="text-secondary"> <i class="bi bi-search"></i> </a>
                                                </td>
                                            </tr>
                                        @empty
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::App Content-->
    </main>
@endsection

@section('scripts')
    <!-- apexcharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js" integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
    <script>
        // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
        // IT'S ALL JUST JUNK FOR DEMO
        // ++++++++++++++++++++++++++++++++++++++++++

        const visitors_chart_options = {
            series: [{
                    name: 'High - 2023',
                    data: {!! json_encode($per_day_transaction_amount_high) !!},
                },
                {
                    name: 'Low - 2023',
                    data: {!! json_encode($per_day_transaction_amount_low) !!},
                },
            ],
            chart: {
                height: 200,
                type: 'line',
                toolbar: {
                    show: false,
                },
            },
            colors: ['#0d6efd', '#adb5bd'],
            stroke: {
                curve: 'smooth',
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5,
                },
            },
            legend: {
                show: false,
            },
            markers: {
                size: 1,
            },
            xaxis: {
                categories: {!! json_encode($days) !!},
            },
        };

        const visitors_chart = new ApexCharts(
            document.querySelector('#visitors-chart'),
            visitors_chart_options,
        );
        visitors_chart.render();

        const sales_chart_options = {
            series: [{
                    name: 'Net Profit',
                    data: [44, 55, 57, 56, 61, 58, 63, 60, 66],
                },
                {
                    name: 'Revenue',
                    data: [76, 85, 101, 98, 87, 105, 91, 114, 94],
                },
                {
                    name: 'Free Cash Flow',
                    data: [35, 41, 36, 26, 45, 48, 52, 53, 41],
                },
            ],
            chart: {
                type: 'bar',
                height: 200,
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded',
                },
            },
            legend: {
                show: false,
            },
            colors: ['#0d6efd', '#20c997', '#ffc107'],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent'],
            },
            xaxis: {
                categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            },
            fill: {
                opacity: 1,
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$ ' + val + ' thousands';
                    },
                },
            },
        };

        const sales_chart = new ApexCharts(
            document.querySelector('#sales-chart'),
            sales_chart_options,
        );
        sales_chart.render();
    </script>
@endsection
