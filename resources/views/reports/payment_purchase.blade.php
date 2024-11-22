@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/daterangepicker.css')}}">
@endsection

<div class="breadcrumb">
<h1>{{ __('translate.Payments_Purchase') }}</h1>
</div>
  
<div class="separator-breadcrumb border-top"></div>

<div class="section_payment_purchase">

    <div class="row">
        <div class="col-md-12">
            <div class="text-end mb-3">
                <a id="reportrange">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </a>

                <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i
                        class="i-Filter-2 me-2 font-weight-bold"></i>
                    {{ __('translate.Filter') }}</a>
            </div>
            <div class="table-responsive">
                <table id="payment_purchase_table" class="display table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('translate.Date') }}</th>
                            <th>{{ __('translate.Ref') }}</th>
                            <th>{{ __('translate.warehouse') }}</th>
                            <th>{{ __('translate.Purchase') }}</th>
                            <th>{{ __('translate.Supplier') }}</th>
                            <th>{{ __('translate.Payment_Method') }}</th>
                            <th>{{ __('translate.Account') }}</th>
                            <th>{{ __('translate.Amount') }}</th>
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
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
                
    <!-- Modal Filter -->
    <div class="modal fade" id="filter_payment_purchase_report_modal" tabindex="-1" role="dialog"
        aria-labelledby="filter_payment_purchase_report_modal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form method="POST" id="filter_payment_purchhase_report">
                        @csrf
                        <div class="row">

                            <div class="form-group col-md-6">
                                <label for="Ref" >{{ __('translate.Reference') }}
                                </label>
                                <input type="text" class="form-control" name="Ref" id="Ref"
                                    placeholder="{{ __('translate.Reference') }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="provider_id" >{{ __('translate.Supplier') }}
                                </label>
                                <select name="provider_id" id="provider_id" class="form-control">
                                    <option value="0">{{ __('translate.All') }}</option>
                                    @foreach ($suppliers as $supplier)
                                    <option value="{{$supplier->id}}">{{$supplier->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="warehouse_id"
                                    class="ul-form__label">{{ __('translate.warehouse') }} </label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control">
                                    <option value="0">{{ __('translate.All') }}</option>
                                    @foreach ($warehouses as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                 <label for="payment_method_id"
                                     class="ul-form__label">{{ __('translate.Payment_choice') }} </label>
                                 <select name="payment_method_id" id="payment_method_id" class="form-control">
                                     <option value="0">{{ __('translate.All') }}</option>
                                     @foreach ($payment_methods   as $payment_method)
                                     <option value="{{$payment_method->id}}">{{$payment_method->title}}</option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="col-md-6">
                                 <label for="account_id"
                                     class="ul-form__label">{{ __('translate.Account') }} </label>
                                 <select name="account_id" id="account_id" class="form-control">
                                     <option value="0">{{ __('translate.All') }}</option>
                                     @foreach ($accounts   as $account)
                                     <option value="{{$account->id}}">{{$account->account_name}}</option>
                                     @endforeach
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
    $(function () {
      "use strict";

       $(document).ready(function () {
          //init datatable
          payment_purchase_datatable();
        });

      $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
            var start_date = picker.startDate.format('YYYY-MM-DD');
            var end_date = picker.endDate.format('YYYY-MM-DD');
            
            var Ref = $('#Ref').val();
            var payment_method_id = $('#payment_method_id').val();
            var account_id = $('#account_id').val();
            let provider_id = $('#provider_id').val();
            let warehouse_id = $('#warehouse_id').val();

            $('#payment_purchase_table').DataTable().destroy();
                payment_purchase_datatable(start_date, end_date, Ref, provider_id , payment_method_id, warehouse_id, account_id);

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
        function payment_purchase_datatable(start_date ='', end_date ='', Ref ='', provider_id ='',  payment_method_id ='', warehouse_id ='', account_id =''){
            var $symbol_placement = @json($symbol_placement);
            var table = $('#payment_purchase_table').DataTable({
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
                        'targets': [1,2,3,4,5,6,7,8],
                        "orderable": false,
                    },
                ],

                ajax: {
                    url: "{{ route('get_payment_purchase_report_datatable') }}",
                    data: {
                        start_date: start_date === null?'':start_date,
                        end_date: end_date === null?'':end_date,
                        Ref: Ref === null?'':Ref,
                        payment_method_id: payment_method_id == '0'?'':payment_method_id,
                        account_id: account_id == '0'?'':account_id,
                        provider_id: provider_id == '0'?'':provider_id,
                        warehouse_id: warehouse_id == '0'?'':warehouse_id,
                        "_token": "{{ csrf_token()}}"
                    },
                    dataType: "json",
                    type:"post"
                },

                columns: [
                    {data: 'id', className: "d-none"},
                    {data: 'date'},
                    {data: 'Ref'},
                    {data: 'warehouse_name'},
                    {data: 'Ref_Purchase'},
                    {data: 'provider_name'},
                    {data: 'Reglement'},
                    {data: 'account_name'},
                    {data: 'montant'},
                ],

                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
        
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$, ]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };
        
                    // Total over this page
                    var total_amount = api.column(8, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Update footer
                    var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;

                     if ($symbol_placement == 'before') {
                        $(api.column(8).footer()).html('{{$currency}}' +' '+ numberRenderer(total_amount));

                    }else{
                        $(api.column(8).footer()).html(numberRenderer(total_amount) +' '+ '{{$currency}}');

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
                                return 'Report Payment Purchase';
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
                              return 'Report Payment Purchase';
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
                              return 'Report Payment Purchase';
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
                              return 'Report Payment Purchase';
                            },
                          },
                        ]
                }],
            });
        }

        // Clear Filter

        $('#Clear_Form').on('click' , function (e) {

            var Ref = $('#Ref').val('');
            var payment_method_id = $('#payment_method_id').val('0');
            var account_id = $('#account_id').val('0');
            let provider_id = $('#provider_id').val('0');
            let warehouse_id = $('#warehouse_id').val('0');

        });


         // Show Modal Filter
        $('#Show_Modal_Filter').on('click' , function (e) {
            $('#filter_payment_purchase_report_modal').modal('show');
        });


         // Submit Filter
        $('#filter_payment_purchhase_report').on('submit' , function (e) {
            e.preventDefault();
            
            var date_range = $('#reportrange > span').text();
            var dates = date_range.split(" - ");
            var start = dates[0];
            var end = dates[1];
            var start_date = moment(dates[0]).format("YYYY-MM-DD");
            var end_date = moment(dates[1]).format("YYYY-MM-DD");

            var Ref = $('#Ref').val();
            var payment_method_id = $('#payment_method_id').val();
            var account_id = $('#account_id').val();
            let provider_id = $('#provider_id').val();
            let warehouse_id = $('#warehouse_id').val();

            $('#payment_purchase_table').DataTable().destroy();
            payment_purchase_datatable(start_date, end_date, Ref, provider_id , payment_method_id, warehouse_id, account_id);

            $('#filter_payment_purchase_report_modal').modal('hide');
           
        });


    });
</script>



@endsection