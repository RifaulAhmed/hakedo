@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/daterangepicker.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.SalesReport') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="section_sale_report">

    <div class="row">
        <div class="col-md-12">
            <div class="text-end mb-3">
                <a id="reportrange">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </a>

                <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i class="i-Filter-2 me-2 font-weight-bold"></i>
                    {{ __('translate.Filter') }}</a>
            </div>
            <div class="table-responsive">
                <table id="sale_table" class="display table table-hover">
                    <thead>
                        <tr>    
                            <th>ID</th>
                            <th>{{ __('translate.Date') }}</th>
                            <th>{{ __('translate.Ref') }}</th>
                            <th>{{ __('translate.Created_by') }}</th>
                            <th>{{ __('translate.Customer') }}</th>
                            <th>{{ __('translate.warehouse') }}</th>
                            <th>{{ __('translate.Total') }}</th>
                            <th>{{ __('translate.Paid') }}</th>
                            <th>{{ __('translate.Due') }}</th>
                            <th>{{ __('translate.Payment_Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="height_140">
                    </tbody>

                    <tfoot>
                        <tr>
                            <th></th>
                            <th>{{ __('translate.Total') }} :</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>


    <!-- Modal Filter -->
    <div class="modal fade" id="filter_sale_report_modal" tabindex="-1" role="dialog"
        aria-labelledby="filter_sale_report_modal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form method="POST" id="filter_sale_report">
                        @csrf
                        <div class="row">

                            <div class="form-group col-md-6">
                                <label for="Ref" class="ul-form__label">{{ __('translate.Reference') }}
                                </label>
                                <input type="text" class="form-control" name="Ref" id="Ref"
                                    placeholder="{{ __('translate.Reference') }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="client_id" class="ul-form__label">{{ __('translate.Client') }}
                                </label>
                                <select name="client_id" id="client_id" class="form-control">
                                    <option value="0">{{ __('translate.All') }}</option>
                                    @foreach ($clients as $client)
                                    <option value="{{$client->id}}">{{$client->username}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="warehouse_id" class="ul-form__label">{{ __('translate.warehouse') }}
                                </label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control">
                                    <option value="0">{{ __('translate.All') }}</option>
                                    @foreach ($warehouses as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="form-group col-md-6">
                                <label for="payment_status" class="ul-form__label">{{ __('translate.Payment_Status') }}
                                </label>
                                <select name="payment_status" id="payment_status" class="form-control">
                                    <option value="0">{{ __('translate.All') }}</option>
                                    <option value="paid">{{ __('translate.Paid') }}</option>
                                    <option value="partial">{{ __('translate.Partial') }}</option>
                                    <option value="unpaid">{{ __('translate.Unpaid') }}</option>
                                </select>
                            </div>

                        </div>

                        <div class="row mt-3">

                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="i-Filter-2 me-2 font-weight-bold"></i> {{ __('translate.Filter') }}
                                </button>
                                <button id="Clear_Form" class="btn btn-danger">
                                     <i class="i-Power-2 me-2 font-weight-bold"></i> {{ __('translate.Clear') }}
                                </button>
                            </div>
                        </div>


                    </form>

                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/daterangepicker.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script type="text/javascript">
    $(function() {
        "use strict";

        $(document).ready(function () {
          //init datatable
          sale_datatable();
        });

            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
                var start_date = picker.startDate.format('YYYY-MM-DD');
                var end_date = picker.endDate.format('YYYY-MM-DD');
                
                var Ref = $('#Ref').val();
                var payment_statut = $('#payment_status').val();
                let client_id = $('#client_id').val();
                let warehouse_id = $('#warehouse_id').val();

                $('#sale_table').DataTable().destroy();
                sale_datatable(start_date, end_date, Ref,client_id, payment_statut,warehouse_id);

            });

            var start = moment().subtract(10, 'year');
            var end = moment().add(10, 'year');
    
            function cb(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
        
            $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                '{{ __('translate.Since_launch') }}' : [moment().subtract(10, 'year'), moment().add(10, 'year')],
                '{{ __('translate.Today') }}': [moment(), moment()],
                '{{ __('translate.Yesterday') }}' : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '{{ __('translate.Last_7_Days') }}' : [moment().subtract(6, 'days'), moment()],
                '{{ __('translate.Last_30_Days') }}': [moment().subtract(29, 'days') , moment()],
                '{{ __('translate.This_Month') }}': [moment().startOf('month'), moment().endOf('month')],
                '{{ __('translate.Last_Month') }}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

            cb(start, end);


            //Get Data
            function sale_datatable(start_date ='', end_date ='', Ref ='',client_id ='', payment_statut ='',warehouse_id =''){
                var $symbol_placement = @json($symbol_placement);
                var table = $('#sale_table').DataTable({
                    processing: true,
                    serverSide: true,
                    "order": [[ 0, "desc" ]],
                    'columnDefs': [
                        {
                            'targets': [0],
                            'visible': false,
                            'searchable': false,
                        },
                        {
                            'targets': [3,4,5,6,7,8,9],
                            "orderable": false,
                        },
                    ],

                    ajax: {
                        url: "{{ route('get_report_sales_datatable') }}",
                        data: {
                            start_date: start_date === null?'':start_date,
                            end_date: end_date === null?'':end_date,
                            Ref: Ref === null?'':Ref,
                            client_id: client_id == '0'?'':client_id,
                            warehouse_id: warehouse_id == '0'?'':warehouse_id,
                            payment_statut: payment_statut == '0'?'':payment_statut,
                            "_token": "{{ csrf_token()}}"
                        },
                        dataType: "json",
                        type:"post"
                    },

                    columns: [
                        {data: 'id', className: "d-none"},
                        {data: 'date'},
                        {data: 'Ref'},
                        {data: 'created_by'},
                        {data: 'client_name'},
                        {data: 'warehouse_name'},
                        {data: 'GrandTotal'},
                        {data: 'paid_amount'},
                        {data: 'due'},
                        {data: 'payment_status', name: 'payment_status'},
                    
                    ],

                    footerCallback: function (row, data, start, end, display) {
                        var api = this.api();
            
                        // Remove the formatting to get integer data for summation
                        var intVal = function (i) {
                            return typeof i === 'string' ? i.replace(/[\$, ]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };
            
                        // Total over this page
                        var grand_total = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                                return intVal(a) + intVal(b);
                            }, 0);

                        var total_paid = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var total_due = api.column(8, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
            
                        // Update footer
                        var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;

                        if ($symbol_placement == 'before') {
                            $(api.column(6).footer()).html('{{$currency}}' +' '+ numberRenderer(grand_total));
                            $(api.column(7).footer()).html('{{$currency}}' +' '+ numberRenderer(total_paid));
                            $(api.column(8).footer()).html('{{$currency}}' +' '+ numberRenderer(total_due));

                        }else{
                            $(api.column(6).footer()).html(numberRenderer(grand_total) +' '+ '{{$currency}}');
                            $(api.column(7).footer()).html(numberRenderer(total_paid) +' '+ '{{$currency}}');
                            $(api.column(8).footer()).html(numberRenderer(total_due) +' '+ '{{$currency}}');

                        }

                    },
                
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    dom: "<'row'<'col-sm-12 col-md-7'lB><'col-sm-12 col-md-5 p-0'f>>rtip",
                    oLanguage: {
                        sEmptyTable: "{{ __('datatable.sEmptyTable') }}",
                        sInfo: "{{ __('datatable.sInfo') }}",
                        sInfoEmpty: "{{ __('datatable.sInfoEmpty') }}",
                        sInfoFiltered: "{{ __('datatable.sInfoFiltered') }}",
                        sInfoThousands: "{{ __('datatable.sInfoThousands') }}",
                        sLengthMenu: "_MENU_", 
                        sLoadingRecords: "{{ __('datatable.sLoadingRecords') }}",
                        sProcessing: "{{ __('datatable.sProcessing') }}",
                        sSearch: "",
                        sSearchPlaceholder: "{{ __('datatable.sSearchPlaceholder') }}",
                        oPaginate: {
                            sFirst: "{{ __('datatable.oPaginate.sFirst') }}",
                            sLast: "{{ __('datatable.oPaginate.sLast') }}",
                            sNext: "{{ __('datatable.oPaginate.sNext') }}",
                            sPrevious: "{{ __('datatable.oPaginate.sPrevious') }}",
                        },
                        oAria: {
                            sSortAscending: "{{ __('datatable.oAria.sSortAscending') }}",
                            sSortDescending: "{{ __('datatable.oAria.sSortDescending') }}",
                        }
                    },
                    buttons: [
                    {
                        extend: 'collection',
                        text: "{{ __('translate.EXPORT') }}",
                        buttons: [
                          {
                            extend: 'print',
                            text: 'Print',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                                return 'Report Sell';
                            },
                          },
                          {
                            extend: 'pdf',
                            text: 'Pdf',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                              return 'Report Sell';
                            },
                           
                        },
                          {
                            extend: 'excel',
                            text: 'Excel',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                              return 'Report Sell';
                            },
                          },
                          {
                            extend: 'csv',
                            text: 'Csv',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                              return 'Report Sell';
                            },
                          },
                        ]
                }],
                });
            }

            // Clear Filter
            $('#Clear_Form').on('click' , function (e) {
               
                var Ref = $('#Ref').val('');
                var payment_statut = $('#payment_status').val('0');
                let client_id = $('#client_id').val('0');
                let warehouse_id = $('#warehouse_id').val('0');

            });


            // Show Modal Filter
            $('#Show_Modal_Filter').on('click' , function (e) {
                $('#filter_sale_report_modal').modal('show');
            });


            // Submit Filter
            $('#filter_sale_report').on('submit' , function (e) {
                e.preventDefault();
                var date_range = $('#reportrange > span').text();
                var dates = date_range.split(" - ");
                var start = dates[0];
                var end = dates[1];
                var start_date = moment(dates[0]).format("YYYY-MM-DD");
                var end_date = moment(dates[1]).format("YYYY-MM-DD");
                
                var Ref = $('#Ref').val();
                var payment_statut = $('#payment_status').val();
                let client_id = $('#client_id').val();
                let warehouse_id = $('#warehouse_id').val();
        
                $('#sale_table').DataTable().destroy();
                sale_datatable(start_date, end_date, Ref,client_id, payment_statut,warehouse_id);

                $('#filter_sale_report_modal').modal('hide');
            
            });

            
        });

</script>

@endsection