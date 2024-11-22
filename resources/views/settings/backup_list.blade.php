@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.Backup') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>


<div class="row" id="section_backup_list">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="text-end mb-3">


                    <a :disabled="SubmitProcessing" id="generate_backup" @click="generate_backup"
                        class="btn btn-outline-primary btn-md m-1">
                        <span v-if="SubmitProcessing" class="spinner-border spinner-border-sm" role="status"
                            aria-hidden="true"></span> <i class="i-Yes me-2 font-weight-bold"></i>
                        {{ __('translate.Generate_backup') }}</a>
                </div>
                <div class="alert alert-danger"> {{ __('translate.You_will_find_your_backups_on') }} <strong>/storage/app/public/backup</strong> {{ __('translate.and_save_it_to_your_pc') }}</div>

                <div class="table-responsive">
                    <table id="ul-contact-list" class="display table">
                        <thead>
                            <tr>
                                <th>{{ __('translate.Date') }}</th>
                                <th>{{ __('translate.File_size') }}</th>
                                <th>{{ __('translate.Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>


                    </table>

                </div>


            </div>
        </div>
    </div>
</div>

@endsection

@section('page-js')

<script src="{{asset('assets/js/vendor/datatables.min.js')}}"></script>

<script type="text/javascript">
    $(function () {
        "use strict";

        $(document).ready(function () {
          //init datatable
          backup_datatable();
        });

        function backup_datatable(){
            var dataTable = $('#ul-contact-list').DataTable({
                buttons: [],
                pageLength: 10,
                "order": [[ 0, "desc" ]],
                ajax: {
                    url: "/settings/backup",
                },

                columns: [
                    {data: 'date', name: 'Date'},
                    {data: 'size', name: 'File Size'},
                    {data: 'action', name: 'action'},
                ],
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
            });
        }

        
          //Remove_Backup
          $(document).on('click', '.delete', function () {
              var id = $(this).attr('id');
              app.Remove_Backup(id);
          });
       
    });
</script>
<script>
    var app = new Vue({
        el: '#section_backup_list',
        data: {
            SubmitProcessing:false,
            errors:[],
           
        },
       
        methods: {

     
            //------------------------ generate_backup ---------------------------\\
            generate_backup() {
                var self = this;
                self.SubmitProcessing = true;
                axios.get("/GenerateBackup").then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/settings/backup'; 
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

            //--------------------------------- Remove Backup ---------------------------\\
            Remove_Backup(id) {

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
                            .delete("/settings/backup/" + id)
                            .then(() => {
                                window.location.href = '/settings/backup'; 
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