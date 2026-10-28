@php
    $all = \App\Support\Portfolio::all();
    $services = (array) enet('services');
    $reg = (array) enet('registration');
    $offices = (array) enet('offices');
@endphp
<x-layouts.site>
    {{-- Hero: the home page's taller variant of the shared header band, on the firm's own survey photo. --}}
    <section class="lanes relative isolate overflow-hidden bg-navy-900 text-white on-dark">
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo key="waikoloa-survey" alt="" :eager="true" sizes="100vw" class="h-full w-full" position="center 55%" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
        <div class="wrap grid gap-10 pb-14 pt-12 md:pb-24 md:pt-24 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
            <div class="hero-in min-w-0">
                <x-flag tone="green">Established {{ enet('founded') }} · Hawaiʻi</x-flag>
                <h1 class="display-1 mt-6 max-w-[17ch]">Environmental, munitions response and construction services.</h1>
                <p class="lead mt-6 max-w-[56ch] text-white/85">Environet, Inc. investigates and cleans up contaminated sites, plans and supports munitions response, and builds for the Department of Defense and other public clients, from offices in Hawaiʻi, Guam and Colorado.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('solicitations.create') }}" class="btn btn-light">Send an RFQ or bid inquiry<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('capability') }}" class="btn btn-on-dark"><x-icon name="file" class="h-4 w-4" />Capability statement</a>
                </div>
            </div>
            <div class="relative hidden lg:block">
                <x-sonar />
                <x-sample-tag class="hero-tag relative" />
            </div>
        </div>
        <span class="photo-note" style="bottom: 3rem">Environet photo: survey lanes at Waikoloa</span>
        <div class="strata" aria-hidden="true"></div>
    </section>

    <x-capability-strip />

    {{-- Sourced figures only, each with its source. --}}
    <section class="wrap mt-14 md:mt-20" aria-labelledby="record-title">
        <h2 id="record-title" class="sr-only">On the public record</h2>
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="readout" data-reveal><b>{{ enet('founded') }}</b><span class="mt-2 block text-ink-2">Year established, per the firm and its SBA profile</span></div>
            <div class="readout" data-reveal><b>HUBZone · VOSB</b><span class="mt-2 block text-ink-2">SBA certifications, active from {{ us_date($reg['certifications'][0]['since'], true) }}</span></div>
            <div class="readout" data-reveal><b><span class="sr-only">{{ usd($reg['usaspending']['last_12_months']) }}</span><span aria-hidden="true" data-count="{{ round($reg['usaspending']['last_12_months'] / 1_000_000, 2) }}" data-decimals="2" data-prefix="$" data-suffix=" million">{{ usd($reg['usaspending']['last_12_months']) }}</span></b><span class="mt-2 block text-ink-2">Federal contract transactions in the last 12 months, per USAspending.gov</span></div>
            <div class="readout" data-reveal><b><span class="sr-only">{{ count($offices) }} offices</span><span aria-hidden="true" data-count="{{ count($offices) }}" data-suffix=" offices">{{ count($offices) }} offices</span></b><span class="mt-2 block text-ink-2">Kamuela, Honolulu, Guam and Colorado, as listed on the firm's website</span></div>
        </div>
        <p class="hint mt-6">Checked {{ us_date($reg['source']['checked']) }}. <a class="text-link" href="{{ $reg['usaspending']['url'] }}" target="_blank" rel="noopener">USAspending.gov profile<span class="sr-only"> (opens in a new tab)</span></a> · <a class="text-link" href="{{ $reg['source']['url'] }}" target="_blank" rel="noopener">SBA profile<span class="sr-only"> (opens in a new tab)</span></a></p>
    </section>

    {{-- Three divisions --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="services-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-flag>What we do</x-flag>
                <h2 id="services-title" class="display-2 mt-4 max-w-[20ch]">Three divisions, one team in the field.</h2>
            </div>
            <a href="{{ route('services') }}" class="btn btn-outline">All services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($services as $key => $s)
                <article class="card-link card flex flex-col overflow-hidden" data-reveal>
                    <div class="card-img relative aspect-[16/10] overflow-hidden">
                        <x-photo :key="$s['photo']" :alt="photo_alt($s['photo'])" sizes="(min-width: 1024px) 400px, 100vw" class="h-full w-full" />
                        <span class="photo-note">{{ in_array($s['photo'], (array) enet('own_photos'), true) ? 'Environet photo' : 'Illustrative photo' }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <x-flag :tone="\App\Support\Portfolio::flag($s['sector'])">{{ $s['short'] }}</x-flag>
                        <h3 class="mt-3 font-display text-[1.375rem] font-semibold leading-snug tracking-tight"><a class="stretch" href="{{ route('services') }}#{{ $key }}">{{ $s['title'] }}</a></h3>
                        <p class="mt-2 text-ink-2">{{ $s['summary'] }}</p>
                        <p class="mt-auto pt-4 text-sm font-semibold text-link">{{ count($s['items']) }} service lines<x-icon name="arrow-right" class="ml-1 inline h-4 w-4" /></p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Past performance --}}
    <section class="mt-20 border-y border-line bg-surface py-16 md:mt-28 md:py-24" aria-labelledby="awards-title">
        <div class="wrap">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <x-flag>Past performance</x-flag>
                    <h2 id="awards-title" class="display-2 mt-4 max-w-[22ch]">Federal awards, as the public record shows them.</h2>
                    <p class="measure mt-4 text-ink-2">Agency, value and dates exactly as USAspending.gov publishes them, with a link to each record.</p>
                </div>
                <a href="{{ route('performance.index') }}" class="btn btn-outline">All {{ $awardCount }} awards<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured->unique('photo')->take(3) as $award)
                    <x-award-card :award="$award" />
                @endforeach
            </div>
            <div class="mt-16 grid gap-10 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] lg:gap-16">
                <div>
                    <h3 class="font-display text-2xl font-semibold tracking-tight">Year by year since {{ substr($all->min('signed'), 0, 4) }}</h3>
                    <p class="mt-3 text-ink-2">The largest award signed in each year on the record, with its contracting office and place of performance.</p>
                    <a href="{{ route('performance.index') }}" class="text-link mt-4 inline-flex min-h-11 items-center font-semibold">The full record</a>
                </div>
                <x-award-timeline :awards="$all" />
            </div>
        </div>
    </section>

    {{-- For contracting officers and primes --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="proc-title">
        <x-flag>For contracting officers and primes</x-flag>
        <h2 id="proc-title" class="display-2 mt-4 max-w-[22ch]">What a source selection or teaming call needs, in one place.</h2>
        <ol class="mt-10 grid gap-6 lg:grid-cols-3">
            <li class="card p-6" data-reveal>
                <span class="step" aria-hidden="true">1</span>
                <h3 class="mt-4 font-display text-xl font-semibold">Read the capability statement</h3>
                <p class="mt-2 text-ink-2">UEI, CAGE, NAICS codes, SBA certifications, divisions and past performance on one printable page.</p>
                <a href="{{ route('capability') }}" class="btn btn-outline btn-sm mt-5">Open it</a>
            </li>
            <li class="card p-6" data-reveal>
                <span class="step" aria-hidden="true">2</span>
                <h3 class="mt-4 font-display text-xl font-semibold">Send the RFQ or sources sought</h3>
                <p class="mt-2 text-ink-2">Attach the solicitation or link to SAM.gov. You get a reference number straight away and a named contact.</p>
                <a href="{{ route('solicitations.create') }}" class="btn btn-primary btn-sm mt-5">Send an RFQ</a>
            </li>
            <li class="card p-6" data-reveal>
                <span class="step" aria-hidden="true">3</span>
                <h3 class="mt-4 font-display text-xl font-semibold">Share project documents</h3>
                <p class="mt-2 text-ink-2">Bid packages, drawings and reports go out through private links that expire when the work is done.</p>
                <a href="{{ route('contact') }}" class="btn btn-outline btn-sm mt-5">Ask about it</a>
            </li>
        </ol>
    </section>

    {{-- Teaming and careers --}}
    <section class="wrap mt-20 grid gap-6 md:mt-28 lg:grid-cols-2">
        <div class="lanes relative isolate overflow-hidden rounded-lg bg-navy-900 p-8 text-white on-dark md:p-10">
            <x-flag tone="green">Teaming partners and vendors</x-flag>
            <h2 class="display-3 mt-4">Register once. We remind you before your insurance lapses.</h2>
            <p class="mt-3 text-white/80">Company details, UEI, self-declared SBA status, insurance and licenses with their expiry dates.</p>
            <a href="{{ route('teaming.create') }}" class="btn btn-light mt-6">Register your company<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="card p-8 md:p-10">
            <x-flag tone="orange">Careers</x-flag>
            <h2 class="display-3 mt-4">Field scientists, UXO technicians and construction trades.</h2>
            <p class="mt-3 text-ink-2">{{ $openJobs ? $openJobs.' '.\Illuminate\Support\Str::plural('opening', $openJobs).' posted now. ' : '' }}Choose your office and list your HAZWOPER, UXO and OSHA certifications with their expiry dates.</p>
            <a href="{{ route('careers.index') }}" class="btn btn-primary mt-6">See openings<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- Offices --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="offices-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-flag>Offices</x-flag>
                <h2 id="offices-title" class="display-2 mt-4">Hawaiʻi, Guam and Colorado</h2>
                <p class="measure mt-3 text-ink-2">Four offices, and work across the Pacific and the mainland.</p>
            </div>
            <a href="{{ route('contact') }}" class="btn btn-outline">Contact details<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <x-pacific-map class="mt-8" />
        <ul class="mt-8 grid gap-px overflow-hidden rounded-lg border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($offices as $key => $o)
                <li class="bg-surface p-5">
                    <p class="kicker text-ink-3">{{ $o['label'] }}</p>
                    <p class="mt-1 font-display text-lg font-semibold">{{ $o['name'] }}</p>
                    <p class="mt-1 text-ink-2">@if($o['street']){{ $o['street'] }}<br>@endif{{ $o['city'] }}</p>
                    @if ($o['phone'])<a href="{{ tel_href($o['phone']) }}" class="mt-1 inline-flex min-h-11 items-center font-semibold text-link">{{ format_phone($o['phone']) }}</a>@else<p class="mt-2 text-sm text-ink-3">Contact the Honolulu office</p>@endif
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.site>
