@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Currency') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>



<div class="row" id="section_Currency_list">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="text-end mb-3">
                    <a class="new_currency btn btn-outline-primary btn-md m-1"><i
                            class="i-Add me-2 font-weight-bold"></i>
                        {{ __('translate.Create') }}</a>
                </div>

                <div class="table-responsive">

                    <table id="currency_list_table" class="display table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('translate.Currency_Code') }}</th>
                                <th>{{ __('translate.Currency_Name') }}</th>
                                <th>{{ __('translate.Symbol') }}</th>
                                <th>{{ __('translate.Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>


    </div>

    <!-- Modal Add & Edit Currency -->
    <div class="modal fade" id="Currency_Modal" tabindex="-1" role="dialog" aria-labelledby="Currency_Modal"
        aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
                    <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form @submit.prevent="editmode?Update_Currency():Create_Currency()">
                        <div class="row">
                                <div class="form-group col-md-12">
                                <label for="name" class="ul-form__label">{{ __('translate.Currency_Name') }} <span
                                        class="field_required">*</span></label>
                                <input type="text" v-model="currency.name" class="form-control" name="name" id="name"
                                    placeholder="{{ __('translate.Enter_Currency_Name') }}">
                                <span class="error" v-if="errors && errors.name">
                                    @{{ errors.name[0] }}
                                </span>
                            </div>

                            <div class="form-group col-md-12">
                                <label for="code" class="ul-form__label">{{ __('translate.Currency_Code') }} <span
                                        class="field_required">*</span></label>
                                <input type="text" v-model="currency.code" class="form-control" id="code" id="code"
                                    placeholder="{{ __('translate.Enter_Currency_Code') }}">
                                <span class="error" v-if="errors && errors.code">
                                    @{{ errors.code[0] }}
                                </span>
                            </div>


                            <div class="form-group col-md-12">
                                <label for="symbol" class="ul-form__label">{{ __('translate.Currency_Symbol') }}
                                    <span class="field_required">*</span></label>
                                <input type="text" v-model="currency.symbol" class="form-control" id="symbol"
                                    placeholder="{{ __('translate.Enter_currency_symbol') }}">
                                <span class="error" v-if="errors && errors.symbol">
                                    @{{ errors.symbol[0] }}
                                </span>
                            </div>
                        </div>

                        <div class="row mt-3">

                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                                    {{ __('translate.Submit') }}
                                </button>
                                <div v-once class="typo__p" v-if="SubmitProcessing">
                                    <div class="spinner spinner-primary mt-3"></div>
                                </div>
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


<script type="text/javascript">
    $(function () {
        "use strict";

        $(document).ready(function () {
          //init datatable
          currency_datatable();
        });
  
          //Get Data
          function currency_datatable(){
              var table = $('#currency_list_table').DataTable({
                  processing: true,
                  serverSide: true,
                  "order": [[ 0, "desc" ]],
                  'columnDefs': [
                    {
                        'targets': [0],
                        'visible': false,
                        'searchable': false,
                    },
                    ],
                  ajax: "{{ route('currency.index') }}",
                  columns: [
                      {data: 'id' , name: 'id',className: "d-none"},
                      {data: 'code', name: 'code'},
                      {data: 'name', name: 'name'},
                      {data: 'symbol', name: 'symbol'},
                      {data: 'action', name: 'action', orderable: false, searchable: false},
                  
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
                              text: 'print',
                              exportOptions: {
                                  columns: ':visible:Not(.not_show)',
                                  rows: ':visible'
                              },
                            },
                            {
                              extend: 'pdf',
                              text: 'pdf',
                              exportOptions: {
                                  columns: ':visible:Not(.not_show)',
                                  rows: ':visible'
                              },
                            },
                            {
                              extend: 'excel',
                              text: 'excel',
                              exportOptions: {
                                  columns: ':visible:Not(.not_show)',
                                  rows: ':visible'
                              },
                            },
                            {
                              extend: 'csv',
                              text: 'csv',
                              exportOptions: {
                                  columns: ':visible:Not(.not_show)',
                                  rows: ':visible'
                              },
                            },
                          ]
                      }]
              });
          }
  
          // event reload Datatatble
          $(document).bind('event_currency', function (e) {
              $('#Currency_Modal').modal('hide');
              $('#currency_list_table').DataTable().destroy();
              currency_datatable();
          });
  
  
           //Create currency
           $(document).on('click', '.new_currency', function () {
              app.editmode = false;
              app.reset_Form();
              $('#Currency_Modal').modal('show');
          });
  
          //Edit currency
          $(document).on('click', '.edit', function () {
              NProgress.start();
              NProgress.set(0.1);
              app.editmode = true;
              app.reset_Form();
              var id = $(this).attr('id');
              app.Get_Data_Edit(id);
             
              setTimeout(() => {
                  NProgress.done()
                  $('#Currency_Modal').modal('show');
              }, 500);
          });
  
          //Remove_Currency
          $(document).on('click', '.delete', function () {
              var id = $(this).attr('id');
              app.Remove_Currency(id);
          });
      });
</script>


<script>
    var app = new Vue({
        el: '#section_Currency_list',
        data: {
            selectedIds:[],
            data: new FormData(),
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            currencies: {}, 
            currency: {
                name: "",
                code: "",
                symbol: "",
            }, 
        },
       
        methods: {

         
            //------------------------------ Modal  (create category) -------------------------------\\
            New_category() {
                this.reset_Form();
                this.editmode = false;
                $('#Currency_Modal').modal('show');
            },

            //----------------------------- Reset Form ---------------------------\\
            reset_Form() {
                this.currency = {
                    id: "",
                    name: "",
                    code: "",
                    symbol: "",
                };
                this.errors = {};
            },

               //---------------------- Get_Data_Edit  ------------------------------\\
               Get_Data_Edit(id) {
                axios
                .get("/settings/currency/"+id+"/edit")
                .then(response => {
                    this.currency   = response.data.currency;
                })
                .catch(error => {
                    
                });
            },
            
            //------------------------ Create currency ---------------------------\\
            Create_Currency() {
                var self = this;
                self.SubmitProcessing = true;
                axios.post("/settings/currency", {
                    name: self.currency.name,
                    code: self.currency.code,
                    symbol: self.currency.symbol
                }).then(response => {
                        self.SubmitProcessing = false;
                        $.event.trigger('event_currency');
                        toastr.success('{{ __('translate.Created_in_successfully') }}');
                        self.errors = {};
                })
                .catch(error => {
                    self.SubmitProcessing = false;
                    if (error.response.status == 422) {
                        self.errors = error.response.data.errors;
                    }
                    toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
            },

           //----------------------- Update Currency ---------------------------\\
            Update_Currency() {
                var self = this;
                self.SubmitProcessing = true;
                axios.put("/settings/currency/" + self.currency.id, {
                    name: self.currency.name,
                    code: self.currency.code,
                    symbol: self.currency.symbol
                }).then(response => {
                        self.SubmitProcessing = false;
                        $.event.trigger('event_currency');
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors = {};
                    })
                    .catch(error => {
                        self.SubmitProcessing = false;
                        if (error.response.status == 422) {
                            self.errors = error.response.data.errors;
                        }
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            },

             //--------------------------------- Remove Currency ---------------------------\\
            Remove_Currency(id) {

                swal({
                    title: '{{ __('translate.Are_you_sure') }}',
                    text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0CC27E',
                    cancelButtonColor: '#FF586B',
                    confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
                    cancelButtonText: '{{ __('translate.No_cancel') }}',
                    confirmButtonClass: 'btn btn-primary me-5',
                    cancelButtonClass: 'btn btn-danger',
                    buttonsStyling: false
                }).then(function () {
                        axios
                            .delete("/settings/currency/" + id)
                            .then(() => {
                                $.event.trigger('event_currency');
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