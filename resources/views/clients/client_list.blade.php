@extends('layouts.master')
@section('main-content')
@section('page-css')

<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/flatpickr.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Client_List') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_Client_list">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="text-end mb-3">
                    @can('client_add')
                        <a class="btn btn-outline-primary btn-md m-1" href="{{route('clients.create')}}"><i
                                class="i-Add me-2 font-weight-bold"></i>
                            {{ __('translate.Create') }}</a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table id="client_list_table" class="display table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="not_show">{{ __('translate.Action') }}</th>
                                <th>{{ __('translate.Image') }}</th>
                                <th>{{ __('translate.Code') }}</th>
                                <th>{{ __('translate.FullName') }}</th>
                                <th>{{ __('translate.Phone') }}</th>
                                <th>{{ __('translate.Total_Sale_Due') }}</th>
                                <th>{{ __('translate.Total_Sell_Return_Due') }}</th>
                                <th>{{ __('translate.Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal add sale payment -->
    <validation-observer ref="add_payment_sale">
        <div class="modal fade" id="add_payment_sale" tabindex="-1" role="dialog" aria-labelledby="add_payment_sale"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('translate.pay_all_sell_due_at_a_time') }}</h5>
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
                                        <span class="badge badge-danger">reste à payer : {{$currency}} @{{ sell_due }}</span>
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
                                    <textarea type="text" v-model="payment.notes" class="form-control" name="note"
                                        id="note"
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
                        <h5 class="modal-title">{{ __('translate.pay_all_sell_return_due_at_a_time') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="Submit_Payment_sell_return_due()">
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
                                            aria-describedby="Paying_Amount-feedback" v-model.number="payment_return.montant"
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
                                        id="note"
                                        placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
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
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/flatpickr.min.js')}}"></script>


<script type="text/javascript">
    $(function () {
      "use strict";

      $(document).ready(function () {

            flatpickr("#datetimepicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i"
            });

            //init datatable
            client_datatable();
        });

     

       //Get Data
       function client_datatable(){
            var table = $('#client_list_table').DataTable({
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
                        'targets': [1,2,5,6,7,8],
                        "orderable": false,
                    },
                ],

                ajax: {
                    url: "{{ route('clients_datatable') }}",
                    data: {
                        "_token": "{{ csrf_token()}}"
                    },
                    dataType: "json",
                    type:"post"
                },

                columns: [
                    {data: 'id' , className: "d-none"},
                    {data: 'action'},
                    {data: 'photo'},
                    {data: 'code'},
                    {data: 'username'},
                    {data: 'phone'},
                    {data: 'sell_due'},
                    {data: 'return_due'},
                    {data: 'status'},
                
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
        $(document).bind('event_client', function (e) {
            $('#client_list_table').DataTable().destroy();
            client_datatable();
        });


        //Add payment sale
        $(document).on('click', '.payment_sale', function () {
            var id = $(this).attr('id');
            NProgress.start();
            NProgress.set(0.1);
            app.reset_Form_payment();
            app.get_client_debt_total(id);
            setTimeout(() => {
                NProgress.done();
                if(app.sell_due > 0){
                    $('#add_payment_sale').modal('show');
                }else{
                    toastr.warning('Pas de dettes');
                }
            }, 1000);
        });

        // event Create_Facture_sale
        $(document).bind('Create_Facture_sale', function (e) {
            $('#add_payment_sale').modal('hide');
            $('#client_list_table').DataTable().destroy();
            client_datatable();
            NProgress.done();
        });

         // add_payment_return
         $(document).on('click', '.payment_sale_return', function () {
            var id = $(this).attr('id');
            NProgress.start();
            NProgress.set(0.1);
            app.reset_Form_payment_return_due();
            app.get_client_debt_return_total(id);
            setTimeout(() => {
                NProgress.done();
                if(app.return_due > 0){
                    $('#add_payment_return').modal('show');
                }else{
                    toastr.warning('Pas de dettes');
                }
            }, 1000);
        });

         // event event_payment_return_sale
         $(document).bind('event_payment_return_sale', function (e) {
            $('#add_payment_return').modal('hide');
            $('#client_list_table').DataTable().destroy();
            client_datatable();
            NProgress.done();
        });


        //Delete client
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Client(id);
        });
    });
</script>

<script>
    Vue.component('v-select', VueSelect.VueSelect)
    Vue.component('validation-provider', VeeValidate.ValidationProvider);
    Vue.component('validation-observer', VeeValidate.ValidationObserver);

    var app = new Vue({
        el: '#section_Client_list',
        data: {
            SubmitProcessing:false,
            paymentProcessing: false,
            payment_return_Processing: false,
            errors:[],
            selectedIds:[],
            payment_methods:[],
            accounts:[],
            clients: {}, 
            payment: {
                date: moment().format('YYYY-MM-DD HH:mm'),
                client_id: "",
                montant: '',
                notes: "",
                payment_method_id: "",
                account_id: "",
            },
            payment_return: {
                date: moment().format('YYYY-MM-DD HH:mm'),
                client_id: "",
                payment_method_id: "",
                account_id: "",
                montant: "",
                notes: "",
            },
            sell_due:0,
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


             //---Validate State Fields
            getValidationState({ dirty, validated, valid = null }) {
                return dirty || validated ? valid : null;
            },


                //---------- keyup paid montant

                Verified_paidAmount() {
                    if (isNaN(this.payment.montant)) {
                        this.payment.montant = 0;
                    } else if (this.payment.montant > this.sell_due) {
                        toastr.warning('The amount paid is greater than the remainder to be paid');
                        this.payment.montant = 0;
                    } 
                },

                 //------ Validate Form Submit_Payment
                Submit_Payment() {
                    this.$refs.add_payment_sale.validate().then(success => {
                        if (!success) {
                            toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
                        }
                        else if (this.payment.montant > this.sell_due) {
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
                        date:  moment().format('YYYY-MM-DD HH:mm'),
                        client_id: "",
                        montant: '',
                        notes: "",
                        payment_method_id: "",
                        account_id: "",
                    };
                    this.sell_due = 0;
                },

                 //---------------------------------------- Submit_Pay_due-------------------------------\\
                 Create_Payment() {
                    this.paymentProcessing = true;
                    NProgress.start();
                    NProgress.set(0.1);
                    axios
                        .post("/clients_pay_due", {
                        date: this.payment.date,
                        client_id: this.payment.client_id,
                        montant: this.payment.montant,
                        notes: this.payment.notes,
                        payment_method_id: this.payment.payment_method_id,
                        account_id: this.payment.account_id,
                        })
                        .then(response => {
                            this.paymentProcessing = false;
                            $.event.trigger('Create_Facture_sale');
                            toastr.success('{{ __('translate.Created_in_successfully') }}');
                        })
                        .catch(error => {
                            if (error.response.status == 422) {
                                this.errors = error.response.data.errors;
                            }
                            this.paymentProcessing = false;
                            NProgress.done();
                        });
                    },



             
            //----------------------------------------- get_client_debt_total  -------------------------------\\
            get_client_debt_total(id) {
                axios
                    .get("/get_client_debt_total/" + id)
                    .then(response => {
                    this.sell_due = parseFloat(response.data.sell_due);
                    this.payment.client_id = id;
                    this.payment.montant = parseFloat(response.data.sell_due);
                    this.payment_methods   = response.data.payment_methods;
                    this.accounts          = response.data.accounts;
                       
                    })
                    .catch(() => {
                    setTimeout(() => NProgress.done(), 500);
                    });
            },


             //-------------------------------Pay sell return due -----------------------------------\\

            Submit_Payment_sell_return_due() {
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
                    client_id: "",
                    montant: "",
                    notes: "",
                    payment_method_id: "",
                    account_id: "",
                };
                this.return_due = 0;
            },

              
            //----------------------------------------- get_client_debt_return_total  -------------------------------\\
            get_client_debt_return_total(id) {
                axios
                    .get("/get_client_debt_return_total/" + id)
                    .then(response => {
                    this.return_due = parseFloat(response.data.return_due);
                    this.payment_return.client_id = id;
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
                    .post("/clients_pay_return_due", {
                        date: this.payment_return.date,
                        client_id: this.payment_return.client_id,
                        montant: this.payment_return.montant,
                        notes: this.payment_return.notes,
                        payment_method_id: this.payment_return.payment_method_id,
                        account_id: this.payment_return.account_id,
                    })
                    .then(response => {
                        $.event.trigger('event_payment_return_sale');
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
                
             //--------------------------------- Remove Client ---------------------------\\
            Remove_Client(id) {
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
                            .delete("/people/clients/" + id)
                            .then(() => {
                                $.event.trigger('event_client');
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