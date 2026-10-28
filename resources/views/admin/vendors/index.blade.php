<x-layouts.admin title="Teaming and vendors">
    <x-slot:actions>
        <a href="{{ route('admin.vendors.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('teaming.create') }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener"><x-icon name="external" class="h-4 w-4" />Public form</a>
    </x-slot:actions>

    @if ($renewalCount)
        <a href="{{ route('admin.vendors.index', ['renewal' => 1]) }}" class="mb-4 flex items-center gap-3 rounded border border-line bg-surface p-4 hover:bg-surface-2"><x-icon name="clock" class="h-5 w-5 flex-none text-[color:var(--attention)]" /><span><strong class="num">{{ $renewalCount }}</strong> approved {{ \Illuminate\Support\Str::plural('vendor', $renewalCount) }} with a certificate or policy expired or expiring within {{ \App\Models\Vendor::EXPIRY_WARNING_DAYS }} days</span><x-icon name="arrow-right" class="ml-auto h-4 w-4" /></a>
    @endif
    @include('admin.partials.status-tabs', ['cases' => \App\Enums\VendorStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.vendors.index', 'extra' => ['type' => $filters['type'], 'trade' => $filters['trade'], 'set_aside' => $filters['set_aside'], 'q' => $filters['q'], 'renewal' => $filters['renewal']]])

    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-type" class="mb-1 block text-sm text-ink-2">Type</label>
            <select id="f-type" name="type" class="a-input"><option value="">All</option>@foreach (\App\Enums\VendorType::cases() as $t)<option value="{{ $t->value }}" @selected($filters['type'] === $t->value)>{{ $t->label() }}</option>@endforeach</select></div>
        <div><label for="f-trade" class="mb-1 block text-sm text-ink-2">Work</label>
            <select id="f-trade" name="trade" class="a-input max-w-[16rem]"><option value="">All</option>@foreach (enet('trades') as $k => $v)<option value="{{ $k }}" @selected($filters['trade'] === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label for="f-sa" class="mb-1 block text-sm text-ink-2">Self-declared status</label>
            <select id="f-sa" name="set_aside" class="a-input"><option value="">Any</option>@foreach (\App\Enums\SetAside::declarable() as $s)<option value="{{ $s->value }}" @selected($filters['set_aside'] === $s->value)>{{ $s->short() }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="renewal" value="1" @checked($filters['renewal']) class="h-5 w-5 accent-[var(--primary)]">Needs renewal</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Company, UEI, CAGE or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>

    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Company</th><th>Type</th><th>Self-declared</th><th>Status</th><th>Certificates</th><th>Next expiry</th></tr></thead>
                <tbody>
                    @forelse ($vendors as $v)
                        @php($next = $v->nextExpiry())
                        <tr>
                            <td class="min-w-[14rem]"><a href="{{ route('admin.vendors.show', $v) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $v->company_name }}</a><p class="num text-xs text-ink-3">{{ $v->reference }}@if($v->uei) · UEI {{ $v->uei }}@endif</p></td>
                            <td class="whitespace-nowrap">{{ $v->type->short() }}<p class="max-w-[14rem] truncate text-xs text-ink-3">{{ collect($v->trades)->map(fn ($t) => enet('trades.'.$t) ?? $t)->implode(', ') }}</p></td>
                            <td class="text-sm">{{ $v->setAsideLabels() ?: '—' }}</td>
                            <td><x-status :status="$v->status" /></td>
                            <td><x-compliance :state="$v->complianceState()" /></td>
                            <td class="num whitespace-nowrap">{{ $next?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">No vendors match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $vendors->links() }}</div>
</x-layouts.admin>
