@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.stock_report') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div id="stock_report">
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
                        <table id="stock_table" class="display table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('translate.Code') }}</th>
                                    <th>{{ __('translate.Name') }}</th>
                                    <th>{{ __('translate.warehouse') }}</th>
                                    <th>{{ __('translate.Current_Stock') }}</th>
                                    <th>{{ __('translate.Total_Amount_Stock') }}</th>
                                </tr>
                            </thead>
                            <tbody class="height_140">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('translate.Total') }} :</th>
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


<script type="text/javascript">
    $(function() {
        "use strict";

        $(document).ready(function () {
          //init datatable
          stock_datatable();
        });

     

            //Get Data
            function stock_datatable(warehouse_id =''){
                var $symbol_placement = @json($symbol_placement);
                var table = $('#stock_table').DataTable({
                    processing: true,
                    serverSide: true,
                    'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0,1,2,3,4]
                    },
                    ],

                    ajax: {
                        url: "{{ route('get_report_stock_datatable') }}",
                        data: {
                            warehouse_id: warehouse_id == '0'?'':warehouse_id,
                            
                            "_token": "{{ csrf_token()}}"
                        },
                        dataType: "json",
                        type:"post"
                    },
                    columns: [
                        {data: 'code'},
                        {data: 'name'},
                        {data: 'warehouse_name'},
                        {data: 'current_stock'},
                        {data: 'total_current_stock'},
                    ],

                    footerCallback: function (row, data, start, end, display) {
                        var api = this.api();
            
                        // Remove the formatting to get integer data for summation
                        var intVal = function (i) {
                            return typeof i === 'string' ? i.replace(/[\$, ]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };

                     
                        var total_current_stock = api.column(4, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    
                        // Update footer
                        var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;

                        if ($symbol_placement == 'before') {
                            $(api.column(4).footer()).html('{{$currency}}' +' '+ numberRenderer(total_current_stock));
                        }else{
                            $(api.column(4).footer()).html(numberRenderer(total_current_stock) +' ' +'{{$currency}}');
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
                                return 'Report Stock';
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
                              return 'Report Stock';
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
                              return 'Report Stock';
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
                              return 'Report Stock';
                            },
                          },
                        ]
                }],
                });
            }


           // Submit Filter
           $('#warehouse_id').on('change' , function (e) {
               
               let warehouse_id = $('#warehouse_id').val();
       
               $('#stock_table').DataTable().destroy();
               stock_datatable(warehouse_id);

           });

      
    });
</script>



@endsection