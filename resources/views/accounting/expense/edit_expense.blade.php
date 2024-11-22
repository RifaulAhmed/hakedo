@extends('layouts.master')
@section('main-content')
@section('page-css')
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Edit_Expense') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="main-content" id="section_Edit_Expense">
  <section class="section">
   

    <div class="section-body">
      <div class="row">
        <div class="col-lg-12 mb-3">
          <div class="card">

            <!--begin::form-->
            <form @submit.prevent="Edit_Expense()">
              <div class="card-body">
                <div class="row">
                  <div class="form-group col-md-4">
                    <label >{{ __('translate.Account') }} <span
                        class="field_required">*</span></label>
                    <v-select @input="Selected_Account" placeholder="{{ __('translate.Choose_Account') }}"
                      v-model="expense.account_id" :reduce="label => label.value"
                      :options="accounts.map(accounts => ({label: accounts.account_name, value: accounts.id}))">
                    </v-select>

                    <span class="error" v-if="errors && errors.account_id">
                      @{{ errors.account_id[0] }}
                    </span>
                  </div>

                  <div class="form-group col-md-4">
                    <label >{{ __('translate.Category') }} <span
                        class="field_required">*</span></label>
                    <v-select @input="Selected_Category" placeholder="{{ __('translate.Choose_Category') }}"
                      v-model="expense.expense_category_id" :reduce="label => label.value"
                      :options="categories.map(categories => ({label: categories.title, value: categories.id}))">
                    </v-select>

                    <span class="error" v-if="errors && errors.expense_category_id">
                      @{{ errors.expense_category_id[0] }}
                    </span>
                  </div>

                  <div class="form-group col-md-4">
                    <label for="expense_ref" >{{ __('translate.Expense_Ref') }} <span
                        class="field_required">*</span></label>
                    <input type="text" class="form-control" id="expense_ref"
                      placeholder="{{ __('translate.Enter_Expense_Ref') }}" v-model="expense.expense_ref">
                    <span class="error" v-if="errors && errors.expense_ref">
                      @{{ errors.expense_ref[0] }}
                    </span>
                  </div>


                  <div class="form-group col-md-4">
                    <label for="date" >{{ __('translate.Date') }} <span
                        class="field_required">*</span></label>
                    <vuejs-datepicker id="expense_date" placeholder="{{ __('translate.Enter_expense_date') }}"
                      v-model="expense.date" input-class="form-control back_important" name="expense_date" format="yyyy-MM-dd"
                      @closed="expense.date=formatDate(expense.date)">
                    </vuejs-datepicker>
                    <span class="error" v-if="errors && errors.date">
                      @{{ errors.date[0] }}
                    </span>
                  </div>


                  <div class="form-group col-md-4">
                    <label for="amount" >{{ __('translate.Amount') }} <span
                        class="field_required">*</span></label>
                    <input type="text" v-model="expense.amount" class="form-control" name="amount"
                      placeholder="{{ __('translate.Enter_Amount') }}" id="amount">
                    <span class="error" v-if="errors && errors.amount">
                      @{{ errors.amount[0] }}
                    </span>
                  </div>

                  <div class="form-group col-md-4">
                    <label >{{ __('translate.Payment_method') }} <span
                        class="field_required">*</span></label>
                    <v-select @input="Selected_Payment_Method" placeholder="{{ __('translate.Choose_Payment_method') }}"
                      v-model="expense.payment_method_id" :reduce="label => label.value"
                      :options="payment_methods.map(payment_methods => ({label: payment_methods.title, value: payment_methods.id}))">
                    </v-select>

                    <span class="error" v-if="errors && errors.payment_method_id">
                      @{{ errors.payment_method_id[0] }}
                    </span>
                  </div>

                  <div class="form-group col-md-4">
                    <label for="attachment" >{{ __('translate.Attachment') }}</label>
                    <input name="attachment" @change="changeAttachement" type="file" class="form-control"
                      id="attachment">
                    <span class="error" v-if="errors && errors.attachment">
                      @{{ errors.attachment[0] }}
                    </span>
                  </div>

                  <div class="form-group col-md-12">
                    <label for="description"
                      >{{ __('translate.Please_provide_any_details') }}</label>
                    <textarea type="text" v-model="expense.description" class="form-control" name="description"
                      id="description" placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
                  </div>

                </div>
                <div class="row mt-3">
                  <div class="col-lg-6">
                      <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                          <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm"
                              role="status" aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
                          {{ __('translate.Submit') }}
                      </button>
                  </div>
                </div>
            </form>

            <!-- end::form -->
          </div>
        </div>

      </div>
    </div>
  </section>
</div>
@endsection

@section('page-js')
<script src="{{asset('assets/js/vendor/vuejs-datepicker/vuejs-datepicker.min.js')}}"></script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)

    var app = new Vue({
    el: '#section_Edit_Expense',
    components: {
        vuejsDatepicker
    },
    data: {
        SubmitProcessing:false,
        errors:[],
        data: new FormData(),
        accounts: @json($accounts),
        categories: @json($categories),
        payment_methods: @json($payment_methods),
        expense: @json($expense),
    },
   
   
    methods: {

    
        formatDate(d){
            var m1 = d.getMonth()+1;
            var m2 = m1 < 10 ? '0' + m1 : m1;
            var d1 = d.getDate();
            var d2 = d1 < 10 ? '0' + d1 : d1;
            return [d.getFullYear(), m2, d2].join('-');
        },

        Selected_Account(value) {
            if (value === null) {
                this.expense.account_id = "";
            }
        },

        Selected_Category(value) {
            if (value === null) {
                this.expense.expense_category_id = "";
            }
        },


        Selected_Payment_Method(value) {
            if (value === null) {
                this.expense.payment_method_id = "";
            }
        },



        changeAttachement (e){
                let file = e.target.files[0];
                this.expense.attachment = file;
            },

        //------------------------ Edit Expense ---------------------------\\
        Edit_Expense() {
            var self = this;
            self.SubmitProcessing = true;

            self.data.append("account_id", self.expense.account_id);
            self.data.append("expense_category_id", self.expense.expense_category_id);
            self.data.append("amount", self.expense.amount);
            self.data.append("payment_method_id", self.expense.payment_method_id);
            self.data.append("date", self.expense.date);
            self.data.append("expense_ref", self.expense.expense_ref);
            self.data.append("description", self.expense.description);
            self.data.append("attachment", self.expense.attachment);
            self.data.append("_method", "put");

            axios.post("/accounting/expense/" + self.expense.id, self.data)
                .then(response => {
                    self.SubmitProcessing = false;
                    window.location.href = '/accounting/expense'; 
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

    },
    //-----------------------------Autoload function-------------------
    created () {
        
    },

})

</script>

@endsection