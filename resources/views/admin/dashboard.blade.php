<x-layouts.admin title="Overview">
    @if ($notifyMissing)
        <div class="mb-5 flex gap-3 rounded border border-line bg-surface p-4 text-sm" role="status"><x-icon name="mail" class="h-5 w-5 flex-none text-link" /><p><strong>Nobody is emailed about new submissions yet.</strong> Set <code>ENET_NOTIFY_EMAIL</code> (and, if you like, <code>ENET_NOTIFY_SOLICITATIONS</code>, <code>_TEAMING</code>, <code>_CAREERS</code>) in <code>.env</code>.</p></div>
    @elseif ($notifyInvalid)
        <div class="mb-5 flex gap-3 rounded border border-line bg-surface p-4 text-sm" role="status"><x-icon name="alert" class="h-5 w-5 flex-none text-[color:var(--critical)]" /><p>These notification addresses look wrong and are skipped: {{ implode(', ', $notifyInvalid) }}</p></div>
    @endif

    {{-- The one number to act on --}}
    <section class="a-card overflow-hidden" aria-labelledby="headline-title">
        <div class="grid gap-6 p-6 md:grid-cols-[auto_minmax(0,1fr)_auto] md:items-center md:p-8">
            <p @class(['big-number', 'text-ink' => $headline['count'] > 0, 'text-ink-3' => $headline['count'] === 0])>{{ str_pad((string) $headline['count'], 2, '0', STR_PAD_LEFT) }}</p>
            <div class="min-w-0">
                <h2 id="headline-title" class="text-xl font-bold">{{ $headline['title'] }}</h2>
                <p class="mt-1 text-ink-2">{{ $headline['body'] }}</p>
            </div>
            <a href="{{ $headline['cta']['href'] }}" class="a-btn {{ $headline['count'] ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $headline['cta']['label'] }}<x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($headline['items'])
            <div class="border-t border-line">
                <p class="px-6 pb-1 pt-3 text-xs font-bold uppercase tracking-wider text-ink-3 md:px-8">{{ $headline['list'] }}</p>
                @foreach ($headline['items'] as $item)
                    <a href="{{ $item['href'] }}" class="flex items-center gap-3 border-t border-line px-6 py-3 first-of-type:border-t-0 hover:bg-surface-2 md:px-8">
                        <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $item['title'] }}</p><p class="truncate text-sm text-ink-3">{{ $item['meta'] }}</p></div>
                        <x-chip :tone="$item['tone']" :icon="$item['icon']" class="num">{{ $item['chip'] }}</x-chip>
                    </a>
                @endforeach
            </div>
        @endif
        <div class="strata thin" aria-hidden="true"></div>
    </section>

    <div @class(['mt-6 grid gap-4 sm:grid-cols-2', 'xl:grid-cols-4' => count($tiles) >= 4, 'xl:grid-cols-3' => count($tiles) === 3])>
        @foreach ($tiles as $t)
            <x-stat-tile :label="$t['label']" :value="$t['value']" :previous="$t['previous'] ?? null" :period="$t['period'] ?? null" :note="$t['note'] ?? null" :href="$t['href']" />
        @endforeach
    </div>

    <div @class(['mt-6 grid gap-6', 'xl:grid-cols-[1.4fr_1fr]' => $pipeline])>
        <section class="a-card min-w-0 p-5" aria-labelledby="chart-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="chart-title" class="font-semibold">{{ $chart['title'] }}</h2>
                <p class="text-sm text-ink-3">{{ $chart['note'] }}</p>
            </div>
            @php($max = max(1, max(array_column($chart['bars'], 'value'))))
            @php($n = count($chart['bars']))
            <div class="relative mt-5" x-data="columnChart" @mouseleave="hide()">
                <svg viewBox="0 0 {{ $n * 40 }} 120" class="h-44 w-full" preserveAspectRatio="none" role="img" aria-label="{{ $chart['title'] }}">
                    @foreach ($chart['bars'] as $i => $b)
                        @php($h = $b['value'] / $max * 110)
                        <rect class="bar" x="{{ $i * 40 + 6 }}" y="{{ 120 - max($h, 1.5) }}" width="28" height="{{ max($h, 1.5) }}" rx="1" fill="{{ $b['value'] ? 'var(--series-1)' : 'var(--series-track)' }}"
                            data-label="{{ $b['hint'] }}" data-value="{{ $b['value'] }}" tabindex="0" @mouseenter="show($el)" @focus="show($el)" @blur="hide()"><title>{{ $b['hint'] }}: {{ $b['value'] }}</title></rect>
                    @endforeach
                </svg>
                <div hidden :hidden="!tip" class="pointer-events-none absolute -translate-x-1/2 -translate-y-full rounded bg-navy-900 px-2.5 py-1.5 text-xs font-semibold text-white shadow" :style="tip && `left:${tip.x}px; top:${tip.y - 6}px`"><span x-text="tip?.label"></span>: <span x-text="tip?.value"></span></div>
                <div class="mt-2 grid text-center text-xs text-ink-3" style="grid-template-columns: repeat({{ $n }}, minmax(0, 1fr))">
                    @foreach ($chart['bars'] as $b)<span class="num truncate">{{ $b['label'] }}</span>@endforeach
                </div>
            </div>
        </section>
        @if ($pipeline)
            <section class="a-card min-w-0 p-5" aria-labelledby="pipe-title">
                <h2 id="pipe-title" class="font-semibold">Solicitation pipeline</h2>
                @php($maxP = max(1, $pipeline->max('count')))
                <ul class="mt-3 space-y-1">
                    @foreach ($pipeline as $row)
                        <li>
                            <a href="{{ route('admin.solicitations.index', ['status' => $row['status']->value]) }}" class="block min-h-11 rounded px-1 py-1.5 hover:bg-surface-2">
                                <div class="flex justify-between gap-3 text-sm"><x-status :status="$row['status']" /><span class="num font-semibold">{{ $row['count'] }}</span></div>
                                <div class="meter mt-1.5"><span style="width: {{ $row['count'] / $maxP * 100 }}%"></span></div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.admin>
