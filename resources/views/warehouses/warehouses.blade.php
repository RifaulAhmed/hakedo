@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Warehouses') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_Warehouse_list">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="text-end  mb-3">
          <a class="new_Warehouse btn btn-outline-primary btn-md m-1"><i class="i-Add me-2 font-weight-bold"></i>
            {{ __('translate.Create') }}</a>
        </div>

        <div class="table-responsive">
          <table id="warehouse_table" class="display table">
            <thead>
              <tr>
                <th>ID</th>
                <th>{{ __('translate.Name') }}</th>
                <th>{{ __('translate.Phone') }}</th>
                <th>{{ __('translate.Country') }}</th>
                <th>{{ __('translate.City') }}</th>
                <th>{{ __('translate.Email') }}</th>
                <th>{{ __('translate.ZipCode') }}</th>
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
  <!-- Modal Add & Edit warehouse -->
  <div class="modal fade" id="modal_warehouse" tabindex="-1" role="dialog" aria-labelledby="modal_warehouse"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 v-if="editmode" class="modal-title">{{ __('translate.Edit') }}</h5>
          <h5 v-else class="modal-title">{{ __('translate.Create') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <form @submit.prevent="editmode?Update_Warehouse():Create_Warehouse()" enctype="multipart/form-data">
            <div class="row">

              <div class="form-group col-md-6">
                <label for="name">{{ __('translate.Name') }} <span class="field_required">*</span></label>
                <input type="text" v-model="warehouse.name" class="form-control" name="name" id="name"
                  placeholder="{{ __('translate.Enter_Name_Warehouse') }}">
                <span class="error" v-if="errors && errors.name">
                  @{{ errors.name[0] }}
                </span>
              </div>

              <div class="form-group col-md-6">
                <label for="mobile">{{ __('translate.Phone') }} </label>
                <input type="text" v-model="warehouse.mobile" class="form-control" name="mobile" id="mobile"
                  placeholder="{{ __('translate.Enter_Phone_Warehouse') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="country">{{ __('translate.Country') }} </label>
                <input type="text" v-model="warehouse.country" class="form-control" name="country" id="country"
                  placeholder="{{ __('translate.Enter_Country_Warehouse') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="city">{{ __('translate.City') }} </label>
                <input type="text" v-model="warehouse.city" class="form-control" name="city" id="city"
                  placeholder="{{ __('translate.Enter_City_Warehouse') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="email">{{ __('translate.Email') }} </label>
                <input type="text" v-model="warehouse.email" class="form-control" name="email" id="email"
                  placeholder="{{ __('translate.Enter_Email_Warehouse') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="zip">{{ __('translate.ZipCode') }} </label>
                <input type="text" v-model="warehouse.zip" class="form-control" name="zip" id="zip"
                  placeholder="{{ __('translate.Enter_ZipCode_Warehouse') }}">
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
      warehouse_datatable();
    });

    //Get Data
    function warehouse_datatable() {
      var table = $('#warehouse_table').DataTable({
        processing: true,
        serverSide: true,
        "order": [[0, "desc"]],
        'columnDefs': [
          {
            'targets': [0],
            'visible': false,
            'searchable': false,
          },
        ],
        ajax: "{{ route('warehouses.index') }}",
        columns: [
          { data: 'id', name: 'id', className: "d-none" },
          { data: 'name', name: 'name' },
          { data: 'mobile', name: 'mobile' },
          { data: 'country', name: 'country' },
          { data: 'city', name: 'city' },
          { data: 'email', name: 'email' },
          { data: 'zip', name: 'zip' },
          { data: 'action', name: 'action', orderable: false, searchable: false },

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
    $(document).bind('event_warehouse', function (e) {
      $('#modal_warehouse').modal('hide');
      $('#warehouse_table').DataTable().destroy();
      warehouse_datatable();
    });


    //Create warehouse
    $(document).on('click', '.new_Warehouse', function () {
      app.editmode = false;
      app.reset_Form();
      $('#modal_warehouse').modal('show');
    });

    //Edit warehouse
    $(document).on('click', '.edit', function () {
      NProgress.start();
      NProgress.set(0.1);
      app.editmode = true;
      app.reset_Form();
      var id = $(this).attr('id');
      app.Get_Data_Edit(id);

      setTimeout(() => {
        NProgress.done()
        $('#modal_warehouse').modal('show');
      }, 500);
    });

    //Delete warehouse
    $(document).on('click', '.delete', function () {
      var id = $(this).attr('id');
      app.Remove_warehouse(id);
    });
  });
</script>

<script>
  var app = new Vue({
    el: '#section_Warehouse_list',
    data: {
      editmode: false,
      SubmitProcessing: false,
      errors: [],
      warehouses: [],
      warehouse: {
        id: "",
        name: "",
        mobile: "",
        email: "",
        zip: "",
        country: "",
        city: ""
      }
    },

    methods: {


      //--------------------------- reset Form ----------------\\
      reset_Form() {
        this.warehouse = {
          id: "",
          name: "",
          mobile: "",
          email: "",
          zip: "",
          country: "",
          city: ""
        };

        this.errors = {};
      },

      //---------------------- Get_Data_Edit  ------------------------------\\
      Get_Data_Edit(id) {
        axios
          .get("/products/warehouses/" + id + "/edit")
          .then(response => {
            this.warehouse = response.data.warehouse;
          })
          .catch(error => {

          });
      },

      //------------------------ Create_Warehouse---------------------------\\
      Create_Warehouse() {
        var self = this;
        self.SubmitProcessing = true;
        axios
          .post("/products/warehouses", {
            name: this.warehouse.name,
            mobile: this.warehouse.mobile,
            email: this.warehouse.email,
            zip: this.warehouse.zip,
            country: this.warehouse.country,
            city: this.warehouse.city
          })
          .then(response => {
            self.SubmitProcessing = false;
            $.event.trigger('event_warehouse');
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

      //----------------------- Update_Warehouse ---------------------------\\
      Update_Warehouse() {
        var self = this;
        self.SubmitProcessing = true;
        axios
          .put("/products/warehouses/" + this.warehouse.id, {
            name: this.warehouse.name,
            mobile: this.warehouse.mobile,
            email: this.warehouse.email,
            zip: this.warehouse.zip,
            country: this.warehouse.country,
            city: this.warehouse.city
          })
          .then(response => {
            self.SubmitProcessing = false;
            $.event.trigger('event_warehouse');
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

      //--------------------------------- Remove_warehouse ---------------------------\\
      Remove_warehouse(id) {

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
            .delete("/products/warehouses/" + id)
            .then(() => {
              $.event.trigger('event_warehouse');
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