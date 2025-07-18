@extends('layouts.master')

@section('title', 'Aggregator')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Aggregator</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Aggregator</li>
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
                    <div class="col-lg">
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                <a href="{{ route('aggregator.create') }}" class="btn btn-success btn-sm float-end px-3">
                                    <i class="fa fa-plus"></i> {{ __('Create') }}
                                </a>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="col-lg-12 table-responsive">
                                    <table id="aggregator_table" class="table table-striped table-bordered" width="100%">
                                        <thead>
                                            <tr>
                                                <th width="10px">
                                                    <input type="checkbox" class="check_all" name="" id="">
                                                </th>
                                                <th width="10px">#</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Assigned Merchants') }}</th>
                                                <th>{{ __('Daily Transaction Limit') }}</th>
                                                <th>{{ __('Running-day Amount') }}</th>
                                                <th>{{ __('Yesterday Transaction Amount') }}</th>
                                                <th>{{ __('Rate') }}</th>
                                                <th>{{ __('To Settle') }}</th>
                                                <th width="100px">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
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

    <script>
        $(document).ready(function() {
            table = $('#aggregator_table').DataTable({
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
                    url: '{{ route('aggregator.index') }}',
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
                        data: "name",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "merchants",
                        sortable: false,
                        orderable: false
                    },
                    {
                        data: "daily_transaction_limit",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "running_day_amount",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "yesterday_transaction_amount",
                        sortable: true,
                        orderable: true
                    },
                    {
                        data: "rate",
                        sortable: false,
                        orderable: false
                    },
                    {
                        data: "to_settle",
                        sortable: false,
                        orderable: false
                    },
                    {
                        data: "action",
                        searchable: false,
                        orderable: false,
                        sortable: false
                    } //action
                ],
            });
        });
    </script>

@endsection
