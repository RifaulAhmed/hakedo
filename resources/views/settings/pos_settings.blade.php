@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">

@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Pos_Receipt_Settings') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="section_System_Settings_list">

  <!-- Reciept Pos Settings -->
    <form @submit.prevent="Update_Pos_Settings">
      <div class="row mt-5">
          
                  <!-- Note to customer -->
                 
                    <div class="form-group col-md-12">
                        <label for="note_customer" class="ul-form__label">{{ __('translate.Note_to_customer') }} <span
                                class="field_required">*</span></label>
                        <input type="text" v-model="pos_settings.note_customer" class="form-control" name="note_customer" id="note_customer"
                            placeholder="{{ __('translate.Note_to_customer') }}">
                        <span class="error" v-if="errors && errors.note_customer">
                            @{{ errors.note_customer[0] }}
                        </span>
                    </div>

                 
                   <!-- Show Phone-->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                          {{ __('translate.Show_Phone') }}
                          <input  type="checkbox" v-model="pos_settings.show_phone">
                          <span class="slider"></span>
                        </label>
                    </div>

                     <!-- Show Address -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                          {{ __('translate.Show_Address') }}
                          <input  type="checkbox" v-model="pos_settings.show_address">
                          <span class="slider"></span>
                        </label>
                    </div>

                      <!-- Show Email  -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                           {{ __('translate.Show_Email') }}
                          <input  type="checkbox" v-model="pos_settings.show_email">
                          <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Show Customer  -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                          {{ __('translate.Show_Customer') }}
                          <input  type="checkbox" v-model="pos_settings.show_customer">
                          <span class="slider"></span>
                        </label>
                    </div>

                     <!-- Show_Warehouse  -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                          {{ __('translate.Show_Warehouse') }}
                          <input  type="checkbox" v-model="pos_settings.show_Warehouse">
                          <span class="slider"></span>
                        </label>
                    </div>

                     <!-- Show Tax & Discount  -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                       {{ __('translate.Show_Tax_Discount_Shipping') }}
                          <input  type="checkbox" v-model="pos_settings.show_discount">
                          <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Show Note_to_customer  -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                       {{ __('translate.Note_to_customer') }}
                          <input  type="checkbox" v-model="pos_settings.show_note">
                          <span class="slider"></span>
                        </label>
                    </div>

                      <!-- Enable_Print_Invoice -->
                    <div class="col-md-4 mt-3 mb-3">
                       <label class="switch switch-primary mr-3">
                        {{ __('translate.Print_Invoice_automatically') }}
                          <input  type="checkbox" v-model="pos_settings.is_printable">
                          <span class="slider"></span>
                        </label>
                    </div>

                    <div class="col-md-12 mt-3 mb-3">
                      <button @click="Update_Pos_Settings" class="btn btn-primary" :disabled="SubmitProcessing">
                        <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                          aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                      </button>
                  </div>

      </div>
    </form>
  


</div>
@endsection

@section('page-js')
<script src="{{asset('assets/js/nprogress.js')}}"></script>


<script>


        var app = new Vue({
        el: '#section_System_Settings_list',
        data: {
            SubmitProcessing:false,
            errors:[],
            pos_settings:@json($pos_settings),
        },
       
        methods: {





            //---------------------------------- Update_Email_Settings----------------\\
            Update_Pos_Settings() {
              NProgress.start();
              NProgress.set(0.1);
                var self = this;
                self.SubmitProcessing = true;
                axios
                    .put("/settings/pos_settings/" + this.pos_settings.id, {
                      note_customer: this.pos_settings.note_customer,
                      show_note: this.pos_settings.show_note,
                      show_discount: this.pos_settings.show_discount,
                      show_phone: this.pos_settings.show_phone,
                      show_email: this.pos_settings.show_email,
                      show_address: this.pos_settings.show_address,
                      show_customer: this.pos_settings.show_customer,  
                      show_Warehouse: this.pos_settings.show_Warehouse,  
                      is_printable: this.pos_settings.is_printable,     
                    })
                    .then(response => {
                        self.SubmitProcessing = false;
                        NProgress.done();
                        window.location.href = '/settings/pos_settings'; 
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors = {};
                    })
                    .catch(error => {
                        self.SubmitProcessing = false;
                        NProgress.done();
                        if (error.response.status == 422) {
                            self.errors = error.response.data.errors;
                        }
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