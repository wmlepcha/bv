@php
    $alt = $alt ?? \App\Support\Brand::trademarkedName();
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    style="height: 100%; width: auto; object-fit: contain;"
/>
