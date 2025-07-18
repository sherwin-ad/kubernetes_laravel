@extends('layouts.master')

@section('title', 'Create Merchant')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Transactions</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Transactions</li>
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
                    <div class="col-sm">
                        <div class="card card-warning card-outline">
                            <div class="card-body">
                                <div class="col-lg-12">
                                        <form class="form-horizontal">
                                            <div class="form-group row mb-2">
                                                <label for="aggregator" class="col-sm-2 col-form-label">Select Aggregator</label>
                                                <div class="col-sm-10">
                                                    <select class="form-control" id="aggregator" name="aggregator">
                                                        <option value="">--Select Aggregator--</option>
                                                        @foreach ($aggregators as $aggregator)
                                                            <option value="{{ $aggregator->id }}"  >{{ $aggregator->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="start_date" class="col-sm-2 col-form-label" >Start Date</label>
                                                <div class="col-sm-10">
                                                    <input type="date" class="form-control datepicker" id="start_date" name="start_date" placeholder="Select start date" value="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="end_date" class="col-sm-2 col-form-label">End Date</label>
                                                <div class="col-sm-10">
                                                    <input type="date" class="form-control datepicker" id="end_date" name="end_date" placeholder="Select end date" >
                                                </div>
                                            </div>

                                        </form>


                                @section('scripts')
                                    @parent
                                    <script>
                                        $(function() {
                                            $('.datepicker').datepicker({
                                                format: 'yyyy-mm-dd',
                                                autoclose: true,
                                                todayHighlight: true
                                            });
                                        });
                                    </script>
                                @endsection

                            </div>
                        </div>


                        <!-- /.card-body -->

                    </div>
                <div>

                    <div class="col-lg-12 table-responsive mt-5">
                        <table id="transaction_table" class="table table-striped table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th width="10px">
                                        <input type="checkbox" class="check_all" name="" id="">
                                    </th>
                                    <th width="10px">#</th>
                                    <th>{{ __('MID') }}</th>
                                    <th>{{ __('Aggregator') }}</th>
                                    <th>{{ __('Trx No') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Phone number') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    {{-- <th width="100px">{{ __('Action') }}</th> --}}
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                    </div>



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
                url: '{{ route('transaction.index') }}',
                data: function(d) {
                    d.aggregator = $("[name='aggregator']").val() || null
                    d.start_date = $("[name='start_date']").val() || null
                    d.end_date = $("[name='end_date']").val() || null
                }
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
                    data: "aggregator",
                    sortable: true,
                    orderable: true
                },
                {
                    data: "trx_no",
                    sortable: true,
                    orderable: true
                },
                {
                    data: "amount",
                    sortable: true,
                    orderable: true
                },
                {
                    data: "mobile_number",
                    sortable: true,
                    orderable: true
                },
                {
                    data: "payment_status",
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

        $("[name='aggregator']").on('change', function() {
            table.draw()
        })
        $("[name='start_date']").on('change', function() {
            table.draw()
        })
        $("[name='end_date']").on('change', function() {
            table.draw()
        })
    });
</script>
@endsection
