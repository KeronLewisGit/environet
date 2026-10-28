@props(['tone' => 'navy'])
{{-- Survey-pin flag kicker. tone: navy (construction, general), green (environmental), orange (munitions response). --}}
<span {{ $attributes->merge(['class' => 'pinflag', 'data-tone' => $tone]) }}>
    <svg viewBox="0 0 17 22" aria-hidden="true"><path class="pole" d="M2 1v21"/><path class="cloth" d="M2.6 1.5h13l-3.2 4.2 3.2 4.3h-13z"/></svg>
    <span>{{ $slot }}</span>
</span>
