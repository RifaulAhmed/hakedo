@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.All_Brands') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_brand_list">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="text-end mb-3">
          <a class="new_brand btn btn-outline-primary btn-md m-1"><i class="i-Add me-2 font-weight-bold"></i>
            {{ __('translate.Create') }}</a>
        </div>

        <div class="table-responsive">
          <table id="brand_table" class="display table">
            <thead>
              <tr>
                <th>ID</th>
                <th>{{ __('translate.Image') }}</th>
                <th>{{ __('translate.Name') }}</th>
                <th>{{ __('translate.Description') }}</th>
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
  <!-- Modal Add & Edit brand -->
  <div class="modal fade" id="modal_brand" tabindex="-1" role="dialog" aria-labelledby="modal_brand" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
          <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
  
          <form @submit.prevent="editmode?Update_Brand():Create_Brand()" enctype="multipart/form-data">
            <div class="row">
  
              <div class="form-group col-md-12">
                <label for="name">{{ __('translate.Name_of_Brand') }} <span class="field_required">*</span></label>
                <input type="text" v-model="brand.name" class="form-control" name="name" id="name"
                  placeholder="{{ __('translate.Enter_Name_Brand') }}">
                <span class="error" v-if="errors && errors.name">
                  @{{ errors.name[0] }}
                </span>
              </div>
  
              <div class="form-group col-md-12">
                <label for="Description">{{ __('translate.Please_provide_any_details') }}
                </label>
                <textarea type="text" v-model="brand.description" class="form-control" name="Description" id="Description"
                  placeholder="{{ __('translate.Enter_description') }}"></textarea>
              </div>
  
              <div class="form-group col-md-12">
                <label for="image">{{ __('translate.Image') }}</label>
                <input name="image" @change="onFileSelected" type="file" class="form-control" id="image">
                <span class="error" v-if="errors && errors.image">
                  @{{ errors.image[0] }}
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
          Brand_datatable();
        });
  
          //Get Data
          function Brand_datatable(){
              var table = $('#brand_table').DataTable({
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

                  ajax: "{{ route('brands.index') }}",
                  columns: [
                      {data: 'id' , name: 'id', className: "d-none"},
                      {data: 'image', name: 'image'},
                      {data: 'name', name: 'name'},
                      {data: 'description', name: 'description'},
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
          $(document).bind('event_brand', function (e) {
              $('#modal_brand').modal('hide');
              $('#brand_table').DataTable().destroy();
              Brand_datatable();
          });
  
  
           //Create brand
           $(document).on('click', '.new_brand', function () {
              app.editmode = false;
              app.reset_Form();
              $('#modal_brand').modal('show');
          });
  
          //Edit brand
          $(document).on('click', '.edit', function () {
              NProgress.start();
              NProgress.set(0.1);
              app.editmode = true;
              app.reset_Form();
              var id = $(this).attr('id');
              app.Get_Data_Edit(id);
             
              setTimeout(() => {
                  NProgress.done()
                  $('#modal_brand').modal('show');
              }, 500);
          });
  
          //Delete brand
          $(document).on('click', '.delete', function () {
              var id = $(this).attr('id');
              app.Delete_Brand(id);
          });
      });
</script>

<script>
  var app = new Vue({
          el: '#section_brand_list',
          data: {
              editmode: false,
              SubmitProcessing:false,
              data: new FormData(),
              errors:[],
              brands: [], 
              brand: {
                  id: "",
                  name: "",
                  description: "",
                  image: ""
              }
          },
         
          methods: {
  
            
              onFileSelected(e){
                  let file = e.target.files[0];
                  this.brand.image = file;
              },
  
              //--------------------------- reset Form ----------------\\
              reset_Form() {
                  this.brand = {
                      id: "",
                      name: "",
                      description: "",
                      image: ""
                  };
                  this.data = new FormData();
                  this.errors = {};
              },
            
              //---------------------- Get_Data_Edit  ------------------------------\\
              Get_Data_Edit(id) {
                  axios
                  .get("/products/brands/"+id+"/edit")
                  .then(response => {
                      this.brand   = response.data.brand;
                      this.brand.image = "";
                  })
                  .catch(error => {
                      
                  });
              },
  
              //------------------------ Create_Brand---------------------------\\
              Create_Brand() {
                  var self = this;
                  self.SubmitProcessing = true;
                  self.data.append("name", self.brand.name);
                  self.data.append("description", self.brand.description);
                  self.data.append("image", self.brand.image);
                  axios
                      .post("/products/brands", self.data)
                      .then(response => {
                          self.SubmitProcessing = false;
                          $.event.trigger('event_brand');
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
  
             //---------------------------------------- Update Brand-----------------\\
              Update_Brand() {
                  var self = this;
                  self.SubmitProcessing = true;
                  self.data.append("name", self.brand.name);
                  self.data.append("description", self.brand.description);
                  self.data.append("image", self.brand.image);
                  self.data.append("_method", "put");
  
                  axios
                      .post("/products/brands/" + self.brand.id, self.data)
                      .then(response => {
                          self.SubmitProcessing = false;
                          $.event.trigger('event_brand');
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
  
               //--------------------------------- Delete_Brand ---------------------------\\
               Delete_Brand(id) {
  
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
                          axios
                              .delete("/products/brands/" + id)
                              .then(() => {
                                  $.event.trigger('event_brand');
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



@endsection