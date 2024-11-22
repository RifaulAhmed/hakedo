@extends('setup.main')
@section('content')
<div class="row">
   <div class="col-12 text-center mt-3">
      <ul class="progressbar">
        <li class="active"><a href="/setup">{{ __('translate.Server_Requirements') }}</a></li>
         <li class="active"><a href="/setup/step-1">{{ __('translate.Settings') }}</a></li>
         <li class="active"> <a href="/setup/step-2">{{ __('translate.Database') }}</a></li>
         <li>{{ __('translate.Summary') }}</li>
      </ul>
   </div>
</div>
<div class="row mt-3 p-5">
   <div class="col-12">
      <form id="dbform" action="{{route('setupStep2')}}" method="post">
         @csrf
         <div id="errormsg"></div>
            <div id="db_settings" class="form-group"></div>
                <label for="app_env">{{ __('translate.Select_Database_Type') }}</label>
                <span class="tip" title="{{ __('translate.Select_Database_Type') }}">
                    <i class="fa fa-question-circle" aria-hidden="true"></i></span>
                <select class="form-control" id="db_connection" name="db_connection">
                    <option value="mysql">{{ __('translate.Mysql') }}</option>
                </select>
                <label for="app_name" class="mt-1" id="db_host_label">{{ __('translate.DB_Host') }}</label>
                    <span class="tip" id="db1tooltip" title="{{ __('translate.DB_Host') }}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                    </span>
                <input type="text" class="form-control" id="db_host" name="db_host" placeholder="127.0.0.1"  required="" value="{{$data["DB_HOST"]}}">
               
               
                <label for="app_name" class="mt-1" id="db_port_label">{{ __('translate.DB_Port') }}</label>
                     <span class="tip" id="db2tooltip" title="{{ __('translate.The port on which your database is running') }}">
                         <i class="fa fa-question-circle" aria-hidden="true"></i>
                    </span>
            <input type="text" class="form-control" id="db_port" name="db_port" placeholder="3306" required="" value="{{$data["DB_PORT"]}}">
           
           
            <label for="app_name" class="mt-1" id="db_database_label">{{ __('translate.DB_Database') }}</label> 
                <span class="tip" title="{{ __('translate.Database_Name') }}">
                    <i class="fa fa-question-circle" aria-hidden="true"></i>
                </span>
            <input type="text" class="form-control" id="db_database" name="db_database" placeholder="{{ __('translate.Database_Name') }}" required="">
            
            
            <label for="app_name" class="mt-1" id="db_username_label">{{ __('translate.DB_Username') }}</label> 
                <span class="tip" id="db3tooltip" title="{{ __('translate.DB_Username') }}">
                    <i class="fa fa-question-circle" aria-hidden="true"></i>
                 </span>
            <input type="text" class="form-control" id="db_username" name="db_username" placeholder="{{ __('translate.DB_Username') }}" required="" value="{{$data["DB_USERNAME"]}}">
           
           
            <label for="app_name" class="mt-1" id="db_password_label">{{ __('translate.DB_Password') }}</label> 
                <span class="tip"  id="db4tooltip"title="{{ __('translate.DB_Password') }}">
                    <i class="fa fa-question-circle" aria-hidden="true"></i>
                </span>
             <input type="text" class="form-control" id="db_password" name="db_password" placeholder="{{ __('translate.DB_Password') }}" required="" value="{{$data["DB_PASSWORD"]}}">
           
           
             <a id="testdb" class="btn btn-dark mb-2 form-control mt-2 text-white">{{ __('translate.Test_Connection') }}
                <i class="fa fa-question-circle-o "></i></a>
           
                <div class="row">
                <div class="col-12 col-md-6">
                 <a href="/setup/step-1" class="btn btn-outline-danger mb-2"  ><i class="fa fa-angle-left"></i>{{ __('translate.Previous_Step') }} </a>
                </div>
                <div class="col-12 col-md-6">
                <button type="submit" class="btn btn-outline-danger mb-2  float-md-right next_step d-none">{{ __('translate.Next_Step') }} <i class="fa fa-angle-right"></i></button>
                </div>
            </div>
        </form>
    </div>
    </div>
</div>
@endsection
