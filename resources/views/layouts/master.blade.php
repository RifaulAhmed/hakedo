<?php $setting = DB::table('settings')->where('deleted_at', '=', null)->first(); ?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{ asset('images/Vector.png') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse - ERP</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,400i,600,700,800,900" rel="stylesheet">
    @yield('before-css')

    {{-- theme css --}}

    {{-- App Css for custom style --}}

    <link rel="stylesheet" href="{{ asset('assets/fonts/iconsmind/iconsmind.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles/css/themes/lite-purple.min.css') }}">

    <link rel="stylesheet" href="{{asset('assets/styles/vendor/toastr.css')}}">
    <link rel="stylesheet" href="{{asset('assets/styles/vendor/vue-select.css')}}">
    <link rel="stylesheet" href="{{asset('assets/styles/vendor/sweetalert2.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/styles/vendor/nprogress.css')}}">

    {{-- axios js --}}
    <script src="{{ asset('assets/js/axios.js') }}"></script>
    {{-- vue select js --}}
    <script src="{{ asset('assets/js/vue-select.js') }}"></script>
    <script defer src="{{ asset('assets/js/compact-layout.js') }}"></script>

    {{-- Alpine Js --}}
    <script defer src="{{ asset('js/plugin-core/alpine-collapse.js') }}"></script>
    <script defer src="{{ asset('js/plugin-core/alpine.js') }}"></script>
    <script src="{{ asset('js/plugin-script/alpine-data.js') }}"></script>
    <script src="{{ asset('js/plugin-script/alpine-store.js') }}"></script>

    {{-- page specific css --}}
    @yield('page-css')
</head>

<body class="text-left">
    <!-- Pre Loader Strat  -->
    <div class='loadscreen' id="preloader">
        <div class="loader spinner-bubble spinner-bubble-primary"></div>
    </div>
    <!-- Pre Loader end  -->

    <!-- ============ Vetical SIdebar Layout start ============= -->
    @include('layouts.new-sidebar.master')
    <!-- ============ Vetical SIdebar Layout End ============= -->

    {{-- vue js --}}
    <script src="{{ asset('assets/js/vue.js') }}"></script>

    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>

    <script src="{{asset('assets/js/vee-validate.min.js')}}"></script>
    <script src="{{asset('assets/js/vee-validate-rules.min.js')}}"></script>
    <script src="{{asset('/assets/js/moment.min.js')}}"></script>

    {{-- sweetalert2 --}}
    <script src="{{asset('assets/js/vendor/sweetalert2.min.js')}}"></script>


    {{-- common js --}}
    <script src="{{ asset('assets/js/common-bundle-script.js') }}"></script>
    {{-- page specific javascript --}}
    @yield('page-js')

    <script src="{{ asset('assets/js/script.js') }}"></script>

    <script src="{{asset('assets/js/vendor/toastr.min.js')}}"></script>

    <script src="{{asset('assets/js/nprogress.js')}}"></script>

    <script src="{{ asset('assets/js/tooltip.script.js') }}"></script>

    <script type="text/javascript" src="<?php echo asset('assets/js/pdfmake_arabic.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset('assets/js/vfs_fonts_arabic.js') ?>"></script>
    @yield('bottom-js')
</body>

</html>