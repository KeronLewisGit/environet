@php
    $services = (array) enet('services');
    $principal = (array) enet('principal');
    $reg = (array) enet('registration');
    $offices = (array) enet('offices');
    $about = (array) enet('about');
    $listed = $awards->sortByDesc('value')->take(6)->sortByDesc('signed'); // the six largest: two Letter pages
    $web = enet('website');
@endphp
<!DOCTYPE html>
<html lang="en-US" data-theme="light">
<head>
    @include('partials.head')
    <title>Capability statement · {{ enet('short_name') }}</title>
    <meta name="description" content="Capability statement for {{ enet('name') }}: UEI, CAGE, NAICS codes, SBA certifications, divisions and federal past performance.">
    @if (enet('preview'))<meta name="robots" content="noindex">@endif
</head>
<body class="bg-surface-2 text-ink">
    <div class="no-print border-b border-line bg-surface">
        <div class="wrap flex min-h-16 flex-wrap items-center gap-3 py-2">
            <a href="{{ route('home') }}" class="mr-auto inline-flex min-h-11 items-center gap-2 font-semibold text-link"><x-icon name="arrow-left" class="h-4 w-4" />Back to the website</a>
            <button type="button" onclick="window.print()" class="btn btn-outline btn-sm"><x-icon name="print" class="h-4 w-4" />Print</button>
            <a href="{{ route('capability.pdf') }}" class="btn btn-primary btn-sm"><x-icon name="download" class="h-4 w-4" />Download PDF</a>
        </div>
    </div>

    <main class="cs-page my-6 border border-line p-6 shadow-sm sm:p-10 print:my-0 print:p-0">
        <header class="flex flex-wrap items-start justify-between gap-6 border-b-[3px] border-[#033491] pb-5">
            <div>
                <x-logo />
                <p class="mt-3 text-sm font-semibold uppercase tracking-[0.14em] text-[#33405a]">Capability statement · {{ now()->format('F Y') }}</p>
            </div>
            <div class="text-sm leading-relaxed sm:text-right">
                <div>{{ $offices['oahu']['street'] }}, {{ $offices['oahu']['city'] }}</div>
                <div>Tel. {{ format_phone(enet('phone')) }} · {{ enet('email') }}</div>
                <div>{{ $web }}</div>
            </div>
        </header>

        <section class="mt-6 grid gap-6 sm:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
            <div>
                <h1 class="font-brand text-[1.9rem] font-normal leading-tight text-[#033491]">{{ enet('name') }}</h1>
                <p class="mt-2 text-[0.9375rem] leading-relaxed">{{ $about['intro'] }}</p>
                <h2 class="font-sans mt-5 border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">Differentiators</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-[0.875rem]">
                    @foreach ($about['strengths'] as $s)<li>{{ $s }}</li>@endforeach
                    <li>SBA-certified HUBZone and Veteran-Owned Small Business.</li>
                </ul>
            </div>
            <div class="self-start rounded border border-[#c9c0a2] bg-[#f1ecdc] p-4 text-sm">
                <p class="border-b-2 border-[#0d2a4d] pb-1 font-display text-xs font-bold uppercase tracking-[0.14em]">Company data</p>
                <dl class="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1">
                    <dt class="text-[#5a5440]">UEI</dt><dd class="num font-semibold">{{ $reg['uei'] }}</dd>
                    <dt class="text-[#5a5440]">CAGE</dt><dd class="num font-semibold">{{ $reg['cage'] }}</dd>
                    <dt class="text-[#5a5440]">Established</dt><dd class="num font-semibold">{{ enet('founded') }}</dd>
                    <dt class="text-[#5a5440]">Primary NAICS</dt><dd class="num font-semibold">{{ $reg['naics_primary'] }}</dd>
                    @foreach ($reg['certifications'] as $c)
                        <dt class="text-[#5a5440]">{{ $c['name'] }}</dt><dd class="font-semibold">@if($c['until']){{ us_date($c['since'], true) }} to {{ us_date($c['until'], true) }}@else Since {{ us_date($c['since'], true) }}@endif</dd>
                    @endforeach
                    <dt class="text-[#5a5440]">{{ $principal['title'] }}</dt><dd class="font-semibold">{{ $principal['name'] }}</dd>
                </dl>
                <p class="mt-2 text-[0.6875rem] text-[#5a5440]">Source: SBA Small Business Search, {{ us_date($reg['source']['checked'], true) }}.</p>
            </div>
        </section>

        <section class="mt-5">
            <h2 class="font-sans border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">Core competencies</h2>
            <div class="mt-3 grid gap-x-6 gap-y-3 text-[0.8125rem] sm:grid-cols-3">
                @foreach ($services as $s)
                    <div>
                        <p class="font-display text-[0.9375rem] font-bold text-[#033491]">{{ $s['title'] }}</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">@foreach (array_slice($s['items'], 0, 8) as $i)<li>{{ $i }}</li>@endforeach</ul>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-5 cs-break print:mt-0">
            <h2 class="font-sans border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">NAICS codes (selected; {{ $reg['naics_count'] }} registered)</h2>
            <ul class="mt-2 grid gap-x-6 gap-y-0.5 text-[0.8125rem] sm:grid-cols-2">
                @foreach ($reg['naics'] as $code => $label)<li><span class="num font-semibold">{{ $code }}</span> {{ $label }}@if($code === $reg['naics_primary']) <em>(primary)</em>@endif</li>@endforeach
            </ul>
        </section>

        <section class="mt-5">
            <h2 class="font-sans border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">Federal past performance (USAspending.gov)</h2>
            <div class="overflow-x-auto">
                <table class="mt-2 w-full min-w-[36rem] border-collapse text-left text-[0.8125rem] print:text-[0.75rem]">
                    <thead><tr class="border-b border-[#9aa3b5] text-[0.6875rem] uppercase tracking-wider text-[#33405a]"><th class="py-1 pr-3 font-bold">Award</th><th class="py-1 pr-3 font-bold">Agency / office</th><th class="py-1 pr-3 font-bold">Awarded</th><th class="py-1 pr-3 text-right font-bold">Obligated</th><th class="py-1 font-bold">PIID</th></tr></thead>
                    <tbody>
                        @foreach ($listed as $a)
                            <tr class="border-b border-[#d9dcd3] align-top">
                                <td class="py-1 pr-3"><span class="font-semibold">{{ $a['title'] }}</span><br><span class="text-[#33405a]">{{ $a['place'] }}</span></td>
                                <td class="w-[11rem] py-1 pr-3">{{ $a['office_label'] }}</td>
                                <td class="num whitespace-nowrap py-1 pr-3">{{ us_date($a['signed'], true) }}</td>
                                <td class="num whitespace-nowrap py-1 pr-3 text-right font-semibold">{{ usd($a['value']) }}</td>
                                <td class="num py-1 text-[#33405a]">{{ $a['piid'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-[0.75rem] text-[#33405a]">Amounts are obligations to date as published on USAspending.gov ({{ us_date(enet('awards_checked'), true) }}); dates are award dates. Each record: {{ $web }}/past-performance</p>
        </section>

        <section class="mt-5 grid gap-6 text-[0.875rem] break-inside-avoid sm:grid-cols-2">
            <div>
                <h2 class="font-sans border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">Offices</h2>
                <ul class="mt-2 space-y-1">
                    @foreach ($offices as $o)<li><strong>{{ $o['label'] }}:</strong> {{ $o['street'] ? $o['street'].', '.$o['city'] : $o['city'].' (address on request)' }}</li>@endforeach
                </ul>
            </div>
            <div>
                <h2 class="font-sans border-b border-[#0d2a4d] pb-1 text-xs font-bold uppercase tracking-[0.16em]">Contact</h2>
                <p class="mt-2">Tel. {{ format_phone(enet('phone')) }} · Fax {{ format_phone(enet('fax')) }}<br>{{ enet('email') }}<br>RFQs and bid inquiries: {{ $web }}/rfq<br>Teaming and vendors: {{ $web }}/teaming</p>
            </div>
        </section>
        <footer class="mt-5 flex items-center justify-between border-t-[3px] border-[#01a02c] pt-2 text-[0.6875rem] uppercase tracking-[0.14em] text-[#33405a]"><span>{{ enet('tagline') }}</span><span>{{ enet('short_name') }} · UEI {{ $reg['uei'] }}</span></footer>
    </main>
</body>
</html>
