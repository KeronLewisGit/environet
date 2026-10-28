@props(['tone' => 'neutral', 'icon' => 'dot'])
<span {{ $attributes->merge(['class' => 'chip']) }} data-tone="{{ $tone }}"><x-icon :name="$icon" />{{ $slot }}</span>
