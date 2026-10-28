<x-layouts.admin title="Job postings">
    <x-slot:actions><a href="{{ route('admin.jobs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New posting</a></x-slot:actions>
    <div class="a-card min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Type of work</th><th>Location</th><th>Status</th><th>Closes</th><th class="r">Applications</th></tr></thead>
                <tbody>
                    @forelse ($jobs as $j)
                        <tr>
                            <td class="min-w-[14rem]"><a href="{{ route('admin.jobs.edit', $j) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $j->title }}</a>@if($j->is_demo) <span class="demo-badge">Sample</span>@endif<p class="text-xs text-ink-3">{{ $j->employment_type->label() }} · {{ $j->openings }} {{ \Illuminate\Support\Str::plural('opening', $j->openings) }}</p></td>
                            <td>{{ $j->category->label() }}</td>
                            <td>{{ $j->location }}</td>
                            <td><x-chip :tone="$j->isOpen() ? 'good' : 'muted'" :icon="$j->isOpen() ? 'check-circle' : 'x'">{{ $j->isOpen() ? 'Open' : $j->status->label() }}</x-chip></td>
                            <td class="num">{{ $j->closes_on?->format('M j, Y') ?? '—' }}</td>
                            <td class="r"><a class="text-link underline" href="{{ route('admin.applications.index', ['job' => $j->id]) }}">{{ $j->applications_count }}</a>@if($j->new_count) <span class="text-ink-3">({{ $j->new_count }} new)</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">No postings yet. The careers page still takes general applications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
