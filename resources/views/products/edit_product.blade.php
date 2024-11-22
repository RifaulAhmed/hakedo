@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Edit_Product') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_edit_product">
    <div class="col-lg-12 mb-3">

         <!--begin::form-->
         <form @submit.prevent="Update_Product()">
            <div class="card">
                <div class="card-body">
                    <div class="row">

                        <div class="form-group col-md-4">
                            <label for="name">{{ __('translate.Product_Name') }} <span
                                    class="field_required">*</span></label>
                            <input type="text" class="form-control" id="name"
                                placeholder="{{ __('translate.Enter_Name_Product') }}" v-model="product.name">
                            <span class="error" v-if="errors && errors.name">
                                @{{ errors.name[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="code">{{ __('translate.Product_Code') }} <span class="field_required">*</span></label>

                            <div class="input-group">
                                <div class="input-group mb-3">
                                    <input v-model.number="product.code" type="text" class="form-control" placeholder="generate the barcode" aria-label="generate the barcode" aria-describedby="basic-addon2">
                                    <span class="input-group-text cursor-pointer" id="basic-addon2" @click="generateNumber()"><i class="i-Bar-Code"></i></span>
                                </div>
                            </div>
                            <span class="error" v-if="errors && errors.code">
                                @{{ errors.code[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('translate.Category') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Category') }}" v-model="product.category_id"
                                :reduce="(option) => option.value"
                                :options="categories.map(categories => ({label: categories.name, value: categories.id}))">
                            </v-select>

                            <span class="error" v-if="errors && errors.category_id">
                                @{{ errors.category_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('translate.Brand') }} </label>
                            <v-select placeholder="{{ __('translate.Choose_Brand') }}" v-model="product.brand_id"
                                :reduce="(option) => option.value"
                                :options="brands.map(brands => ({label: brands.name, value: brands.id}))">
                            </v-select>
                        </div>

                       

                        <div class="form-group col-md-4">
                            <label for="stock_alert">{{ __('translate.Order_Tax') }} </label>

                            <div class="input-group mb-3">
                                <input v-model.number="product.TaxNet" type="text" class="form-control" aria-describedby="basic-addon3">
                                <span class="input-group-text cursor-pointer" id="basic-addon3">%</span>
                            </div>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('translate.Tax_Method') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Method') }}" v-model="product.tax_method"
                                :reduce="(option) => option.value" :options="
                                              [
                                              {label: 'Exclusive', value: '1'},
                                              {label: 'Inclusive', value: '2'}
                                              ]">
                            </v-select>

                            <span class="error" v-if="errors && errors.tax_method">
                                @{{ errors.tax_method[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="image">{{ __('translate.Image') }} </label>
                            <input name="image" @change="onFileSelected" type="file" class="form-control" id="image">
                            <span class="error" v-if="errors && errors.image">
                                @{{ errors.image[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-12 mb-4">
                            <label for="note">{{ __('translate.Please_provide_any_details') }} </label>
                            <textarea type="text" v-model="product.note" class="form-control" name="note" id="note"
                                placeholder="{{ __('translate.Please_provide_any_details') }}"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-5">
                <div class="card-body">
                    <div class="row">

                    <div class="form-group col-md-4 mb-3" v-if="product.type == 'is_single'">
                            <label for="type" >{{ __('translate.Product_Type') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="type" placeholder="Standard Product"
                                value="Standard Product" disabled>

                            <span class="error" v-if="errors && errors.type">
                                @{{ errors.type[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4 mb-3" v-if="product.type == 'is_variant'">
                            <label for="type" >{{ __('translate.Product_Type') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="type" placeholder="Variable Product"
                                value="Variable Product" disabled>

                            <span class="error" v-if="errors && errors.type">
                                @{{ errors.type[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4 mb-3" v-if="product.type == 'is_service'">
                            <label for="type" >{{ __('translate.Product_Type') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="type" placeholder="Service Product"
                                value="Service Product" disabled>

                            <span class="error" v-if="errors && errors.type">
                                @{{ errors.type[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type == 'is_single'">
                            <label for="cost">{{ __('translate.Product_Cost') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="cost" placeholder="{{ __('translate.Enter_Product_Cost') }}"
                                v-model="product.cost">

                            <span class="error" v-if="errors && errors.cost">
                                @{{ errors.cost[0] }}
                            </span>
                        </div>
    
                        <div class="form-group col-md-4" v-if="product.type == 'is_single' || product.type == 'is_service'">
                            <label for="price">{{ __('translate.Product_Price') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="price" placeholder="{{ __('translate.Enter_Product_Price') }}"
                                v-model="product.price">

                            <span class="error" v-if="errors && errors.price">
                                @{{ errors.price[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label>{{ __('translate.Unit_Product') }} <span class="field_required">*</span></label>
                            <v-select @input="Selected_Unit" placeholder="{{ __('translate.Choose_Unit_Product') }}"
                                v-model="product.unit_id" :reduce="label => label.value"
                                :options="units.map(units => ({label: units.name, value: units.id}))">
                            </v-select>

                            <span class="error" v-if="errors && errors.unit_id">
                                @{{ errors.unit_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label>{{ __('translate.Unit_Sale') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Unit_Sale') }}"
                                v-model="product.unit_sale_id" :reduce="label => label.value"
                                :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))">
                            </v-select>

                            <span class="error" v-if="errors && errors.unit_sale_id">
                                @{{ errors.unit_sale_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label>{{ __('translate.Unit_Purchase') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Unit_Purchase') }}"
                                v-model="product.unit_purchase_id" :reduce="label => label.value"
                                :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))">
                            </v-select>

                            <span class="error" v-if="errors && errors.unit_purchase_id">
                                @{{ errors.unit_purchase_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="qty_min">{{ __('translate.Minimum_sale_quantity') }} </label>
                            <input type="text" class="form-control" id="qty_min"
                                placeholder="{{ __('translate.Enter_Minimum_sale_quantity') }}"
                                v-model="product.qty_min">
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="stock_alert">{{ __('translate.Stock_Alert') }} </label>
                            <input type="text" class="form-control" id="stock_alert"
                                placeholder="{{ __('translate.Enter_Stock_alert') }}" v-model="product.stock_alert">
                        </div>

                        <div class="col-md-9 mb-3 mt-3" v-if="product.type == 'is_variant'">
                                <div class="d-flex">
                                    <input placeholder="Enter the Variant" type="text"
                                        name="variant" v-model="tag" class="form-control">
                                    <a @click="add_variant(tag)" class=" ms-3 btn btn-md btn-primary">
                                        {{ __('translate.Add') }}
                                    </a>
                                </div>
                            </div>
        
                            <div class="col-md-9 mb-2 " v-if="product.type == 'is_variant'">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="bg-gray-300">
                                            <tr>
                                               <th scope="col">{{ __('translate.Variant_code') }}</th>
                                                <th scope="col">{{ __('translate.Variant_Name') }}</th>
                                                <th scope="col">{{ __('translate.Product_Cost') }}</th>
                                                <th scope="col">{{ __('translate.Product_Price') }}</th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="variants.length <=0">
                                                <td colspan="3">{{ __('translate.No_data_Available') }}</td>
                                            </tr>
                                            <tr v-for="variant in variants">
                                               <td>
                                                    <input required class="form-control" v-model="variant.code">
                                                </td>
                                                <td>
                                                    <input required class="form-control" v-model="variant.text">
                                                </td>
                                                <td>
                                                    <input required class="form-control" v-model="variant.cost">
                                                </td>
                                                <td>
                                                    <input required class="form-control" v-model="variant.price">
                                                </td>
                                                <td>
                                                    <a @click="delete_variant(variant.var_id)" class="btn btn-danger"
                                                        title="Delete">
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

                    <!-- Product_Has_Imei_Serial_number -->
                    <div class="col-md-12 mb-2">
                        <div class="form-check form-check-inline">
                            <label class="checkbox checkbox-primary" for="is_imei">
                                <input type="checkbox" id="is_imei" v-model="product.is_imei">
                                <span>{{ __('translate.Product_Has_Imei/Serial_number') }}</span><span class="checkmark"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                        <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                            aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.Submit') }}
                    </button>

                </div>
            </div>
        </form>

        <!-- end::form -->
    </div>

</div>

@endsection

@section('page-js')
<script src="{{asset('assets/js/nprogress.js')}}"></script>
<script src="{{asset('assets/js/bootstrap-tagsinput.js')}}"></script>
<script src="{{asset('assets/js/vendor/vuejs-datepicker/vuejs-datepicker.min.js')}}"></script>

<script type="text/javascript">
    $(function () {
          "use strict";
  
          $(document).on('keyup keypress', 'form input[type="text"]', function(e) {
              if(e.keyCode == 13) {
              e.preventDefault();
              return false;
              }
          });
  
      });
</script>

<script>
    Vue.component('v-select', VueSelect.VueSelect)
  
      var app = new Vue({
          el: '#section_edit_product',
          components: {
            vuejsDatepicker,
          },
          data: {
              len: 8,
              tag:"",
              SubmitProcessing:false,
              data: new FormData(),
              errors:[],
              categories: @json($categories),
              units: @json($units),
              units_sub: @json($units_sub),
              brands: @json($brands),
              product: @json($product),
              variants: @json($product['ProductVariant']),
          },
  
         
          methods: {

            //------ Generate code
            generateNumber() {
                this.code_exist = "";
                this.product.code = Math.floor(
                    Math.pow(10, this.len - 1) +
                    Math.random() *
                        (Math.pow(10, this.len) - Math.pow(10, this.len - 1) - 1)
                );
            },


            Selected_Brand(value) {
                if (value === null) {
                    this.product.brand_id = "";
                }
            },


            formatDate(d){
                var m1 = d.getMonth()+1;
                var m2 = m1 < 10 ? '0' + m1 : m1;
                var d1 = d.getDate();
                var d2 = d1 < 10 ? '0' + d1 : d1;
                return [d.getFullYear(), m2, d2].join('-');
            },
  
              
            add_variant(tag) {
                if (
                    this.variants.length > 0 &&
                    this.variants.some(variant => variant.text === tag)
                ) {

                    toastr.error('Variant Duplicate');

                } else {
                    if(this.tag != ''){
                        var variant_tag = {
                        var_id: this.variants.length + 1, // generate unique ID
                        text: tag
                        };
                        this.variants.push(variant_tag);
                        this.tag = "";
                    }else{

                        toastr.error('Please Enter the Variant');
                        
                    }
                }
            
            },


            //-----------------------------------Delete variant------------------------------\\
            delete_variant(var_id) {
            for (var i = 0; i < this.variants.length; i++) {
                if (var_id === this.variants[i].var_id) {
                this.variants.splice(i, 1);
                }
            }
            },

  
            
              onFileSelected(e){
                  let file = e.target.files[0];
                  this.product.image = file;
              },
  
               //---------------------- Get Sub Units with Unit id ------------------------------\\
              Get_Units_SubBase(value) {
              axios
                  .get("/products/Get_Units_SubBase?id=" + value)
                  .then(({ data }) => (this.units_sub = data));
              },
  
  
              //---------------------- Event Select Unit Product ------------------------------\\
              Selected_Unit(value) {
              this.units_sub = [];
              this.product.unit_sale_id = "";
              this.product.unit_purchase_id = "";
              this.Get_Units_SubBase(value);
              },
  
  
              //------------------------------ Update_Product ------------------------------\\
              Update_Product() {

                if (this.product.type == 'is_variant' && this.variants.length <= 0) {
                    toastr.error('The variants array is required.');
                }else{

                  // Start the progress bar.
                  NProgress.start();
                  NProgress.set(0.1);
                  var self = this;
                  self.SubmitProcessing = true;
                  
                 
                if (self.product.type == 'is_variant' && self.variants.length > 0) {
                    self.product.is_variant = true;
                }else{
                    self.product.is_variant = false;
                }

                // append objet product
                Object.entries(self.product).forEach(([key, value]) => {
                    self.data.append(key, value);
                });
                
                //append array variants
                if (self.variants.length) {
                    for (var i = 0; i < self.variants.length; i++) {
                    Object.entries(self.variants[i]).forEach(([key, value]) => {
                        self.data.append("variants[" + i + "][" + key + "]", value);
                    });
                    }
                }
  
                  self.data.append("_method", "put");
                  
                  // Send Data with axios
                  axios
                      .post("/products/products/" + self.product.id, self.data)
                      .then(response => {
                      // Complete the animation of theprogress bar.
                      NProgress.done();
                      self.SubmitProcessing = false;
                      window.location.href = '/products/products'; 
                      toastr.success('{{ __('translate.Updated_in_successfully') }}');
                      self.errors = {};
                      })
                      .catch(error => {
                        NProgress.done();
                        self.SubmitProcessing = false;

                        
                        if (error.response.status == 422) {
                            self.errors = error.response.data.errors;
                            toastr.error('{{ __('translate.There_was_something_wronge') }}');
                        }

                        if(self.errors.variants && self.errors.variants.length > 0){
                            toastr.error(self.errors.variants[0]);
                        }
                        
                      });
                    }
              }
  
            
          },
          //-----------------------------Autoload function-------------------
          created() {
          }
  
      })
  
</script>

@endsection