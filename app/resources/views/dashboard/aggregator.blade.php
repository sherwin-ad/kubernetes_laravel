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
                @include('partials.alerts')

                <!--begin::Row-->
                <div class="row">
                    <div class="col-12">
                        <div class="bg-info-subtle border border-info px-3" style="padding-top: 20px; padding-bottom: 20px">
                            <div class="border-0 d-flex flex-column">
                                <h3 class="card-title fw-bold fs-2">{{ $running_day_amount }}</h3>
                                <div>Running total for today ({{ date('F d, Y') }})</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="bg-success-subtle border border-success px-3" style="padding-top: 20px; padding-bottom: 20px">
                            <div class="border-0 d-flex flex-column">
                                <h3 class="card-title fw-bold fs-2">{{ $yesterday_amount }}</h3>
                                <div>Yesterday's total to settle ({{ date('F d, Y', strtotime('yesterday')) }})</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <hr>
                    </div>
                    <div class="col-12">
                        <h4>Payout Reports</h4>
                    </div>
                    <div class="col-md-4 ">
                        <div class="form-group d-flex flex-column mb-2 bg-body-secondary border pb-2 px-3">
                            <label for="start_date" class="col-form-label">Start Date</label>
                            <div class="col-sm-10 w-100">
                                <input type="date" class="form-control datepicker" id="start_date" name="start_date" placeholder="Select start date" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 ">
                        <div class="form-group d-flex flex-column mb-2 bg-body-secondary border pb-2 px-3">
                            <label for="end_date" class="col-form-label">End Date</label>
                            <div class="col-sm-10 w-100">
                                <input type="date" class="form-control datepicker" id="end_date" name="end_date" placeholder="Select end date" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Row-->

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group d-flex flex-column mb-2 bg-body-secondary border px-3" style="padding-top: 20px; padding-bottom: 20px">
                            <h3 class="card-title fw-bold fs-2" id="average_amount">₱0.00</h3>
                            <div>Average amount</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group d-flex flex-column mb-2 bg-body-secondary border px-3" style="padding-top: 20px; padding-bottom: 20px">
                            <h3 class="card-title fw-bold fs-2" id="total_amount">₱0.00</h3>
                            <div>Total amount</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group d-flex flex-column mb-2 bg-body-secondary border px-3" style="padding-top: 20px; padding-bottom: 20px">
                            <h3 class="card-title fw-bold fs-2" id="total_count">0</h3>
                            <div>Total count</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-5">
                    <table id="transaction_table" class="table table-striped table-bordered" width="100%">
                        <thead>
                            <tr>
                                <th width="10px">
                                    <input type="checkbox" class="check_all" name="" id="">
                                </th>
                                <th width="10px">#</th>
                                <th>{{ __('Merchant') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Transaction No.') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                {{-- <th width="100px">{{ __('Action') }}</th> --}}
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>

                <hr>

                <form action="{{route('updateCallback')}}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-sm-4">
                            <label for="">Callback URL</label>
                            <input name="callback_url" type="text" class="form-control" value="{{ Auth::user()->aggregator->callback_url }}">

                            <button type="submit" class="btn btn-primary mt-2   ">Update</button>
                        </div>
                    </div>
                </form>
            </div>
            <!--end::Container-->
        </div>
        <!--end::App Content-->
    </main>
@endsection

@section('scripts')
    <!-- apexcharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js" integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
    {{-- <script>
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
    </script> --}}

    <script>
        $(document).ready(function() {
            table = $('#transaction_table').DataTable({
                "lengthMenu": [
                    [10, 25, 50, 100, 500, 1000, -1],
                    [10, 25, 50, 100, 500, 1000, "All"]
                ],
                dom: "<'row'<'col-sm-4'l><'col-sm-4'B><'col-sm-4'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-4'i><'col-sm-8'p>>",
                buttons: [{
                        extend: 'copyHtml5',
                        text: '<i class="fas fa-copy"></i> Copy',
                        titleAttr: 'Copy'
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        titleAttr: 'Excel'
                    },
                    {
                        extend: 'csvHtml5',
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        titleAttr: 'CSV'
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="fas fa-eye"></i>',
                        titleAttr: 'View'
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: '{{ route('dashboard') }}',
                    data: function(d) {
                        d.start_date = $("[name='start_date']").val() || null
                        d.end_date = $("[name='end_date']").val() || null
                    }
                },

                drawCallback(data) {
                    $("#average_amount").html('₱' + (data.json.data?.[0]?.average_amount || "0.00"))
                    $("#total_amount").html('₱' + (data.json.data?.[0]?.total_amount || "0.00"))
                    $("#total_count").html(data.json.data?.[0]?.total_count || "0")
                },
                fixedHeader: true,
                "columns": [{
                        data: "bulk_checkbox",
                        searchable: false,
                        sortable: false,
                        orderable: false
                    },
                    {
                        data: "DT_RowIndex",
                        name: "DT_RowIndex",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: "merchant",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "amount",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "trx_no",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "status",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "created_at",
                        sortable: true,
                        orderable: true
                    },

                    //{
                    //    data: "action",
                    //    searchable: false,
                    //    orderable: false,
                    //    sortable: false
                    //} //action
                ],
            });
            $("[name='start_date']").on('change', function() {
                table.draw()
            })
            $("[name='end_date']").on('change', function() {
                table.draw()
            })
        });
    </script>
@endsection
