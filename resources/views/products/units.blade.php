@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Units') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_Units_list">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="text-end mb-3">
          <a class="new_unit btn btn-outline-primary btn-md m-1"><i class="i-Add me-2 font-weight-bold"></i>
            {{ __('translate.Create') }}</a>
        </div>
        <div class="table-responsive">
          <table id="unit_table" class="display table">
            <thead>
              <tr>
                <th>ID</th>
                <th>{{ __('translate.Name') }}</th>
                <th>{{ __('translate.ShortName') }}</th>
                <th>{{ __('translate.Base_Unit') }}</th>
                <th>{{ __('translate.Operator') }}</th>
                <th>{{ __('translate.Operation_Value') }}</th>
                <th class="not_show">{{ __('translate.Action') }}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>

          </table>
        </div>


      </div>
    </div>
  </div>
  <!-- Modal Add & Edit Unit -->
  <div class="modal fade" id="modal_unit" tabindex="-1" role="dialog" aria-labelledby="modal_unit" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
          <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
  
          <form @submit.prevent="editmode?Update_Unit():Create_Unit()" enctype="multipart/form-data">
            <div class="row">
  
              <div class="form-group col-md-12">
                <label for="name">{{ __('translate.Title') }} <span class="field_required">*</span></label>
                <input type="text" v-model="unit.name" class="form-control" name="name" id="name"
                  placeholder="{{ __('translate.Enter_Name_Unit') }}">
                <span class="error" v-if="errors && errors.name">
                  @{{ errors.name[0] }}
                </span>
              </div>
  
              <div class="form-group col-md-12">
                <label for="ShortName">{{ __('translate.ShortName') }} <span class="field_required">*</span></label>
                <input type="text" v-model="unit.ShortName" class="form-control" name="ShortName" id="ShortName"
                  placeholder="{{ __('translate.Enter_ShortName_Unit') }}">
                <span class="error" v-if="errors && errors.ShortName">
                  @{{ errors.ShortName[0] }}
                </span>
              </div>
  
              <div class="form-group col-md-12">
                <label>{{ __('translate.Base_Unit') }} </label>
                <v-select @input="Selected_Base_Unit" placeholder="{{ __('translate.Choose_Base_Unit') }}"
                  v-model="unit.base_unit" :reduce="label => label.value"
                  :options="units_base.map(units_base => ({label: units_base.name, value: units_base.id}))">
                </v-select>
                <span class="error" v-if="errors && errors.base_unit">
                  @{{ errors.base_unit[0] }}
                </span>
              </div>
  
              <div class="form-group col-md-12" v-show="show_operator">
                <label>{{ __('translate.Operator') }} </label>
                <v-select placeholder="{{ __('translate.Choose_Operator') }}" v-model="unit.operator"
                  :reduce="label => label.value" :options="
                                       [
                                           {label: 'Multiply (*)', value: '*'},
                                           {label: 'Divide (/)', value: '/'},
                                       ]">
                </v-select>
                <span class="error" v-if="errors && errors.operator">
                  @{{ errors.operator[0] }}
                </span>
              </div>
  
              <div class="form-group col-md-12" v-show="show_operator">
                <label for="operator_value">{{ __('translate.Operation_Value') }}<span
                    class="field_required">*</span></label>
                <input type="text" v-model="unit.operator_value" class="form-control" name="operator_value"
                  id="operator_value" placeholder="{{ __('translate.Enter_Operation_Value') }}">
                <span class="error" v-if="errors && errors.operator_value">
                  @{{ errors.operator_value[0] }}
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
</div>


@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>
<script src="{{asset('assets/js/nprogress.js')}}"></script>

<script type="text/javascript">
  $(function () {
      "use strict";

        $(document).ready(function () {
          //init datatable
          Unit_datatable();
        });

        //Get Data
        function Unit_datatable(){
            var table = $('#unit_table').DataTable({
                processing: true,
                serverSide: true,
                "order": [[ 0, "desc" ]],
                'columnDefs': [
                  {
                      'targets': [0],
                      'visible': false,
                      'searchable': false,
                  },
                ],
                ajax: "{{ route('units.index') }}",
                columns: [
                    {data: 'id', name: 'id',className: "d-none"},
                    {data: 'name', name: 'name'},
                    {data: 'ShortName', name: 'ShortName'},
                    {data: 'base_unit_name', name: 'base_unit_name'},
                    {data: 'operator_value', name: 'operator_value'},
                    {data: 'operator', name: 'operator'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                
                ],
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
                          {
                            extend: 'print',
                            text: 'print',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                          },
                          {
                            extend: 'pdf',
                            text: 'pdf',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                          },
                          {
                            extend: 'excel',
                            text: 'excel',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                          },
                          {
                            extend: 'csv',
                            text: 'csv',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                          },
                        ]
                    }]
            });
        }

        // event reload Datatatble
        $(document).bind('event_unit', function (e) {
            $('#modal_unit').modal('hide');
            $('#unit_table').DataTable().destroy();
            Unit_datatable();
        });


         //Create Unit
         $(document).on('click', '.new_unit', function () {
            NProgress.start();
            NProgress.set(0.1);
            app.editmode = false;
            app.reset_Form();
            app.show_operator = false;
            app.Get_Data_Create();
            setTimeout(() => {
                NProgress.done()
                $('#modal_unit').modal('show');
            }, 500);
        });

        //Edit Unit
        $(document).on('click', '.edit', function () {

            NProgress.start();
            NProgress.set(0.1);
            app.editmode = true;
            app.reset_Form();
            var id = $(this).attr('id');
            app.Get_Data_Edit(id);
           
            setTimeout(() => {
                NProgress.done()
                $('#modal_unit').modal('show');
            }, 1000);
        });

        //Delete Unit
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Unit(id);
        });
    });
</script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)
        var app = new Vue({
        el: '#section_Units_list',
        data: {
            SubmitProcessing:false,
            errors:[],
            units: [],
            units_base:[],
            editmode: false,
            show_operator: false,
            unit: {
                id: "",
                name: "",
                ShortName: "",
                base_unit: "",
                base_unit_name: "",
                operator: "*",
                operator_value: 1
            }
        },
       
        methods: {

        
            Selected_Base_Unit(value) {
                if (value == null) {
                    this.show_operator = false;
                } else {
                    this.show_operator = true;
                }
            },


            //---------------------- Get_Data_Create  ------------------------------\\
            Get_Data_Create() {
                axios
                .get("/products/units/create")
                .then(response => {
                    this.units_base   = response.data.units_base;
                })
                .catch(error => {
                    
                });
            },
          
            //---------------------- Get_Data_Edit  ------------------------------\\
            Get_Data_Edit(id) {
                axios
                .get("/products/units/"+id+"/edit")
                .then(response => {
                    this.unit   = response.data.unit;
                    this.units_base   = response.data.units_base;
                    if (this.unit.base_unit == null) {
                        this.show_operator = false;
                    } else {
                        this.show_operator = true;
                    }
                })
                .catch(error => {
                    
                });
            },

             //------------------------------ reset Form ------------------------------\\
            reset_Form() {
                this.unit = {
                    id: "",
                    name: "",
                    ShortName: "",
                    base_unit: "",
                    base_unit_name: "",
                    operator: "*",
                    operator_value: 1
                };
                this.errors = {};
            },

            //---------------------------------------- Set To Strings-------------------------\\
            setToStrings() {
                // Simply replaces null values with strings=''
                if (this.unit.base_unit === null) {
                    this.unit.base_unit = "";
                }
            },

            //------------------------ Create_Unit---------------------------\\
            Create_Unit() {
                var self = this;
                self.SubmitProcessing = true;
                self.setToStrings();
                axios
                .post("/products/units", {
                    name: self.unit.name,
                    ShortName: self.unit.ShortName,
                    base_unit: self.unit.base_unit,
                    operator: self.unit.operator,
                    operator_value: self.unit.operator_value
                    })
                    .then(response => {
                        self.SubmitProcessing = false;
                        $.event.trigger('event_unit');
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

           //----------------------- Update_Unit ---------------------------\\
           Update_Unit(){
                var self = this;
                self.SubmitProcessing = true;
                self.setToStrings();
                axios.put("/products/units/" + self.unit.id, {
                    name: self.unit.name,
                    ShortName: self.unit.ShortName,
                    base_unit: self.unit.base_unit,
                    operator: self.unit.operator,
                    operator_value: self.unit.operator_value
                })
                .then(response => {
                        self.SubmitProcessing = false;
                        $.event.trigger('event_unit');
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

             //--------------------------------- Remove_Unit ---------------------------\\
             Remove_Unit(id) {

                swal({
                    title: '{{ __('translate.Are_you_sure') }}',
                    text: '{{ __('translate.You_wont_be_able_to_revert_this') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0CC27E',
                    cancelButtonColor: '#FF586B',
                    confirmButtonText: '{{ __('translate.Yes_delete_it') }}',
                    cancelButtonText: '{{ __('translate.No_cancel') }}',
                    confirmButtonClass: 'btn btn-primary me-5',
                    cancelButtonClass: 'btn btn-danger',
                    buttonsStyling: false
                }).then(function () {
                    axios.delete("/products/units/" + id)
                    .then(response => {
                        if (response.data.success) {
                            toastr.success('{{ __('translate.Deleted_in_successfully') }}');
                            $.event.trigger('event_unit');
                        } else {
                            toastr.error('{{ __('translate.Unit_already_linked_with_sub_unit') }}');
                        }
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



@endsection