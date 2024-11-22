@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.ProductQuantityAlerts') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div id="alert_quantity">
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
                        
                    <div class="table-responsive">
                        <table id="quantity_alerts_table" class="display table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('translate.Code') }}</th>
                                    <th>{{ __('translate.Name') }}</th>
                                    <th>{{ __('translate.warehouse') }}</th>
                                    <th>{{ __('translate.Current_Stock') }}</th>
                                    <th>{{ __('translate.Stock_Alert') }}</th>
                                </tr>
                            </thead>
                            <tbody class="height_140">
                            </tbody>
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


<script type="text/javascript">
    $(function() {
        "use strict";

        $(document).ready(function () {
          //init datatable
          quantity_alerts_datatable();
        });


            //Get Data
            function quantity_alerts_datatable(warehouse_id = ''){
                var table = $('#quantity_alerts_table').DataTable({
                    processing: true,
                    serverSide: true,
                    "order": [[ 0, "desc" ]],
                    ajax: {
                        url: "{{ route('reports_quantity_alerts') }}",
                        data: {
                            warehouse_id: warehouse_id == '0'?'':warehouse_id,

                            "_token": "{{ csrf_token()}}"
                        },
                    },
                    columns: [
                        {data: 'product_code', name: 'product_code'},
                        {data: 'product_name', name: 'product_name'},
                        {data: 'warehouse_name', name: 'warehouse_name'},
                        {data: 'current_stock', name: 'current_stock'},
                        {data: 'stock_alert', name: 'stock_alert'},
                    ],
                
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
                                return 'Report Quantity alert';
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
                              return 'Report Quantity alert';
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
                              return 'Report Quantity alert';
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
                              return 'Report Quantity alert';
                            },
                          },
                        ]
                }],
                });
            }

               // Submit Filter
           $('#warehouse_id').on('change' , function (e) {

                let warehouse_id = $('#warehouse_id').val();

                $('#quantity_alerts_table').DataTable().destroy();
                quantity_alerts_datatable(warehouse_id);

            });

      
    });
</script>



@endsection