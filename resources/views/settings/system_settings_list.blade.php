@extends('layouts.master')
@section('main-content')
@section('page-css')
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.System_Settings') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>
<div id="section_System_Settings_list">
  {{-- System_Settings --}}
  <div class="row">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4>{{ __('translate.System_Settings') }}</h4>
        </div>
        <!--begin::form-->
        <div class="card-body">
          <form @submit.prevent="Update_Settings()" enctype="multipart/form-data">
            <div class="row">

              <!-- Customer -->
              <div class="form-group col-md-4">
                  <label>{{ __('translate.Default_Currency') }} <span class="field_required">*</span></label>
                  <v-select v-model="setting.currency_id" :reduce="label => label.value"
                    placeholder="{{ __('translate.Default_Currency') }}"
                    :options="currencies.map(currencies => ({label: currencies.name, value: currencies.id}))">
                  </v-select>
                  <span class="error" v-if="errors_settings && errors_settings.currency_id">
                      @{{ errors_settings.currency_id[0] }}
                    </span>
              </div>

              <div class="form-group col-md-4">
                <label for="email">{{ __('translate.Default_Email') }} <span class="field_required">*</span></label>
                <input type="text" v-model="setting.email" class="form-control" id="email" id="email"
                  placeholder="{{ __('translate.Enter_Default_Email') }}">
                <span class="error" v-if="errors_settings && errors_settings.email">
                  @{{ errors_settings.email[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="logo">{{ __('translate.Change_Logo') }} </label>
                <input name="logo" @change="changeLogo" type="file" class="form-control" id="logo">
                <span class="error" v-if="errors_settings && errors_settings.logo">
                  @{{ errors_settings.logo[0] }}
                </span>
              </div>


              <div class="form-group col-md-4">
                <label for="name">{{ __('translate.Company_Name') }} <span class="field_required">*</span></label>
                <input type="text" v-model="setting.CompanyName" class="form-control" id="name"
                  placeholder="{{ __('translate.Enter_Company_Name') }}">
                <span class="error" v-if="errors_settings && errors_settings.name">
                  @{{ errors_settings.name[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="Phone">{{ __('translate.Company_Phone') }} <span class="field_required">*</span></label>
                <input type="text" v-model="setting.CompanyPhone" class="form-control" id="Phone"
                  placeholder="{{ __('translate.Enter_Company_Phone') }}">
                <span class="error" v-if="errors_settings && errors_settings.phone">
                  @{{ errors_settings.phone[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="invoice_footer">{{ __('translate.pdf_footer') }} </label>
                <input type="text" v-model="setting.invoice_footer" class="form-control" id="invoice_footer"
                  placeholder="{{ __('translate.pdf_footer') }}">
              </div>

              <div class="form-group col-md-4">
                  <label for="invoice_footer">{{ __('translate.Developed_by') }} <span class="field_required">*</span></label>
                  <input type="text" v-model="setting.developed_by" class="form-control" id="developed_by"
                    placeholder="{{ __('translate.Developed_by') }}">
                  <span class="error" v-if="errors_settings && errors_settings.developed_by">
                    @{{ errors_settings.developed_by[0] }}
                  </span>
              </div>

              <div class="form-group col-md-4">
                <label for="app_name">{{ __('translate.App_name') }} <span class="field_required">*</span></label>
                <input type="text" v-model="setting.app_name" class="form-control" id="app_name"
                  placeholder="{{ __('translate.App_name') }}">
                <span class="error" v-if="errors_settings && errors_settings.app_name">
                  @{{ errors_settings.app_name[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                  <label for="Footer">{{ __('translate.Footer') }} <span class="field_required">*</span></label>
                  <input type="text" v-model="setting.footer" class="form-control" id="Footer"
                    placeholder="{{ __('translate.Footer') }}">
                  <span class="error" v-if="errors_settings && errors_settings.footer">
                    @{{ errors_settings.footer[0] }}
                  </span>
              </div>

              <div class="form-group col-md-4">
                  <label>{{ __('translate.Default_Language') }} <span class="field_required">*</span></label>
                  <v-select placeholder="{{ __('translate.Default_Language') }}"
                      v-model="setting.default_language" :reduce="(option) => option.value" :options="
                              [
                                {label: 'English', value: 'en'},
                                {label: 'French', value: 'fr'},
                                {label: 'Arabic', value: 'ar'},
                              ]">
                  </v-select>
                  <span class="error" v-if="errors_settings && errors_settings.default_language">
                      @{{ errors_settings.default_language[0] }}
                  </span>
              </div>

                <!-- Customer -->
                <div class="form-group col-md-4">
                    <label>{{ __('translate.Default_Customer') }} </label>
                    <v-select v-model="setting.client_id" :reduce="label => label.value"
                      placeholder="{{ __('translate.Default_Customer') }}"
                      :options="clients.map(clients => ({label: clients.username, value: clients.id}))">
                    </v-select>
                </div>

            

               <!-- Default Warehouse -->
                 <div class="form-group col-md-4">
                    <label for="warehouse_id">{{ __('translate.Default_Warehouse') }} </label>
                    <v-select
                      v-model="setting.warehouse_id"
                      :reduce="label => label.value"
                      placeholder="{{ __('translate.Default_Warehouse') }}"
                      :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
                    >
                  </v-select>
                </div>

                  <!-- Time_Zone -->
                  <div class="form-group col-md-4">
                      <label for="timezone">{{ __('translate.Time_Zone') }} </label>
                       <v-select
                            placeholder="{{ __('translate.Time_Zone') }}"
                            v-model="setting.timezone" :reduce="label => label.value"
                            :options="zones_array.map(zones_array => ({label: zones_array.label, value: zones_array.zone}))">
                        </v-select>
                  </div>

                <div class="form-group col-md-4">
                    <label>{{ __('translate.Symbol_Placement') }} <span class="field_required">*</span></label>
                    <v-select placeholder="{{ __('translate.Symbol_Placement') }}"
                        v-model="setting.symbol_placement" :reduce="(option) => option.value" :options="
                                [
                                  {label: 'After (0.00 $)', value: 'after'},
                                  {label: 'Before ($ 0.00)', value: 'before'},
                                ]">
                    </v-select>
                    <span class="error" v-if="errors_settings && errors_settings.symbol_placement">
                        @{{ errors_settings.symbol_placement[0] }}
                    </span>
                </div>

              <div class="form-group col-md-12">
                <label for="CompanyAdress">{{ __('translate.Company_Adress') }}
                  <span class="field_required">*</span></label>
                <textarea type="text" v-model="setting.CompanyAdress" class="form-control" name="CompanyAdress"
                  id="CompanyAdress" placeholder="{{ __('translate.Enter_Company_Adress') }}"></textarea>
                <span class="error" v-if="errors_settings && errors_settings.CompanyAdress">
                  @{{ errors_settings.CompanyAdress[0] }}
                </span>
              </div>

            </div>

            <div class="row mt-3">

              <div class="col-md-6">
                <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                  <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- SMTP Configuration --}}
  <div class="row mt-5">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4>{{ __('translate.Email_Settings') }}</h4>
        </div>
        <!--begin::form-->
        <div class="card-body">
          <form @submit.prevent="Update_Email_Settings()" enctype="multipart/form-data">
            <div class="row">

              <div class="form-group col-md-4">
                <label for="mailer">{{ __('translate.MAIL_MAILER') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.mailer" class="form-control" id="mailer" id="mailer"
                  placeholder="{{ __('translate.MAIL_MAILER') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.mailer">
                  @{{ errors_email_setting.mailer[0] }}
                </span>
                <p class="text-danger">Supported: "smtp", "sendmail", "mailgun", "ses","postmark", "log"</p>
              </div>

              <div class="form-group col-md-4">
                <label for="host">{{ __('translate.MAIL_HOST') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.host" class="form-control" id="host" id="host"
                  placeholder="{{ __('translate.MAIL_HOST') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.host">
                  @{{ errors_email_setting.host[0] }}
                </span>
              </div>


              <div class="form-group col-md-4">
                <label for="from_name">{{ __('translate.MAIL_FROM_NAME') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.from_name" class="form-control" id="from_name" id="from_name"
                  placeholder="{{ __('translate.MAIL_FROM_NAME') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.from_name">
                  @{{ errors_email_setting.from_name[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="from_email">{{ __('translate.MAIL_FROM_ADDRESS') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.from_email" class="form-control" id="from_email"
                  id="from_email" placeholder="{{ __('translate.MAIL_FROM_ADDRESS') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.from_email">
                  @{{ errors_email_setting.from_email[0] }}
                </span>
              </div>



              <div class="form-group col-md-4">
                <label for="port">{{ __('translate.MAIL_PORT') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.port" class="form-control" id="port" id="port"
                  placeholder="{{ __('translate.MAIL_PORT') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.port">
                  @{{ errors_email_setting.port[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="username">{{ __('translate.MAIL_USERNAME') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.username" class="form-control" id="username" id="username"
                  placeholder="{{ __('translate.MAIL_USERNAME') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.username">
                  @{{ errors_email_setting.username[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="password">{{ __('translate.MAIL_PASSWORD') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.password" class="form-control" id="password" id="password"
                  placeholder="{{ __('translate.MAIL_PASSWORD') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.password">
                  @{{ errors_email_setting.password[0] }}
                </span>
              </div>

              <div class="form-group col-md-4">
                <label for="encryption">{{ __('translate.MAIL_ENCRYPTION') }} <span class="field_required">*</span></label>
                <input type="text" v-model="email_settings.encryption" class="form-control" id="encryption"
                  id="encryption" placeholder="{{ __('translate.Mail_Encryption') }}">
                <span class="error" v-if="errors_email_setting && errors_email_setting.encryption">
                  @{{ errors_email_setting.encryption[0] }}
                </span>
              </div>


            </div>

            <div class="row mt-3">

              <div class="col-md-6">
                <button type="submit" class="btn btn-primary" :disabled="Submit_Processing_Email_Setting">
                  <span v-if="Submit_Processing_Email_Setting" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Backup Settings --}}
  <div class="row mt-5">
      <div class="col-md-12">
  
        <div class="card">
          <div class="card-header">
            <h4>{{ __('translate.Backup_Settings') }}</h4>
          </div>
          <!--begin::form-->
          <div class="card-body">
            <form @submit.prevent="Update_Backup_Settings()" enctype="multipart/form-data">
              <div class="row">
  
                <div class="form-group col-md-6">
                  <label for="dump_path">{{ __('translate.DUMP_PATH') }} <span class="field_required">*</span></label>
                  <input type="text" v-model="backup_settings.dump_path" class="form-control" id="dump_path"
                    placeholder="{{ __('translate.DUMP_PATH') }}">
                  <span class="error" v-if="errors_backup_setting && errors_backup_setting.from_email">
                    @{{ errors_backup_setting.from_email[0] }}
                  </span>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-md-12">
                    <ul>
                      <li><strong>{{ __('translate.Live Server (Hosting)') }} :</strong>  mysqldump </li>
                      <li><strong>{{ __('translate.Xampp') }} :</strong> C:\xampp\mysql\bin\mysqldump.exe </li>
                      <li><strong>{{ __('translate.Laragon') }} :</strong> C:\laragon\bin\mysql\mysql-5.7.24-winx64\bin\mysqldump.exe </li>
                    </ul>
                </div>
              </div>

              <div class="row mt-3">
  
                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary"> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  {{-- Clear_Cache--}}
  <div class="row mt-5">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <h4>{{ __('translate.Clear_Cache') }}</h4>
        </div>
        <!--begin::form-->
        <div class="card-body">
          <form @submit.prevent="Clear_Cache()">
            <div class="row">
              <div class="col-md-6">
              <button type="submit" class="btn btn-primary" :disabled="Submit_Processing_Clear_Cache">
                <span v-if="Submit_Processing_Clear_Cache" class="spinner-border spinner-border-sm" role="status"
                  aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Clear_Cache') }}
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

<script>
  Vue.component('v-select', VueSelect.VueSelect)

        var app = new Vue({
        el: '#section_System_Settings_list',
        data: {
            data: new FormData(),
            SubmitProcessing:false,
            Submit_Processing_Clear_Cache:false,
            Submit_Processing_Email_Setting:false,
            errors_settings:[],
            errors_email_setting:[],
            errors_backup_setting:[],
            currencies: @json($currencies),
            clients: @json($clients),
            warehouses: @json($warehouses),
            zones_array: @json($zones_array),
            setting: @json($setting),
            email_settings: @json($email_settings),
            backup_settings: @json($backup_settings),
        },
       
        methods: {


            changeLogo(e){
                let file = e.target.files[0];
                this.setting.logo = file;
            }, 

         
                //---------------------------------- Clear_Cache ----------------\\
            Clear_Cache() {
                var self = this;
                self.Submit_Processing_Clear_Cache = true;
                axios
                    .get("/clear_cache")
                    .then(response => {
                        self.Submit_Processing_Clear_Cache = false;
                        toastr.success('{{ __('translate.Cache_cleared_successfully') }}');
                    })
                    .catch(error => {
                        self.Submit_Processing_Clear_Cache = false;
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            },   


           //----------------------- Update setting ---------------------------\\
           Update_Settings() {
                var self = this;
                self.SubmitProcessing = true;
                self.data.append("client_id", self.setting.client_id);
                self.data.append("warehouse_id", self.setting.warehouse_id);
                self.data.append("currency_id", self.setting.currency_id);
                self.data.append("email", self.setting.email);
                self.data.append("logo", self.setting.logo);
                self.data.append("CompanyName", self.setting.CompanyName);
                self.data.append("CompanyPhone", self.setting.CompanyPhone);
                self.data.append("CompanyAdress", self.setting.CompanyAdress);
                self.data.append("footer", self.setting.footer);
                self.data.append("developed_by", self.setting.developed_by);
                self.data.append("app_name", self.setting.app_name);
                self.data.append("default_language", self.setting.default_language);
                self.data.append("invoice_footer", self.setting.invoice_footer);
                self.data.append("timezone", self.setting.timezone);
                self.data.append("symbol_placement", self.setting.symbol_placement);
                self.data.append("_method", "put");

                axios
                    .post("/settings/system_settings/" + self.setting.id, self.data)
                    .then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/settings/system_settings'; 
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors_settings = {};
                    })
                    .catch(error => {
                        self.SubmitProcessing = false;
                        if (error.response.status == 422) {
                            self.errors_settings = error.response.data.errors;
                        }
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            },


            //---------------------------------- Update_Email_Settings----------------\\
            Update_Email_Settings() {
                var self = this;
                self.Submit_Processing_Email_Setting = true;
                axios
                    .post("/settings/email_settings", {
                        mailer: self.email_settings.mailer,
                        from_name: self.email_settings.from_name,
                        from_email: self.email_settings.from_email,
                        host: self.email_settings.host,
                        port: self.email_settings.port,
                        username: self.email_settings.username,
                        password: self.email_settings.password,
                        encryption: self.email_settings.encryption
                    })
                    .then(response => {
                        self.Submit_Processing_Email_Setting = false;
                        window.location.href = '/settings/system_settings'; 
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors_email_setting = {};
                    })
                    .catch(error => {
                        self.Submit_Processing_Email_Setting = false;
                        if (error.response.status == 422) {
                            self.errors_email_setting = error.response.data.errors;
                        }
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            },


             //---------------------------------- Update_Backup_Settings----------------\\
             Update_Backup_Settings() {
                var self = this;
                axios
                    .post("/settings/update_backup_settings", {
                      dump_path: self.backup_settings.dump_path,
                    })
                    .then(response => {
                        window.location.href = '/settings/system_settings'; 
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors_backup_setting = {};
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            self.errors_backup_setting = error.response.data.errors;
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