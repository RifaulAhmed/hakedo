<div class="compact-layout">
    <div 
        data-compact-width="100" 
        class="layout-sidebar"
    >
        @include('layouts.new-sidebar.sidebar')
    </div>

    <div class="layout-content"> 
        @include('layouts.new-sidebar.header')

        <div class="content-section">
            @yield('main-content')
        </div>

        <div class="flex-grow-1"></div>
        @include('layouts.new-sidebar.footer')
        
    </div>
</div>
