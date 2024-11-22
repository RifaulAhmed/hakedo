@extends('layouts.master')
@section('main-content')
@section('page-css')

<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/flatpickr.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Suppliers') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_sup_list">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="text-end mb-3">
                    @can('suppliers_add')
                         <a class="btn btn-outline-primary btn-md m-1 new_Supplier"><i class="i-Add me-2 font-weight-bold"></i>
                        {{ __('translate.Create') }}</a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table id="supplier_list_table" class="display table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="not_show">{{ __('translate.Action') }}</th>
                                <th>{{ __('Supplier ID') }}</th>
                                <th>{{ __('Company Name') }}</th>
                                <th>{{ __('First Name') }}</th>
                                <th>{{ __('Middle Name') }}</th>
                                <th>{{ __('Last Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('translate.Phone') }}</th>
                                <th>{{ __('Billing Address') }}</th>
                                <th>{{ __('Billing Address') }}</th>
                                <th>{{ __('Billing Address No') }}</th>
                                <th>{{ __('Billing Address RT') }}</th>
                                <th>{{ __('Billing Address RW') }}</th>
                                <th>{{ __('Billing Address PostCode') }}</th>
                                <th>{{ __('Billing Address Urban Ward') }}</th>
                                <th>{{ __('Billing Address City') }}</th>
                                <th>{{ __('Billing Address Province') }}</th>
                                <th>{{ __('Tax Number') }}</th>
                                
                                <!-- <th>{{ __('translate.City') }}</th>
                                <th>{{ __('translate.Total_Purchase_Due') }}</th>
                                <th>{{ __('translate.Total_Purchase_Return_Due') }}</th> -->
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Add & Edit Supplier -->
    
    <div class="modal fade" id="Modal_Supplier" tabindex="-1" role="dialog" aria-labelledby="Modal_Supplier"
        aria-hidden="true" data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
    
                <div class="modal-header">
                    <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
                    <h5 v-else class="modal-title">{{ __('Create Supplier') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
    
                    <form @submit.prevent="editmode?Update_Supplier():Create_Supplier()">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name" class="ul-form__label">{{ __('Company Name') }}
                                    <span class="field_required">*</span></label>
                                <input type="text" v-model="supplier.name" class="form-control" name="name" id="name"
                                    placeholder="{{ __('translate.Name') }}">
                                <span class="error" v-if="errors && errors.name">
                                    @{{ errors.name[0] }}
                                </span>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="name" class="ul-form__label">{{ __('First Name') }}
                                    <span class="field_required">*</span></label>
                                <input type="text" v-model="supplier.name" class="form-control" name="name" id="name"
                                    placeholder="{{ __('translate.Name') }}">
                                <span class="error" v-if="errors && errors.name">
                                    @{{ errors.name[0] }}
                                </span>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="name" class="ul-form__label">{{ __('Middle Name') }}
                                    <span class="field_required">*</span></label>
                                <input type="text" v-model="supplier.name" class="form-control" name="name" id="name"
                                    placeholder="{{ __('translate.Name') }}">
                                <span class="error" v-if="errors && errors.name">
                                    @{{ errors.name[0] }}
                                </span>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="name" class="ul-form__label">{{ __('Last Name') }}
                                    <span class="field_required">*</span></label>
                                <input type="text" v-model="supplier.name" class="form-control" name="name" id="name"
                                    placeholder="{{ __('translate.Name') }}">
                                <span class="error" v-if="errors && errors.name">
                                    @{{ errors.name[0] }}
                                </span>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="email" class="ul-form__label">{{ __('translate.Email') }}
                                </label>
                                <input type="text" v-model="supplier.email" class="form-control" id="email" id="email"
                                    placeholder="{{ __('translate.Email') }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="Phone" class="ul-form__label">{{ __('translate.Phone') }}</label>
                                <input type="text" v-model="supplier.phone" class="form-control" id="Phone"
                                    placeholder="{{ __('translate.Phone') }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="country" class="ul-form__label">{{ __('Billing Address') }}</label>
                                <input type="text" v-model="supplier.country" class="form-control" id="country"
                                    placeholder="{{ __('translate.Country') }}">
    
                            </div>

                            <div class="form-group col-md-6">
                                <label for="country" class="ul-form__label">{{ __('Billing Address No') }}</label>
                                <input type="text" v-model="supplier.country" class="form-control" id="country"
                                    placeholder="{{ __('translate.Country') }}">
    
                            </div>
                            <div class="form-group col-md-6">
                                <label for="city" class="ul-form__label">{{ __('Billing Address  RT') }}</label>
                                <input type="text" v-model="supplier.city" class="form-control" id="city"
                                    placeholder="{{ __('translate.City') }}">
    
                            </div>
                            <div class="form-group col-md-6">
                                <label for="city" class="ul-form__label">{{ __('Billing Address  RW') }}</label>
                                <input type="text" v-model="supplier.city" class="form-control" id="city"
                                    placeholder="{{ __('translate.City') }}">
    
                            </div>
                            <div class="form-group col-md-6">
                                <label for="city" class="ul-form__label">{{ __('Billing Address  PostCode') }}</label>
                                <input type="text" v-model="supplier.city" class="form-control" id="city"
                                    placeholder="{{ __('translate.City') }}">
    
                            </div>
                            <div class="form-group col-md-6">
                                <label for="city" class="ul-form__label">{{ __('Billing Address  Urban Ward') }}</label>
                                <input type="text" v-model="supplier.city" class="form-control" id="city"
                                    placeholder="{{ __('translate.City') }}">
    
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="city" class="ul-form__label">{{ __('Billing Address District') }}</label>
                                <input type="text" v-model="supplier.city" class="form-control" id="city"
                                    placeholder="{{ __('translate.City') }}">
    
                            </div>
    
    
                            <div class="form-group col-md-6">
                                <label for="address" class="ul-form__label">{{ __('Billing Address Province') }}</label>
                                <input type="text" v-model="supplier.address" class="form-control" id="address"
                                    placeholder="{{ __('translate.Address') }}">
    
                            </div>
                            <div class="form-group col-md-6">
                                <label for="address" class="ul-form__label">{{ __('Tax Number') }}</label>
                                <input type="text" v-model="supplier.address" class="form-control" id="address"
                                    placeholder="{{ __('translate.Address') }}">
    
                            </div>
                        </div>
    
                        <div class="row mt-3">
    
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                                    <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
                                    {{ __('translate.Submit') }}
                                </button>
    
                            </div>
                        </div>
    
    
                    </form>
    
                </div>
    
            </div>
        </div>
    </div>
    
    <!-- Modal add_payment_purchase -->
    <validation-observer ref="add_payment_purchase">
        <div class="modal fade" id="add_payment_purchase" tabindex="-1" role="dialog" aria-labelledby="add_payment_purchase"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('translate.pay_all_purchase_due_at_a_time') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="Submit_Payment()">
                            <div class="row">

                                 <!-- Date -->
                                 <div class="col-md-6">
                                    <validation-provider name="date" rules="required" v-slot="validationContext">
                                    <div class="form-group">
                                        <label for="picker3">{{ __('translate.Date') }}</label>
                                        
                                        <input type="text" 
                                        :state="getValidationState(validationContext)" 
                                        aria-describedby="date-feedback" 
                                        class="form-control" 
                                        placeholder="{{ __('translate.Select_Date') }}"  
                                        id="datetimepicker" 
                                        v-model="payment.date">
                                        <span class="error">@{{  validationContext.errors[0] }}</span>
                                    </div>
                                    </validation-provider>
                                </div>
    
                                <!-- Paying_Amount -->
                                <div class="form-group col-md-6">
                                    <validation-provider name="Montant à payer"
                                        :rules="{ required: true , regex: /^\d*\.?\d*$/}" v-slot="validationContext">
                                        <label for="Paying_Amount">{{ __('translate.Paying_Amount') }}
                                            <span class="field_required">*</span></label>
                                        <input @keyup="Verified_paidAmount(payment.montant)"
                                            :state="getValidationState(validationContext)"
                                            aria-describedby="Paying_Amount-feedback" v-model.number="payment.montant"
                                            placeholder="{{ __('translate.Paying_Amount') }}" type="text"
                                            class="form-control">
                                        <div class="error">@{{ validationContext.errors[0] }}</div>
                                        <span class="badge badge-danger">reste à payer : {{$currency}} @{{ total_debt }}</span>
                                    </validation-provider>
                                </div>
    
                                <div class="form-group col-md-6">
                                    <validation-provider name="Payment choice" rules="required"
                                        v-slot="{ valid, errors }">
                                        <label> {{ __('translate.Payment_choice') }}<span
                                                class="field_required">*</span></label>
                                        <v-select @input="Selected_Payment_Method" 
                                                placeholder="{{ __('translate.Choose_Payment_Choice') }}"
                                            :class="{'is-invalid': !!errors.length}"
                                            :state="errors[0] ? false : (valid ? true : null)"
                                            v-model="payment.payment_method_id" :reduce="(option) => option.value" 
                                            :options="payment_methods.map(payment_methods => ({label: payment_methods.title, value: payment_methods.id}))">

                                        </v-select>
                                        <span class="error">@{{ errors[0] }}</span>
                                    </validation-provider>
                                </div>

                                <div class="form-group col-md-6">
                                    <label> {{ __('translate.Account') }} </label>
                                    <v-select 
                                            placeholder="{{ __('translate.Choose_Account') }}"
                                        v-model="payment.account_id" :reduce="(option) => option.value" 
                                        :options="accounts.map(accounts => ({label: accounts.account_name, value: accounts.id}))">

                                    </v-select>
                                </div>
    
    
                                <div class="form-group col-md-12">
                                    <label for="note">{{ __('translate.Please_provide_any_details') }}
                                    </label>
                                    <textarea type="text" v-model="payment.notes" class="form-control" name="note" id="note"
                                        placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
                                </div>
    
                                <div class="col-lg-6">
                                    <button type="submit" class="btn btn-primary" :disabled="paymentProcessing">
                                        <span v-if="paymentProcessing" class="spinner-border spinner-border-sm"
                                            role="status" aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
                                        {{ __('translate.Submit') }}
                                    </button>
    
                                </div>
    
                            </div>
    
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </validation-observer>
    
    <!-- Modal add return payment -->
    <validation-observer ref="add_payment_return">
        <div class="modal fade" id="add_payment_return" tabindex="-1" role="dialog" aria-labelledby="add_payment_return"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('translate.pay_all_purchase_return_due_at_a_time') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="Submit_Payment_return_due()">
                            <div class="row">

                                 <!-- Date -->
                                 <div class="col-md-6">
                                        <validation-provider name="date" rules="required" v-slot="validationContext">
                                        <div class="form-group">
                                            <label for="picker3">{{ __('translate.Date') }}</label>
                                            
                                            <input type="text" 
                                            :state="getValidationState(validationContext)" 
                                            aria-describedby="date-feedback" 
                                            class="form-control" 
                                            placeholder="{{ __('translate.Select_Date') }}"  
                                            id="datetimepicker" 
                                            v-model="payment_return.date">
                                            <span class="error">@{{  validationContext.errors[0] }}</span>
                                        </div>
                                        </validation-provider>
                                    </div>
    
                                <!-- Paying_Amount -->
                                <div class="form-group col-md-6">
                                    <validation-provider name="Montant à payer"
                                        :rules="{ required: true , regex: /^\d*\.?\d*$/}" v-slot="validationContext">
                                        <label for="Paying_Amount">{{ __('translate.Paying_Amount') }}
                                            <span class="field_required">*</span></label>
                                        <input @keyup="Verified_return_paidAmount(payment_return.montant)"
                                            :state="getValidationState(validationContext)"
                                            aria-describedby="Paying_Amount-feedback"
                                            v-model.number="payment_return.montant"
                                            placeholder="{{ __('translate.Paying_Amount') }}" type="text"
                                            class="form-control">
                                        <div class="error">@{{ validationContext.errors[0] }}</div>
                                        <span class="badge badge-danger">reste à payer : {{$currency}} @{{ return_due }}</span>
                                    </validation-provider>
                                </div>

                                <div class="form-group col-md-6">
                                    <validation-provider name="Payment choice" rules="required"
                                        v-slot="{ valid, errors }">
                                        <label> {{ __('translate.Payment_choice') }}<span
                                                class="field_required">*</span></label>
                                        <v-select @input="Selected_return_Payment_Method" 
                                                placeholder="{{ __('translate.Choose_Payment_Choice') }}"
                                            :class="{'is-invalid': !!errors.length}"
                                            :state="errors[0] ? false : (valid ? true : null)"
                                            v-model="payment_return.payment_method_id" :reduce="(option) => option.value" 
                                            :options="payment_methods.map(payment_methods => ({label: payment_methods.title, value: payment_methods.id}))">

                                        </v-select>
                                        <span class="error">@{{ errors[0] }}</span>
                                    </validation-provider>
                                </div>

                                <div class="form-group col-md-6">
                                    <label> {{ __('translate.Account') }} </label>
                                    <v-select 
                                            placeholder="{{ __('translate.Choose_Account') }}"
                                        v-model="payment_return.account_id" :reduce="(option) => option.value" 
                                        :options="accounts.map(accounts => ({label: accounts.account_name, value: accounts.id}))">

                                    </v-select>
                                </div>

                  
    
                                <div class="form-group col-md-12">
                                    <label for="note">{{ __('translate.Please_provide_any_details') }}
                                    </label>
                                    <textarea type="text" v-model="payment_return.notes" class="form-control" name="note"
                                        id="note" placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
                                </div>
    
                                <div class="col-lg-6">
                                    <button type="submit" class="btn btn-primary" :disabled="payment_return_Processing">
                                        <span v-if="payment_return_Processing" class="spinner-border spinner-border-sm"
                                            role="status" aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
                                        {{ __('translate.Submit') }}
                                    </button>
    
                                </div>
    
                            </div>
    
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </validation-observer>
</div>



@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/flatpickr.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script type="text/javascript">
    $(function () {
      "use strict";

     
      
    $(document).ready(function () {

        flatpickr("#datetimepicker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i"
        });

        //init datatable
        providers_datatable();
    });


       //Get Data
       function providers_datatable(){
            var table = $('#supplier_list_table').DataTable({
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
                        'targets': [1,4,5,6,7],
                        "orderable": false,
                    },
                ],

                ajax: {
                    url: "{{ route('get_suppliers_datatable') }}",
                    data: {
                        "_token": "{{ csrf_token()}}"
                    },
                    dataType: "json",
                    type:"post"
                },
                columns: [
                    {data: 'id',className: "d-none"},
                    {data: 'action'},
                    {data: 'code'},
                    {data: 'name'},
                    {data: 'name'},
                    {data: 'name'},
                    {data: 'name'},
                    {data: 'phone'},
                    {data: 'phone'},
                    {data: 'city'},
                    {data: 'city'},
                    {data: 'city'},
                    {data: 'city'},
                    {data: 'city'},
                    {data: 'code'},
                    {data: 'code'},
                    {data: 'city'},
                    {data: 'code'},
                    {data: 'phone'},
                
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
        $(document).bind('event_delete_supplier', function (e) {
            $('#supplier_list_table').DataTable().destroy();
            providers_datatable();
        });

        // event reload Datatatble
        $(document).bind('event_supplier', function (e) {
            $('#Modal_Supplier').modal('hide');
            $('#supplier_list_table').DataTable().destroy();
            providers_datatable();
        });

      
         // add_payment_purchase
         $(document).on('click', '.payment_purchase', function () {
            var id = $(this).attr('id');
            NProgress.start();
            NProgress.set(0.1);
            app.reset_Form_payment();
            app.get_provider_debt_total(id);
            setTimeout(() => {
                NProgress.done();
                if(app.total_debt > 0){
                    $('#add_payment_purchase').modal('show');
                }else{
                    toastr.warning('Pas de dettes');
                }
            }, 1000);
        });

        // event create_payment
        $(document).bind('event_create_payment_purchase', function (e) {
            $('#add_payment_purchase').modal('hide');
            $('#supplier_list_table').DataTable().destroy();
            providers_datatable();
            NProgress.done();
        });

         // add_payment_return
         $(document).on('click', '.payment_return', function () {
            var id = $(this).attr('id');
            NProgress.start();
            NProgress.set(0.1);
            app.reset_Form_payment_return_due();
            app.get_provider_debt_return_total(id);
            setTimeout(() => {
                NProgress.done();
                if(app.return_due > 0){
                    $('#add_payment_return').modal('show');
                }else{
                    toastr.warning('Pas de dettes');
                }
            }, 1000);
        });

        // event event_payment_return_purchase
        $(document).bind('event_payment_return_purchase', function (e) {
            $('#add_payment_return').modal('hide');
            $('#supplier_list_table').DataTable().destroy();
            providers_datatable();
            NProgress.done();
        });


        //Create Supplier
        $(document).on('click', '.new_Supplier', function () {
            app.editmode = false;
            app.reset_Form();
            $('#Modal_Supplier').modal('show');
        });

        //Edit Supplier
        $(document).on('click', '.edit', function () {
            NProgress.start();
            NProgress.set(0.1);
            app.editmode = true;
            app.reset_Form();
            var id = $(this).attr('id');
            app.Get_Data_Edit(id);
           
            setTimeout(() => {
                NProgress.done()
                $('#Modal_Supplier').modal('show');
            }, 500);
        });


        //Delete Supplier
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Supplier(id);
        });
    });

</script>

<script>
    Vue.component('v-select', VueSelect.VueSelect)
    Vue.component('validation-provider', VeeValidate.ValidationProvider);
    Vue.component('validation-observer', VeeValidate.ValidationObserver);

    var app = new Vue({
          el: '#section_sup_list',
          data: {
              editmode: false,
              SubmitProcessing:false,
              paymentProcessing: false,
              payment_return_Processing : false,
              payment_methods:[],
              accounts:[],
              errors:[],
              selectedIds:[],
              suppliers: {}, 
              supplier: {
                  name: "",
                  email: "",
                  country: "",
                  city: "",
                  phone: "",
                  address: "",
              }, 
              payment: {
                date: moment().format('YYYY-MM-DD HH:mm'),
                provider_id: "",
                montant: '',
                notes: "",
                payment_method_id: "",
                account_id: "",
            },
            total_debt:0,

            payment_return: {
                date: moment().format('YYYY-MM-DD HH:mm'),
                provider_id: "",
                montant: "",
                notes: "",
                payment_method_id: "",
                account_id: "",
            },
            return_due:0,
          },
         
          methods: {

            Selected_Payment_Method(value) {
                if (value === null) {
                    this.payment.payment_method_id = "";
                }
            },

            Selected_return_Payment_Method(value) {
                if (value === null) {
                    this.payment_return.payment_method_id = "";
                }
            },

               //-------------------------------Pay return due -----------------------------------\\

            Submit_Payment_return_due() {
                this.$refs.add_payment_return.validate().then(success => {
                    if (!success) {
                        toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
                    } else if (this.payment_return.montant > this.return_due) {
                    
                    toastr.error('The amount to be paid is greater than the total debt');
                    this.payment_return.montant = 0;
                    }
                else {
                        this.Submit_Pay_return_due();
                    }

                });
            },


            //---------- keyup paid montant

            Verified_return_paidAmount() {
                if (isNaN(this.payment_return.montant)) {
                    this.payment_return.montant = 0;
                } else if (this.payment_return.montant > this.return_due) {
                    toastr.error('The amount to be paid is greater than the total debt');
                    this.payment_return.montant = 0;
                } 
            },

            //-------------------------------- reset_Form_payment-------------------------------\\
            reset_Form_payment_return_due() {
                this.payment_return = {
                    date:  moment().format('YYYY-MM-DD HH:mm'),
                    provider_id: "",
                    montant: "",
                    notes: "",
                    payment_method_id: "",
                    account_id: "",
                };
                this.return_due = 0;
            },

              
            //----------------------------------------- get_provider_debt_return_total  -------------------------------\\
            get_provider_debt_return_total(id) {
                axios
                    .get("/get_provider_debt_return_total/" + id)
                    .then(response => {
                    this.return_due = parseFloat(response.data.return_due);
                    this.payment_return.provider_id = id;
                    this.payment_return.montant = parseFloat(response.data.return_due);
                    this.payment_methods   = response.data.payment_methods;
                    this.accounts          = response.data.accounts;
                       
                    })
                    .catch(() => {
                    setTimeout(() => NProgress.done(), 500);
                    });
            },



            //---------------------------------------- Submit_Pay_return_due-------------------------------\\
            Submit_Pay_return_due() {
                this.payment_return_Processing = true;
                axios
                    .post("/providers_pay_return_due", {
                        date: this.payment_return.date,
                        provider_id: this.payment_return.provider_id,
                        montant: this.payment_return.montant,
                        notes: this.payment_return.notes,
                        payment_method_id: this.payment_return.payment_method_id,
                        account_id: this.payment_return.account_id,
                    })
                    .then(response => {
                        $.event.trigger('event_payment_return_purchase');
                        toastr.success('{{ __('translate.Created_in_successfully') }}');
                        this.payment_return_Processing = false;
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            this.errors = error.response.data.errors;
                        }
                        NProgress.done();
                    this.payment_return_Processing = false;
                    });
            },

               //---Validate State Fields
            getValidationState({ dirty, validated, valid = null }) {
                return dirty || validated ? valid : null;
            },

         

                //---------- keyup paid Amount

                Verified_paidAmount() {
                    if (isNaN(this.payment.montant)) {
                        this.payment.montant = 0;
                    } else if (this.payment.montant > this.total_debt) {
                        toastr.warning('The amount paid is greater than the remainder to be paid');
                        this.payment.montant = 0;
                    } 
                },

                 //------ Validate Form Submit_Payment
                Submit_Payment() {
                    this.$refs.add_payment_purchase.validate().then(success => {
                        if (!success) {
                            toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
                        }
                        else if (this.payment.montant > this.total_debt) {
                            toastr.error('The amount paid is greater than the remainder to be paid');
                            this.payment.montant = 0;
                        }else{
                            this.Create_Payment();
                        } 
                        
                    });
                },

                   //-------------------------------- reset_Form_payment-------------------------------\\
                reset_Form_payment() {

                    this.payment = {
                        date: moment().format('YYYY-MM-DD HH:mm'),
                        provider_id: "",
                        montant: '',
                        notes: "",
                        payment_method_id: "",
                        account_id: "",
                    };
                    this.total_debt = 0;
                    this.errors = {};
                },

                 //---------------------------------------- Submit_Pay_due-------------------------------\\
                 Create_Payment() {
                    this.paymentProcessing = true;
                    NProgress.start();
                    NProgress.set(0.1);
                    axios
                        .post("/providers_pay_due", {
                        date: this.payment.date,
                        provider_id: this.payment.provider_id,
                        amount: this.payment.montant,
                        notes: this.payment.notes,
                        payment_method_id: this.payment.payment_method_id,
                        account_id: this.payment.account_id,
                        })
                        .then(response => {
                            this.paymentProcessing = false;
                            toastr.success('{{ __('translate.Created_in_successfully') }}');
                            $.event.trigger('event_create_payment_purchase');
                            self.errors = {};
                        })
                        .catch(error => {
                            if (error.response.status == 422) {
                                self.errors = error.response.data.errors;
                            }
                            this.paymentProcessing = false;
                            NProgress.done();
                        });
                    },



             
            //----------------------------------------- get_provider_debt_total  -------------------------------\\
            get_provider_debt_total(id) {
                axios
                    .get("/get_provider_debt_total/" + id)
                    .then(response => {
                    this.total_debt = parseFloat(response.data.total_debt);
                    this.payment.provider_id = id;
                    this.payment.montant = parseFloat(response.data.total_debt);
                    this.payment_methods   = response.data.payment_methods;
                    this.accounts          = response.data.accounts;
                    
                    })
                    .catch(() => {
                    // Complete the animation of the  progress bar.
                    setTimeout(() => NProgress.done(), 500);
                    });
            },
      
              //---------------------- Get_Data_Edit  ------------------------------\\
            Get_Data_Edit(id) {
                axios
                .get("/people/suppliers/"+id+"/edit")
                .then(response => {
                    this.supplier   = response.data.supplier;
                })
                .catch(error => {
                    
                });
            },

              //----------------------------- Reset Form ---------------------------\\
              reset_Form() {
                  this.supplier = {
                      id: "",
                      name: "",
                      email: "",
                      country: "",
                      city: "",
                      phone: "",
                      address: "",
                  };
                  this.errors = {};
              },
              
             
              //------------------------ Create supplier ---------------------------\\
              Create_Supplier() {
                  var self = this;
                  self.SubmitProcessing = true;
                  axios.post("/people/suppliers", {
                      name: self.supplier.name,
                      email: self.supplier.email,
                      country: self.supplier.country,
                      city: self.supplier.city,
                      phone: self.supplier.phone,
                      address: self.supplier.address,
                  }).then(response => {
                          self.SubmitProcessing = false;
                          $.event.trigger('event_supplier');
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
             //----------------------- Update supplier ---------------------------\\
              Update_Supplier() {
                  var self = this;
                  self.SubmitProcessing = true;
                  axios.put("/people/suppliers/" + self.supplier.id, {
                      name: self.supplier.name,
                      email: self.supplier.email,
                      country: self.supplier.country,
                      city: self.supplier.city,
                      phone: self.supplier.phone,
                      address: self.supplier.address,
                  }).then(response => {
                          self.SubmitProcessing = false;
                          $.event.trigger('event_supplier');
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
            
               //--------------------------------- Remove supplier ---------------------------\\
              Remove_Supplier(id) {
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
                              .delete("/people/suppliers/" + id)
                              .then(() => {
                                $.event.trigger('event_delete_supplier');
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