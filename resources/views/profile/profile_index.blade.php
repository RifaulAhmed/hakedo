@extends('layouts.master')
@section('main-content')
@section('page-css')
@endsection

<div class="breadcrumb">
    <h1>{{ __('translate.User_Profile') }}</h1>
</div>

<div class="separator-breadcrumb border-top"></div>

<div id="section_Profile_list">
    <div class="card profile-widget mt-5">
        <div class="profile-widget-header">
            <img class="rounded-circle profile-widget-picture"
                src="{{asset('images/avatar/'.Auth::user()->avatar)}}" alt="">
        </div>
        <div class="card-body profile-widget-description">
            <div class="profile-widget-name">@{{ user.username }}</div>
            <form @submit.prevent="Update_Profile()" enctype="multipart/form-data">
                <div class="row">

                    <div class="form-group col-md-6">
                        <label for="username" class="ul-form__label">{{ __('translate.FullName') }} <span
                                class="field_required">*</span></label>
                        <input type="text" v-model="user.username" class="form-control" id="username"
                            placeholder="{{ __('translate.Enter_FullName') }}">
                        <span class="error" v-if="errors && errors.username">
                            @{{ errors.username[0] }}
                        </span>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="email" class="ul-form__label">{{ __('translate.Email') }} <span
                                class="field_required">*</span></label>
                        <input type="text" v-model="user.email" class="form-control" id="email"
                            placeholder="{{ __('translate.Enter_email_address') }}">
                        <span class="error" v-if="errors && errors.email">
                            @{{ errors.email[0] }}
                        </span>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="Avatar" class="ul-form__label">{{ __('translate.Avatar') }}</label>
                        <input name="Avatar" @change="changeAvatar" type="file" class="form-control" id="Avatar">
                        <span class="error" v-if="errors && errors.avatar">
                            @{{ errors.avatar[0] }}
                        </span>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="password" class="ul-form__label">{{ __('translate.Password') }} <span
                                class="field_required">*</span></label>
                        <input type="password" v-model="user.password" class="form-control" id="password"
                            placeholder="{{ __('translate.min_6_characters') }}">
                        <span class="error" v-if="errors && errors.password">
                            @{{ errors.password[0] }}
                        </span>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="password_confirmation" class="ul-form__label">{{ __('translate.Repeat_Password') }}
                            <span class="field_required">*</span></label>
                        <input type="password" v-model="user.password_confirmation" class="form-control"
                            id="password_confirmation" placeholder="{{ __('translate.Repeat_Password') }}">
                        <span class="error" v-if="errors && errors.password_confirmation">
                            @{{ errors.password_confirmation[0] }}
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

@endsection

@section('page-js')

<script>
    var app = new Vue({
        el: '#section_Profile_list',
        data: {
            data: new FormData(),
            SubmitProcessing:false,
            errors:[],
            user: @json($user),
        },
       
        methods: {


            changeAvatar(e){
                let file = e.target.files[0];
                this.user.avatar = file;
            },


           //----------------------- Update Profile ---------------------------\\
           Update_Profile() {
                var self = this;
                self.SubmitProcessing = true;
                self.data.append("username", self.user.username);
                self.data.append("email", self.user.email);
                self.data.append("password", self.user.password);
                self.data.append("password_confirmation", self.user.password_confirmation);
                self.data.append("avatar", self.user.avatar);
                self.data.append("_method", "put");

                axios
                    .post("/updateProfile/" + self.user.id, self.data)
                    .then(response => {
                        self.SubmitProcessing = false;
                        window.location.href = '/profile'; 
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
           
        },
        //-----------------------------Autoload function-------------------
        created() {
        }

    })

</script>

@endsection