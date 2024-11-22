@extends('layouts.master')
@section('main-content')
@section('page-css')

<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datepicker.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Payments_Purchase_Return') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row">
    <div class="col-md-12">
        <div class="text-left">
            <div class="text-end bg-transparent mb-3">

                <a class="btn btn-outline-primary btn-rounded btn-md m-1" id="Show_Modal_Filter"><i class="i-Filter-2 mr-2 font-weight-bold"></i>
                    {{ __('translate.Filter') }}</a>
            </div>
                <div class="table-responsive">
                    <table id="payment_purchase_return_table" class="display table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('translate.Date') }}</th>
                                <th>{{ __('translate.Ref') }}</th>
                                <th>{{ __('translate.Return') }}</th>
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
                                <th>{{ __('translate.Total') }}</th>
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

         <!-- Modal Filter -->
         <div class="modal fade" id="filter_payment_purchase_return_modal" tabindex="-1" role="dialog"
         aria-labelledby="filter_payment_purchase_return_modal" aria-hidden="true">
         <div class="modal-dialog modal-lg" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">

                     <form method="POST" id="filter_payment_purchase_return">
                         @csrf
                         <div class="row">

                             <div class="col-md-6">
                                 <label for="Ref" class="ul-form__label">{{ __('translate.Reference') }}
                                 </label>
                                 <input type="text" class="form-control" name="Ref" id="Ref"
                                     placeholder="{{ __('translate.Reference') }}">
                             </div>

                             <div class="col-md-6">
                                 <label for="provider_id" class="ul-form__label">{{ __('translate.Supplier') }}
                                 </label>
                                 <select name="provider_id" id="provider_id" class="form-control">
                                     <option value="0">{{ __('translate.All') }}</option>
                                     @foreach ($suppliers  as $supplier)
                                     <option value="{{$supplier->id}}">{{$supplier->name}}</option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="col-md-6">
                                 <label for="purchase_return_id"
                                     class="ul-form__label">{{ __('translate.Return') }} </label>
                                 <select name="purchase_return_id" id="purchase_return_id" class="form-control">
                                     <option value="0">{{ __('translate.All') }}</option>
                                     @foreach ($purchase_returns   as $purchase_return)
                                     <option value="{{$purchase_return->id}}">{{$purchase_return->Ref}}</option>
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

                             <div class="col-md-6">
                                 <label for="start_date" class="ul-form__label">{{ __('translate.From_Date') }}
                                 </label>
                                 <input type="text" class="form-control date" name="start_date" id="start_date"
                                     placeholder="{{ __('translate.From_Date') }}" value="">
                             </div>

                             <div class="col-md-6">
                                 <label for="end_date" class="ul-form__label">{{ __('translate.To_Date') }} </label>
                                 <input type="text" class="form-control date" name="end_date" id="end_date"
                                     placeholder="{{ __('translate.To_Date') }}" value="">
                             </div>


                         </div>

                         <div class="row mt-3">

                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                  <i class="i-Filter-2 mr-2"></i> {{ __('translate.Filter') }}
                                </button>
                                <button id="Clear_Form" class="btn btn-danger">
                                  <i class="i-Power-2 mr-2"></i> {{ __('translate.Clear') }}
                                </button>
                              </div>
                         </div>


                     </form>

                 </div>

             </div>
         </div>
     </div>


    </div>
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/datepicker.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script type="text/javascript">
    $(function () {
      "use strict";

      $(document).ready(function () {

        $("#start_date,#end_date").datepicker({
            format: 'yyyy-mm-dd',
            changeMonth: true,
            changeYear: true,
            autoclose: true,
            todayHighlight: true,
        });

        var end_date = new Date();
        var start_date = new Date();

        end_date.setDate(end_date.getDate() + 365);
        $("#end_date").datepicker("setDate" , end_date);

        start_date.setDate(start_date.getDate() - 365);
        $("#start_date").datepicker("setDate" , start_date);

        //init datatable
        payment_purchase_return_datatable();

      });


        //Get Data
        function payment_purchase_return_datatable(start_date ='', end_date ='', Ref ='', provider_id ='', purchase_return_id ='' , payment_method_id ='', account_id =''){
            var $symbol_placement = @json($symbol_placement);
            var table = $('#payment_purchase_return_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('payment_purchase_return_report') }}",
                    data: {
                        start_date: start_date === null?'':start_date,
                        end_date: end_date === null?'':end_date,
                        Ref: Ref === null?'':Ref,
                        payment_method_id: payment_method_id == '0'?'':payment_method_id,
                        account_id: account_id == '0'?'':account_id,
                        provider_id: provider_id == '0'?'':provider_id,
                        purchase_return_id: purchase_return_id == '0'?'':purchase_return_id,
                        "_token": "{{ csrf_token()}}"
                    },
                },
                columns: [
                    {data: 'date', name: 'date'},
                    {data: 'Ref', name: 'Ref'},
                    {data: 'Ref_return', name: 'Ref_return'},
                    {data: 'provider_name', name: 'provider_name'},
                    {data: 'Reglement', name: 'Reglement'},
                    {data: 'account_name', name: 'account_name'},
                    {data: 'montant', name: 'montant'},
                ],

                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
        
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };
        
                    // Total over this page
                    var total_amount = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                     // Update footer
                     var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;

                    if ($symbol_placement == 'before') {
                        $(api.column(6).footer()).html('{{$currency}}' +' '+ numberRenderer(total_amount));

                    }else{
                        $(api.column(6).footer()).html(numberRenderer(total_amount) +' '+ '{{$currency}}');

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
                                return 'Report Payment Purchase return';
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
                              return 'Report Payment Purchase return';
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
                              return 'Report Payment Purchase return';
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
                              return 'Report Payment Purchase return';
                            },
                          },
                        ]
                }],
            });
        }

        // Clear Filter

        $('#Clear_Form').on('click' , function (e) {
            var end_date = new Date();
            var start_date = new Date();

            end_date.setDate(end_date.getDate() + 365);
            $("#end_date").datepicker("setDate" , end_date);

            start_date.setDate(start_date.getDate() - 365);
            $("#start_date").datepicker("setDate" , start_date);

            var Ref = $('#Ref').val('');
            var payment_method_id = $('#payment_method_id').val('0');
            var account_id = $('#account_id').val('0');
            let provider_id = $('#provider_id').val('0');
            let purchase_return_id = $('#purchase_return_id').val('0');

        });


         // Show Modal Filter
        $('#Show_Modal_Filter').on('click' , function (e) {
            $('#filter_payment_purchase_return_modal').modal('show');
        });


         // Submit Filter
        $('#filter_payment_purchase_return').on('submit' , function (e) {
            e.preventDefault();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var Ref = $('#Ref').val();
            var payment_method_id = $('#payment_method_id').val();
            var account_id = $('#account_id').val();
            let provider_id = $('#provider_id').val();
            let purchase_return_id = $('#purchase_return_id').val();
      
            $('#payment_purchase_return_table').DataTable().destroy();
            payment_purchase_return_datatable(start_date, end_date, Ref, provider_id, purchase_return_id , payment_method_id, account_id);

            $('#filter_payment_purchase_return_modal').modal('hide');
           
        });

         

      
    });
</script>



@endsection