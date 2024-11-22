@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.sms_settings') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<section id="section_sms">

    <!-- Default SMS -->
    <div class="row mt-5">
      <div class="col-md-12">

        <div class="card">
          <div class="card-header">
            <h4>{{ __('translate.Default_SMS') }}</h4>
          </div>
          <!--begin::form-->
          <div class="card-body">
            <form @submit.prevent="update_Default_SMS()" enctype="multipart/form-data">
              <div class="row">

                <div class="form-group col-md-6">
                  <label>{{ __('translate.Default_SMS') }}</label>
                  <v-select placeholder="{{ __('translate.Default_SMS') }}" v-model="default_sms_gateway"
                    :reduce="(option) => option.value" :options="
                                    [
                                        {label: 'Twilio', value: 'twilio'},
                                        {label: 'Nexmo (now Vonage)', value: 'nexmo'},
                                        {label: 'Infobip', value: 'infobip'},
                                    ]">
                  </v-select>
                </div>

              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary">
                    <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Twilio SMS API -->
    <div class="row mt-5">
      <div class="col-md-12">

        <div class="card">
          <div class="card-header">
            <h4>{{ __('translate.Twilio API') }}</h4>
          </div>
          <!--begin::form-->
          <div class="card-body">
            <form @submit.prevent="update_twilio_config()" enctype="multipart/form-data">
              <div class="row">

                <div class="form-group col-md-4">
                  <label for="TWILIO_SID">{{ __('translate.Twilio SID') }}</label>
                  <input type="text" v-model="twilio.TWILIO_SID" class="form-control" id="TWILIO_SID"
                    placeholder="{{ __('translate.Twilio SID') }}">

                </div>

                <div class="form-group col-md-4">
                  <label for="TWILIO_TOKEN">{{ __('translate.Twilio TOKEN') }} </label>
                  <input type="text" v-model="twilio.TWILIO_TOKEN" class="form-control" id="TWILIO_TOKEN"
                    placeholder="{{ __('translate.Twilio TOKEN') }}">

                </div>


                <div class="form-group col-md-4">
                  <label for="TWILIO_FROM">{{ __('translate.Twilio FROM') }} </label>
                  <input type="text" v-model="twilio.TWILIO_FROM" class="form-control" id="TWILIO_FROM"
                    placeholder="{{ __('translate.Twilio FROM') }}">

                </div>

              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary" :disabled="Submit_twilio">
                    <span v-if="Submit_twilio" class="spinner-border spinner-border-sm" role="status"
                      aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Nexmo (now Vonage) API -->
    <div class="row mt-5">
      <div class="col-md-12">

        <div class="card">
          <div class="card-header">
            <h4>{{ __('translate.Nexmo (now Vonage)') }}</h4>
          </div>
          <!--begin::form-->
          <div class="card-body">
            <form @submit.prevent="update_nexmo_config()" enctype="multipart/form-data">
              <div class="row">

                <div class="form-group col-md-4">
                  <label for="nexmo_key">{{ __('translate.NEXMO KEY') }}</label>
                  <input type="text" v-model="nexmo.nexmo_key" class="form-control" id="nexmo_key"
                    placeholder="{{ __('translate.NEXMO KEY') }}">

                </div>

                <div class="form-group col-md-4">
                  <label for="nexmo_secret">{{ __('translate.NEXMO SECRET') }}</label>
                  <input type="text" v-model="nexmo.nexmo_secret" class="form-control" id="nexmo_secret"
                    placeholder="{{ __('translate.NEXMO SECRET') }}">

                </div>


                <div class="form-group col-md-4">
                  <label for="nexmo_from">{{ __('translate.NEXMO FROM') }}</label>
                  <input type="text" v-model="nexmo.nexmo_from" class="form-control" id="nexmo_from"
                    placeholder="{{ __('translate.NEXMO FROM') }}">

                </div>

              </div>

              <div class="row mt-3">

                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary" :disabled="Submit_nexmo">
                    <span v-if="Submit_nexmo" class="spinner-border spinner-border-sm" role="status"
                      aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Infobip SMS API -->
    <div class="row mt-5">
      <div class="col-md-12">

        <div class="card">
          <div class="card-header">
            <h4>{{ __('translate.Infobip API') }}</h4>
          </div>
          <!--begin::form-->
          <div class="card-body">
            <form @submit.prevent="update_infobip_config()" enctype="multipart/form-data">
              <div class="row">

                <div class="form-group col-md-4">
                  <label for="base_url">{{ __('translate.BASE URL') }}</label>
                  <input type="text" v-model="infobip.base_url" class="form-control" id="base_url"
                    placeholder="{{ __('translate.BASE URL') }}">

                </div>

                <div class="form-group col-md-4">
                  <label for="api_key">{{ __('translate.API KEY') }}</label>
                  <input type="text" v-model="infobip.api_key" class="form-control" id="api_key"
                    placeholder="{{ __('translate.API KEY') }}">

                </div>


                <div class="form-group col-md-4">
                  <label for="sender_from">{{ __('translate.Sender From') }}</label>
                  <input type="text" v-model="infobip.sender_from" class="form-control" id="sender_from"
                    placeholder="{{ __('translate.SMS sender number Or Name') }}">

                </div>

              </div>

              <div class="row mt-3">

                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary" :disabled="Submit_infobip">
                    <span v-if="Submit_infobip" class="spinner-border spinner-border-sm" role="status"
                      aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

</section>

@endsection

@section('page-js')
<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)

        var app = new Vue({
        el: '#section_sms',
        data: {
          Submit_twilio:false,
          Submit_nexmo:false,
          Submit_infobip:false,

          twilio: @json($twilio),
          nexmo: @json($nexmo),
          infobip: @json($infobip),
          default_sms_gateway: @json($default_sms_gateway),
        },
       
        methods: {


            //---------------------------------- update_twilio_config ----------------\\
            update_Default_SMS() {
              NProgress.start();
              NProgress.set(0.1);
              axios
                .put("/settings/update_Default_SMS",{
                  default_sms_gateway: this.default_sms_gateway,
                })
                .then(response => {
                  toastr.success('{{ __('translate.Updated_in_successfully') }}');
                  NProgress.done();
                })
                .catch(error => {
                  NProgress.done();
                  toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
            },


  

            //---------------------------------- update_twilio_config ----------------\\
            update_twilio_config() {
              var self = this;
              self.Submit_twilio = true;
              NProgress.start();
              NProgress.set(0.1);
              axios
                .post("/settings/update_twilio_config",{
                  TWILIO_SID: this.twilio.TWILIO_SID,
                  TWILIO_TOKEN: this.twilio.TWILIO_TOKEN,
                  TWILIO_FROM: this.twilio.TWILIO_FROM,
                })
                .then(response => {
                  self.Submit_twilio = false;
                  toastr.success('{{ __('translate.Updated_in_successfully') }}');
                  NProgress.done();
                })
                .catch(error => {
                  self.Submit_twilio = false;
                  NProgress.done();
                  toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
            },

              //---------------------------------- update_nexmo_config ----------------\\
            update_nexmo_config() {
              var self = this;
              self.Submit_nexmo = true;
              NProgress.start();
              NProgress.set(0.1);
              axios
                .post("/settings/update_nexmo_config",{
                  nexmo_key: this.nexmo.nexmo_key,
                  nexmo_secret: this.nexmo.nexmo_secret,
                  nexmo_from: this.nexmo.nexmo_from,
                })
                .then(response => {
                  self.Submit_nexmo = false;
                  toastr.success('{{ __('translate.Updated_in_successfully') }}');
                  NProgress.done();
                })
                .catch(error => {
                  self.Submit_nexmo = false;
                  NProgress.done();
                  toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
            },

            //---------------------------------- update_nexmo_config ----------------\\
            update_infobip_config() {
              var self = this;
              self.Submit_infobip = true;
              NProgress.start();
              NProgress.set(0.1);
              axios
                .post("/settings/update_infobip_config",{
                  base_url: this.infobip.base_url,
                  api_key: this.infobip.api_key,
                  sender_from: this.infobip.sender_from,
                })
                .then(response => {
                  self.Submit_infobip = false;
                  toastr.success('{{ __('translate.Updated_in_successfully') }}');
                  NProgress.done();
                })
                .catch(error => {
                  self.Submit_infobip = false;
                  NProgress.done();
                  toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
            },




        
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>
@endsection