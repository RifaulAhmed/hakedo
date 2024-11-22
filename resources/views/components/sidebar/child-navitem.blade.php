<a 
    href="{{$href}}"
    class="nav-item child-nav @if($path == substr($href, 1)) active @endif" 
>
    <span class="prefix rounded-circle"></span>
    <span class="item-name">{{$title}}</span>
</a>