@props(['awards'])
@php
    // One award a year: the largest obligated value signed that year, straight from the record.
    $years = collect($awards)
        ->groupBy(fn (array $a) => substr($a['signed'], 0, 4))
        ->map(fn ($g) => $g->sortByDesc('value')->first())
        ->sortKeysDesc();
@endphp
{{--
    Past-performance timeline: one federal award per year, as USAspending.gov publishes it. The
    rail draws itself as the list scrolls past (a transform, set by JS); without JS or with
    reduced motion the rail is simply there.
--}}
<ol {{ $attributes->merge(['class' => 'tl']) }} data-timeline>
    <span class="tl-rail" aria-hidden="true"><span class="tl-progress"></span></span>
    @foreach ($years as $year => $a)
        <li class="tl-item" data-reveal>
            <span class="tl-node" data-sector="{{ $a['sector'] }}" aria-hidden="true"></span>
            <p class="tl-year num">{{ $year }}</p>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold leading-snug"><a class="text-link-quiet" href="{{ route('performance.show', $a['slug']) }}">{{ $a['title'] }}</a></h3>
                <p class="mt-1 text-ink-2">{{ $a['office_label'] }} · {{ $a['place'] }}</p>
                <p class="mt-1 text-sm text-ink-3"><span class="num font-semibold text-ink-2">{{ usd($a['value']) }}</span> obligated · signed {{ us_date($a['signed'], true) }} · {{ \App\Support\Portfolio::sectorLabel($a['sector']) }}</p>
            </div>
        </li>
    @endforeach
</ol>
