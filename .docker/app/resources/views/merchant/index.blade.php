@extends('layouts.master')

@section('title', 'Merchant')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Merchant</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Merchant</li>
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
                                <a href="{{ route('merchant.create') }}" class="btn btn-success btn-sm float-end px-3">
                                    <i class="fa fa-plus"></i> {{ __('Create') }}
                                </a>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="col-lg-12 table-responsive">
                                    <table id="merchant_table" class="table table-striped table-bordered" width="100%">
                                        <thead>
                                            <tr>
                                                <th width="10px">
                                                    <input type="checkbox" class="check_all" name="" id="">
                                                </th>
                                                <th width="10px">#</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Aggregator') }}</th>
                                                <th>{{ __('Daily Transaction Limit') }}</th>
                                                <th>{{ __('Running-day Amount') }}</th>
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



    <!-- Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Assign to Aggregator</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form  action="{{route('merchant.assign')}}" id="assignForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <input type="hidden" name="merchant_id" id="merchant_id">

                            <label for="aggregator" class="form-label">Select Aggregator</label>
                            <select class="form-select" id="aggregator" name="aggregator_id">
                                <option value="">-- Select Aggregator --</option>
                                @foreach ($aggregators as $aggregator)
                                    <option value="{{ $aggregator->id }}">{{ $aggregator->name }}</option>
                                @endforeach
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
            $(document).on('click', '.assign', function() {
                var id = $(this).data('id');
                $('#merchant_id').val(id);
                $('#assignModal').modal('show');
            });

            table = $('#merchant_table').DataTable({
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
                    url: '{{ route('merchant.index') }}',
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
                        data: "aggregator_id",
                        sortable: true,
                        orderable: true
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
