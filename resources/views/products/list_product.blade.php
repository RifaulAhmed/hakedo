@extends('layouts.master')
@section('main-content')

@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Products') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div class="row" id="section_product_list">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <div class="text-end mb-3">
          @can('products_add')
        <a href="/products/products/create" class=" btn btn-outline-primary btn-md m-1"><i
          class="i-Add me-2 font-weight-bold"></i>
        {{ __('translate.Create') }}</a>
      @endcan
          <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i
              class="i-Filter-2 me-2 font-weight-bold"></i>
            {{ __('translate.Filter') }}</a>
        </div>

        <div class="table-responsive">
          <table id="product_table" class="display table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>{{ __('translate.Image') }}</th>
                <th>{{ __('translate.type') }}</th>
                <th>{{ __('Batch Number') }}</th>
                <th>{{ __('translate.Name') }}</th>
                <th>{{ __('translate.Code') }}</th>
                <th>{{ __('translate.Category') }}</th>
                <th>{{ __('translate.Brand') }}</th>
                <th>{{ __('translate.Product_Cost') }}</th>
                <th>{{ __('translate.Product_Price') }}</th>
                <th>{{ __('translate.Current_Stock') }}</th>
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
  <!-- Modal Filter -->
  <div class="modal fade" id="filter_products_modal" tabindex="-1" role="dialog" aria-labelledby="filter_products_modal"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <form method="POST" id="filter_products">
            @csrf
            <div class="row">

              <div class="form-group col-md-6">
                <label for="code">{{ __('translate.Code_Product') }}
                </label>
                <input type="text" class="form-control" name="code" id="code"
                  placeholder="{{ __('translate.Code_Product') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="name">{{ __('translate.Product_Name') }}
                </label>
                <input type="text" class="form-control" name="name" id="product_name"
                  placeholder="{{ __('translate.Product_Name') }}">
              </div>

              <div class="form-group col-md-6">
                <label for="category_id">{{ __('translate.Category') }}
                </label>
                <select name="category_id" id="category_id" class="form-control">
                  <option value="0">{{ __('translate.All') }}</option>
                  @foreach ($categories as $category)
            <option value="{{$category->id}}">{{$category->name}}</option>
          @endforeach
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="brand_id">{{ __('translate.Brand') }}
                </label>
                <select name="brand_id" id="brand_id" class="form-control">
                  <option value="0">{{ __('translate.All') }}</option>
                  @foreach ($brands as $brand)
            <option value="{{$brand->id}}">{{$brand->name}}</option>
          @endforeach
                </select>
              </div>

            </div>

            <div class="row mt-3">

              <div class="col-md-6">
                <button type="submit" class="btn btn-primary">
                  <i class="i-Filter-2 me-2 font-weight-bold"></i> {{ __('translate.Filter') }}
                </button>
                <button id="Clear_Form" class="btn btn-danger">
                  <i class="i-Power-2 me-2 font-weight-bold"></i> {{ __('translate.Clear') }}
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
      product_datatable();
    });

    //Get Data
    function product_datatable(name = '', category_id = '', brand_id = '', code = '') {
      var table = $('#product_table').DataTable({
        processing: true,
        serverSide: true,
        "order": [[0, "desc"]],
        'columnDefs': [
          {
            'targets': [0],
            'visible': false,
            'searchable': false,
          },
          {
            'targets': [1, 2, 5, 6, 7, 8, 9, 10],
            "orderable": false,
          },
        ],

        ajax: {
          url: "{{ route('products_datatable') }}",
          data: {
            name: name === null ? '' : name,
            category_id: category_id == '0' ? '' : category_id,
            brand_id: brand_id == '0' ? '' : brand_id,
            code: code === null ? '' : code,
            "_token": "{{ csrf_token()}}"
          },
          dataType: "json",
          type: "post"
        },

        columns: [
          { data: 'id', className: "d-none" },
          { data: 'image' },
          { data: 'type' },
          { data: 'quantity' },
          { data: 'name' },
          { data: 'code' },
          { data: 'category' },
          { data: 'brand' },
          { data: 'cost' },
          { data: 'price' },
          { data: 'quantity' },
          { data: 'action' },

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

    // Clear Filter
    $('#Clear_Form').on('click', function (e) {
      var name = $('#product_name').val('');
      let category_id = $('#category_id').val('0');
      let brand_id = $('#brand_id').val('0');
      var code = $('#code').val('');

    });


    // Show Modal Filter
    $('#Show_Modal_Filter').on('click', function (e) {
      $('#filter_products_modal').modal('show');
    });


    // Submit Filter
    $('#filter_products').on('submit', function (e) {
      e.preventDefault();
      var name = $('#product_name').val();
      let category_id = $('#category_id').val();
      let brand_id = $('#brand_id').val();
      var code = $('#code').val();

      $('#product_table').DataTable().destroy();
      product_datatable(name, category_id, brand_id, code);

      $('#filter_products_modal').modal('hide');

    });

    // event reload Datatatble
    $(document).bind('event_product', function (e) {
      $('#product_table').DataTable().destroy();
      product_datatable();
    });

    //Delete Category
    $(document).on('click', '.delete', function () {
      var id = $(this).attr('id');
      app.Remove_product(id);
    });

  });
</script>

<script>
  var app = new Vue({
    el: '#section_product_list',
    data: {
      editmode: false,
      SubmitProcessing: false,
      errors: [],
      products: [],
    },

    methods: {


      //--------------------------------- Remove_product ---------------------------\\
      Remove_product(id) {

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
            .delete("/products/products/" + id)
            .then(() => {
              $.event.trigger('event_product');
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