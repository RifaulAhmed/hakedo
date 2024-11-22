@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">

@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Expense_Category') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_Expense_Category">
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        @can('expense_category')
                        <div class="text-end mb-3">
                            <a class="btn btn-outline-primary btn-md m-1" @click="New_Category"><i
                                    class="i-Add me-2 font-weight-bold"></i>
                                {{ __('translate.Create') }}</a>
                           
                        </div>
                        @endcan
                        <div class="table-responsive">
                            <table id="expense_category_table" class="display table">
                                <thead>
                                    <tr>
                                        <th>{{ __('translate.Category') }}</th>
                                        <th>{{ __('translate.Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $category)
                                    <tr>
                                        <td>{{$category->title}}</td>
                                        <td>
                                            @can('expense_category')
                                            <a @click="Edit_Category( {{ $category}})"
                                                class="cursor-pointer ul-link-action text-success" data-toggle="tooltip"
                                                data-placement="top" title="Edit">
                                                <i class="i-Edit"></i>
                                            </a>
                                            @endcan
                                            @can('expense_category')
                                            <a @click="Remove_Category( {{ $category->id}})"
                                                class="cursor-pointer ul-link-action text-danger mr-1" data-toggle="tooltip"
                                                data-placement="top" title="Delete">
                                                <i class="i-Close-Window"></i>
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add & Edit Category -->
    <div class="modal fade" id="Expense_Category_Modal" tabindex="-1" role="dialog"
        aria-labelledby="Expense_Category_Modal" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
                    <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form @submit.prevent="editmode?Update_Category():Create_Category()">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="title">{{ __('translate.Title') }} <span
                                        class="field_required">*</span></label>
                                <input type="text" v-model="category.title" class="form-control" name="title" id="title"
                                    placeholder="{{ __('translate.Enter_title') }}">
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
        el: '#section_Expense_Category',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            categories: [], 
            category: {
                title: "",
            }, 
        },
       
        methods: {

            //------------------------------ Show Modal (Create Category) -------------------------------\\
            New_Category() {
                this.reset_Form();
                this.editmode = false;
                $('#Expense_Category_Modal').modal('show');
            },

            //------------------------------ Show Modal (Update category) -------------------------------\\
            Edit_Category(category) {
                this.editmode = true;
                this.reset_Form();
                this.category = category;
                $('#Expense_Category_Modal').modal('show');
            },

            //----------------------------- Reset Form ---------------------------\\
            reset_Form() {
                this.category = {
                    id: "",
                    title: "",
                };
                this.errors = {};
            },

            //------------------------ Create Category ---------------------------\\
            Create_Category() {
                var self = this;
                self.SubmitProcessing = true;
                axios.post("/accounting/expense_category", {
                    title: self.category.title,
                }).then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/accounting/expense_category'; 
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

           //----------------------- Update Category ---------------------------\\
            Update_Category() {
                var self = this;
                self.SubmitProcessing = true;
                axios.put("/accounting/expense_category/" + self.category.id, {
                    title: self.category.title,
                }).then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/accounting/expense_category'; 
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

             //--------------------------------- Remove Category ---------------------------\\
            Remove_Category(id) {

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
                            .delete("/accounting/expense_category/" + id)
                            .then(() => {
                                window.location.href = '/accounting/expense_category'; 
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
    
        $('#expense_category_table').DataTable( {
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