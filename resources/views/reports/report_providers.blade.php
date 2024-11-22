@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/daterangepicker.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.SuppliersReport') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="report_providers">
    <div class="row">
        <div class="form-group col-md-6">
            <label for="warehouse_id">{{ __('translate.warehouse') }}
            </label>
            <select name="warehouse_id" id="warehouse_id" class="form-control">
                <option value="0">{{ __('translate.All') }}</option>
                @foreach ($warehouses as $warehouse)
                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-end mb-3">
                        <a id="reportrange">
                            <i class="fa fa-calendar"></i>&nbsp;
                            <span></span> <i class="fa fa-caret-down"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table id="suppliers_table" class="display table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{ __('translate.Code') }}</th>
                                    <th>{{ __('translate.FullName') }}</th>
                                    <th>{{ __('translate.TotalPurchases') }}</th>
                                    <th>{{ __('translate.Total_Amount') }}</th>
                                    <th>{{ __('translate.TotalPaid') }}</th>
                                    <th>{{ __('translate.Total_Purchase_Due') }}</th>
                                    <th>{{ __('translate.Total_Purchase_Return_Due') }}</th>
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
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/daterangepicker.min.js')}}"></script>


<script type="text/javascript">
    $(function() {
        "use strict";

        $(document).ready(function () {
          //init datatable
          suppliers_datatable();
        });

            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
                var start_date = picker.startDate.format('YYYY-MM-DD');
                var end_date = picker.endDate.format('YYYY-MM-DD');
                let warehouse_id = $('#warehouse_id').val();

                $('#suppliers_table').DataTable().destroy();
                suppliers_datatable(start_date, end_date, warehouse_id);

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
            function suppliers_datatable(start_date ='', end_date ='' , warehouse_id =''){
                var $symbol_placement = @json($symbol_placement);
                var table = $('#suppliers_table').DataTable({
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
                            'targets': [3,4,5,6,7],
                            "orderable": false,
                        },
                    ],
                    ajax: {
                        url: "{{ route('get_report_providers_datatable') }}",
                        data: {
                            start_date: start_date === null?'':start_date,
                            end_date: end_date === null?'':end_date,
                            warehouse_id: warehouse_id == '0'?'':warehouse_id,
                            "_token": "{{ csrf_token()}}"
                        },
                        dataType: "json",
                        type:"post"
                    },

                    columns: [
                        {data: 'id', className: "d-none"},
                        {data: 'code'},
                        {data: 'name'},
                        {data: 'total_purchase'},
                        {data: 'total_amount'},
                        {data: 'total_paid'},
                        {data: 'due'},
                        {data: 'return_due'},
                    ],

                    footerCallback: function (row, data, start, end, display) {
                        var api = this.api();
            
                        // Remove the formatting to get integer data for summation
                        var intVal = function (i) {
                            return typeof i === 'string' ? i.replace(/[\$, ]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };
            
                        // Total over this page
                        var total_purchase = api.column(3, { page: 'current' }).data().reduce(function (a, b) {
                                return intVal(a) + intVal(b);
                            }, 0);

                        var chiffre_aff = api.column(4, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var total_paid = api.column(5, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var purchase_due = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var return_due = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
            
                        // Update footer
                        var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;

                        $(api.column(3).footer()).html(total_purchase);

                        if ($symbol_placement == 'before') {
                            $(api.column(4).footer()).html('{{$currency}}' +' '+ numberRenderer(chiffre_aff));
                            $(api.column(5).footer()).html('{{$currency}}' +' '+ numberRenderer(total_paid));
                            $(api.column(6).footer()).html('{{$currency}}' +' '+ numberRenderer(purchase_due));
                            $(api.column(7).footer()).html('{{$currency}}' +' '+ numberRenderer(return_due));

                        }else{
                            $(api.column(4).footer()).html(numberRenderer(chiffre_aff) +' '+ '{{$currency}}');
                            $(api.column(5).footer()).html(numberRenderer(total_paid) +' '+ '{{$currency}}');
                            $(api.column(6).footer()).html(numberRenderer(purchase_due)  +' '+ '{{$currency}}');
                            $(api.column(7).footer()).html(numberRenderer(return_due) +' '+ '{{$currency}}');

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
                                return 'Report Providers';
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
                              return 'Report Providers';
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
                              return 'Report Providers';
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
                              return 'Report Providers';
                            },
                          },
                        ]
                }],
                });
            }

            // Submit Filter
           $('#warehouse_id').on('change' , function (e) {

                var date_range = $('#reportrange > span').text();
                var dates = date_range.split(" - ");
                var start = dates[0];
                var end = dates[1];
                var start_date = moment(dates[0]).format("YYYY-MM-DD");
                var end_date = moment(dates[1]).format("YYYY-MM-DD");

                let warehouse_id = $('#warehouse_id').val();

                $('#suppliers_table').DataTable().destroy();
                suppliers_datatable(start_date, end_date, warehouse_id);
            });


      
    });
</script>



@endsection