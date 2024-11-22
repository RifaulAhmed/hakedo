<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel=icon href={{ asset('images/logo.svg') }}>

    <title>Posly - Ultimate Inventory Management System with POS</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,400i,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('assets/styles/css/themes/lite-purple.min.css')}}">
</head>

<body>
    <div class="auth-layout-wrap">
        <div class="auth-content">
            <div class="card o-hidden">
                   <div class="row">
                    <div class="col-md-12">
                        <div class="p-4">
                            <div class="auth-logo text-center mb-4">
                                <img src="{{asset('images/logo.svg')}}" alt="">
                            </div>
                            <h1 class="mb-3 text-18">{{ __('translate.Forgot_Password') }}</h1>
                            <p class="text-muted">{{ __('translate.We_will_send_a_link_to_reset_your_password') }}</p>
                            <form method="POST" action="{{route('password.email')}}">
                                @csrf

                                <div class="form-group">
                                    <label for="email">{{ __('translate.Email_Address') }}</label>
                                    <input id="email" type="email" class="form-control" name="email" tabindex="1" required autofocus>
                                    @if ($errors->has('email'))
                                    <span class="text-danger">{{ $errors->first('email') }}</span>
                                @endif
                                </div>
    
                                <button type="submit" class="btn btn-primary mt-3">{{ __('translate.Send_Password_Reset_Link') }}</button>

                            </form>
                            <div class="mt-3 text-center">
                                <a class="text-muted" href="/login"><u>{{ __('translate.Sign_in') }}</u></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{asset('assets/js/common-bundle-script.js')}}"></script>

    <script src="{{asset('assets/js/script.js')}}"></script>
</body>

</html>
