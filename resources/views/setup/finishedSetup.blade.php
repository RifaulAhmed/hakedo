@extends('setup.main')
@section('content')

<div class="row mt-3 p-5">
    <div class="col-12 text-center">
        <div class="col-12 mb-2"><i  class="fa fa-check-circle fa-4x text-success" aria-hidden="true"></i> <h1>{{ __('translate.Setup_complete') }}</h1></div>
        <div class="col-12 mb-2"><a href="/">{{ __('translate.Click_here') }} </a> {{ __('translate.to_get_back_to_your_project') }}</div>
    </div>
</div>
@endsection
