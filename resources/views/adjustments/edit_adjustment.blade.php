@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/autocomplete.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/flatpickr.min.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Edit_Adjustment') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_edit_adjustment">
  <div class="col-lg-12 mb-3">
    <validation-observer ref="Edit_adjustment">
      <form @submit.prevent="Submit_Adjustment">

        <div class="card">
          <div class="card-body">
            <div class="row">

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
                      v-model="adjustment.date">

                    <span class="error">@{{  validationContext.errors[0] }}</span>
                  </div>
                </validation-provider>
              </div>

              <!-- warehouse -->
              <div class="form-group col-md-6">
                <validation-provider name="warehouse" rules="required" v-slot="{ valid, errors }">
                  <label>{{ __('translate.warehouse') }} <span class="field_required">*</span></label>
                  <v-select @input="Selected_Warehouse" :disabled="details.length > 0"
                    placeholder="{{ __('translate.Choose_Warehouse') }}" v-model="adjustment.warehouse_id"
                    :reduce="(option) => option.value"
                    :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))">
                  </v-select>
                  <span class="error">@{{ errors[0] }}</span>
                </validation-provider>
              </div>
            </div>
          </div>
        </div>

        <div class="card mt-5">
          <div class="card-body">
            <div class="row">

              <!-- Product -->
              <div class="col-md-12 mb-5 mt-3">
                <div id="autocomplete" class="autocomplete">
                    <input placeholder="{{ __('translate.Scan_Search_Product_by_Code_Name') }}"
                      @input='e => search_input = e.target.value' @keyup="search(search_input)" @focus="handleFocus"
                      @blur="handleBlur" ref="product_autocomplete" class="autocomplete-input" />
                    <ul class="autocomplete-result-list" v-show="focused">
                      <li class="autocomplete-result" v-for="product_fil in product_filter"
                        @mousedown="SearchProduct(product_fil)">@{{getResultValue(product_fil)}}</li>
                    </ul>
                </div>
              </div>


              <!-- Products -->
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-hover table-md">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('translate.Code_Product') }}</th>
                        <th scope="col">{{ __('translate.Product_Name') }}</th>
                        <th scope="col">{{ __('translate.Current_Stock') }}</th>
                        <th scope="col">{{ __('translate.Qty') }}</th>
                        <th scope="col">{{ __('translate.type') }}</th>
                        <th scope="col" class="text-center">
                          <i class="fa fa-trash"></i>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="details.length <=0">
                        <td colspan="7">{{ __('translate.No_data_Available') }}</td>
                      </tr>
                      <tr v-for="detail in details" :key="detail.detail_id">
                        <td>@{{detail.detail_id}}</td>
                        <td>@{{detail.code}}</td>
                        <td>(@{{detail.name}})</td>
                        <td>
                          <span class="badge badge-warning">@{{detail.current}}
                            @{{detail.unit}}</span>
                        </td>

                        <td>
                          <div class="d-flex align-items-center">
                            <span class="increment-decrement btn btn-light rounded-circle"
                              @click="decrement(detail ,detail.detail_id)">-</span>
                            <input class="fw-semibold cart-qty m-0 px-2"
                              @keyup="Verified_Qty(detail,detail.detail_id)" :min="0.00" :max="detail.current"
                              v-model.number="detail.quantity" :disabled="detail.del === 1">
  
                            <span class="increment-decrement btn btn-light rounded-circle"
                              @click="increment(detail ,detail.detail_id)">+</span>
                          </div>
                        </td>

                        <td>
                          <select v-model="detail.type" @change="Verified_Qty(detail,detail.detail_id)" type="text"
                            required class="form-control">
                            <option value="add">{{ __('translate.Addition') }}</option>
                            <option value="sub">{{ __('translate.Subtraction') }}</option>
                          </select>
                        </td>
                        <td>
                          <a @click="Remove_Product(detail.detail_id)" class="btn btn-danger btn-sm" title="Delete">
                            <i class="i-Close-Window"></i>
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card mt-5">
          <div class="card-body">
            <div class="row">

              <div class="form-group col-md-12">
                <label for="note">{{ __('translate.Please_provide_any_details') }} </label>
                <textarea type="text" v-model="adjustment.notes" class="form-control" name="note" id="note"
                  placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
              </div>

            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-lg-6">
            <button @click="Submit_Adjustment" class="btn btn-primary" :disabled="SubmitProcessing">
              <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
            </button>
          </div>
        </div>
      </form>
    </validation-observer>
  </div>
</div>

@endsection

@section('page-js')
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/flatpickr.min.js')}}"></script>
<script src="{{asset('assets/js/autocomplete.js')}}"></script>


<script type="text/javascript">
  $(function () {
      "use strict";

      $(document).ready(function () {

        flatpickr("#datetimepicker", {
          enableTime: true,
          dateFormat: "Y-m-d H:i"
        });

      });

    });
</script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)
    Vue.component('validation-provider', VeeValidate.ValidationProvider);
    Vue.component('validation-observer', VeeValidate.ValidationObserver);

    var app = new Vue({
        el: '#section_edit_adjustment',
        data: {
            focused: false,
            timer:null,
            search_input:'',
            product_filter:[],
            isLoading: true,
            SubmitProcessing:false,
            warehouses: @json($warehouses),
            products: @json($products),
            details: @json($details),
            errors:[],
            adjustment: @json($adjustment),
            product: {
                id: "",
                code: "",
                current: "",
                quantity: 1,
                name: "",
                product_id: "",
                detail_id: "",
                product_variant_id: "",
                unit: ""
            },
        },

       
       
    methods: {

      formatDate(d){
        var m1 = d.getMonth()+1;
        var m2 = m1 < 10 ? '0' + m1 : m1;
        var d1 = d.getDate();
        var d2 = d1 < 10 ? '0' + d1 : d1;
        return [d.getFullYear(), m2, d2].join('-');
    },

      handleFocus() {
      this.focused = true
    },
    handleBlur() {
      this.focused = false
    },

     //------------- Submit Validation Update Adjustment
     Submit_Adjustment() {
      this.$refs.Edit_adjustment.validate().then(success => {
        if (!success) {
          toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
        } else {
          this.Update_Adjustment();
        }
      });
    },

   //------------- Event Get Validation state
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },
    
    // Search Products
    search(){
      if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
      }
      if (this.search_input.length < 2) {
        return this.product_filter= [];
      }
      if (this.adjustment.warehouse_id != "" &&  this.adjustment.warehouse_id != null) {
        this.timer = setTimeout(() => {
          const product_filter = this.products.filter(product => product.code === this.search_input);
            if(product_filter.length === 1){
                this.SearchProduct(product_filter[0])
            }else{
                this.product_filter=  this.products.filter(product => {
                  return (
                    product.name.toLowerCase().includes(this.search_input.toLowerCase()) ||
                    product.code.toLowerCase().includes(this.search_input.toLowerCase()) ||
                    product.barcode.toLowerCase().includes(this.search_input.toLowerCase())
                    );
                });
            }
        }, 800);
      } else {
        toastr.error('{{ __('translate.Please_Select_Warehouse') }}');
      }
    },
    //---------------------- Event Get Value Search ------------------------------\\
    getResultValue(result) {
      return result.code + " " + "(" + result.name + ")";
    },
    //---------------- Submit Search Product-----------------\\
    SearchProduct(result) {
      this.product = {};
      if (
        this.details.length > 0 &&
        this.details.some(detail => detail.code === result.code)
      ) {
        toastr.error('{{ __('translate.Product_Already_added') }}');
      } else {
        this.product.code = result.code;
        this.product.current = result.qte;
        if (result.qte < 1) {
          this.product.quantity = result.qte;
        } else {
          this.product.quantity = 1;
        }
        this.product.product_variant_id = result.product_variant_id;
        this.Get_Product_Details(result.id, result.product_variant_id);
      }
      this.search_input= '';
      this.$refs.product_autocomplete.value = "";
      this.product_filter = [];
    },
  
  
     //------------------------------------ Get Products By Warehouse -------------------------\\
    Get_Products_By_Warehouse(id) {
      // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
      axios
        .get("/products/products_by_Warehouse/" + id + "?stock=" + 0 + "&product_service=" + 0)
         .then(response => {
            this.products = response.data;
             NProgress.done();
            })
          .catch(error => {
          });
    },
    //----------------------------------------- Add Product To list -------------------------\\
    add_product() {
      if (this.details.length > 0) {
        this.detail_order_id();
      } else if (this.details.length === 0) {
        this.product.detail_id = 1;
      }
      this.details.push(this.product);
    },
    //-----------------------------------Verified QTY ------------------------------\\
    Verified_Qty(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === id) {
          if (isNaN(detail.quantity)) {
            this.details[i].quantity = detail.current;
          }
          if (detail.type == "sub" && detail.quantity > detail.current) {
            toastr.error('{{ __('translate.Low_Stock') }}');
            this.details[i].quantity = detail.current;
          } else {
            this.details[i].quantity = detail.quantity;
          }
        }
      }
      this.$forceUpdate();
    },
    //----------------------------------- Increment QTY ------------------------------\\
    increment(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.type == "sub") {
            if (detail.quantity + 1 > detail.current) {
              toastr.error('{{ __('translate.Low_Stock') }}');
            } else {
              this.details[i].quantity = Number((this.details[i].quantity + 1).toFixed(2));
            }
          } else {
            this.details[i].quantity = Number((this.details[i].quantity + 1).toFixed(2));
          }
        }
      }
      this.$forceUpdate();
    },
    //----------------------------------- Decrement QTY ------------------------------\\
    decrement(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity - 1 > 0) {
            if (detail.type == "sub" && detail.quantity - 1 > detail.current) {
              toastr.error('{{ __('translate.Low_Stock') }}');
            } else {
              this.details[i].quantity = Number((this.details[i].quantity - 1).toFixed(2));
            }
          }
        }
      }
      this.$forceUpdate();
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
    //-----------------------------------Remove the product from the order list ------------------------------\\
    Remove_Product(id) {
      for (var i = 0; i < this.details.length; i++) {
        if (id === this.details[i].detail_id) {
          this.details.splice(i, 1);
        }
      }
    },
    //----------------------------------- Verified Quantity if Null Or zero ------------------------------\\
    verifiedForm() {
      if (this.details.length <= 0) {
        toastr.error('{{ __('translate.Please_Add_Product_To_List') }}');
        return false;
      } else {
        var count = 0;
        for (var i = 0; i < this.details.length; i++) {
          if (
            this.details[i].quantity == "" ||
            this.details[i].quantity === 0
          ) {
            count += 1;
          }
        }
        if (count > 0) {
          toastr.error('{{ __('translate.Please_Add_Quantity') }}');
          return false;
        } else {
          return true;
        }
      }
    },
    //--------------------------------- Update Adjustment -------------------------\\
    Update_Adjustment() {
      if (this.verifiedForm()) {
        this.SubmitProcessing = true;
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
        axios
          .put('/adjustment/adjustments/'+ this.adjustment.id, {
            warehouse_id: this.adjustment.warehouse_id,
            date: this.adjustment.date,
            notes: this.adjustment.notes,
            details: this.details
          })
          .then(response => {
            // Complete the animation of theprogress bar.
            NProgress.done();
            this.SubmitProcessing = false;
            window.location.href = '/adjustment/adjustments';
            toastr.success('{{ __('translate.Updated_in_successfully') }}');
          })
          .catch(error => {
              // Complete the animation of theprogress bar.
              NProgress.done();
              self.SubmitProcessing = false;
              if (error.response.status == 422) {
                  self.errors = error.response.data.errors;
              }
              toastr.error('{{ __('translate.There_was_something_wronge') }}');
          });
      }
    },
    //-------------------------------- detail order id -------------------------\\
    detail_order_id() {
      this.product.detail_id = 0;
      var len = this.details.length;
      this.product.detail_id = this.details[len - 1].detail_id + 1;
    },
    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      this.search_input= '';
      this.product_filter = [];
      this.Get_Products_By_Warehouse(value);
    },
    //---------------------------------Get Product Details ------------------------\\
    Get_Product_Details(product_id , variant_id) {
      axios.get("/products/show_product_data/" + product_id +"/"+ variant_id).then(response => {
        this.product.del = 0;
        this.product.id = 0;
        this.product.product_id = response.data.id;
        this.product.name = response.data.name;
        this.product.type = "add";
        this.product.unit = response.data.unit;
        this.add_product();
      });
    },
  
   
          
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>

@endsection