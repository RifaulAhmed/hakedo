@extends('layouts.master')
@section('main-content')
@section('page-css')
<link rel="stylesheet" href="{{asset('assets/styles/vendor/datatables.min.css')}}">
<link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">
@endsection

<div class="breadcrumb">
  <h1>{{ __('translate.User_Controller') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="section_User_list">
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-12">
          @can('user_add')
          <div class="text-end mb-3">
            <a class="new_user btn btn-outline-primary btn-md m-1" @click="New_User"><i class="i-Add me-2 font-weight-bold"></i></i>
              {{ __('translate.Create') }}</a>
          </div>
          @endcan
          <div class="table-responsive">
            <table id="ul-contact-list" class="display table">
              <thead>
                <tr>
                  <th>{{ __('translate.Avatar') }}</th>
                  <th>{{ __('translate.Username') }}</th>
                  <th>{{ __('translate.Email') }}</th>
                  <th>{{ __('translate.Status') }}</th>
                  <th>{{ __('translate.Role') }}</th>
                  <th>{{ __('translate.Assign_Role') }}</th>
                  <th>{{ __('translate.Action') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($users as $user)
                <tr>
                  <td>
                    <div class="avatar me-2 avatar-md">
                      <img src="{{ asset('images/avatar/'.$user->avatar) }}" alt="">
                    </div>
                  </td>
                  <td>{{$user->username}}</td>
                  <td>{{$user->email}}</td>
                  <td>
                    @if($user->status)
                    <span class="badge badge-success m-2">{{ __('translate.Active') }}</span>
                    @else
                    <span class="badge badge-danger m-2">{{ __('translate.Inactive') }}</span>
                    @endif
                  </td>
                  <td>{{$user->RoleUser['name']}}</td>
                  @can('group_permission')
                  @if($user->role_users_id === 1 || $user->role_users_id === 2)
                  <td>{{ __('translate.Cannot_change_Default_Permissions') }}</td>
                  @else
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-primary btn-sm m-1 dropdown-toggle" type="button" id="assignRole"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ __('translate.Assign_Role') }}
                      </button>
                      <div class="dropdown-menu" aria-labelledby="assignRole">
                        @foreach ($roles as $role)
                        <a class="dropdown-item cursor-pointer"
                          @click="assignRole( {{ $user->id}} , {{ $role->id}})">{{$role->name}}</a>
                        @endforeach
                      </div>
                    </div>
                  </td>
                  @endif

                  @endcan

                  <td>
                    @can('user_edit')
                    <a @click="Edit_User( {{ $user}})" class="cursor-pointer text-success ul-link-action" data-toggle="tooltip"
                      data-placement="top" title="Edit">
                      <i class="i-Edit"></i>
                    </a>
                    @endcan

                    @if (auth()->user()->can('user_delete') && Auth::user()->id !== $user->id)
                    <a @click="Remove_User( {{ $user->id}})" class="cursor-pointer text-danger me-1 ul-link-action"
                      data-toggle="tooltip" data-placement="top" title="Delete">
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
    </div>
  </div>

  <!-- Modal Add & Edit User -->
  <div class="modal fade" id="user_Modal" tabindex="-1" role="dialog" aria-labelledby="user_Modal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 v-if="editmode" class="modal-title" id="user_Modal">{{ __('translate.Edit') }}</h5>
          <h5 v-else class="modal-title" id="user_Modal">{{ __('translate.Create') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <form @submit.prevent="editmode?Update_User():Create_User()" enctype="multipart/form-data">
            <div class="row">

              <div class="form-group col-md-6">
                <label for="username">{{ __('translate.FullName') }}<span class="field_required">*</span></label>
                <input type="text" v-model="user.username" class="form-control" name="username" id="username"
                  placeholder="{{ __('translate.Enter_FullName') }}">
                <span class="error" v-if="errors && errors.username">
                  @{{ errors.username[0] }}
                </span>
              </div>

              <div class="form-group col-md-6">
                <label for="email">{{ __('translate.Email_Address') }}<span class="field_required">*</span></label>
                <input type="text" v-model="user.email" class="form-control" name="email" id="email"
                  placeholder="{{ __('translate.Enter_email_address') }}">
                <span class="error" v-if="errors && errors.email">
                  @{{ errors.email[0] }}
                </span>
              </div>

              <div class="form-group col-md-6">
                <label for="password">{{ __('translate.Password') }} <span class="field_required">*</span></label>
                <input type="password" v-model="user.password" class="form-control" id="password"
                  placeholder="{{ __('translate.min_6_characters') }}">
                <span class="error" v-if="errors && errors.password">
                  @{{ errors.password[0] }}
                </span>
              </div>

              <div class="form-group col-md-6">
                <label for="password_confirmation">{{ __('translate.Repeat_Password') }}
                  <span class="field_required">*</span></label>
                <input type="password" v-model="user.password_confirmation" class="form-control"
                  id="password_confirmation" placeholder="{{ __('translate.Repeat_Password') }}">
                <span class="error" v-if="errors && errors.password_confirmation">
                  @{{ errors.password_confirmation[0] }}
                </span>
              </div>

              <div class="form-group col-md-6" v-if="auth_user_id !== user.id">
                <label>{{ __('translate.Status') }} <span class="field_required">*</span></label>
                <v-select @input="Selected_Status" placeholder="{{ __('translate.Choose_status') }}"
                  v-model="user.status" :reduce="(option) => option.value" :options="
                                                 [
                                                     {label: 'Active', value: 1},
                                                     {label: 'Inactive', value: 0},
                                                 ]">
                </v-select>

                <span class="error" v-if="errors && errors.status">
                  @{{ errors.status[0] }}
                </span>
              </div>

              <div class="form-group col-md-6" v-if="!editmode">
                <label>{{ __('translate.Role') }} <span class="field_required">*</span></label>
                <v-select @input="Selected_Role" placeholder="{{ __('translate.Choose_Role') }}"
                  v-model="user.role_users_id" :reduce="(option) => option.value"
                  :options="roles.map(roles => ({label: roles.name, value: roles.id}))">
                </v-select>

                <span class="error" v-if="errors && errors.role_users_id">
                  @{{ errors.role_users_id[0] }}
                </span>
              </div>

              <div class="form-group col-md-6">
                <label for="Avatar">{{ __('translate.Avatar') }}</label>
                <input name="Avatar" @change="changeAvatar" type="file" class="form-control" id="Avatar">
                <span class="error" v-if="errors && errors.avatar">
                  @{{ errors.avatar[0] }}
                </span>
              </div>

              <hr/>

              <div class="form-group col-md-12">
                  <h5>{{ __('translate.Assigned_warehouses') }}</h5>
                  <div class="form-check form-check-inline w-15">
                    <input class="form-check-input" type="checkbox" id="is_all_warehouses" v-model="user.is_all_warehouses">
                    <label class="form-check-label" for="is_all_warehouses">{{ __('translate.All_Warehouses') }}</label>
                  </div>
                  <v-select
                      multiple
                      v-model="assigned_warehouses"
                      @input="Selected_Warehouse"
                      :reduce="label => label.value"
                      placeholder="{{ __('translate.PleaseSelect') }}"
                      :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))">
                  </v-select>
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

<script>
  var auth_user_id = {!! json_encode(Auth::user()->id); !!};
</script>

<script type="text/javascript">
  $(function () {
    "use strict";

      $('#ul-contact-list').DataTable( {
          "processing": true, // for show progress bar
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
  });
</script>

<script>
  Vue.component('v-select', VueSelect.VueSelect)

        var app = new Vue({
        el: '#section_User_list',
        data: {
            data: new FormData(),
            editmode: false,
            SubmitProcessing:false,
            errors:[],
            roles: @json($roles), 
            warehouses: @json($warehouses), 
            assigned_warehouses : [],
            users: {}, 
            user: {
                username: "",
                password: "",
                password_confirmation:"",
                is_all_warehouses:1,
                email: "",
                status: 1,
                avatar: "",
                role_users_id: "",
            }, 
            old_photo: '',
        },
       
        methods: {



            Selected_Status(value) {
                if (value === null) {
                    this.user.status = "";
                }
            },

            Selected_Role(value) {
                if (value === null) {
                    this.user.role_users_id = "";
                }
            },

            Selected_Warehouse(value) {
                if (!value.length) {
                    this.assigned_warehouses = [];
                }
            },


            //------------------------------ Show Modal (Create User) -------------------------------\\
            New_User() {
                this.reset_Form();
                this.editmode = false;
                this.Get_Data_Create();
                $('#user_Modal').modal('show');
            },

            //------------------------------ Show Modal (Update User) -------------------------------\\
            Edit_User(user) {
                this.editmode = true;
                this.reset_Form();
                this.Get_Data_Edit(user.id);
                setTimeout(() => {
                  $('#user_Modal').modal('show');
                }, 800);
            },

            changeAvatar(e){
                let file = e.target.files[0];
                this.user.avatar = file;
            },

            //----------------------------- Reset Form ---------------------------\\
            reset_Form() {
                this.user = {
                    id: "",
                    username: "",
                    password: "",
                    password_confirmation:"",
                    is_all_warehouses:1,
                    email: "",
                    status: 1,
                    avatar: "",
                    role_users_id: "",
                };
                this.errors = {};
                this.assigned_warehouses = [];
            },

             //---------------------- Get_Data_Create  ------------------------------\\
             Get_Data_Create() {
                axios
                    .get("/user-management/users/create")
                    .then(response => {
                        this.roles   = response.data;
                    })
                    .catch(error => {
                       
                    });
            },

               //---------------------- Get_Data_Edit  ------------------------------\\
               Get_Data_Edit(id) {
                axios
                    .get("/user-management/users/"+id+"/edit")
                    .then(response => {
                        this.user   = response.data.User;
                        this.old_photo = this.user.avatar;
                        this.user.password =  "";
                        this.user.password_confirmation = "";
                        this.assigned_warehouses   = response.data.assigned_warehouses;
                    })
                    .catch(error => {
                       
                    });
            },

            //----------------------- assignRole ---------------------------\\

            assignRole(user_id , role_id) {
                var self = this;
                axios.post("/user-management/assignRole", {
                    user_id: user_id,
                    role_id: role_id,
                }).then(response => {
                       window.location.href = '/user-management/users'; 
                        toastr.success('{{ __('translate.Updated_in_successfully') }}');
                        self.errors = {};
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            self.errors = error.response.data.errors;
                        }
                        toastr.error('{{ __('translate.There_was_something_wronge') }}');
                    });
            },

            
            //------------------------ Create User ---------------------------\\
            Create_User() {
                var self = this;
                self.SubmitProcessing = true;
                self.data.append("username", self.user.username);
                self.data.append("email", self.user.email);
                self.data.append("password", self.user.password);
                self.data.append("password_confirmation", self.user.password_confirmation);
                self.data.append("status", self.user.status);
                self.data.append("avatar", self.user.avatar);
                self.data.append("role_users_id", self.user.role_users_id);
                self.data.append("is_all_warehouses", self.user.is_all_warehouses);

                // append array assigned_warehouses
                if (self.assigned_warehouses.length) {
                  for (var i = 0; i < self.assigned_warehouses.length; i++) {
                    self.data.append("assigned_to[" + i + "]", self.assigned_warehouses[i]);
                  }
                }else{
                  self.data.append("assigned_to", []);
                }

                axios
                    .post("/user-management/users", self.data)
                    .then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/user-management/users'; 
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

           //----------------------- Update User ---------------------------\\
            Update_User() {
                var self = this;
                self.SubmitProcessing = true;
                self.data.append("username", self.user.username);
                self.data.append("email", self.user.email);
                self.data.append("password", self.user.password);
                self.data.append("password_confirmation", self.user.password_confirmation);
                self.data.append("status", self.user.status);
                self.data.append("is_all_warehouses", self.user.is_all_warehouses);

                if(self.old_photo != self.user.avatar){
                 self.data.append("avatar", self.user.avatar);
                }

                // append array assigned_warehouses
                if (self.assigned_warehouses.length) {
                  for (var i = 0; i < self.assigned_warehouses.length; i++) {
                    self.data.append("assigned_to[" + i + "]", self.assigned_warehouses[i]);
                  }
                }else{
                  self.data.append("assigned_to", []);
                }

                self.data.append("_method", "put");

                axios
                    .post("/user-management/users/" + this.user.id, self.data)
                    .then(response => {
                        self.SubmitProcessing = false;
                        $.event.trigger('event_users');
                        window.location.href = '/user-management/users'; 
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

             //--------------------------------- Remove User ---------------------------\\
            Remove_User(id) {

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
                            .delete("/user-management/users/" + id)
                            .then(() => {
                              window.location.href = '/user-management/users'; 
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