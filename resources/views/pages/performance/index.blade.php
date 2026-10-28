@php($checked = us_date(enet('awards_checked')))
<x-layouts.site title="Past performance" description="Federal awards to Environet, Inc. as published on USAspending.gov: construction, munitions response and environmental work for the Army, Navy, Air Force and other agencies.">
    <x-page-header title="Past performance" kicker="Federal awards" photo="pearl-harbor"
        lead="Federal contract awards and task orders as USAspending.gov publishes them: the title, agency, contracting office, amount obligated and dates, with a link to each record. Nothing is added to the published description." />

    <section class="wrap mt-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <nav aria-label="Filter by line of work" class="w-full min-w-0 sm:w-auto">
                <ul class="tag-row">
                    <li class="flex-none"><a class="tag" href="{{ route('performance.index') }}" @if(! $sector) aria-current="true" @endif>All <span class="count">{{ $counts->sum() }}</span></a></li>
                    @foreach ($sectors as $key => $label)
                        <li class="flex-none"><a class="tag" href="{{ route('performance.index', ['sector' => $key]) }}" @if($sector === $key) aria-current="true" @endif>{{ $label }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
            <p class="num text-ink-2">{{ $awards->count() }} {{ \Illuminate\Support\Str::plural('award', $awards->count()) }} · {{ usd($awards->sum('value')) }} obligated in total</p>
        </div>
        <h2 class="sr-only">{{ $sector ? $sectors[$sector] : 'All awards' }}</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($awards as $award)
                <x-award-card :award="$award" />
            @endforeach
        </div>
        <div class="measure mt-10 space-y-2 text-[0.9375rem] text-ink-3">
            <p>Source: USAspending.gov, checked {{ $checked }}. "Obligated to date" is the amount the record shows as obligated, which can change with modifications. Dates are the recorded award date and period of performance; a record is not a statement that work is complete.</p>
            <p>This is a selection of the firm's federal records, not every award. Photos only illustrate the place or kind of work; see <a class="text-link" href="{{ route('credits') }}">photo credits</a>.</p>
        </div>
    </section>
</x-layouts.site>
