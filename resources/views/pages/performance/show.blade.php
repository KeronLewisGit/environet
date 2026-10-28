@php
    use App\Support\Portfolio;
    $current = Portfolio::isCurrent($award);
    $fields = [
        'Agency' => $award['agency'].' ('.$award['department'].')',
        'Contracting office' => $award['office_label'].' · '.$award['office'],
        'Award ID (PIID)' => $award['piid'],
        'Parent contract' => $award['parent_idv'] ?: 'None (definitive contract)',
        'Obligated to date' => usd($award['value'], false),
        'Award date' => us_date($award['signed']),
        'Period of performance' => us_date($award['pop_start'], true).' to '.us_date($award['pop_end'], true),
        'Place of performance' => $award['place'],
        'NAICS' => $award['naics'].' · '.$award['naics_label'],
    ];
@endphp
<x-layouts.site :title="$award['title']" :description="$award['title'].': '.$award['office_label'].', '.usd($award['value']).' obligated, awarded '.us_date($award['signed']).'. Source: USAspending.gov.'">
    <x-page-header :title="$award['title']" :kicker="Portfolio::sectorLabel($award['sector'])" :tone="Portfolio::flag($award['sector'])"
        :lead="$award['office_label'].'. '.usd($award['value']).' obligated to date; awarded '.us_date($award['signed']).'.'"
        :tag-fields="['Award' => $award['piid'], 'Agency' => $award['agency'], 'NAICS' => $award['naics'], 'Place' => $award['place']]">
        <p class="mt-6"><a href="{{ route('performance.index') }}" class="inline-flex min-h-11 items-center gap-2 font-semibold text-white/85 hover:text-white"><x-icon name="arrow-left" class="h-4 w-4" />All past performance</a></p>
    </x-page-header>

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <x-flag>The record</x-flag>
            <h2 class="sr-only">Award details</h2>
            <dl class="mt-5 divide-y divide-line border-y border-line">
                @foreach ($fields as $label => $value)
                    <div class="grid gap-1 py-3 sm:grid-cols-[13rem_minmax(0,1fr)] sm:gap-4">
                        <dt class="text-ink-3">{{ $label }}</dt>
                        <dd class="num min-w-0 font-medium [overflow-wrap:anywhere]">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4">@if($current)<x-chip tone="good" icon="clock">Period of performance runs to {{ us_date($award['pop_end'], true) }}</x-chip>@else<x-chip tone="neutral" icon="calendar">{{ Portfolio::periodLabel($award) }}</x-chip>@endif</p>

            <h2 class="mt-10 font-display text-xl font-semibold">Published description</h2>
            <p class="measure mt-2 rounded-lg border border-line bg-surface p-4 font-medium uppercase tracking-wide text-ink-2 [overflow-wrap:anywhere]">{{ $award['published'] }}</p>

            <a href="{{ $award['source'] }}" target="_blank" rel="noopener" class="mt-6 flex min-h-14 items-center gap-4 rounded-lg border border-line bg-surface px-4 py-3 hover:border-ink-3">
                <x-icon name="file" class="h-5 w-5 flex-none text-ink-3" />
                <span class="flex-1"><span class="block font-semibold">Open the record on USAspending.gov</span><span class="block text-sm text-ink-3">Checked {{ us_date(enet('awards_checked')) }}</span></span>
                <x-icon name="external" class="h-4 w-4 flex-none" /><span class="sr-only">(opens in a new tab)</span>
            </a>

            <figure class="mt-10">
                <x-photo :key="$award['photo']" :alt="photo_alt($award['photo'])" sizes="(min-width: 1024px) 760px, 100vw" class="aspect-[16/9] rounded-lg" />
                <figcaption class="photo-caption">Illustrative photo: {{ lcfirst(photo_alt($award['photo'])) }}. It does not show this work. <a class="text-link" href="{{ route('credits') }}">Credit</a></figcaption>
            </figure>
        </div>

        <aside class="space-y-6">
            <div class="panel p-5">
                <h2 class="font-display text-lg font-semibold">Similar requirement?</h2>
                <p class="mt-2 text-ink-2">Send the RFQ, sources sought or teaming request.</p>
                <a href="{{ route('solicitations.create') }}" class="btn btn-primary mt-4 w-full">Send an RFQ</a>
                <a href="{{ route('capability') }}" class="btn btn-outline mt-3 w-full">Capability statement</a>
            </div>
        </aside>
    </section>

    @if ($related->isNotEmpty())
        <section class="wrap mt-20">
            <x-flag>More past performance</x-flag>
            <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $a)<x-award-card :award="$a" />@endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
