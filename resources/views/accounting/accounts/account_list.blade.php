@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Account_List') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_Account_list">
    <div class="col-lg-12 mb-3">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
               <div class="text-end mb-3">
                  @can('account_add')
                  <a class="btn btn-outline-primary btn-md m-1" href="{{route('account.create')}}"><i
                      class="i-Add me-2 font-weight-bold"></i> {{ __('translate.Create') }}</a>
                  @endcan
                </div>
                  <div class="table-responsive">
                    <table id="account_table" class="display table ">
                      <thead>
                        <tr>
                          <th>{{ __('translate.Account_Name') }}</th>
                          <th>{{ __('translate.Account_Num') }}</th>
                          <th>{{ __('translate.Balance') }}</th>
                          <th>{{ __('translate.Action') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($accounts as $account)
                        <tr>
                          <td>{{$account->account_name}}</td>
                          <td>{{$account->account_num}}</td>
                          <td>{{$account->initial_balance}}</td>
                          <td>
                            @can('account_edit')
                            <a href="/accounting/account/{{$account->id}}/edit" class="cursor-pointer ul-link-action text-success"
                              data-toggle="tooltip" data-placement="top" title="Edit">
                              <i class="i-Edit"></i>
                            </a>
                            @endcan
                            @can('account_delete')
                            <a @click="Remove_Account( {{ $account->id}})" class="cursor-pointer ul-link-action text-danger mr-1"
                              data-toggle="tooltip" data-placement="top" title="Delete">
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
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>

<script>
  var app = new Vue({
        el: '#section_Account_list',
        data: {
            SubmitProcessing:false,
        },
       
        methods: {

            //--------------------------------- Remove Account ---------------------------\\
            Remove_Account(id) {

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
                            .delete("/accounting/account/" + id)
                            .then(() => {
                                window.location.href = '/accounting/account'; 
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

        $('#account_table').DataTable( {
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
                          {
                            extend: 'print',
                            text: 'Print',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                                return 'Account List';
                            },
                          },
                          {
                            extend: 'pdf',
                            text: 'Pdf',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                                return 'Account List';
                            },
                           
                        },
                          {
                            extend: 'excel',
                            text: 'Excel',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                              return 'Account List';
                            },
                          },
                          {
                            extend: 'csv',
                            text: 'Csv',
                            exportOptions: {
                                columns: ':visible:Not(.not_show)',
                                rows: ':visible'
                            },
                            title: function(){
                              return 'Account List';
                            },
                          },
                        ]
                }],
        });
        
    });
</script>
@endsection