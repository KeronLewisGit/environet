@props(['award', 'heading' => 'h3'])
{{--
    Past-performance card: one federal award as USAspending.gov publishes it. The photo only
    illustrates the place or kind of work, so it is always labelled.
--}}
@php($current = \App\Support\Portfolio::isCurrent($award))
<article {{ $attributes->merge(['class' => 'card-link card flex flex-col overflow-hidden']) }} data-reveal>
    <div class="card-img relative aspect-[16/9] overflow-hidden">
        <x-photo :key="$award['photo']" alt="" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" class="h-full w-full" />
        <span class="photo-note">Illustrative photo</span>
        <span class="on-dark absolute left-3 top-3 rounded bg-navy-950/90 px-2.5 py-1.5 text-white"><x-flag :tone="\App\Support\Portfolio::flag($award['sector'])" class="!text-[0.75rem]">{{ \App\Support\Portfolio::sectorLabel($award['sector']) }}</x-flag></span>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-sm text-ink-3">{{ $award['office_label'] }}</p>
        <{{ $heading }} class="mt-1 font-display text-[1.1875rem] font-semibold leading-snug tracking-tight"><a class="stretch" href="{{ route('performance.show', $award['slug']) }}">{{ $award['title'] }}</a></{{ $heading }}>
        <p class="mt-1 text-ink-3">{{ $award['place'] }}</p>
        <dl class="mt-auto grid grid-cols-2 gap-3 border-t border-line pt-4 text-sm">
            <div><dt class="text-ink-3">Obligated to date</dt><dd class="num font-display text-base font-semibold">{{ usd($award['value']) }}</dd></div>
            <div><dt class="text-ink-3">Awarded</dt><dd class="num font-display text-base font-semibold">{{ us_date($award['signed'], true) }}</dd></div>
        </dl>
        @if ($current)<p class="mt-3"><x-chip tone="good" icon="clock">Period of performance runs to {{ us_date($award['pop_end'], true) }}</x-chip></p>@endif
    </div>
</article>
