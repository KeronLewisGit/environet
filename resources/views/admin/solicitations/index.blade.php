<x-layouts.admin title="Bid register">
    <x-slot:actions>
        <a href="{{ route('admin.solicitations.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('solicitations.create') }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener"><x-icon name="external" class="h-4 w-4" />Public form</a>
    </x-slot:actions>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.solicitations.index', ['status' => 'open']) }}" class="tag" @if($filters['status'] === 'open') aria-current="true" @endif><x-icon name="clock" class="h-4 w-4" />Open <span class="count">{{ $openCount }}</span></a>
        <a href="{{ route('admin.solicitations.index', ['due' => 1]) }}" class="tag" @if($filters['due']) aria-current="true" @endif>Due in {{ \App\Models\Solicitation::DUE_SOON_DAYS }} days</a>
    </div>
    @include('admin.partials.status-tabs', ['cases' => \App\Enums\SolicitationStatus::cases(), 'counts' => $counts, 'current' => $filters['status'] === 'open' ? null : $filters['status'], 'route' => 'admin.solicitations.index', 'extra' => ['source' => $filters['source'], 'q' => $filters['q']]])

    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-source" class="mb-1 block text-sm text-ink-2">From</label>
            <select id="f-source" name="source" class="a-input"><option value="">Anyone</option>@foreach (\App\Models\Solicitation::SOURCES as $k => $v)<option value="{{ $k }}" @selected($filters['source'] === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label for="f-assigned" class="mb-1 block text-sm text-ink-2">Owner</label>
            <select id="f-assigned" name="assigned" class="a-input"><option value="">Anyone</option><option value="none" @selected($filters['assigned'] === 'none')>Unassigned</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected($filters['assigned'] === (string) $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Title, organization or solicitation number"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>

    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Response due</th><th>Opportunity</th><th>From</th><th>Type</th><th>Status</th><th>Owner</th><th class="r">Files</th></tr></thead>
                <tbody>
                    @forelse ($solicitations as $t)
                        <tr>
                            <td class="whitespace-nowrap"><p class="num font-semibold">{{ $t->response_due_at->format('D M j') }}</p><x-chip :tone="$t->dueTone()" icon="clock" class="mt-1">{{ $t->dueLabel() }}</x-chip></td>
                            <td class="min-w-[16rem]"><a href="{{ route('admin.solicitations.show', $t) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $t->title }}</a><p class="num text-xs text-ink-3">{{ $t->reference }}@if($t->solicitation_number) · {{ $t->solicitation_number }}@endif</p></td>
                            <td>{{ $t->organization }}<p class="text-xs text-ink-3">{{ \App\Models\Solicitation::SOURCES[$t->source] ?? '' }}@if($t->region) · {{ $t->region }}@endif</p></td>
                            <td class="whitespace-nowrap">{{ $t->type->short() }}@if($t->set_aside)<p class="text-xs text-ink-3">{{ $t->set_aside->short() }}</p>@endif</td>
                            <td><x-status :status="$t->status" /></td>
                            <td class="whitespace-nowrap">{{ $t->assignee->name ?? '—' }}</td>
                            <td class="r">{{ $t->files_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-ink-3">No RFQs or bid inquiries match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $solicitations->links() }}</div>
</x-layouts.admin>
