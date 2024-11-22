<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Posly - Ultimate Inventory Management System with POS</title>

  <!-- Favicon icon -->
  <link rel=icon href={{ asset('images/logo.svg') }}>
  <!-- Base Styling  -->

  <link rel="stylesheet" href="{{ asset('assets/pos/main/css/fonts.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/pos/main/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/styles/css/themes/lite-purple.min.css') }}">
  <link  rel="stylesheet" href="{{ asset('assets/fonts/iconsmind/iconsmind.css') }}">

  <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,400i,600,700,800,900" rel="stylesheet">

  <link rel="stylesheet" href="{{asset('assets/styles/vendor/bootstrap-vue.min.css')}}">
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/toastr.css')}}">
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/vue-select.css')}}">
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/sweetalert2.min.css')}}">
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/autocomplete.css')}}">

  <script src="{{ asset('assets/js/axios.js') }}"></script>
  <script src="{{ asset('assets/js/vue-select.js') }}"></script>
  <script src="{{ asset('assets/pos/plugins/jquery/jquery.min.js') }}"></script>
  <link rel="stylesheet" href="{{asset('assets/styles/vendor/flatpickr.min.css')}}">

  {{-- Alpine Js --}}
  <script defer src="{{ asset('js/plugin-core/alpine-collapse.js') }}"></script>
  <script defer src="{{ asset('js/plugin-core/alpine.js') }}"></script>
  <script src="{{ asset('js/plugin-script/alpine-data.js') }}"></script>
  <script src="{{ asset('js/plugin-script/alpine-store.js') }}"></script>

</head>

<body class="sidebar-toggled sidebar-fixed-page pos-body">

  <!-- Pre Loader Strat  -->
  <div class='loadscreen' id="preloader">
      <div class="loader spinner-border spinner-border-lg">
      </div>
  </div>

  <div class="compact-layout pos-layout">
    <div data-compact-width="100" class="layout-sidebar pos-sidebar">
      @include('layouts.new-sidebar.sidebar')
    </div>

    <div class="layout-content">
      @include('layouts.new-sidebar.header')

      <div class="content-section" id="main-pos">
        <section class="pos-content">
          <div class="d-flex align-items-center">
            <div class="w-100 text-gray-600 position-relative">
              <div id="autocomplete" class="autocomplete">
                <input type="text"
                  class="form-control border border-gray-300 py-3 pr-3"
                  placeholder="{{ __('translate.Scan_Search_Product_by_Code_Name') }}"
                  @input='e => search_input = e.target.value' @keyup="search(search_input)" @focus="handleFocus"
                  @blur="handleBlur" ref="product_autocomplete" class="autocomplete-input" />
                <ul class="autocomplete-result-list" v-show="focused">
                  <li class="autocomplete-result" v-for="product_fil in product_filter"
                    @mousedown="SearchProduct(product_fil)">@{{getResultValue(product_fil)}}</li>
                </ul>
                <span v-show="!focused">
                  @include('components.icons.search', [
                  'class'=>'position-absolute top-50 translate-middle left-30 ',
                  ])
                </span>
              </div>
            </div>
            <button class="btn btn-light p-3 rounded-circle ms-3">
              @include('components.icons.full-screen', ['class'=>'width_20'])
            </button>
          </div>

          <div class="row pos-card-left">
            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
              
              <validation-observer ref="create_pos">
                <form>

                  <!-- Customer -->
                  <div class="filter-box">
                    <validation-provider name="Customer" rules="required" v-slot="{ valid, errors }">
                      <label>{{ __('translate.Customer') }} <span class="field_required">*</span></label>
                      <v-select @input="Selected_Customer" v-model="sale.client_id"
                        placeholder="{{ __('translate.Choose_Customer') }}" :reduce="username => username.value"
                        :options="clients.map(clients => ({label: clients.username, value: clients.id}))">

                      </v-select>
                      <span class="error">@{{ errors[0] }}</span>
                    </validation-provider>
                  </div>

                  <!-- warehouse -->
                  <div class="filter-box">
                    <validation-provider name="warehouse" rules="required" v-slot="{ valid, errors }">
                      <label>{{ __('translate.warehouse') }} <span class="field_required">*</span></label>
                      <v-select @input="Selected_Warehouse" :disabled="details.length > 0"
                        placeholder="{{ __('translate.Choose_Warehouse') }}" v-model="sale.warehouse_id"
                        :reduce="(option) => option.value"
                        :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))">
                      </v-select>
                      <span class="error">@{{ errors[0] }}</span>
                    </validation-provider>
                  </div>

                  <!-- card -->
                  <div class="card m-0 card-list-products">
                    <div class="d-flex align-items-center justify-content-between">
                      <h6 class="fw-semibold m-0">{{ __('translate.Cart') }}</h6>
                    </div>
                   
                    <div class="card-items">
                      <div class="cart-item box-shadow-3" v-for="(detail, index) in details" :key="index">
                        <div class="d-flex align-items-center">
                          <img :src="'/images/products/'+detail.image" alt="">
                          <div>
                            <p class="text-gray-600 m-0 font_12">@{{detail.name}}</p>

                            @if($symbol_placement == 'before')
                              <h6 class="fw-semibold m-0 font_16">{{$currency}} @{{detail.subtotal.toFixed(2)}}</h6>
                            @else
                              <h6 class="fw-semibold m-0 font_16">@{{detail.subtotal.toFixed(2)}} {{$currency}}</h6>
                            @endif
                           
                              <a @click="Modal_Updat_Detail(detail)"
                                  class="cursor-pointer ul-link-action text-success"
                                  title="Edit">
                                  <i class="i-Edit"></i>
                              </a>
                              <a @click="delete_Product_Detail(detail.detail_id)"
                                  title="Delete"
                                  class="cursor-pointer ul-link-action text-danger">
                                  <i class="i-Close-Window"></i>
                              </a>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <span class="increment-decrement btn btn-light rounded-circle"
                            @click="decrement(detail ,detail.detail_id)">-</span>
                          <input class="fw-semibold cart-qty m-0 px-2"
                            @keyup="Verified_Qty(detail,detail.detail_id)" :min="0.00" :max="detail.stock"
                            v-model.number="detail.quantity">

                          <span class="increment-decrement btn btn-light rounded-circle"
                            @click="increment(detail ,detail.detail_id)">+</span>
                        </div>
                      </div>
                    </div>

                    <div class="cart-summery">
                      
                    <div>
                      <div class="summery-item mb-2 row">
                        <span class="title mr-2 col-lg-12 col-sm-12">{{ __('translate.Shipping') }}</span>
                        
                        <div class="col-lg-8 col-sm-12">
                          <validation-provider name="Shipping" :rules="{ regex: /^\d*\.?\d*$/}"
                            v-slot="validationContext">

                            <div class="input-group text-right">
                              <input :state="getValidationState(validationContext)"
                                aria-describedby="Shipping-feedback" v-model.number="sale.shipping"
                                @keyup="keyup_Shipping()" type="text" class="no-focus form-control pos-shipping">
                              <span class="input-group-text cursor-pointer" id="basic-addon3">$</span>
                            </div>
                            <span class="error">@{{ validationContext.errors[0] }}</span>
                          </validation-provider>
                        </div>
                      </div>

                      <div class="summery-item mb-2 row">
                        <span class="title mr-2 col-lg-12 col-sm-12">{{ __('translate.Order_Tax') }}</span>

                        <div class="col-lg-8 col-sm-12">
                          <validation-provider name="Order Tax" :rules="{ regex: /^\d*\.?\d*$/}"
                            v-slot="validationContext">

                            <div class="input-group text-right">
                              <input :state="getValidationState(validationContext)"
                                aria-describedby="OrderTax-feedback" v-model.number="sale.tax_rate"
                                @keyup="keyup_OrderTax()" type="text" class="no-focus form-control pos-tax">

                              <span class="input-group-text cursor-pointer" id="basic-addon3">%</span>
                            </div>
                            <span class="error">@{{ validationContext.errors[0] }}</span>
                          </validation-provider>
                        </div>
                      </div>

                      <div class="summery-item mb-3 row">
                          <span class="title mr-2 col-lg-12 col-sm-12">{{ __('translate.Discount') }}</span>
                          <div class="col-lg-8 col-sm-12 summery-item-discount">
                            <validation-provider name="Discount" :rules="{ regex: /^\d*\.?\d*$/}"
                              v-slot="validationContext">

                              <input :state="getValidationState(validationContext)"
                                aria-describedby="Discount-feedback" v-model.number="sale.discount"
                                @keyup="keyup_Discount()" type="text" class="no-focus form-control pos-discount" />
                              <span class="error">@{{ validationContext.errors[0] }}</span>
                            </validation-provider>
                            <select class="input-group-text discount-select-type" id="inputGroupSelect02"
                              @change="CaclulTotal()" v-model="sale.discount_type">
                              <option value="fixed">$</option>
                              <option value="percent">%</option>
                            </select>
                        </div>
                      </div>
                    </div>

                      <div class="pt-3 border-top border-gray-300 summery-total">
                        <h5 class="summery-item m-0">
                          <span>{{ __('translate.Total') }}</span>
                          @if($symbol_placement == 'before')
                            <span>{{$currency}} @{{GrandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}}</span>
                          @else
                            <span>@{{GrandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}} {{$currency}}</span>
                          @endif
                        </h5>
                      </div>
                    
                   

                      <div class="half-circle half-circle-left"></div>
                      <div class="half-circle half-circle-right"></div>
                    </div>

                    <button @click.prevent="Submit_Pos" class="cart-btn btn btn-primary">
                      {{ __('translate.Pay_Now') }}
                    </button>
                  
                  </div>

                </form>
              </validation-observer>

              <!-- Modal Update Detail Product -->
              <validation-observer ref="Update_Detail">
                <div class="modal fade" id="form_Update_Detail" tabindex="-1" role="dialog"
                  aria-labelledby="form_Update_Detail" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">@{{ detail.name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form @submit.prevent="submit_Update_Detail">
                          <div class="row">

                            <!-- Unit Price -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Product Price"
                                :rules="{ required: true , regex: /^\d*\.?\d*$/}" v-slot="validationContext">
                                <label for="Unit_price">{{ __('translate.Product_Price') }}
                                  <span class="field_required">*</span></label>
                                <input :state="getValidationState(validationContext)"
                                  aria-describedby="Unit_price-feedback"v-model.number="detail.Unit_price" type="text"
                                  class="form-control">
                                <span class="error">@{{ validationContext.errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- Tax Method -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Tax Method" rules="required" v-slot="{ valid, errors }">
                                <label>{{ __('translate.Tax_Method') }} <span class="field_required">*</span></label>
                                <v-select placeholder="{{ __('translate.Choose_Method') }}" v-model="detail.tax_method"
                                  :reduce="(option) => option.value" :options="
                        [
                          {label: 'Exclusive', value: '1'},
                          {label: 'Inclusive', value: '2'}
                        ]">
                                </v-select>
                                <span class="error">@{{ errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- Tax Rate -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Order Tax" :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                                v-slot="validationContext">
                                <label for="ordertax">{{ __('translate.Order_Tax') }}
                                  <span class="field_required">*</span></label>
                                <div class="input-group">
                                  <input :state="getValidationState(validationContext)"
                                    aria-describedby="OrderTax-feedback" v-model="detail.tax_percent" type="text"
                                    class="form-control">
                                  <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                  </div>
                                </div>
                                <span class="error">@{{ validationContext.errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- Discount Method -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Discount_Method" rules="required" v-slot="{ valid, errors }">
                                <label>{{ __('translate.Discount_Method') }} <span
                                    class="field_required">*</span></label>
                                <v-select placeholder="{{ __('translate.Choose_Method') }}"
                                  v-model="detail.discount_Method" :reduce="(option) => option.value" :options="
                        [
                          {label: 'Percent %', value: '1'},
                          {label: 'Fixed', value: '2'}
                        ]">
                                </v-select>
                                <span class="error">@{{ errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- Discount Rate -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Discount" :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                                v-slot="validationContext">
                                <label for="discount">{{ __('translate.Discount') }}
                                  <span class="field_required">*</span></label>
                                <input :state="getValidationState(validationContext)"
                                  aria-describedby="Discount-feedback" v-model="detail.discount" type="text"
                                  class="form-control">
                                <span class="error">@{{ validationContext.errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- Unit Sale -->
                            <div class="form-group col-md-6" v-if="detail.product_type != 'is_service'">
                              <validation-provider name="UnitSale" rules="required" v-slot="{ valid, errors }">
                                <label>{{ __('translate.Unit_Sale') }} <span class="field_required">*</span></label>
                                <v-select v-model="detail.sale_unit_id" :reduce="label => label.value"
                                  placeholder="{{ __('translate.Choose_Unit_Sale') }}"
                                  :options="units.map(units => ({label: units.name, value: units.id}))">
                                </v-select>
                                <span class="error">@{{ errors[0] }}</span>
                              </validation-provider>
                            </div>

                            <!-- imei_number -->
                            <div class="form-group col-md-12" v-show="detail.is_imei">
                              <label for="imei_number">{{ __('translate.Add_product_IMEI_Serial_number') }}</label>
                              <input v-model="detail.imei_number" type="text" class="form-control"
                                placeholder="{{ __('translate.Add_product_IMEI_Serial_number') }}">
                            </div>

                            <div class="col-lg-12">
                              <button type="submit" :disabled="Submit_Processing_detail" class="btn btn-primary">
                                <span v-if="Submit_Processing_detail" class="spinner-border spinner-border-sm"
                                  role="status" aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
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

              <!-- Modal add sale payment -->
              <validation-observer ref="add_payment_sale">
                <div class="modal fade" id="add_payment_sale" tabindex="-1" role="dialog"
                  aria-labelledby="add_payment_sale" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">{{ __('translate.AddPayment') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form @submit.prevent="Submit_Payment()">
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
                                        v-model="payment.date">
                  
                                      <span class="error">@{{  validationContext.errors[0] }}</span>
                                    </div>
                                  </validation-provider>
                                </div>

                            <!-- Paying_Amount -->
                            <div class="form-group col-md-6">
                              <validation-provider name="Montant à payer"
                                :rules="{ required: true , regex: /^\d*\.?\d*$/}" v-slot="validationContext">
                                <label for="Paying_Amount">{{ __('translate.Paying_Amount') }}
                                  <span class="field_required">*</span></label>
                                <input @keyup="Verified_paidAmount(payment.montant)"
                                  :state="getValidationState(validationContext)"
                                  aria-describedby="Paying_Amount-feedback" v-model.number="payment.montant"
                                  placeholder="{{ __('translate.Paying_Amount') }}" type="text" class="form-control">
                                <div class="error">
                                  @{{ validationContext.errors[0] }}</div>

                                @if($symbol_placement == 'before')
                                   <span class="badge badge-danger mt-2">{{ __('translate.Total') }} : {{$currency}}  @{{GrandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}} </span>
                                @else
                                   <span class="badge badge-danger mt-2">{{ __('translate.Total') }} : @{{GrandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}} {{$currency}}</span>
                                @endif

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

                            <div class="form-group col-md-6">
                              <label for="note">{{ __('translate.Payment_note') }}
                              </label>
                              <textarea type="text" v-model="payment.notes" class="form-control" name="note" id="note"
                                placeholder="{{ __('translate.Payment_note') }}"></textarea>
                            </div>

                            <div class="form-group col-md-6">
                              <label for="note">{{ __('translate.sale_note') }}
                              </label>
                              <textarea type="text" v-model="sale.notes" class="form-control" name="note" id="note"
                                placeholder="{{ __('translate.sale_note') }}"></textarea>
                            </div>
                          </div>

                          <div class="row mt-3">

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

            <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12 mt-3">
              <div class="row">
                <div class="col-12 col-lg-8">
                  <div class="row">

                    <div class="col-lg-4 col-md-6 col-sm-6" v-for="product in products"
                      @click="Check_Product_Exist(product , product.id)">
                      <div class="card product-card cursor-pointer">
                        <img :src="'/images/products/'+product.image" alt="">
                        <div class="card-body pos-card-product">
                          <p class="text-gray-600">@{{product.name}}</p>
                          <h6 class="title m-0"> @{{product.Net_price}}</h6>
                        </div>
                        <div class="quantity">
                          <span>@{{formatNumber(product.qte_sale , 2)}} @{{product.unitSale}}</span>
                        </div>
                      </div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <b-pagination @change="Product_onPageChanged" :total-rows="product_totalRows"
                            :per-page="product_perPage" v-model="product_currentPage">
                        </b-pagination>
                    </div>
                   
                  </div>
                </div>

                <div class="d-md-block col-12 col-lg-4">
                  <div class="card category-card">
                    <div class="category-head">
                      <h5 class="fw-semibold m-0">{{ __('translate.All_Category') }}</h5>
                    </div>
                    <ul class="p-0">
                        <li class="category-item" @click="Selected_Category('')" :class="{ 'active': category_id === '' }">
                          <i class="i-Bookmark"></i> {{ __('translate.All_Category') }}
                        </li>
                        <li class="category-item" @click="Selected_Category(category.id)"  v-for="category in categories" :key="category.id" :class="{ 'active': category.id === category_id }">
                          <i class="i-Bookmark"></i> @{{ category.name }}
                        </li>
                      </ul>
                      <nav aria-label="Page navigation example mt-3">
                        <ul class="pagination justify-content-center">
                          <li class="page-item" :class="{ 'disabled': currentPage_cat == 1 }">
                            <a class="page-link" href="#" aria-label="Previous" @click.prevent="previousPage_Category">
                              <span aria-hidden="true">&laquo;</span>
                            </a>
                          </li>
                          <li class="page-item" v-for="i in pages_cat" :key="i" :class="{ 'active': currentPage_cat == i }">
                            <a class="page-link" href="#" @click.prevent="goToPage_Category(i)">@{{ i }}</a>
                          </li>
                          <li class="page-item" :class="{ 'disabled': currentPage_cat == pages_cat }">
                            <a class="page-link" href="#" aria-label="Next" @click.prevent="nextPage_Category">
                              <span aria-hidden="true">&raquo;</span>
                            </a>
                          </li>
                        </ul>
                      </nav>
                        
                  </div>

                  <div class="card category-card">
                    <div class="category-head">
                      <h5 class="fw-semibold m-0">{{ __('translate.All_brands') }}</h5>
                    </div>
                    <ul class="p-0">
                        <li class="category-item" @click="Selected_Brand('')" :class="{ 'active': brand_id === '' }">
                          <i class="i-Bookmark"></i> {{ __('translate.All_brands') }}
                        </li>
                      <li class="category-item" @click="Selected_Brand(brand.id)" v-for="brand in brands" :key="brand.id" :class="{ 'active': brand.id === brand_id }">
                        <i class="i-Bookmark"></i> @{{ brand.name }}</li>
                    </ul>
                      <nav aria-label="Page navigation example mt-3">
                        <ul class="pagination justify-content-center">
                          <li class="page-item" :class="{ 'disabled': currentPage_brand == 1 }">
                            <a class="page-link" href="#" aria-label="Previous" @click.prevent="previousPage_brand">
                              <span aria-hidden="true">&laquo;</span>
                            </a>
                          </li>
                          <li class="page-item" v-for="i in pages_brand" :key="i" :class="{ 'active': currentPage_brand == i }">
                            <a class="page-link" href="#" @click.prevent="goToPage_brand(i)">@{{ i }}</a>
                          </li>
                          <li class="page-item" :class="{ 'disabled': currentPage_brand == pages_brand }">
                            <a class="page-link" href="#" aria-label="Next" @click.prevent="nextPage_brand">
                              <span aria-hidden="true">&raquo;</span>
                            </a>
                          </li>
                        </ul>
                      </nav>
                      

                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
  {{-- -----------------------------------------------------------------------------------------------}}

  <script type="text/javascript">
    $(window).on('load', function(){
      jQuery("#loader").fadeOut(); // will fade out the whole DIV that covers the website.
      jQuery("#preloader").delay(800).fadeOut("slow");
      app.getProducts(1);
      app.Get_Products_By_Warehouse(app.sale.warehouse_id);
      app.paginate_products(app.product_perPage, 0);
      jQuery("pos-layout").show(); // will fade out the whole DIV that covers the website.
      
  });
</script>

  {{-- vue js --}}
  <script src="{{ asset('assets/js/vue.js') }}"></script>
  <script src="{{asset('assets/js/bootstrap-vue.min.js')}}"></script>
  <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>

  <script src="{{asset('assets/js/vee-validate.min.js')}}"></script>
  <script src="{{asset('assets/js/vee-validate-rules.min.js')}}"></script>
  <script src="{{asset('/assets/js/moment.min.js')}}"></script>

  {{-- sweetalert2 --}}
  <script src="{{asset('assets/js/vendor/sweetalert2.min.js')}}"></script>


  {{-- common js --}}
  <script src="{{ asset('assets/js/common-bundle-script.js') }}"></script>
  {{-- page specific javascript --}}
  @yield('page-js')

  <script src="{{ asset('assets/js/script.js') }}"></script>

  <script src="{{asset('assets/js/vendor/toastr.min.js')}}"></script>
  <script src="{{asset('assets/js/toastr.script.js')}}"></script>

  <script src="{{ asset('assets/js/customizer.script.js') }}"></script>
  <script src="{{asset('assets/js/nprogress.js')}}"></script>


  <script src="{{ asset('assets/js/tooltip.script.js') }}"></script>
  <script src="{{ asset('assets/js/script_2.js') }}"></script>
  <script src="{{ asset('assets/js/vendor/feather.min.js') }}"></script>
  <script src="{{asset('assets/js/flatpickr.min.js')}}"></script>


  <script src="{{ asset('assets/js/compact-layout.js') }}"></script>

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
        el: '#main-pos',
        data: {
            categories: [],
            currentPage_cat: 1,
            perPage_cat: 4,
            pages_cat: 0,
            payment_methods:@json($payment_methods),
            accounts:@json($accounts),

            brands: [],
            currentPage_brand: 1,
            perPage_brand: 4,
            pages_brand: 0,
            
            load_product: true,
            is_data_invoice_pos: false,
            isLoading: true,
            paymentProcessing: false,
            Submit_Processing_detail : false,
            focused: false,
            timer:null,
            search_input:'',
            product_filter:[],
            GrandTotal: 0,
            total: 0,
            Ref: "",
            units: [],
            warehouses: @json($warehouses),
            clients: @json($clients),
            payments: [],
            products: [],
            products_pos: [],
            details: [],
            detail: {},
            sale: {
              sale: "",
              warehouse_id: @json($default_warehouse),
              client_id: @json($default_Client),
              tax_rate: 0,
              shipping: 0,
              discount: 0,
              discount_type:"fixed",
              discount_percent_total: 0,
              TaxNet: 0,
              notes:'',
            },
            payment: {
                date:moment().format('YYYY-MM-DD HH:mm'),
                client_id: "",
                montant: '',
                notes: "",
                payment_method_id: "",
                account_id: "",
            },
            currentPage: 1,
            perPage: 6,
            product_currentPage: 1,
            paginated_Products: "",
            product_perPage: 8,
            product_totalRows: @json($totalRows),
            category_id: "",
            brand_id: "",
            product: {
              id: "",
              code: "",
              product_type: "",
              current: "",
              quantity: "",
              check_qty: "",
              discount: "",
              DiscountNet: "",
              discount_Method: "",
              sale_unit_id: "",
              fix_stock: "",
              fix_price: "",
              name: "",
              unitSale: "",
              Net_price: "",
              Unit_price: "",
              Total_price: "",
              subtotal: "",
              product_id: "",
              detail_id: "",
              taxe: "",
              tax_percent: "",
              tax_method: "",
              product_variant_id: "",
              is_imei: "",
              imei_number:"",
              qty_min:"",
              is_promotion:"",
              promo_percent:"",
            },
            sound: "/assets/audio/Beep.wav",
            audio: new Audio("/assets/audio/Beep.wav")
        },

        mounted() {
          this.fetchCategories();
          this.fetchBrands();
        },

        methods: {

          Selected_Payment_Method(value) {
                if (value === null) {
                    this.payment.payment_method_id = "";
                }
            },


        //--------------- Paginate Category ------------------
        fetchCategories() {
          axios.get('/api/categories?page=' + this.currentPage_cat + '&perPage=' + this.perPage_cat)
            .then(response => {
              this.categories = response.data.data;
              this.pages_cat = response.data.last_page;
            })
            .catch(error => {
              console.log(error);
            });
        },

        goToPage_Category(page) {
          this.currentPage_cat = page;
          this.fetchCategories();
        },

        previousPage_Category() {
          if (this.currentPage_cat > 1) {
            this.currentPage_cat--;
            this.fetchCategories();
          }
        },

        nextPage_Category() {
          if (this.currentPage_cat < this.pages_cat) {
            this.currentPage_cat++;
            this.fetchCategories();
          }
        },

         //--------------- Paginate brands ------------------
         fetchBrands() {
          axios.get('/api/brands?page=' + this.currentPage_brand + '&perPage=' + this.perPage_brand)
            .then(response => {
              this.brands = response.data.data;
              this.pages_brand = response.data.last_page;
            })
            .catch(error => {
              console.log(error);
            });
        },

        goToPage_brand(page) {
          this.currentPage_brand = page;
          this.fetchBrands();
        },

        previousPage_brand() {
          if (this.currentPage_brand > 1) {
            this.currentPage_brand--;
            this.fetchBrands();
          }
        },

        nextPage_brand() {
          if (this.currentPage_brand < this.pages_brand) {
            this.currentPage_brand++;
            this.fetchBrands();
          }
        },

        //---------------------------------

          
           handleFocus() {
            this.focused = true
          },
          handleBlur() {
            this.focused = false
          },

          // ------------------------ Paginate Products --------------------\\
          Product_paginatePerPage() {
            this.paginate_products(this.product_perPage, 0);
          },
          paginate_products(pageSize, pageNumber) {
            let itemsToParse = this.products;
            this.paginated_Products = itemsToParse.slice(
              pageNumber * pageSize,
              (pageNumber + 1) * pageSize
            );
          },

          Product_onPageChanged(page) {
            this.paginate_products(this.product_perPage, page - 1);
            this.getProducts(page);
          },

         

          //--- Submit Validate Create Sale
          Submit_Pos() {
            // Start the progress bar.
            NProgress.start();
            NProgress.set(0.1);
            this.$refs.create_pos.validate().then(success => {
              if (!success) {
                NProgress.done();
                if (this.sale.client_id == "" || this.sale.client_id === null) {
                  toastr.error('Veuillez choisir le client');
                  
                } else if (
                  this.sale.warehouse_id == "" ||
                  this.sale.warehouse_id === null
                ) {
                  toastr.error('Veuillez choisir le Magasin');
                  
                } else {
                  toastr.error('Veuillez remplir correctement le formulaire');
                }
              } else {
                if (this.verifiedForm()) {
                  this.pay_now();
                } else {
                  NProgress.done();
                }
              }
            });
          },

          pay_now(){
              this.payment.montant = this.formatNumber(this.GrandTotal, 2);
              $('#add_payment_sale').modal('show');
              NProgress.done();
          },

         //------ Validate Form Submit_Payment
         Submit_Payment() {
              this.$refs.add_payment_sale.validate().then(success => {
                  if (!success) {
                  toastr.error('Veuillez remplir correctement le formulaire');
                  }
                  else if (this.payment.montant > this.GrandTotal) {
                      toastr.error('Le montant à payer est supérieur au total à payer');
                      this.payment.montant = 0;
                  }else{
                      this.CreatePOS();
                  } 
                  
              });
          },

          //---------- keyup paid montant
          Verified_paidAmount() {
              if (isNaN(this.payment.montant)) {
                  this.payment.montant = 0;
                  
              } else if (this.payment.montant > this.GrandTotal) {
                  toastr.warning('Le montant à payer est supérieur au total à payer');
                  this.payment.montant = 0;
              } 
          },

          //---Submit Validation Update Detail
          submit_Update_Detail() {
            this.$refs.Update_Detail.validate().then(success => {
              if (!success) {
                return;
              } else {
                this.Update_Detail();
              }
            });
          },
         
          //------------- Submit Validation Create & Edit Customer
          Submit_Customer() {
            // Start the progress bar.
            NProgress.start();
            NProgress.set(0.1);
            this.$refs.Create_Customer.validate().then(success => {
              if (!success) {
                NProgress.done();
                toastr.error('Veuillez remplir correctement le formulaire');
              } else {
                this.Create_Client();
              }
            });
          },
        
          //---Validate State Fields
          getValidationState({ dirty, validated, valid = null }) {
            return dirty || validated ? valid : null;
          },
       
          Selected_Customer(value){
            if (value === null) {
              this.sale.client_id = "";
             
            }

          },
         
          //---------------------- Event Select Warehouse ------------------------------\\
          Selected_Warehouse(value) {
            if (value === null) {
              this.search_input= '';
              this.product_filter = [];
              this.sale.warehouse_id = '';
              this.products_pos = [];
              this.getProducts(1);
            }else{
              this.getProducts(1);
              this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            }
          },

          
          //---------------------- Event Select Brand ------------------------------\\
          Selected_Brand(value) {
            if (value === null) {
              this.search_input= '';
              this.product_filter = [];
              this.brand_id = '';
              this.getProducts(1);
              this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            }else{
              this.brand_id = value;
              this.getProducts(1);
              this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            }
          },

           //---------------------- Event Select category_id ------------------------------\\
           Selected_Category(value) {
            if (value === null) {
              this.search_input= '';
              this.product_filter = [];
              this.category_id = '';
              this.getProducts(1);
              this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            }else{
              this.category_id = value;
              this.getProducts(1);
              this.Get_Products_By_Warehouse(this.sale.warehouse_id);
            }
          },

          //----------------------------------------- Add Detail of Sale -------------------------\\
          add_product(code) {
            this.audio.play();
            if (this.details.some(detail => detail.code === code)) {
              this.increment_qty_scanner(code);
          
            } else {
              if (this.details.length > 0) {
                this.order_detail_id();
              } else if (this.details.length === 0) {
                this.product.detail_id = 1;
              }
              if(this.product.qty_min > this.product.fix_stock){
                toastr.error('Minimum sales qty is' + '  ' + '('+this.product.qty_min + ' ' + this.product.unitSale +')' + ' '+ 'But not enough in stock');
              }else{
                this.details.push(this.product);
                setTimeout(() => {
                  this.load_product = true;
                }, 300);

              }
            }
          },

          //-------------------------------- order detail id -------------------------\\
          order_detail_id() {
            this.product.detail_id = 0;
            var len = this.details.length;
            this.product.detail_id = this.details[len - 1].detail_id + 1;
          },

           //---------------------- Get_sales_units ------------------------------\\
          Get_sales_units(value) {
            axios
              .get("/products/Get_sales_units?id=" + value)
              .then(({ data }) => (this.units = data));
          },
          
          //------ Show Modal Update Detail Product
          Modal_Updat_Detail(detail) {
            NProgress.start();
            NProgress.set(0.1);
            this.detail = {};
            this.Get_sales_units(detail.product_id);
            this.detail.detail_id = detail.detail_id;
            this.detail.sale_unit_id = detail.sale_unit_id;
            this.detail.name = detail.name;
            this.detail.product_type = detail.product_type;
            this.detail.Unit_price = detail.Unit_price;
            this.detail.fix_price = detail.fix_price;
            this.detail.fix_stock = detail.fix_stock;
            this.detail.current = detail.current;
            this.detail.tax_method = detail.tax_method;
            this.detail.discount_Method = detail.discount_Method;
            this.detail.discount = detail.discount;
            this.detail.quantity = detail.quantity;
            this.detail.tax_percent = detail.tax_percent;
            this.detail.is_imei = detail.is_imei;
            this.detail.imei_number = detail.imei_number;
             setTimeout(() => {
              NProgress.done();
              $('#form_Update_Detail').modal('show');
            }, 1000);
          },


          //------ Submit Update Detail Product
          Update_Detail() {
            NProgress.start();
            NProgress.set(0.1);
            this.Submit_Processing_detail = true;

            for (var i = 0; i < this.details.length; i++) {
              if (this.details[i].detail_id === this.detail.detail_id) {
                // this.convert_unit();
                for (var k = 0; k < this.units.length; k++) {
                  if (this.units[k].id == this.detail.sale_unit_id) {
                    if (this.units[k].operator == "/") {
                      this.details[i].current =
                        this.detail.fix_stock * this.units[k].operator_value;
                      this.details[i].unitSale = this.units[k].ShortName;
                    } else {
                      this.details[i].current =
                        this.detail.fix_stock / this.units[k].operator_value;
                      this.details[i].unitSale = this.units[k].ShortName;
                    }
                  }
                }
                if (this.details[i].current < this.details[i].quantity) {
                  this.details[i].quantity = this.details[i].current;
                } else {
                  this.details[i].quantity = 1;
                }

                this.detail.Unit_price         = Number((this.detail.Unit_price).toFixed(2));

                this.details[i].Unit_price      = this.detail.Unit_price,
                this.details[i].tax_percent     = this.detail.tax_percent;
                this.details[i].tax_method      = this.detail.tax_method;
                this.details[i].discount_Method = this.detail.discount_Method;
                this.details[i].discount        = this.detail.discount;
                this.details[i].sale_unit_id    = this.detail.sale_unit_id;
                this.details[i].imei_number     = this.detail.imei_number;
                this.details[i].product_type    = this.detail.product_type;

                if (this.details[i].discount_Method == "2") {
                  //Fixed
                  this.details[i].DiscountNet = this.details[i].discount;
                } else {
                  //Percentage %
                  this.details[i].DiscountNet = parseFloat(
                    (this.details[i].Unit_price * this.details[i].discount) / 100
                  );
                }
                if (this.details[i].tax_method == "1") {
                  //Exclusive
                  this.details[i].Net_price = parseFloat(
                    this.details[i].Unit_price - this.details[i].DiscountNet
                  );
                  this.details[i].taxe = parseFloat(
                    (this.details[i].tax_percent *
                      (this.details[i].Unit_price - this.details[i].DiscountNet)) /
                      100
                  );
                  this.details[i].Total_price = parseFloat(
                    this.details[i].Net_price + this.details[i].taxe
                  );
                } else {
                  //Inclusive
                  this.details[i].Net_price = parseFloat(
                    (this.details[i].Unit_price - this.details[i].DiscountNet) /
                      (this.details[i].tax_percent / 100 + 1)
                  );
                  this.details[i].taxe = parseFloat(
                    this.details[i].Unit_price -
                      this.details[i].Net_price -
                      this.details[i].DiscountNet
                  );
                  this.details[i].Total_price = parseFloat(
                    this.details[i].Net_price + this.details[i].taxe
                  );
                }
                this.$forceUpdate();
              }
            }
            this.CaclulTotal();
             setTimeout(() => {
              NProgress.done();
              this.Submit_Processing_detail = false;
              $('#form_Update_Detail').modal('hide');
            }, 1000);
          },

          unique_arr(array){
            return array.filter(function(el, index, arr) {
                return index == arr.indexOf(el);
            });
          },
          
          //-- check Qty of  details order if Null or zero
          verifiedForm() {
            if (this.details.length <= 0) {
              toastr.error('Please add the product');
              return false;
            } else {
              var code_array = [];
              for (var i = 0; i < this.details.length; i++) {
                code_array.push(this.details[i].code);

                if (
                  this.details[i].quantity == "" ||
                  this.details[i].quantity === null ||
                  this.details[i].quantity === 0
                ) {
                  // count += 1;
                  toastr.error('please add quantity to product');
                  return false;
                }
                else if(this.details[i].quantity < this.details[i].qty_min){
                  toastr.error('The minimum sale quantity for the product' + ' ' + this.details[i].name + '  and' + ' '+ this.details[i].qty_min +' ' + this.details[i].unitSale);
                  return false;
                }else if (this.details[i].quantity > this.details[i].current) {
                  toastr.error('insufficient stock for the product'  + ' ' + this.details[i].name);
                  return false;
                }
              }
              const uniqueArray = this.unique_arr(code_array);
              if (this.details.length != uniqueArray.length) {
                toastr.error('the product is duplicated');
                return false;
              } else {
                return true;
              }

            }
          },
       
        
          //----------------------------------Create POS ------------------------------\\
          CreatePOS() {
            if (this.verifiedForm()) {
            NProgress.start();
            NProgress.set(0.1);
           
              this.paymentProcessing = true;
              axios
                .post("/pos/create_pos", {
                  date:this.payment.date,
                  client_id: this.sale.client_id,
                  warehouse_id: this.sale.warehouse_id,
                  tax_rate: this.sale.tax_rate?this.sale.tax_rate:0,
                  TaxNet: this.sale.TaxNet?this.sale.TaxNet:0,
                  discount: this.sale.discount?this.sale.discount:0,
                  discount_type: this.sale.discount_type,
                  discount_percent_total: this.sale.discount_percent_total?this.sale.discount_percent_total:0,
                  shipping: this.sale.shipping?this.sale.shipping:0,
                  notes: this.sale.notes,
                  details: this.details,
                  GrandTotal: this.GrandTotal,
                  payment_method_id: this.payment.payment_method_id,
                  account_id: this.payment.account_id,
                  payment_notes: this.payment.notes,
                  montant : parseFloat(this.payment.montant).toFixed(2),
                 
                 
                })
                .then(response => {
                  if (response.data.success === true) {
                    NProgress.done();
                    this.paymentProcessing = false;
                    toastr.success('{{ __('translate.Created_in_successfully') }}');
                    window.open("/invoice_pos/" + response.data.id, "_blank");
                    window.location.reload();
                  }
                })
                .catch(error => {
                  NProgress.done();
                  this.paymentProcessing = false;
                  toastr.error('{{ __('translate.There_was_something_wronge') }}');
                });
              }
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

        //---------------------------------Get Product Details ------------------------\\
        Get_Product_Details(product_id , variant_id) {
          axios.get("/products/show_product_data/" + product_id +"/"+ variant_id).then(response => {
              this.product.discount = 0;
              this.product.DiscountNet = 0;
              this.product.discount_Method = "2";
              this.product.product_id = response.data.id;
              this.product.image = response.data.image;
              this.product.name = response.data.name;
              this.product.product_type = response.data.product_type;
              this.product.Net_price = response.data.Net_price;
              this.product.Total_price = response.data.Total_price;
              this.product.Unit_price = response.data.Unit_price;
              this.product.taxe = response.data.tax_price;
              this.product.tax_method = response.data.tax_method;
              this.product.tax_percent = response.data.tax_percent;
              this.product.unitSale = response.data.unitSale;
              this.product.product_variant_id = variant_id;
              this.product.code = response.data.code;
              this.product.fix_price = response.data.fix_price;
              this.product.sale_unit_id = response.data.sale_unit_id;
              this.product.qty_min = response.data.qty_min;
              this.product.is_imei = response.data.is_imei;
              this.product.imei_number = '';
              this.product.is_promotion = response.data.is_promotion;
              this.product.promo_percent = response.data.promo_percent;

              this.add_product(response.data.code);
              this.CaclulTotal();
              NProgress.done();
            });
          },

          //----------- Calcul Total
          CaclulTotal() {
            this.total = 0;
            for (var i = 0; i < this.details.length; i++) {
              var tax = this.details[i].taxe * this.details[i].quantity;
              this.details[i].subtotal = parseFloat(
                this.details[i].quantity * this.details[i].Net_price + tax
              );
              this.total = parseFloat(this.total + this.details[i].subtotal);
            }

            if (this.sale.discount_type == 'percent') {
                this.sale.discount_percent_total = parseFloat((this.total * this.sale.discount) / 100);
                const total_without_discount = parseFloat(this.total -  this.sale.discount_percent_total);

                this.sale.TaxNet = parseFloat((total_without_discount * this.sale.tax_rate) / 100);
                this.GrandTotal = parseFloat(total_without_discount + this.sale.TaxNet + this.sale.shipping);

                var grand_total =  this.GrandTotal.toFixed(2);
                this.GrandTotal = parseFloat(grand_total);

            } else {
                this.sale.discount_percent_total = 0;
                const total_without_discount = parseFloat(this.total - this.sale.discount);

                this.sale.TaxNet = parseFloat((total_without_discount * this.sale.tax_rate) / 100);
                this.GrandTotal  = parseFloat(total_without_discount + this.sale.TaxNet + this.sale.shipping);
                var grand_total  =  this.GrandTotal.toFixed(2);
                this.GrandTotal  = parseFloat(grand_total);
            }
      
          },

          //-------Verified QTY
          Verified_Qty(detail, id) {
            for (var i = 0; i < this.details.length; i++) {
              if (this.details[i].detail_id === id) {
                  if (isNaN(detail.quantity)) {
                    this.details[i].quantity = detail.current;
                  }
                  else if (detail.quantity > detail.current) {
                    toastr.error('{{ __('translate.Low_Stock') }}');
                    this.details[i].quantity = detail.current;
                    
                  } else if(detail.quantity < detail.qty_min){
                  
                      toastr.warning('Minimum Sales Quantity Is' + ' '+ detail.qty_min +' ' + detail.unitSale);
                  } else {
                    this.details[i].quantity = detail.quantity;
                  }
              }
            }
            this.$forceUpdate();
            this.CaclulTotal();
          },
          //----------------------------------- Increment QTY with barcode scanner ------------------------------\\
          increment_qty_scanner(code) {
            for (var i = 0; i < this.details.length; i++) {
              if (this.details[i].code === code) {
                if (this.details[i].quantity + 1 > this.details[i].current) {
                  toastr.error('{{ __('translate.Low_Stock') }}');
                } else {
                  this.details[i].quantity++;
                }
              }
            }
            this.CaclulTotal();
            this.$forceUpdate();

            NProgress.done();
            setTimeout(() => {
              this.load_product = true;
            }, 300);
          },
          //----------------------------------- Increment QTY ------------------------------\\
          increment(detail, id) {
            for (var i = 0; i < this.details.length; i++) {
              if (this.details[i].detail_id == id) {
                if (detail.quantity + 1 > detail.current) {
                  toastr.error('{{ __('translate.Low_Stock') }}');
                } else {
                  this.details[i].quantity++;
                }
              }
            }
            this.CaclulTotal();
            this.$forceUpdate();
          },
          //----------------------------------- decrement QTY ------------------------------\\
          decrement(detail, id) {
            for (var i = 0; i < this.details.length; i++) {
              if (this.details[i].detail_id == id) {
                if (detail.quantity - 1 > detail.current || detail.quantity - 1 < 1) {
                  toastr.error('{{ __('translate.Low_Stock') }}');
                } else if(detail.quantity - 1 < detail.qty_min){
                  toastr.warning('Minimum Sales Quantity Is' + ' '+ detail.qty_min +' ' + detail.unitSale);
                } else {
                  this.details[i].quantity--;
                }
              }
            }
            this.CaclulTotal();
            this.$forceUpdate();
          },
        
          //---------- keyup OrderTax
          keyup_OrderTax() {
            if (isNaN(this.sale.tax_rate)) {
              this.sale.tax_rate = 0;
            } else if(this.sale.tax_rate == ''){
               this.sale.tax_rate = 0;
              this.CaclulTotal();
            }else {
              this.CaclulTotal();
            }
          },
          //---------- keyup Discount
          keyup_Discount() {
            if (isNaN(this.sale.discount)) {
              this.sale.discount = 0;
            } else if(this.sale.discount == ''){
               this.sale.discount = 0;
              this.CaclulTotal();
            }else {
              this.CaclulTotal();
            }
          },
          //---------- keyup Shipping
          keyup_Shipping() {
            if (isNaN(this.sale.shipping)) {
              this.sale.shipping = 0;
            } else if(this.sale.shipping == ''){
               this.sale.shipping = 0;
              this.CaclulTotal();
            }else {
              this.CaclulTotal();
            }
          },
        
          //-----------------------------------Delete Detail Product ------------------------------\\
          delete_Product_Detail(id) {
            for (var i = 0; i < this.details.length; i++) {
              if (id === this.details[i].detail_id) {
                this.details.splice(i, 1);
                this.CaclulTotal();
              }
            }
          },
         
          //------------------------- get Result Value Search Product
          getResultValue(result) {
            return result.code + " " + "(" + result.name + ")";
          },
          //------------------------- Submit Search Product
          SearchProduct(result) {

            if(this.load_product){
              this.load_product = false;
              this.product = {};

              if( result.product_type == 'is_service'){
                this.product.quantity = 1;
                this.product.code = result.code;

              }else{
                  this.product.image = result.image;
                  this.product.code = result.code;
                  this.product.current = result.qte_sale;
                  this.product.fix_stock = result.qte;
                  if (result.qte_sale < 1) {
                    this.product.quantity = result.qte_sale;
                  }else if(result.qty_min !== 0){
                    this.product.quantity = result.qty_min;
                  } else {
                    this.product.quantity = 1;
                  }
                  this.product.product_variant_id = result.product_variant_id;
              }

              this.Get_Product_Details(result.id, result.product_variant_id);
              this.search_input= '';
              this.$refs.product_autocomplete.value = "";
              this.product_filter = [];
            }else{
              toastr.error('Please wait until the product is loaded');
            }

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
            if (this.sale.warehouse_id != "" &&  this.sale.warehouse_id != null) {
              this.timer = setTimeout(() => {
                const product_filter = this.products_pos.filter(product => product.code === this.search_input);
                  if(product_filter.length === 1){
                      this.Check_Product_Exist(product_filter[0], product_filter[0].id);
                  }else{
                      this.product_filter=  this.products_pos.filter(product => {
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
         
          //---------------------------------- Check if Product Exist in Order List ---------------------\\
          Check_Product_Exist(product, id) {
           
            if(this.load_product){
              this.load_product = false;
              NProgress.start();
              NProgress.set(0.1);
              this.product = {};

              if( product.product_type == 'is_service'){
                this.product.quantity = 1;

              }else{

                this.product.image = product.image;
                this.product.current = product.qte_sale;
                this.product.fix_stock = product.qte;
                if (product.qte_sale < 1) {
                  this.product.quantity = product.qte_sale;
                }else if(product.qty_min !== 0){
                  this.product.quantity = product.qty_min;
                } else {
                  this.product.quantity = 1;
                }
              }

              this.Get_Product_Details(id, product.product_variant_id);
              this.search_input= '';
              this.$refs.product_autocomplete.value = "";
              this.product_filter = [];
            }else{
              toastr.error('Please wait until the product is loaded');
            }

          },

          //------------------------------------ Get Products By Warehouse -------------------------\\
          Get_Products_By_Warehouse(id) {
            // Start the progress bar.
              NProgress.start();
              NProgress.set(0.1);
            axios
              .get("/pos/autocomplete_product_pos/" + id 
                  + "?stock=" + 1 
                  + "&product_service=" + 1
                  + "&category_id=" 
                  + this.category_id 
                  +"&brand_id=" +
                  this.brand_id)
              .then(response => {
                  this.products_pos = response.data;
                  NProgress.done();
                  })
                .catch(error => {
                });
          },
         
          //------------------------------- Get Products with Filters ------------------------------\\
          getProducts(page = 1) {
            NProgress.start();
            NProgress.set(0.1);
            axios
              .get(
                "/pos/get_products_pos?page=" +
                  page +
                  "&category_id=" +
                  this.category_id +
                  "&brand_id=" +
                  this.brand_id +
                  "&warehouse_id=" +
                  this.sale.warehouse_id +
                  "&stock=" + 1 + 
                  "&product_service=" + 1
              )
              .then(response => {
                this.products = response.data.products;
                this.product_totalRows = response.data.totalRows;
                this.Product_paginatePerPage();
                NProgress.done();
              })
              .catch(response => {
                NProgress.done();
              });
          },
        
        },
        //-----------------------------Autoload function-------------------
        created() {

        }

      })
  
  </script>



</body>

</html>