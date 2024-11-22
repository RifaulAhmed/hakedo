@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{ asset('assets/styles/vendor/nprogress.css') }}">
<link rel="stylesheet" href="{{ asset('assets/styles/vendor/datepicker.min.css') }}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Add_Product') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<!-- begin::main-row -->
<div class="row" id="section_create_product">
    <div class="col-lg-12 mb-3">
        <!--begin::form-->
        <form @submit.prevent="Create_Product()">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <!-- Prefix Field -->
                        <div class="form-group col-md-4">
                            <label for="prefix">{{ __('Prefix Code') }}</label>
                            <input type="text" class="form-control" id="prefix"
                                placeholder="{{ __('Enter Prefix Code') }}" v-model="product.prefix">
                        </div>

                        <div class="form-group col-md-4">
                            <label for="prefix">{{ __('Batch Number') }}</label>
                            <input type="text" class="form-control" id="prefix"
                                placeholder="{{ __('Enter Batch Number') }}" v-model="product.prefix">
                        </div>

                        <div class="form-group col-md-4">
                            <label for="name">{{ __('translate.Product_Name') }} <span class="field_required">*</span></label>
                            <input type="text" class="form-control" id="name"
                                placeholder="{{ __('translate.Enter_Name_Product') }}" v-model="product.name">
                            <span class="error" v-if="errors && errors.name">
                                @{{ errors.name[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="code">{{ __('translate.Product_Code') }} <span class="field_required">*</span></label>
                            <div class="input-group mb-3">
                                <input v-model="product.code" type="text" class="form-control" placeholder="generate the barcode" aria-label="generate the barcode" aria-describedby="basic-addon2">
                                <span class="input-group-text cursor-pointer" id="basic-addon2" @click="generateNumber()"><i class="i-Bar-Code"></i></span>
                            </div>
                            <span class="error" v-if="errors && errors.code">
                                @{{ errors.code[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('translate.Category') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Category') }}" v-model="product.category_id"
                                :reduce="(option) => option.value"
                                :options="categories.map(categories => ({ label: categories.name, value: categories.id }))">
                            </v-select>
                            <span class="error" v-if="errors && errors.category_id">
                                @{{ errors.category_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{ __('translate.Brand') }} </label>
                            <v-select placeholder="{{ __('translate.Choose_Brand') }}" v-model="product.brand_id"
                                :reduce="(option) => option.value"
                                :options="brands.map(brands => ({ label: brands.name, value: brands.id }))">
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
                                            { label: 'Exclusive', value: '1' },
                                            { label: 'Inclusive', value: '2' }
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
                        <div class="form-group col-md-4 mb-3">
                            <label>{{ __('translate.Product_Type') }} <span class="field_required">*</span></label>
                            <v-select placeholder="Product Type" v-model="product.type"
                                :reduce="(option) => option.value" :options="
                                                [
                                                { label: 'Standard Product', value: 'is_single' },
                                                { label: 'Variable Product', value: 'is_variant' },
                                                { label: 'Service Product', value: 'is_service' }
                                                ]">
                            </v-select>
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
                                :options="units.map(units => ({ label: units.name, value: units.id }))">
                            </v-select>
                            <span class="error" v-if="errors && errors.unit_id">
                                @{{ errors.unit_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label>{{ __('translate.Unit_Sale') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Unit_Sale') }}"
                                v-model="product.unit_sale_id" :reduce="label => label.value"
                                :options="units_sub.map(units_sub => ({ label: units_sub.name, value: units_sub.id }))">
                            </v-select>
                            <span class="error" v-if="errors && errors.unit_sale_id">
                                @{{ errors.unit_sale_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label>{{ __('translate.Unit_Purchase') }} <span class="field_required">*</span></label>
                            <v-select placeholder="{{ __('translate.Choose_Unit_Purchase') }}"
                                v-model="product.unit_purchase_id" :reduce="label => label.value"
                                :options="units_sub.map(units_sub => ({ label: units_sub.name, value: units_sub.id }))">
                            </v-select>
                            <span class="error" v-if="errors && errors.unit_purchase_id">
                                @{{ errors.unit_purchase_id[0] }}
                            </span>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="qty_min">{{ __('translate.Minimum_sale_quantity') }} </label>
                            <input type="text" class="form-control" id="qty_min"
                                placeholder="{{ __('translate.Minimum_sale_quantity') }}" v-model="product.qty_min">
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="stock_alert">{{ __('translate.Stock_Alert') }} </label>
                            <input type="text" class="form-control" id="stock_alert"
                                placeholder="{{ __('translate.Stock_Alert') }}" v-model="product.stock_alert">
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="Type_barcode">{{ __('translate.Type_barcode') }} </label>
                            <input type="text" class="form-control" id="Type_barcode"
                                placeholder="{{ __('translate.Enter_Type_barcode') }}" v-model="product.Type_barcode">
                        </div>

                        <div class="form-group col-md-4" v-if="product.type != 'is_service'">
                            <label for="Type_barcode">{{ __('translate.product_promotion') }} </label>
                            <input class="form-check-input" type="checkbox" v-model="product.is_promo" id="is_promo">
                        </div>

                        <div class="form-group col-md-4" v-if="product.is_promo">
                            <label for="promo_price">{{ __('translate.promo_price') }} </label>
                            <input type="text" class="form-control" id="promo_price"
                                placeholder="{{ __('translate.promo_price') }}" v-model="product.promo_price">
                        </div>

                        <div class="form-group col-md-4" v-if="product.is_promo">
                            <label for="promo_start_date">{{ __('translate.promo_start_date') }} </label>
                            <vuejs-datepicker v-model="product.promo_start_date"></vuejs-datepicker>
                        </div>

                        <div class="form-group col-md-4" v-if="product.is_promo">
                            <label for="promo_end_date">{{ __('translate.promo_end_date') }} </label>
                            <vuejs-datepicker v-model="product.promo_end_date"></vuejs-datepicker>
                        </div>

                        <div class="form-group col-md-4" v-if="product.type == 'is_variant'">
                            <label>{{ __('translate.Variants') }} </label>
                            <button @click="addVariant()" class="btn btn-primary">{{ __('translate.Add_Variant') }}</button>
                            <div v-for="(variant, index) in variants" :key="index" class="mt-2">
                                <input type="text" v-model="variant.name" class="form-control" placeholder="{{ __('translate.Variant_Name') }}">
                                <input type="text" v-model="variant.price" class="form-control mt-2" placeholder="{{ __('translate.Variant_Price') }}">
                                <button @click="removeVariant(index)" class="btn btn-danger mt-2">{{ __('translate.Remove_Variant') }}</button>
                            </div>
                            <span class="error" v-if="errors && errors.variants">
                                @{{ errors.variants[0] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                        <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <i class="i-Yes me-2 font-weight-bold"></i> {{ __('translate.submit') }}
                    </button>
                </div>
            </div>
        </form>
        <!-- end::form -->
    </div>
</div>
@endsection

@section('page-js')
<script src="{{ asset('assets/js/nprogress.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap-tagsinput.js') }}"></script>
<script src="{{ asset('assets/js/datepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/vuejs-datepicker/vuejs-datepicker.min.js') }}"></script>

<script type="text/javascript">
    $(function () {
        "use strict";

        $(document).on('keyup keypress', 'form input[type="text"]', function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>

<script>
    Vue.component('v-select', VueSelect.VueSelect)

    var app = new Vue({
        el: '#section_create_product',
        components: {
            vuejsDatepicker,
        },
        data: {
            tag: "",
            len: 8,
            SubmitProcessing: false,
            data: new FormData(),
            errors: [],
            categories: @json($categories),
            units: @json($units),
            units_sub: [],
            brands: @json($brands),
            variants: [],
            product: {
                prefix: "", // Added prefix field
                type: "is_single",
                name: "",
                code: "",
                Type_barcode: "",
                cost: "",
                price: "",
                brand_id: "",
                category_id: "",
                TaxNet: "0",
                tax_method: "1",
                unit_id: "",
                unit_sale_id: "",
                unit_purchase_id: "",
                stock_alert: "0",
                qty_min: 0,
                image: "",
                note: "",
                is_variant: false,
                is_imei: false,
                is_promo: false,
                promo_price: '',
                promo_start_date: new Date().toISOString().slice(0, 10),
                promo_end_date: '',
            },
        },

        methods: {
            generateNumber() {
                this.code_exist = "";
                let prefix = this.product.prefix || ""; // Use prefix if available
                this.product.code = prefix + Math.floor(
                    Math.pow(10, this.len - 1) +
                    Math.random() *
                        (Math.pow(10, this.len) - Math.pow(10, this.len - 1) - 1)
                );
            },

            onFileSelected(event) {
                this.product.image = event.target.files[0];
            },

            addVariant() {
                this.variants.push({
                    name: "",
                    price: "",
                });
            },

            removeVariant(index) {
                this.variants.splice(index, 1);
            },

            Selected_Unit() {
                // Logic to handle unit selection
            },

            Create_Product() {
                if (this.product.type == 'is_variant' && this.variants.length <= 0) {
                    toastr.error('The variants array is required.');
                } else {
                    NProgress.start();
                    NProgress.set(0.1);
                    var self = this;
                    self.SubmitProcessing = true;

                    if (self.product.type == 'is_variant' && self.variants.length > 0) {
                        self.product.is_variant = true;
                    } else {
                        self.product.is_variant = false;
                    }

                    Object.entries(self.product).forEach(([key, value]) => {
                        self.data.append(key, value);
                    });

                    if (self.variants.length) {
                        self.data.append("variants", JSON.stringify(self.variants));
                    }

                    axios
                        .post("/products/products", self.data)
                        .then(response => {
                            NProgress.done();
                            self.SubmitProcessing = false;
                            window.location.href = '/products/products';
                            toastr.success('{{ __('translate.Created_in_successfully') }}');
                            self.errors = {};
                        })
                        .catch(error => {
                            NProgress.done();
                            self.SubmitProcessing = false;

                            if (error.response.status == 422) {
                                self.errors = error.response.data.errors;
                                toastr.error('{{ __('translate.There_was_something_wronge') }}');
                            }

                            if (self.errors.variants && self.errors.variants.length > 0) {
                                toastr.error(self.errors.variants[0]);
                            }
                        });
                }
            }
        },
    })
</script>

@endsection
