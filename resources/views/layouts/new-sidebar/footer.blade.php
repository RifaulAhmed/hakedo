<!-- Footer Start -->
<div class="flex-grow-1"></div>
<div class="app-footer">
    <div class="row">
        <div class="col-md-9">
            <p><strong>{{$setting->footer}}</strong></p>
            <div class="footer-bottom border-top pt-3 d-flex flex-column flex-sm-row align-items-center">
                <img class="logo" src="{{asset('images/'.$setting->logo)}}" alt="">
                <div>
                    <p class="m-0">&copy; <?php echo date ('Y'); ?>  {{$setting->developed_by}} v1.0</p>
                    <p class="m-0">{{ __('translate.All rights reserved') }}</p>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- fotter end -->