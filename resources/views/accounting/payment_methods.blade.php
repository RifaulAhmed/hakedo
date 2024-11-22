@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Payment_Methods') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_payment_methods">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
            @can('payment_method')
                <div class="text-end mb-3">

                <a class="btn btn-outline-primary btn-md m-1" @click="New_payment_method"><i
                            class="i-Add me-2 font-weight-bold"></i>{{ __('translate.Create') }}</a>
                </div>
            @endcan

              
                <div class="table-responsive">
                            <table id="payment_method_table" class="display table">
                                <thead>
                                    <tr>
                                        <th>{{ __('translate.Title') }}</th>
                                        <th>{{ __('translate.Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payment_methods as $payment_method)
                                    <tr>
                                        <td>{{$payment_method->title}}</td>
                                        <td>
                                            @if (auth()->user()->can('payment_method'))
                                            <a @click="Edit_payment_method( {{ $payment_method}})"
                                                class="cursor-pointer ul-link-action text-success" data-toggle="tooltip" data-placement="top"
                                                title="Edit">
                                                <i class="i-Edit"></i>
                                            </a>
                                            <a @click="Remove_payment_method( {{ $payment_method->id}})"
                                                class="cursor-pointer ul-link-action text-danger mr-1" data-toggle="tooltip" data-placement="top"
                                                title="Delete">
                                                <i class="i-Close-Window"></i>
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>

                            </table>


            </div>
        </div>
    </div>

       

    <!-- Modal Add & Edit payment_method -->
    <div class="modal fade" id="payment_method_Modal" tabindex="-1" role="dialog" aria-labelledby="payment_method_Modal"
        aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
                    <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form @submit.prevent="editmode?Update_payment_method():Create_payment_method()">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="title">{{ __('translate.Title') }} <span
                                        class="field_required">*</span></label>
                                <input type="text" v-model="payment_method.title" class="form-control" name="title"
                                    id="title" placeholder="{{ __('translate.Enter_title') }}">
                                <span class="error" v-if="errors && errors.title">
                                    @{{ errors.title[0] }}
                                </span>
                            </div>

                        </div>


                        <div class="row mt-3">

                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary" :disabled="SubmitProcessing">
                                    <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
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

    </div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>

<script>
    var app = new Vue({
        el: '#section_payment_methods',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            payment_methods: [], 
            payment_method: {
                title: "",
            }, 
        },
       
        methods: {


            //------------------------------ Show Modal (Create payment_method) -------------------------------\\
            New_payment_method() {
                this.reset_Form();
                this.editmode = false;
                $('#payment_method_Modal').modal('show');
            },

            //------------------------------ Show Modal (Update payment_method) -------------------------------\\
            Edit_payment_method(payment_method) {
                this.editmode = true;
                this.reset_Form();
                this.payment_method = payment_method;
                $('#payment_method_Modal').modal('show');
            },

            //----------------------------- Reset Form ---------------------------\\
            reset_Form() {
                this.payment_method = {
                    id: "",
                    title: "",
                };
                this.errors = {};
            },

            //------------------------ Create payment_method ---------------------------\\
            Create_payment_method() {
                var self = this;
                self.SubmitProcessing = true;
                axios.post("/accounting/payment_methods", {
                    title: self.payment_method.title,
                }).then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/accounting/payment_methods'; 
                        toastr.success('{{ __('translate.Created_in_successfully') }}');
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

           //----------------------- Update payment_method ---------------------------\\
            Update_payment_method() {
                var self = this;
                self.SubmitProcessing = true;
                axios.put("/accounting/payment_methods/" + self.payment_method.id, {
                    title: self.payment_method.title,
                }).then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/accounting/payment_methods'; 
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

             //--------------------------------- Remove payment_method ---------------------------\\
            Remove_payment_method(id) {

                swal({
                    title: '{{ __('translate.Are_you_sure') }}',
                    text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0CC27E',
                    cancelButtonColor: '#FF586B',
                    confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
                    cancelButtonText: '{{ __('translate.No_cancel') }}',
                    confirmButtonClass: 'btn btn-primary mr-5',
                    cancelButtonClass: 'btn btn-danger',
                    buttonsStyling: false
                }).then(function () {
                        axios
                            .delete("/accounting/payment_methods/" + id)
                            .then(() => {
                                window.location.href = '/accounting/payment_methods'; 
                                toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                            })
                            .catch(() => {
                                toastr.error('{{ __('translate.There_was_something_wronge') }}');
                            });
                    });
                },

           
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>

<script type="text/javascript">
    $(function () {
      "use strict";

        $('#payment_method_table').DataTable( {
            "processing": true, // for show progress bar
           
        
            dom: "<'row'<'col-sm-12 col-md-7'lB><'col-sm-12 col-md-5 p-0'f>>rtip",
            oLanguage: {
                    sEmptyTable: "{{ __('datatable.sEmptyTable') }}",
                    sInfo: "{{ __('datatable.sInfo') }}",
                    sInfoEmpty: "{{ __('datatable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ __('datatable.sInfoFiltered') }}",
                    sInfoThousands: "{{ __('datatable.sInfoThousands') }}",
                    sLengthMenu: "_MENU_", 
                    sLoadingRecords: "{{ __('datatable.sLoadingRecords') }}",
                    sProcessing: "{{ __('datatable.sProcessing') }}",
                    sSearch: "",
                    sSearchPlaceholder: "{{ __('datatable.sSearchPlaceholder') }}",
                    oPaginate: {
                        sFirst: "{{ __('datatable.oPaginate.sFirst') }}",
                        sLast: "{{ __('datatable.oPaginate.sLast') }}",
                        sNext: "{{ __('datatable.oPaginate.sNext') }}",
                        sPrevious: "{{ __('datatable.oPaginate.sPrevious') }}",
                    },
                    oAria: {
                        sSortAscending: "{{ __('datatable.oAria.sSortAscending') }}",
                        sSortDescending: "{{ __('datatable.oAria.sSortDescending') }}",
                    }
                },
            buttons: [
                {
                    extend: 'collection',
                    text: "{{ __('translate.EXPORT') }}",
                    buttons: [
                        'csv','excel', 'pdf', 'print'
                    ]
                }]
        });

    });
</script>
@endsection