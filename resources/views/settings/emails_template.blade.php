@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/summernote/summernote-bs5.min.css')}}">

@endsection


<div class="breadcrumb">
  <h1>{{ __('translate.emails_template') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div id="section_notifications_template">

  {{-- Notification Client --}}
  <div class="row mt-5">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4>{{ __('translate.Notification_Client') }}</h4>
        </div>
        <!--begin::form-->
        <div class="card-body">

          <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="sale_tab" data-toggle="tab" data-target="#sell" type="button"
                role="tab" aria-controls="sell" aria-selected="true">{{ __('translate.Sales') }}</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="quotation_tab" data-toggle="tab" data-target="#quotation" type="button"
                role="tab" aria-controls="quotation" aria-selected="false">{{ __('translate.Quotations') }}</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="payment_received_tab" data-toggle="tab" data-target="#payment_received"
                type="button" role="tab" aria-controls="payment_received" aria-selected="false">{{ __('translate.payment_sale') }}</button>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">

            {{-- Sell Tab--}}
            <div class="tab-pane fade show active" id="sell" role="tabpanel" aria-labelledby="sale_tab">
              <form @submit.prevent="update_custom_email('sale')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{invoice_number},{invoice_url},{total_amount},{paid_amount},{due_amount}
                    </p>
                  </div>
                  <hr>

                  <div class="form-group col-md-12">
                      <label for="email_subject_sale">{{ __('translate.Email_Subject') }} </label>
                      <input type="text" v-model="sale.subject" class="form-control"
                        name="email_subject_sale" id="email_subject_sale" placeholder="{{ __('translate.Email_Subject') }}">
                  </div>
                  <div class="form-group col-md-12">
                    <label for="email_body_sale">{{ __('translate.Email_body') }} </label>
                    <textarea id="email_body_sale" name="email_body_sale" v-model="sale.body"></textarea>
                  </div>

                </div>

                <div class="row mt-3">
                  <div class="col-md-6">
                    <button type="submit" :disabled="Submit_Processing" class="btn btn-primary">
                      <span v-if="Submit_Processing" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                    </button>
                  </div>
                </div>
              </form>

            </div>

            {{-- quotation_tab --}}
            <div class="tab-pane fade" id="quotation" role="tabpanel" aria-labelledby="quotation_tab">
              <form @submit.prevent="update_custom_email('quotation')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{quotation_number},{quotation_url},{total_amount}
                    </p>
                  </div>
                  <hr>

                  <div class="form-group col-md-12">
                      <label for="email_subject_quotation">{{ __('translate.Email_Subject') }} </label>
                      <input type="text" v-model="quotation.subject" class="form-control"
                        name="email_subject_quotation" id="email_subject_quotation" placeholder="{{ __('translate.Email_Subject') }}">
                  </div>

                  <div class="form-group col-md-12">
                    <label for="email_body_quotation">{{ __('translate.Email_body') }} </label>
                    <textarea id="email_body_quotation" name="email_body_quotation" v-model="quotation.body"></textarea>
                  </div>

                </div>

                <div class="row mt-3">
                  <div class="col-md-6">
                    <button type="submit" :disabled="Submit_Processing" class="btn btn-primary">
                      <span v-if="Submit_Processing" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                    </button>
                  </div>
                </div>
              </form>
            </div>

            {{-- payment_received tab --}}
            <div class="tab-pane fade" id="payment_received" role="tabpanel" aria-labelledby="payment_received_tab">

              <form @submit.prevent="update_custom_email('payment_received')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{payment_number},{paid_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                      <label for="email_subject_payment_received">{{ __('translate.Email_Subject') }} </label>
                      <input type="text" v-model="payment_received.subject" class="form-control"
                        name="email_subject_payment_received" id="email_subject_payment_received" placeholder="{{ __('translate.Email_Subject') }}">
                  </div>

                  <div class="form-group col-md-12">
                    <label for="email_body_payment_received">{{ __('translate.Email_body') }} </label>
                    <textarea id="email_body_payment_received" name="email_body_payment_received" v-model="payment_received.body"></textarea>
                  </div>

                </div>

                <div class="row mt-3">
                  <div class="col-md-6">
                    <button type="submit" :disabled="Submit_Processing" class="btn btn-primary">
                      <span v-if="Submit_Processing" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
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


  {{-- Notification Supplier --}}
  <div class="row mt-5">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4>{{ __('translate.Notification_Supplier') }}</h4>
        </div>
        <!--begin::form-->
        <div class="card-body">

          <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="purchase_tab" data-toggle="tab" data-target="#purchase" type="button"
                role="tab" aria-controls="purchase" aria-selected="true">{{ __('translate.Purchases') }}</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="payment_sent_tab" data-toggle="tab" data-target="#payment_sent" type="button"
                role="tab" aria-controls="payment_sent" aria-selected="false">{{ __('translate.payment_purchase') }}</button>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="purchase" role="tabpanel" aria-labelledby="purchase_tab">
              <form @submit.prevent="update_custom_email('purchase')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{invoice_number},{invoice_url},{total_amount},{paid_amount},{due_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                      <label for="email_subject_purchase">{{ __('translate.Email_Subject') }} </label>
                      <input type="text" v-model="purchase.subject" class="form-control"
                        name="email_subject_purchase" id="email_subject_purchase" placeholder="{{ __('translate.Email_Subject') }}">
                  </div>

                  <div class="form-group col-md-12">
                    <label for="email_body_purchase">{{ __('translate.Email_body') }} </label>
                    <textarea id="email_body_purchase" name="email_body_purchase" v-model="purchase.body"></textarea>
                  </div>

                </div>

                <div class="row mt-3">
                  <div class="col-md-6">
                    <button type="submit" :disabled="Submit_Processing" class="btn btn-primary">
                      <span v-if="Submit_Processing" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                    </button>
                  </div>
                </div>
              </form>

            </div>
            <div class="tab-pane fade" id="payment_sent" role="tabpanel" aria-labelledby="payment_sent_tab">
              <form @submit.prevent="update_custom_email('payment_sent')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{payment_number},{paid_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                      <label for="email_subject_payment_sent">{{ __('translate.Email_Subject') }} </label>
                      <input type="text" v-model="payment_sent.subject" class="form-control"
                        name="email_subject_payment_sent" id="email_subject_payment_sent" placeholder="{{ __('translate.Email_Subject') }}">
                  </div>

                  <div class="form-group col-md-12">
                    <label for="email_body_payment_sent">{{ __('translate.Email_body') }} </label>
                    <textarea id="email_body_payment_sent" name="email_body_payment_sent" v-model="payment_sent.body"></textarea>
                  </div>

                </div>

                <div class="row mt-3">
                  <div class="col-md-6">
                    <button type="submit" :disabled="Submit_Processing" class="btn btn-primary">
                      <span v-if="Submit_Processing" class="spinner-border spinner-border-sm" role="status"
                        aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
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


</div>

@endsection


@section('page-js')
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/styles/vendor/summernote/summernote-bs5.min.js')}}"></script>

<script>
  var app = new Vue({
        el: '#section_notifications_template',
        data: {
          Submit_Processing :false,
          sale: @json($sale),
          quotation: @json($quotation),
          payment_received: @json($payment_received),
          purchase: @json($purchase),
          payment_sent: @json($payment_sent),

          custom_email_body:'',
          custom_email_subject:'',
        },

        mounted() {
          const self = this;

          //init sale body
          $('#email_body_sale').summernote({
            height: 300,
            toolbar: [
              ['style', ['style']],
              [ 'font', [ 'bold', 'italic', 'underline','clear'] ],
              ['fontsize', ['fontsize']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph','height']],
              [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ]],
          ],
            callbacks: {
              onChange: function (contents) {
                self.sale.body = contents;
              },
            },
          });

          //init quotation body
          $('#email_body_quotation').summernote({
            height: 300,
            toolbar: [
              ['style', ['style']],
              [ 'font', [ 'bold', 'italic', 'underline','clear'] ],
              ['fontsize', ['fontsize']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph','height']],
              [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ]],
          ],
            callbacks: {
              onChange: function (contents) {
                self.quotation.body = contents;
              },
            },
          });

           //init payment_received body
           $('#email_body_payment_received').summernote({
            height: 300,
            toolbar: [
              ['style', ['style']],
              [ 'font', [ 'bold', 'italic', 'underline','clear'] ],
              ['fontsize', ['fontsize']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph','height']],
              [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ]],
          ],
            callbacks: {
              onChange: function (contents) {
                self.payment_received.body = contents;
              },
            },
          });

           //init purchase body
           $('#email_body_purchase').summernote({
            height: 300,
            toolbar: [
              ['style', ['style']],
              [ 'font', [ 'bold', 'italic', 'underline','clear'] ],
              ['fontsize', ['fontsize']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph','height']],
              [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ]],
          ],
            callbacks: {
              onChange: function (contents) {
                self.purchase.body = contents;
              },
            },
          });

           //init payment_sent body
           $('#email_body_payment_sent').summernote({
            height: 300,
            toolbar: [
              ['style', ['style']],
              [ 'font', [ 'bold', 'italic', 'underline','clear'] ],
              ['fontsize', ['fontsize']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph','height']],
              [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ]],
          ],
            callbacks: {
              onChange: function (contents) {
                self.payment_sent.body = contents;
              },
            },
          });
        },
       
        methods: {


          //---------------------------------- update_custom_email ----------------\\
          update_custom_email(email_type) {
              this.Submit_Processing = true;
              NProgress.start();
              NProgress.set(0.1);

              if(email_type == 'sale'){
                this.custom_email_body = $('#email_body_sale').summernote('code');
                this.custom_email_subject =  this.sale.subject;
              }else if(email_type == 'quotation'){
                this.custom_email_body = $('#email_body_quotation').summernote('code');
                this.custom_email_subject =  this.quotation.subject;
              }else if(email_type == 'payment_received'){
                this.custom_email_body = $('#email_body_payment_received').summernote('code');
                this.custom_email_subject =  this.payment_received.subject;
              }else if(email_type == 'purchase'){
                this.custom_email_body = $('#email_body_purchase').summernote('code');
                this.custom_email_subject =  this.purchase.subject;
              }else if(email_type == 'payment_sent'){
                this.custom_email_body = $('#email_body_payment_sent').summernote('code');
                this.custom_email_subject =  this.payment_sent.subject;
              }
              
              axios.put("/settings/update_custom_email", {
                custom_email_body: this.custom_email_body,
                custom_email_subject: this.custom_email_subject,
                email_type: email_type
              }, {
                headers: {
                  'Content-Type': 'text/html'
                }
              })
              .then(response => {
                toastr.success('{{ __('translate.Updated_in_successfully') }}');
                NProgress.done();
                this.Submit_Processing = false;
              })
              .catch(error => {
                NProgress.done();
                toastr.error('{{ __('translate.There_was_something_wronge') }}');
                this.Submit_Processing = false;
              });
          },

      
          
        
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>
@endsection