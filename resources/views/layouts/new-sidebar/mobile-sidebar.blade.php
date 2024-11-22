<button type="button" id="mobileSidebarToggle" data-bs-toggle="modal" data-bs-target="#mobile-sidebar" class="d-block d-lg-none btn btn-light p-2">
    @include('components.icons.toggle2', ['class'=>'width_20'])
</button>
  
<!-- Modal -->
<div class="modal fade" id="mobile-sidebar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog m-0"></div>
    <div class="layout-sidebar-mobile">
        @include('layouts.new-sidebar.sidebar')
    </div>
</div>