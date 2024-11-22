@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datepicker.min.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.Adjustments') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

    <div class="row" id="section_adjustment_list">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="text-end bg-transparent mb-3">
              @can('adjustment_add')
              <a href="/adjustment/adjustments/create" class="btn btn-outline-primary btn-md m-1"><i
                  class="i-Add me-2 font-weight-bold"></i>
                {{ __('translate.Create') }}</a>
              @endcan
              <a class="btn btn-outline-success btn-md m-1" id="Show_Modal_Filter"><i class="i-Filter-2 me-2 font-weight-bold"></i>
                {{ __('translate.Filter') }}</a>
            </div>

            <div class="table-responsive">
              <table id="adjustment_table" class="display table">
                <thead>
                  <tr>
                    <th>{{ __('translate.Date') }}</th>
                    <th>{{ __('translate.Ref') }}</th>
                    <th>{{ __('translate.warehouse') }}</th>
                    <th>{{ __('translate.Total_Products') }}</th>
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
      <div class="modal fade" id="filter_adjustment_modal" tabindex="-1" role="dialog"
        aria-labelledby="filter_adjustment_modal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ __('translate.Filter') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
    
              <form method="POST" id="filter_adjustment">
                @csrf
                <div class="row">
    
                  <div class="form-group col-md-6">
                    <label for="start_date" >{{ __('translate.From_Date') }}
                    </label>
                    <input type="text" class="form-control date" name="start_date" id="start_date"
                      placeholder="{{ __('translate.From_Date') }}" value="">
                  </div>
    
                  <div class="form-group col-md-6">
                    <label for="end_date" >{{ __('translate.To_Date') }} </label>
                    <input type="text" class="form-control date" name="end_date" id="end_date"
                      placeholder="{{ __('translate.To_Date') }}" value="">
                  </div>
    
                  <div class="form-group col-md-6">
                    <label for="Ref" >{{ __('translate.Reference') }}
                    </label>
                    <input type="text" class="form-control" name="Ref" id="Ref" placeholder="{{ __('translate.Ref') }}">
                  </div>
    
                  <div class="form-group col-md-6">
                    <label for="warehouse_id" >{{ __('translate.warehouse') }} </label>
                    <select name="warehouse_id" id="warehouse_id" class="form-control">
                      <option value="0">{{ __('translate.All') }}</option>
                      @foreach ($warehouses as $warehouse)
                      <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                      @endforeach
                    </select>
                  </div>
    
    
                </div>
    
                <div class="row mt-3">
    
                  <div class="col-md-6">
                    <button type="submit" class="btn btn-outline-primary">
                      <i class="i-Filter-2 me-2 font-weight-bold"></i> {{ __('translate.Filter') }}
                    </button>
                    <button id="Clear_Form" class="btn btn-outline-danger">
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
<script src="{{asset('assets/js/datepicker.min.js')}}"></script>

<script type="text/javascript">
    $(function () {
      "use strict";

      $(document).ready(function () {

            $("#start_date,#end_date").datepicker({
                format: 'yyyy-mm-dd',
                changeMonth: true,
                changeYear: true,
                autoclose: true,
                todayHighlight: true,
            });

            var end_date = new Date();
            var start_date = new Date();

            end_date.setDate(end_date.getDate() + 365);
            $("#end_date").datepicker("setDate" , end_date);

            start_date.setDate(start_date.getDate() - 365);
            $("#start_date").datepicker("setDate" , start_date);

            //init datatable
            adjustment_datatable();
        });



        //Get Data
        function adjustment_datatable(start_date ='', end_date ='', Ref ='', warehouse_id =''){
            var table = $('#adjustment_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('adjustments.index') }}",
                    data: {
                        start_date: start_date === null?'':start_date,
                        end_date: end_date === null?'':end_date,
                        Ref: Ref === null?'':Ref,
                        warehouse_id: warehouse_id == '0'?'':warehouse_id,
                        "_token": "{{ csrf_token()}}"
                    },
                },
                columns: [
                    {data: 'date', name: 'date'},
                    {data: 'Ref', name: 'Ref'},
                    {data: 'warehouse_name', name: 'warehouse_name'},
                    {data: 'items', name: 'items'},
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

        
         // Clear Filter
         $('#Clear_Form').on('click' , function (e) {
            var end_date = new Date();
            var start_date = new Date();

            end_date.setDate(end_date.getDate() + 365);
            $("#end_date").datepicker("setDate" , end_date);

            start_date.setDate(start_date.getDate() - 365);
            $("#start_date").datepicker("setDate" , start_date);

            var Ref = $('#Ref').val('');
            let warehouse_id = $('#warehouse_id').val('0');

        });


         // Show Modal Filter
        $('#Show_Modal_Filter').on('click' , function (e) {
            $('#filter_adjustment_modal').modal('show');
        });


         // Submit Filter
        $('#filter_adjustment').on('submit' , function (e) {
            e.preventDefault();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var Ref = $('#Ref').val();
            let warehouse_id = $('#warehouse_id').val();
      
            $('#adjustment_table').DataTable().destroy();
            adjustment_datatable(start_date, end_date, Ref, warehouse_id);

            $('#filter_adjustment_modal').modal('hide');
           
        });

        // event reload Datatatble
        $(document).bind('event_adjustment', function (e) {
            $('#adjustment_table').DataTable().destroy();
            adjustment_datatable();
        });

        //Delete adjustment
        $(document).on('click', '.delete', function () {
            var id = $(this).attr('id');
            app.Remove_Adjustment(id);
        });
    });
</script>

<script>

        var app = new Vue({
        el: '#section_adjustment_list',
        data: {
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            adjustments: [], 
        },
       
        methods: {


             //--------------------------------- Remove_Adjustment ---------------------------\\
             Remove_Adjustment(id) {

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
                            .delete("/adjustment/adjustments/" + id)
                            .then(() => {
                                $.event.trigger('event_adjustment');
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