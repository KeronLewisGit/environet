@props(['key', 'alt', 'sizes' => '100vw', 'eager' => false, 'imgClass' => '', 'position' => null])
@php($p = photo($key))
<figure {{ $attributes->merge(['class' => 'photo']) }}>
    <img src="{{ $p['src'] }}" srcset="{{ $p['srcset'] }}" sizes="{{ $sizes }}" width="{{ $p['width'] }}" height="{{ $p['height'] }}" alt="{{ $alt }}" class="{{ $imgClass }}" @if($position) style="object-position: {{ $position }}" @endif @if($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif>
</figure>
