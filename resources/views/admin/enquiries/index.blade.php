<x-layouts.admin title="Inquiries">
    @include('admin.partials.status-tabs', ['cases' => \App\Enums\EnquiryStatus::cases(), 'counts' => $counts, 'current' => $status?->value, 'route' => 'admin.enquiries.index'])
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>From</th><th>Topic</th><th>Message</th><th>Status</th><th class="r">Received</th></tr></thead>
                <tbody>
                    @forelse ($enquiries as $e)
                        <tr>
                            <td><a href="{{ route('admin.enquiries.show', $e) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $e->name }}</a><p class="text-xs text-ink-3">{{ $e->organization ?? $e->email }}</p></td>
                            <td class="whitespace-nowrap">{{ $e->topic->label() }}</td>
                            <td class="min-w-[16rem] text-ink-2">{{ \Illuminate\Support\Str::limit($e->message, 90) }}</td>
                            <td><x-status :status="$e->status" /></td>
                            <td class="r whitespace-nowrap">{{ $e->created_at->format('M j') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-ink-3">No enquiries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $enquiries->links() }}</div>
</x-layouts.admin>
