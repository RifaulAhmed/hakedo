@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datepicker.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Expense_List') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_Expense_list">
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="text-end mb-3">
                            @can('expense_add')
                            <a class="btn btn-outline-primary btn-md m-1" href="{{route('expense.create')}}"><i
                                    class="i-Add me-2 font-weight-bold"></i> {{ __('translate.Create') }}</a>
                            @endcan
                            <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i class="i-Filter-2 me-2 font-weight-bold"></i>
                                {{ __('translate.Filter') }}</a>
                        </div>
                        <div class="table-responsive">
                            <table id="expense_table" class="display table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('translate.Account_Name') }}</th>
                                        <th>{{ __('translate.Expense_Ref') }}</th>
                                        <th>{{ __('translate.Date') }}</th>
                                        <th>{{ __('translate.Amount') }}</th>
                                        <th>{{ __('translate.Category') }}</th>
                                        <th>{{ __('translate.Payment_method') }}</th>
                                        <th class="not_show">{{ __('translate.Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th>Total :</th>
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

      <!-- Modal Filter -->
  <div class="modal fade" id="filter_expense_modal" tabindex="-1" role="dialog"
  aria-labelledby="filter_expense_modal" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <form method="POST" id="filter_expense">
          @csrf
          <div class="row">

            <div class="form-group col-md-12">
              <label for="start_date" >{{ __('translate.From_Date') }}
              </label>
              <input type="text" class="form-control date" name="start_date" id="start_date"
                placeholder="{{ __('translate.From_Date') }}" value="">
            </div>

            <div class="form-group col-md-12">
              <label for="end_date" >{{ __('translate.To_Date') }} </label>
              <input type="text" class="form-control date" name="end_date" id="end_date"
                placeholder="{{ __('translate.To_Date') }}" value="">
            </div>

            <div class="form-group col-md-12">
              <label for="expense_category_id" >{{ __('translate.Category') }} </label>
              <select name="expense_category_id" id="expense_category_id" class="form-control">
                <option value="0">{{ __('translate.All') }}</option>
                @foreach ($categories as $category)
                <option value="{{$category->id}}">{{$category->title}}</option>
                @endforeach
              </select>
            </div>


          </div>

          <div class="row mt-3">

            <div class="col-md-6">
              <button type="submit" class="btn btn-outline-primary">
                <i class="fas fa-filter"></i> {{ __('translate.Filter') }}
              </button>
              <button id="Clear_Form" class="btn btn-outline-danger">
                <i class="fas fa-power-off"></i> {{ __('translate.Clear') }}
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
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/datepicker.min.js')}}"></script>


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

        });


        //init datatable
        expenses_datatable();

        //Get Data
        function expenses_datatable(start_date ='', end_date ='', expense_category_id =''){
            var table = $('#expense_table').DataTable({
                processing: true,
                serverSide: true,
                "order": [[ 0, "desc" ]],
                ajax: {
                    url: "/accounting/expense",
                    data: {
                        start_date: start_date === null?'':start_date,
                        end_date: end_date === null?'':end_date,
                        expense_category_id: expense_category_id == '0'?'':expense_category_id,
                        "_token": "{{ csrf_token()}}"
                    },
                },
                columns: [
                    {data: 'id', name: 'id' , className: "d-none"},
                    {data: 'account_name', name: 'account_name'},
                    {data: 'expense_ref', name: 'expense_ref'},
                    {data: 'date', name: 'date'},
                    {data: 'amount', name: 'amount'},
                    {data: 'expense_category_title', name: 'expense_category_title'},
                    {data: 'payment_method', name: 'payment_method'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],

                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
        
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };
        
                    // Total over this page
                    var amount = api.column(4, { page: 'current' }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    
                    // Update footer
                    var numberRenderer = $.fn.dataTable.render.number(',', '.', 2).display;
                    $(api.column(4).footer()).html(numberRenderer(amount) + ' DH');
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
                                return 'Expense List';
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
                                return 'Expense List';
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
                                return 'Expense List';
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
                                return 'Expense List';
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

            let expense_category_id = $('#expense_category_id').val('0');

        });


        // Show Modal Filter
        $('#Show_Modal_Filter').on('click' , function (e) {
            $('#filter_expense_modal').modal('show');
        });


         // Submit Filter
        $('#filter_expense').on('submit' , function (e) {
            e.preventDefault();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            let expense_category_id = $('#expense_category_id').val();
      
            $('#expense_table').DataTable().destroy();
            expenses_datatable(start_date, end_date, expense_category_id);

            $('#filter_expense_modal').modal('hide');
           
        });

        // event reload Datatatble
        $(document).bind('event_expense', function (e) {
            $('#expense_table').DataTable().destroy();
            expenses_datatable();
        });

        //Delete Expense
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Expense(id);
        });
    });
</script>

<script>
    var app = new Vue({
        el: '#section_Expense_list',
        data: {
            SubmitProcessing:false,
            selectedIds:[],
        },
       
        methods: {

            
             //---- Event selected_row
             selected_row(id) {
                //in here you can check what ever condition  before append to array.
                if(this.selectedIds.includes(id)){
                    const index = this.selectedIds.indexOf(id);
                    this.selectedIds.splice(index, 1);
                }else{
                    this.selectedIds.push(id)
                }
            },


            //--------------------------------- Remove Expense ---------------------------\\
            Remove_Expense(id) {

                swal({
                    title: '{{ __('translate.Are_you_sure') }}',
                    text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0CC27E',
                    cancelButtonColor: '#FF586B',
                    confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
                    cancelButtonText: '{{ __('translate.No_cancel') }}',
                    confirmButtonClass: 'btn btn-primary mr-5',
                    cancelButtonClass: 'btn btn-danger',
                    buttonsStyling: false
                }).then(function () {
                        axios
                            .delete("/accounting/expense" + id)
                            .then(() => {
                                $.event.trigger('event_expense');
                                toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                            })
                            .catch(() => {
                                toastr.error('{{ __('translate.There_was_something_wronge') }}');
                            });
                    });
                },

            //--------------------------------- delete_selected ---------------------------\\
            delete_selected() {
                var self = this;
                swal({
                    title: '{{ __('translate.Are_you_sure') }}',
                    text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0CC27E',
                    cancelButtonColor: '#FF586B',
                    confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
                    cancelButtonText: '{{ __('translate.No_cancel') }}',
                    confirmButtonClass: 'btn btn-primary mr-5',
                    cancelButtonClass: 'btn btn-danger',
                    buttonsStyling: false
                }).then(function () {
                        axios
                        .post("/accounting/expense/delete/by_selection", {
                            selectedIds: self.selectedIds
                        })
                            .then(() => {
                                window.location.href = '/accounting/expense'; 
                                toastr.success('{{ __('translate.Deleted_in_successfully') }}');

                            })
                            .catch(() => {
                                toastr.error('{{ __('translate.There_was_something_wronge') }}');
                            });
                    });
            },






           
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>


@endsection