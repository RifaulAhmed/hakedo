@component('mail::message')

<h4>{{ __('translate.You are changed your password successful') }}</h4>
<span>{{ __('translate.If you did change password, no further action is required') }}</span>
<span>{{ __('translate.If you did not change password, protect your account') }}</span>

<span>{{ __('translate.Regards') }}<span><br>
{{ config('app.name') }}
@endcomponent