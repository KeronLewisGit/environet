@php
    $about = (array) enet('about');
    $principal = (array) enet('principal');
    $reg = (array) enet('registration');
    $years = \App\Support\Portfolio::all()->groupBy(fn ($a) => substr($a['signed'], 0, 4))->sortKeys();
@endphp
<x-layouts.site title="About the firm" description="Environet, Inc.: established in 1995, a Hawaiʻi environmental, munitions response and construction firm with offices in Kamuela, Honolulu, Guam and Colorado.">
    <x-page-header title="A Hawaiʻi firm since 1995" kicker="About Environet" tone="green" photo="waimea" position="center 60%"
        :lead="$about['intro']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="prose-copy measure min-w-0">
            @foreach ($about['paragraphs'] as $p)<p>{{ $p }}</p>@endforeach
            <h2>What sets the firm apart</h2>
            <ul>@foreach ($about['strengths'] as $s)<li>{{ $s }}</li>@endforeach</ul>
            <h2>Core values</h2>
            <p>{{ $about['values_text'] }}</p>
            <p class="hint">Text from the firm's own website ({{ $about['source'] }}).</p>
        </div>
        <aside class="space-y-6">
            <x-sample-tag :tilt="false" title="Company" :fields="['Name' => enet('name'), 'Established' => (string) enet('founded'), $principal['title'] => $principal['name'], 'UEI' => $reg['uei'], 'CAGE' => $reg['cage']]" />
            <p class="hint">{{ $principal['name'] }} is named as {{ $reg['principal'] }} on the <a class="text-link" href="{{ $principal['source']['url'] }}" target="_blank" rel="noopener">{{ $principal['source']['label'] }}</a> ({{ $principal['source']['date'] }}).</p>
        </aside>
    </section>

    <section class="wrap mt-16" aria-labelledby="values-title">
        <h2 id="values-title" class="sr-only">Core values</h2>
        <ul class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($about['values'] as $v)
                <li class="card flex min-h-20 items-center gap-3 p-4"><span class="h-3 w-3 flex-none rounded-full bg-leaf" aria-hidden="true"></span><span class="font-display text-lg font-semibold">{{ $v }}</span></li>
            @endforeach
        </ul>
    </section>

    <section class="wrap mt-16 grid gap-6 lg:grid-cols-2" aria-labelledby="certs-title">
        <div class="card p-6 md:p-8">
            <x-flag tone="green">SBA certifications</x-flag>
            <h2 id="certs-title" class="display-3 mt-4">HUBZone and Veteran-Owned Small Business</h2>
            <ul class="mt-6 divide-y divide-line border-y border-line">
                @foreach ($reg['certifications'] as $c)
                    <li class="flex flex-wrap items-baseline justify-between gap-2 py-3"><span><strong class="font-semibold">{{ $c['name'] }}</strong> <span class="text-ink-2">· {{ $c['detail'] }}</span></span><span class="num text-sm text-ink-3">From {{ us_date($c['since'], true) }}@if($c['until']) to {{ us_date($c['until'], true) }}@endif</span></li>
                @endforeach
            </ul>
            <p class="hint mt-4">As shown on the <a class="text-link" href="{{ $reg['source']['url'] }}" target="_blank" rel="noopener">SBA Small Business Search</a>, checked {{ us_date($reg['source']['checked']) }}.</p>
        </div>
        <div class="card p-6 md:p-8">
            <x-flag>Timeline from the public record</x-flag>
            <ol class="mt-6 border-l-2 border-line">
                <li class="relative pb-6 pl-6"><span class="absolute -left-[7px] top-2 h-3 w-3 rounded-full bg-leaf"></span><p class="num font-display text-lg font-semibold">{{ enet('founded') }}</p><p class="text-ink-2">Environet, Inc. established.</p></li>
                @foreach ($years as $year => $list)
                    <li class="relative pb-6 pl-6"><span class="absolute -left-[7px] top-2 h-3 w-3 rounded-full bg-primary"></span><p class="num font-display text-lg font-semibold">{{ $year }}</p><p class="text-ink-2">{{ $list->count() }} federal {{ \Illuminate\Support\Str::plural('award', $list->count()) }} listed here, including <a class="text-link" href="{{ route('performance.show', $list->first()['slug']) }}">{{ $list->first()['title'] }}</a>.</p></li>
                @endforeach
                <li class="relative pl-6"><span class="absolute -left-[7px] top-2 h-3 w-3 rounded-full bg-leaf"></span><p class="num font-display text-lg font-semibold">2026</p><p class="text-ink-2">SBA HUBZone and VOSB certifications active from {{ us_date($reg['certifications'][0]['since']) }}.</p></li>
            </ol>
        </div>
    </section>

    <section class="wrap mt-16 flex flex-wrap gap-3">
        <a href="{{ route('capability') }}" class="btn btn-primary"><x-icon name="file" class="h-4 w-4" />Capability statement</a>
        <a href="{{ route('contact') }}" class="btn btn-outline">Contact an office</a>
    </section>
</x-layouts.site>
