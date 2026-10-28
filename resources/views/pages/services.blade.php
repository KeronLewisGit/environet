@php($services = (array) enet('services'))
<x-layouts.site title="Services" description="Environmental investigation and remediation, munitions response (MEC) planning and field operations, and construction by Environet, Inc., Hawaiʻi.">
    <x-page-header title="Services" kicker="Three divisions" photo="crew-concrete" position="center 40%"
        lead="Environmental, munitions response and construction work for federal, state and county agencies and private clients. Each division links to the federal awards on the public record that show it." />

    <nav class="wrap mt-10" aria-label="Divisions on this page">
        <ul class="tag-row">
            @foreach ($services as $key => $s)
                <li class="flex-none"><a class="tag" href="#{{ $key }}"><x-icon :name="$s['icon']" class="h-4 w-4" />{{ $s['title'] }}</a></li>
            @endforeach
        </ul>
    </nav>

    <div class="wrap mt-8 space-y-8">
        @foreach ($services as $key => $s)
            @php($awards = \App\Support\Portfolio::inSector($s['sector']))
            <section id="{{ $key }}" class="card scroll-mt-28 overflow-hidden" aria-labelledby="{{ $key }}-title">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_24rem]">
                    <div class="min-w-0 p-6 md:p-8">
                        <x-flag :tone="\App\Support\Portfolio::flag($s['sector'])">Division {{ $loop->iteration }}</x-flag>
                        <h2 id="{{ $key }}-title" class="display-3 mt-4">{{ $s['title'] }}</h2>
                        <p class="measure mt-3 text-ink-2">{{ $s['summary'] }}</p>
                        <ul class="ticked mt-6 grid gap-2.5 sm:grid-cols-2">
                            @foreach ($s['items'] as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                        <p class="hint mt-6">Service lines as listed on the firm's website.</p>
                    </div>
                    <div class="border-t border-line bg-surface-2/50 lg:border-l lg:border-t-0">
                        <div class="relative aspect-[16/10]">
                            <x-photo :key="$s['photo']" :alt="photo_alt($s['photo'])" sizes="(min-width: 1024px) 384px, 100vw" class="h-full w-full" />
                            <span class="photo-note">{{ in_array($s['photo'], (array) enet('own_photos'), true) ? 'Environet photo' : 'Illustrative photo' }}</span>
                        </div>
                        <div class="p-6">
                            <p class="kicker text-ink-3">On the public record</p>
                            @forelse ($awards->take(4) as $a)
                                <a href="{{ route('performance.show', $a['slug']) }}" class="mt-3 block min-h-11 border-b border-line pb-3 last:border-0 hover:text-link">
                                    <span class="block font-semibold leading-snug">{{ $a['title'] }}</span>
                                    <span class="num block text-sm text-ink-3">{{ $a['office_label'] }} · {{ usd($a['value']) }}</span>
                                </a>
                            @empty
                                <p class="mt-3 text-ink-2">References available on request.</p>
                            @endforelse
                            @if ($awards->count() > 4)
                                <a href="{{ route('performance.index', ['sector' => $s['sector']]) }}" class="mt-3 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">All {{ $awards->count() }} {{ strtolower(\App\Support\Portfolio::sectorLabel($s['sector'])) }} awards<x-icon name="arrow-right" class="h-4 w-4" /></a>
                            @endif
                        </div>
                    </div>
                </div>
                @if ($key === 'mec')
                    {{-- Public safety: the Department of Defense explosives safety message, stated plainly. --}}
                    <div class="border-t border-line bg-surface p-6 md:p-8" role="note" aria-labelledby="three-rs-title">
                        <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] md:items-start">
                            <div>
                                <h3 id="three-rs-title" class="font-display text-xl font-semibold">If you find something that could be a munition</h3>
                                <p class="mt-2 text-ink-2">Former military training areas in Hawaiʻi and the Pacific can still hold munitions. The Department of Defense asks everyone to follow the 3Rs.</p>
                                <a href="{{ enet('three_rs.source.url') }}" target="_blank" rel="noopener" class="mt-2 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">{{ enet('three_rs.source.label') }}<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens in a new tab)</span></a>
                            </div>
                            <ol class="grid gap-3 sm:grid-cols-3">
                                <li class="rounded-lg border border-line p-4"><p class="font-display text-lg font-semibold">Recognize</p><p class="mt-1 text-ink-2">when you may have found a munition.</p></li>
                                <li class="rounded-lg border border-line p-4"><p class="font-display text-lg font-semibold">Retreat</p><p class="mt-1 text-ink-2">Do not touch, move or disturb it. Carefully leave the area.</p></li>
                                <li class="rounded-lg border border-line p-4"><p class="font-display text-lg font-semibold">Report</p><p class="mt-1 text-ink-2">Call <a class="text-link" href="tel:911">911</a>.</p></li>
                            </ol>
                        </div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <section class="wrap mt-16">
        <div class="lanes relative isolate overflow-hidden rounded-lg bg-navy-900 p-8 text-white on-dark md:flex md:items-center md:justify-between md:gap-8 md:p-10">
            <div><h2 class="display-3">Have a requirement out for quotes?</h2><p class="mt-2 text-white/80">Send the RFQ, sources sought or teaming request and we confirm a bid decision before your deadline.</p></div>
            <a href="{{ route('solicitations.create') }}" class="btn btn-light mt-6 flex-none md:mt-0">Send an RFQ<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>
</x-layouts.site>
