@extends('layouts.master')
@section('main-content')
@section('page-css')

<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/autocomplete.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/barcode.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Print_Labels') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<!-- begin::main-row -->
<div class="row" id="section_barcode">
  <div class="col-lg-12 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="row">
          
          <!-- warehouse -->
          <div class="form-group col-md-6">
              <validation-observer ref="show_Barcode">
                    <validation-provider name="warehouse" rules="required"  v-slot="{ valid, errors }">
                      <label class="ul-form__label">{{ __('translate.warehouse') }} <span class="field_required">*</span></label>
                      <v-select 
                        @input="Selected_Warehouse" 
                        placeholder="{{ __('translate.Choose_Warehouse') }}"
                        v-model="barcode.warehouse_id" 
                        :reduce="(option) => option.value"
                        :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))">
                      </v-select>
                      <span class="error">@{{ errors[0] }}</span>
                    </validation-provider>
              </validation-observer>
                </div>

                <!-- Product -->
                <div class="col-md-12 mb-5 mt-3">
                  <h6>{{ __('translate.Products') }}</h6>

                  <div id="autocomplete" class="autocomplete">
                    <input placeholder="{{ __('translate.Scan_Search_Product_by_Code_Name') }}" @input='e => search_input = e.target.value'
                      @keyup="search(search_input)" @focus="handleFocus" @blur="handleBlur" ref="product_autocomplete"
                      class="autocomplete-input" />
                    <ul class="autocomplete-result-list" v-show="focused">
                      <li class="autocomplete-result" v-for="product_fil in product_filter"
                        @mousedown="SearchProduct(product_fil)">@{{getResultValue(product_fil)}}</li>
                    </ul>
                  </div>
                </div>

                <!-- Products -->
                <div class="col-md-12">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="bg-gray-300">
                        <tr>
                          <th scope="col">{{ __('translate.Product_Name') }}</th>
                          <th scope="col">{{ __('translate.Product_Code') }}</th>
                          <th scope="col">{{ __('translate.Product_Price') }}</th>
                          <th scope="col">{{ __('translate.Quantity') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="product.code === ''">
                          <td colspan="3">{{ __('translate.No_data_Available') }}</td>
                        </tr>
                        <tr v-else>
                          <td>@{{product.name}}</td>
                          <td>@{{product.code}}</td>
                          <td>{{$currency}} @{{formatNumber(product.price, 2)}}</td>
                          <td>
                            <input
                              v-model.number="barcode.qte"
                              class="form-control w-50 h-25"
                              id="qte"
                              type="number"
                              name="qte"
                            >
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>


                <div class="form-group col-md-6">
                    <validation-provider name="Paper_size" rules="required"  v-slot="{ valid, errors }">
                      <label class="ul-form__label">{{ __('translate.Paper_size') }} <span class="field_required">*</span></label>
                      <v-select 
                          placeholder="{{ __('translate.Choose_Paper_size') }}" 
                          @input="Selected_Paper_size"
                          v-model="paper_size" 
                          :reduce="(option) => option.value" 
                          :options="
                            [
                              {label: '40 per sheet (a4) (1.799 * 1.003)', value: 'style40'},
                              {label: '30 per sheet (2.625 * 1)', value: 'style30'},
                              {label: '24 per sheet (a4) (2.48 * 1.334)', value: 'style24'},
                              {label: '20 per sheet (4 * 1)', value: 'style20'},
                              {label: '18 per sheet (a4) (2.5 * 1.835)', value: 'style18'},
                              {label: '14 per sheet (4 * 1.33)', value: 'style14'},
                              {label: '12 per sheet (a4) (2.5 * 2.834)', value: 'style12'},
                              {label: '10 per sheet (4 * 2)', value: 'style10'},
                            ]">
                      </v-select>
                      <span class="error">@{{ errors[0] }}</span>
                    </validation-provider>
                </div>
              </div>


                <div class="row mt-3">
                  <div class="col-md-12">
                    <button @click="submit()" type="submit" class="btn btn-primary m-1">
                      <i class="i-Edit"></i>
                      {{ __('translate.Refresh') }}
                    </button>
                    <button @click="reset()" type="submit" class="btn btn-danger  m-1">
                      <i class="i-Power-2"></i>
                      {{ __('translate.Reset') }}
                    </button>
                    <button
                      @click="print_all_Barcode()"
                      value="Print"
                      type="submit"
                      class="btn btn-success  pull-right m-1"
                    >
                      <i class="i-Billing"></i>
                      {{ __('translate.Print_Labels') }}
                    </button>
                  </div>

                  <div class="col-md-12">
                      <div class="barcode-row" v-if="ShowCard" id="print_barcode_label">
                        <div :class="class_type_page" v-for ="(k, i) in total_a4" :key="i">
                          <div class="barcode-item" :class="class_sheet"  v-for="(sheet, index) in sheets" :key="index" >
                              <span class="barcode-name">@{{product.name}}</span>
                              <span class="barcode-name">{{$currency}} @{{formatNumber(product.price, 2)}}</span>
                              <svg id="barcode" class="barcode"></svg>
                          </div>
                        </div>
                        <div :class="class_type_page"  v-if="rest > 0">
                          <div class="barcode-item" :class="class_sheet"  v-for="(sheet, index) in rest" :key="index" >
                              <span  class="barcode-name">@{{product.name}}</span>
                              <span class="barcode-name">{{$currency}} @{{formatNumber(product.price, 2)}}</span>
                              <svg id="barcode" class="barcode"></svg>
                          </div>
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
<script src="{{asset('assets/js/autocomplete.js')}}"></script>
<script src="{{asset('assets/js/barcode.js')}}"></script>


<script>
  Vue.component('v-select', VueSelect.VueSelect)
  Vue.component('validation-provider', VeeValidate.ValidationProvider);
  Vue.component('validation-observer', VeeValidate.ValidationObserver);

    var app = new Vue({
        el: '#section_barcode',
       
        data: {
          focused: false,
          timer:null,
          search_input:'',
          product_filter:[],
          isLoading: true,
          ShowCard: false,
          barcode: {
            product_id: "",
            warehouse_id: "",
            qte: 10
          },
          count: "",
          paper_size:"",
          sheets:'',
          total_a4:'',
          class_sheet:'',
          class_type_page:'',
          rest:'',     
          warehouses: @json($warehouses),
          submitStatus: null,
          products: [],
          product: {
            name: "",
            code: "",
            price: "",
            Type_barcode: "",
            barcode:"",
          }
        },

       
       
    methods: {

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


    Per_Page(){
        this.total_a4 = parseInt(this.barcode.qte/this.sheets);
        this.rest = this.barcode.qte%this.sheets;
    },
 //---------------------- Event Selected_Paper_size------------------------------\\
    Selected_Paper_size(value) {
      if(value == 'style40'){
        this.sheets = 40;
        this.class_sheet = 'style40';
        this.class_type_page = 'barcodea4';
      }else if(value == 'style30'){
        this.sheets = 30;
        this.class_type_page = 'barcode_non_a4';
        this.class_sheet = 'style30';
      }else if(value == 'style24'){
        this.sheets = 24;
        this.class_sheet = 'style24';
       this.class_type_page = 'barcodea4';
      }else if(value == 'style20'){
        this.sheets = 20;
        this.class_sheet = 'style20';
        this.class_type_page = 'barcode_non_a4';
      }else if(value == 'style18'){
        this.sheets =  18;
        this.class_sheet = 'style18';
        this.class_type_page = 'barcodea4';
      }else if(value == 'style14'){
        this.sheets = 14;
        this.class_sheet = 'style14';
        this.class_type_page = 'barcode_non_a4';
      }else if(value == 'style12'){
        this.sheets = 12;
        this.class_sheet = 'style12';
       this.class_type_page = 'barcodea4';
      }else if(value == 'style10'){
        this.sheets = 10;
        this.class_sheet = 'style10';
       this.class_type_page = 'barcode_non_a4';
      }

      setTimeout(() => {
        JsBarcode("#barcode", this.product.barcode, {
          format: this.product.Type_barcode,
          width:1,
          height:25,
          textMargin:0,
          fontSize:15,
        });
      }, 1000);
    
     
      this.Per_Page();
    },
    //------ Validate Form
    submit() {
      this.$refs.show_Barcode.validate().then(success => {
        if (!success) {
          toastr.error('{{ __('translate.Please_fill_the_form_correctly') }}');
        } else {
         
          this.showBarcode();
        }
      });
    },
    //---Validate State Fields
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },
      handleFocus() {
      this.focused = true
    },
    handleBlur() {
      this.focused = false
    },
    
   // Search Products
    search(){
      if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
      }
      if (this.search_input.length < 1) {
        return this.product_filter= [];
      }
      if (this.barcode.warehouse_id != "" &&  this.barcode.warehouse_id != null) {
          this.timer = setTimeout(() => {
          const product_filter = this.products.filter(product => product.code === this.search_input || product.barcode.includes(this.search_input));
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
    //------ Search Result value
    getResultValue(result) {
      return result.code + " " + "(" + result.name + ")";
    },
    //------ Submit Search Product
    SearchProduct(result) {
      this.product = {};
      if (this.product.code === result.code) {
        toastr.error('{{ __('translate.Product_Already_added') }}');
      } else {
        this.product.code = result.code;
        this.product.barcode = result.barcode;
        this.product.name = result.name;
        this.product.price = result.Net_price;
        this.product.Type_barcode = result.Type_barcode;
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
        .get("/products/products_by_Warehouse/" + id + "?stock=" + 0)
         .then(response => {
            this.products = response.data;
             NProgress.done();
            })
          .catch(error => {
          });
    },
    //-------------------------------------- Print Barcode -------------------------\\
    print_all_Barcode() {
      var divContents = document.getElementById("print_barcode_label").innerHTML;
      var a = window.open("", "", "height=500, width=500");
      a.document.write(
        '<link rel="stylesheet" href="/assets/styles/vendor/print_label.css"><html>'
      );
      a.document.write("<body >");
      a.document.write(divContents);
      a.document.write("</body></html>");
      a.document.close();
      setTimeout(() => {
         a.print();
      }, 1000);
      
    },
   
    //-------------------------------------- Show Barcode -------------------------\\
    showBarcode() {
      
      this.Per_Page();
      this.count = this.barcode.qte;
      setTimeout(() => {
        JsBarcode("#barcode", this.product.barcode, {
          format: this.product.Type_barcode,
          width:1,
          height:25,
          textMargin:0,
          fontSize:15,
        });
      }, 1000);

      this.ShowCard = true;
    },
    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      this.search_input= '';
      this.product_filter = [];
      this.Get_Products_By_Warehouse(value);
    },
  
    //----------------------------------- Reset Data -------------------------\\
    reset() {
      this.ShowCard = false;
      this.products = [];
      this.product.name = "";
      this.product.code = "";
      this.barcode.qte = 10;
      this.count = 10;
      this.barcode.warehouse_id = "";
      this.search_input= '';
      this.$refs.product_autocomplete.value = "";
      this.product_filter = [];
    }
          
      },
      
      //-----------------------------Autoload function-------------------
      created() {
      }

  })

</script>

<script>
    JsBarcode("#barcode").init();
</script>

@endsection