@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datepicker.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/flatpickr.min.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Return_Purchases') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div id="section_return_purchase_list">
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-12">
          <div class="text-end mb-3">

            <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i
                class="i-Filter-2 me-2 font-weight-bold"></i>
              {{ __('translate.Filter') }}</a>
          </div>

          <div class="table-responsive">
            <table id="return_purchase_table" class="display table table_height">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>{{ __('translate.Date') }}</th>
                  <th>{{ __('translate.Reference') }}</th>
                  <th>{{ __('translate.Supplier') }}</th>
                  <th>{{ __('translate.warehouse') }}</th>
                  <th>{{ __('translate.Purchase_Ref') }}</th>
                  <th>{{ __('translate.Total') }}</th>
                  <th>{{ __('translate.Paid') }}</th>
                  <th>{{ __('translate.Due') }}</th>
                  <th>{{ __('translate.Payment_Status') }}</th>
                  <th class="not_show">{{ __('translate.Action') }}</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Filter -->
  <div class="modal fade" id="filter_purchase_return_modal" tabindex="-1" role="dialog"
    aria-labelledby="filter_purchase_return_modal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <form method="POST" id="filter_purchase_return">
            @csrf
            <div class="row">

              <div class="form-group col-md-6">
                <label for="start_date">{{ __('translate.From_Date') }}
                </label>
                <input type="text" class="form-control date" name="start_date" id="start_date"
                  placeholder="{{ __('translate.From_Date') }}" value="">
              </div>

              <div class="form-group col-md-6">
                <label for="end_date">{{ __('translate.To_Date') }} </label>
                <input type="text" class="form-control date" name="end_date" id="end_date"
                  placeholder="{{ __('translate.To_Date') }}" value="">
              </div>

              <div class="form-group col-md-6">
                <label for="Ref">{{ __('translate.Reference') }}
                </label>
                <input type="text" class="form-control" name="Ref" id="Ref"
                  placeholder="{{ __('translate.Reference') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="purchase_id">{{ __('translate.Purchase') }}
                </label>
                <select name="purchase_id" id="purchase_id" class="form-control">
                  <option value="0">{{ __('translate.All') }}</option>
                  @foreach ($purchases as $purchase)
                  <option value="{{$purchase->id}}">{{$purchase->Ref}}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="provider_id">{{ __('translate.Supplier') }}
                </label>
                <select name="provider_id" id="provider_id" class="form-control">
                  <option value="0">{{ __('translate.All') }}</option>
                  @foreach ($suppliers as $supplier)
                  <option value="{{$supplier->id}}">{{$supplier->name}}</option>
                  @endforeach
                </select>
              </div>

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

              <div class="form-group col-md-6">
                <label for="payment_status">{{ __('translate.Payment_Status') }} </label>
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

  <!-- Modal Show_payment -->
  <div class="modal fade" id="Show_payment" tabindex="-1" role="dialog" aria-labelledby="Show_payment"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('translate.Show_Payments') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12 mt-3">
              <div class="table-responsive">
                <table class="table table-hover table-bordered table-md">
                  <thead>
                    <tr>
                      <th scope="col">{{ __('translate.date') }}</th>
                      <th scope="col">{{ __('translate.Reference') }}</th>
                      <th scope="col">{{ __('translate.Amount') }}</th>
                      <th scope="col">{{ __('translate.Action') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="payments.length <= 0">
                      <td colspan="5">{{ __('translate.No_data_Available') }}</td>
                    </tr>
                    <tr v-for="payment in payments">
                      <td>@{{payment.date}}</td>
                      <td>@{{payment.Ref}}</td>
                      <td>{{$currency}} @{{formatNumber(payment.montant,2)}}</td>
                      <td>
                        <div role="group" aria-label="Basic example" class="btn-group">
                          <span title="PDF" class="btn btn-icon btn-info btn-sm"
                            @click="Payment_purchase_return_pdf(payment,payment.id)">
                            <i class="i-File-TXT"></i>
                          </span>
                          @can('payment_purchase_returns_edit')
                          <span title="Edit" class="btn btn-icon btn-success btn-sm" @click="Edit_Payment(payment)">
                            <i class="i-Edit"></i>
                          </span>
                          @endcan
                          @can('payment_purchase_returns_delete')
                          <span title="Delete" class="btn btn-icon btn-danger btn-sm"
                            @click="Remove_Payment(payment.id)">
                            <i class="i-Close-Window"></i>
                          </span>
                          @endcan
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Modal add_payment -->
  <validation-observer ref="Add_payment">
    <div class="modal fade" id="Add_Payment" tabindex="-1" role="dialog" aria-labelledby="Add_Payment"
      aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 v-if="EditPaiementMode" class="modal-title">{{ __('translate.Edit') }}</h5>
            <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="Submit_Payment()">
              <div class="row">


                <div class="col-md-6">
                  <validation-provider name="date" rules="required" v-slot="validationContext">
                    <div class="form-group">
                      <label for="picker3">{{ __('translate.Date') }} <span class="field_required">*</span></label>

                      <input type="text" :state="getValidationState(validationContext)" aria-describedby="date-feedback"
                        class="form-control" placeholder="{{ __('translate.Select_Date') }}" id="datetimepicker"
                        v-model="payment.date">

                      <span class="error">@{{  validationContext.errors[0] }}</span>
                    </div>
                  </validation-provider>
                </div>

                <!-- Paying_Amount -->
                <div class="form-group col-md-6">
                  <validation-provider name="Montant à payer" :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext">
                    <label for="Paying_Amount">{{ __('translate.Paying_Amount') }}
                      <span class="field_required">*</span></label>
                    <input @keyup="Verified_paidAmount(payment.montant)" :state="getValidationState(validationContext)"
                      aria-describedby="Paying_Amount-feedback" v-model.number="payment.montant"
                      placeholder="{{ __('translate.Paying_Amount') }}" type="text" class="form-control">
                    <div class="error">@{{ validationContext.errors[0] }}</div>
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
                    <span v-if="paymentProcessing" class="spinner-border spinner-border-sm" role="status"
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
  </validation-observer>

</div>
@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/flatpickr.min.js')}}"></script>
<script src="{{asset('assets/js/datepicker.min.js')}}"></script>

<script type="text/javascript">
  $(function () {
      "use strict";

      $(document).ready(function () {

        flatpickr("#datetimepicker", {
          enableTime: true,
          dateFormat: "Y-m-d H:i"
        });

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
          return_purchase_datatable();
        });


        //Get Data
        function return_purchase_datatable(start_date ='', end_date ='', Ref ='',provider_id ='', payment_statut ='', warehouse_id ='', purchase_id =''){
            var table = $('#return_purchase_table').DataTable({
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
                          'targets': [1,2,3,4,5,6,7,8,9,10],
                          "orderable": false,
                      },
                ],

                ajax: {
                    url: "{{ route('returns_purchase.index') }}",
                    data: {
                        start_date: start_date === null?'':start_date,
                        end_date: end_date === null?'':end_date,
                        Ref: Ref === null?'':Ref,
                        provider_id: provider_id == '0'?'':provider_id,
                        payment_statut: payment_statut == '0'?'':payment_statut,
                        warehouse_id: warehouse_id == '0'?'':warehouse_id,
                        purchase_id: purchase_id == '0'?'':purchase_id,
                        "_token": "{{ csrf_token()}}"
                    },
                },

                columns: [
                    {data: 'id', name: 'id', className: "d-none"},
                    {data: 'date', name: 'date'},
                    {data: 'Ref', name: 'Ref'},
                    {data: 'provider_name', name: 'provider_name'},
                    {data: 'warehouse_name', name: 'warehouse_name'},
                    {data: 'purchase_ref', name: 'purchase_ref'},
                    {data: 'GrandTotal', name: 'GrandTotal'},
                    {data: 'paid_amount', name: 'paid_amount'},
                    {data: 'due', name: 'due'},
                    {data: 'payment_status', name: 'payment_status'},
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

         // Clear Filter
         $('#Clear_Form').on('click' , function (e) {
            var end_date = new Date();
            var start_date = new Date();

            end_date.setDate(end_date.getDate() + 365);
            $("#end_date").datepicker("setDate" , end_date);

            start_date.setDate(start_date.getDate() - 365);
            $("#start_date").datepicker("setDate" , start_date);
            
            var Ref = $('#Ref').val('');
            let provider_id = $('#provider_id').val('0');
            var payment_statut = $('#payment_status').val('0');
            let warehouse_id = $('#warehouse_id').val('0');
            let purchase_id = $('#purchase_id').val('0');

        });


         // Show Modal Filter
        $('#Show_Modal_Filter').on('click' , function (e) {
            $('#filter_purchase_return_modal').modal('show');
        });


         // Submit Filter
        $('#filter_purchase_return').on('submit' , function (e) {
            e.preventDefault();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var Ref = $('#Ref').val();
            let provider_id = $('#provider_id').val();
            var payment_statut = $('#payment_status').val();
            let warehouse_id = $('#warehouse_id').val();
            let purchase_id = $('#purchase_id').val();

            $('#return_purchase_table').DataTable().destroy();
            return_purchase_datatable(start_date, end_date, Ref, provider_id, payment_statut, warehouse_id, purchase_id);

            $('#filter_purchase_return_modal').modal('hide');
           
        });


          //email return purchase
          $(document).on('click', '.send_email', function () {
            var id = $(this).attr('id');
            app.SendEmail(id);
        });

          //return PDF
          $(document).on('click', '.download_pdf', function () {
            var id = $(this).attr('id');
            var Ref = $(this).attr('Ref');
            app.Return_PDF(Ref , id);
        });

       
        //Delete return purchase
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Return(id);
        });

          // event delete return purchase (reload Datatatble)
          $(document).bind('Delete_purchase_return', function (e) {
            $('#return_purchase_table').DataTable().destroy();
            return_purchase_datatable();
        });

         //Show_Payments
         $(document).on('click', '.Show_Payments', function () {
            var id = $(this).attr('id');
            app.Show_Payments(id);
        });

        //New_Payment
        $(document).on('click', '.New_Payment', function () {
            var id = $(this).attr('id');
            var payment_status = $(this).attr('payment_status');
            app.New_Payment(payment_status , id);
        });


        // event create_payment_event
        $(document).bind('create_payment_event', function (e) {

          $('#Add_Payment').modal('hide');
          NProgress.done();

          $('#return_purchase_table').DataTable().destroy();
          return_purchase_datatable();
        });


      // event update_payment_event
      $(document).bind('update_payment_event', function (e) {
          $('#Add_Payment').modal('hide');
          $('#Show_payment').modal('hide');
          NProgress.done();

          $('#return_purchase_table').DataTable().destroy();
          return_purchase_datatable();
      });

      // event delete_payment_event
      $(document).bind('delete_payment_event', function (e) {
          $('#Show_payment').modal('hide');

          $('#return_purchase_table').DataTable().destroy();
          return_purchase_datatable();
      });


    });
</script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)
       Vue.component('validation-provider', VeeValidate.ValidationProvider);
       Vue.component('validation-observer', VeeValidate.ValidationObserver);

        var app = new Vue({
        el: '#section_return_purchase_list',
        data: {
            EditPaiementMode: false,
            SubmitProcessing:false,
            paymentProcessing :false,
            purchase_returns: [],
            payment_methods:[],
            accounts:[],
            payments: [], 
            payment: {},
            purchase_return_id: "",
            purchase_return_due:'',
            due:0,
            setting: {
                logo: "",
                CompanyName: "",
                CompanyAdress: "",
                email: "",
                CompanyPhone: ""
            },
           
        },
       
        methods: {

          Selected_Payment_Method(value) {
                if (value === null) {
                    this.payment.payment_method_id = "";
                }
            },



            //---------- keyup paid Amount
            Verified_paidAmount() {
              if (isNaN(this.payment.montant)) {
                this.payment.montant = 0;
              }else if (this.payment.montant > this.due) {
                toastr.error('{{ __('translate.Paying_amount_is_greater_than_Grand_Total') }}');
                this.payment.montant = 0;
              }
            },

            //------ Validate Form Submit_Payment
            Submit_Payment() {
              this.$refs.Add_payment.validate().then(success => {
                if (!success) {
                  toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
                }else if (this.payment.montant > this.due) {
                  toastr.error('{{ __('translate.Paying_amount_is_greater_than_Grand_Total') }}');
                  this.payment.montant = 0;
                }else if (!this.EditPaiementMode) {
                    this.Create_Payment();
                } else {
                    this.Update_Payment();
                }
              });
            },



     //------------------------ Payment_purchase_return_pdf ------------------------------\\
     Payment_purchase_return_pdf(payment, id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
     
      axios
        .get("/payment_return_purchase_pdf/" + id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "payment_" + payment.Ref + ".pdf");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },


    Send_Email_Payment(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("/payment/returns_purchase/send/email", {
          id: id,
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          toastr.success('{{ __('translate.sent_in_successfully') }}');
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          toastr.error('{{ __('translate.There_was_something_wronge') }}');
        });
    },


     //----------------------------------- New Payment Sale ------------------------------\\
     New_Payment(payment_status , id) {
      if (payment_status == "paid") {
        toastr.error('{{ __('translate.Payment_Complete') }}');
      } else {
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
        this.reset_form_payment();
        this.EditPaiementMode = false;
        this.get_data_create(id);
       
      }
    },


      //----------------------------------------- get_data_create  -------------------------------\\
      get_data_create(id) {
      axios
        .get("/payment/returns_purchase/get_data_create/" + id)
        .then(response => {
          this.payment.montant   = response.data.due;
          this.purchase_return_id    = id;
          this.due               = parseFloat(response.data.due);
          this.payment.date      = moment().format('YYYY-MM-DD HH:mm');

          this.payment_methods   = response.data.payment_methods;
          this.accounts          = response.data.accounts;

          setTimeout(() => {
            // Complete the animation of the  progress bar.
            NProgress.done();
            $('#Add_Payment').modal('show');
          }, 1000);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },
    

     //------------------------------------Edit Payment ------------------------------\\
     Edit_Payment(payment) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.reset_form_payment();
      this.EditPaiementMode  = true;
      this.payment.id        = payment.id;
      this.payment.date      = payment.date;
      this.payment.payment_method_id = payment.payment_method_id;
      this.payment.account_id = payment.account_id;
      this.payment.montant   = payment.montant;
      this.payment.notes     = payment.notes;
      this.due               = parseFloat(this.purchase_return_due) + payment.montant;
      setTimeout(() => {
        // Complete the animation of the  progress bar.
        NProgress.done();
        $('#Add_Payment').modal('show');
      }, 1000);
    },


    
     //----------------------------------------- Get Payments  -------------------------------\\
     Get_Payments(id) {
      axios
        .get("/purchase-return/returns/purchase/payment/" + id)
        .then(response => {
          this.payments = response.data.payments;
          this.purchase_return_due = response.data.due;
          this.payment_methods   = response.data.payment_methods;
          this.accounts          = response.data.accounts;

          setTimeout(() => {
            // Complete the animation of the  progress bar.
            NProgress.done();
            $('#Show_payment').modal('show');
          }, 1000);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },


      //-------------------------------Show All Payment with Sale ---------------------\\
      Show_Payments(id) {
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
        this.reset_form_payment();
        this.purchase_return_id = id;
        this.Get_Payments(id);
      },



       //----------------------------------Create Payment sale ------------------------------\\
    Create_Payment() {
      this.paymentProcessing = true;
      NProgress.start();
      NProgress.set(0.1);
      
        axios
          .post("/payment_returns_purchase", {
            purchase_return_id: this.purchase_return_id,
            date: this.payment.date,
            montant: parseFloat(this.payment.montant).toFixed(2),
            notes: this.payment.notes,
            payment_method_id: this.payment.payment_method_id,
            account_id: this.payment.account_id,
          })
          .then(response => {
            this.paymentProcessing = false;
            $.event.trigger('create_payment_event');
            toastr.success('{{ __('translate.Created_in_successfully') }}');
            this.errors = {};
          })
          .catch(() => {
            this.paymentProcessing = false;
            NProgress.done();
          });
    },
    //---------------------------------------- Update Payment ------------------------------\\
    Update_Payment() {
      this.paymentProcessing = true;
      NProgress.start();
      NProgress.set(0.1);
      
        axios
          .put("/payment_returns_purchase/" + this.payment.id, {
            purchase_return_id: this.purchase_return_id,
            date: this.payment.date,
            payment_method_id: this.payment.payment_method_id,
            account_id: this.payment.account_id,
            montant: parseFloat(this.payment.montant).toFixed(2),
            notes: this.payment.notes,
          })
          .then(response => {
            this.paymentProcessing = false;
            $.event.trigger('update_payment_event');
            toastr.success('{{ __('translate.Updated_in_successfully') }}');  
            this.errors = {};
          })
          .catch(error => {
            this.paymentProcessing = false;
            NProgress.done();
          });
    },


     //--------------------------------- Remove_Payment ---------------------------\\
     Remove_Payment(id) {
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
                    .delete("/payment_returns_purchase/" + id)
                    .then(() => {
                        $.event.trigger('delete_payment_event');
                        toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                    })
                    .catch(() => {
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            });
        },
         //------------------------------------------ Reset Form Payment ------------------------------\\
        reset_form_payment() {
            this.due = 0;
            this.payment = {
                id: "",
                date: "",
                purchase_return_id: "",
                provider_id: "",
                montant: '',
                notes: "",
                payment_method_id: "",
                account_id: "",
            };
        },


        //---Validate State Fields
        getValidationState({ dirty, validated, valid = null }) {
          return dirty || validated ? valid : null;
        },

    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //-----------------------------  Return purchase pdf------------------------------\\
    Return_PDF(Ref, id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
     
      axios
      .get("/return_purchase_pdf/" + id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute(
            "download",
            "purchase_return_" + Ref + ".pdf"
          );
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },

    SendEmail(id) {
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
        axios
            .post("/purchase-return/returns/purchase/send/email", {
                id: id,
            })
            .then(response => {
            // Complete the animation of the  progress bar.
            setTimeout(() => NProgress.done(), 500);
            toastr.success('{{ __('translate.sent_in_successfully') }}');
            })
            .catch(error => {
                // Complete the animation of the  progress bar.
                setTimeout(() => NProgress.done(), 500);
                toastr.error('{{ __('translate.There_was_something_wronge') }}');
            });
    },

      //--------------------------------- Remove_Return ---------------------------\\
      Remove_Return(id) {

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
                .delete("/purchase-return/returns_purchase/" + id)
                .then(() => {
                  $.event.trigger('Delete_purchase_return');
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