@component('mail::message')

<span>{{ __('translate.You are receiving this email because we received a password reset request for your account') }}</span>

@component('mail::button', ['url' => $url])
{{ __('translate.Reset Password') }}
@endcomponent

<span>{{ __('translate.If you did not request a password reset, no further action is required') }}</span>

<span>{{ __('translate.Regards') }}<span><br>
{{ config('app.name') }}
@endcomponent