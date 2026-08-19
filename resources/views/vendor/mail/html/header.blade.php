@props(['url'])
@php
    $logoSrc = url(\App\Support\Brand::marketingLogoLightUrl());
    $logoAlt = \App\Support\Brand::name();
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img
    src="{{ $logoSrc }}"
    class="logo"
    alt="{{ $logoAlt }}"
    width="220"
    height="48"
>
</a>
</td>
</tr>
