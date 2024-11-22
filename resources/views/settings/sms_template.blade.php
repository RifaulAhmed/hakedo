@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection


<div class="breadcrumb">
  <h1>{{ __('translate.sms_template') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div id="section_notifications_template">

  {{-- Notification Client --}}
  <div class="row mt-5">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4> {{ __('translate.Notification_Client') }}</h4>
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
              <form @submit.prevent="update_sms_body('sale')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{invoice_number},{invoice_url},{total_amount},{paid_amount},{due_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                    <label for="sms_body_sale">{{ __('translate.SMS_Body') }} </label>
                    <textarea type="text" v-model="sms_body_sale" class="form-control height-200"
                      name="sms_body_sale" id="sms_body_sale" placeholder="{{ __('translate.SMS_Body') }}"></textarea>
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
              <form @submit.prevent="update_sms_body('quotation')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{quotation_number},{quotation_url},{total_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                    <label for="sms_body_quotation">{{ __('translate.SMS_Body') }} </label>
                    <textarea type="text" v-model="sms_body_quotation" class="form-control height-200"
                       name="sms_body_quotation" id="sms_body_quotation"
                      placeholder="{{ __('translate.SMS_Body') }}"></textarea>
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

              <form @submit.prevent="update_sms_body('payment_received')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{payment_number},{paid_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                    <label for="sms_body_payment_received">{{ __('translate.SMS_Body') }} </label>
                    <textarea type="text" v-model="sms_body_payment_received" class="form-control height-200"
                      name="sms_body_payment_received" id="sms_body_payment_received"
                      placeholder="{{ __('translate.SMS_Body') }}"></textarea>
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
              <form @submit.prevent="update_sms_body('purchase')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{invoice_number},{invoice_url},{total_amount},{paid_amount},{due_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                    <label for="sms_body_purchase">{{ __('translate.SMS_Body') }} </label>
                    <textarea type="text" v-model="sms_body_purchase" class="form-control height-200"
                      name="sms_body_purchase" id="sms_body_purchase"
                      placeholder="{{ __('translate.SMS_Body') }}"></textarea>
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
              <form @submit.prevent="update_sms_body('payment_sent')">
                <div class="row">
                  <div class=" col-md-12">
                    <span> <strong>{{ __('translate.Available_Tags') }} : </strong></span>
                    <p>
                      {contact_name},{business_name},{payment_number},{paid_amount}
                    </p>
                  </div>
                  <hr>
                  <div class="form-group col-md-12">
                    <label for="sms_body_payment_sent">{{ __('translate.SMS_Body') }} </label>
                    <textarea type="text" v-model="sms_body_payment_sent" class="form-control height-200"
                      name="sms_body_payment_sent" id="sms_body_payment_sent"
                      placeholder="{{ __('translate.SMS_Body') }}"></textarea>
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

<script>
  var app = new Vue({
        el: '#section_notifications_template',
        data: {
          
          Submit_Processing :false,
          sms_body_sale: @json($sms_body_sale),
          sms_body_quotation: @json($sms_body_quotation),
          sms_body_payment_received: @json($sms_body_payment_received),

          sms_body_purchase: @json($sms_body_purchase),
          sms_body_payment_sent: @json($sms_body_payment_sent),

          sms_body:'',
        },
       
        methods: {


          //---------------------------------- update_sms_body_sale ----------------\\
          update_sms_body(sms_body_type) {
              this.Submit_Processing = true;
              NProgress.start();
              NProgress.set(0.1);

              if(sms_body_type == 'sale'){
                this.sms_body = this.sms_body_sale;
              }else if(sms_body_type == 'quotation'){
                this.sms_body = this.sms_body_quotation;
              }else if(sms_body_type == 'payment_received'){
                this.sms_body = this.sms_body_payment_received;
              }else if(sms_body_type == 'purchase'){
                this.sms_body = this.sms_body_purchase;
              }else if(sms_body_type == 'payment_sent'){
                this.sms_body = this.sms_body_payment_sent;
              }
              
              axios
                .put("/settings/update_sms_body",{
                  sms_body: this.sms_body,
                  sms_body_type: sms_body_type,
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