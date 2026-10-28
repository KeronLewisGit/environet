@props(['n', 'title', 'intro' => null])
<fieldset {{ $attributes->merge(['class' => 'space-y-5 rule-top pt-6']) }}>
    <legend class="fieldset-title float-left mb-5 w-full"><span class="step" aria-hidden="true">{{ $n }}</span>{{ $title }}</legend>
    @if ($intro)<p class="clear-both -mt-2 text-ink-2">{{ $intro }}</p>@endif
    <div class="clear-both space-y-5">{{ $slot }}</div>
</fieldset>
