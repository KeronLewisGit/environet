<x-layouts.admin title="Project documents">
    <p class="measure mb-5 text-ink-2">A document room holds a project's drawings, reports and certificates for the client. Share it with an expiring link: the client needs no password, and you can withdraw every link at once.</p>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="a-card min-w-0">
            <div class="table-box">
                <table class="data-table">
                    <thead><tr><th>Project</th><th>Client</th><th class="r">Documents</th><th>Link</th><th>Last opened</th></tr></thead>
                    <tbody>
                        @forelse ($rooms as $r)
                            <tr>
                                <td class="min-w-[14rem]"><a href="{{ route('admin.rooms.show', $r) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $r->name }}</a>@if($r->is_demo) <span class="demo-badge">Sample</span>@endif</td>
                                <td>{{ $r->client_name }}</td>
                                <td class="r">{{ $r->documents_count }}</td>
                                <td>@if($r->linkIsLive())<x-chip tone="good" icon="link">Until {{ $r->link_expires_at?->format('M j') }}</x-chip>@else<x-chip tone="muted" icon="lock">No live link</x-chip>@endif</td>
                                <td class="whitespace-nowrap">{{ $r->last_opened_at?->diffForHumans() ?? 'Never' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-ink-3">No rooms yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.rooms.store') }}" class="a-card min-w-0 space-y-4 p-5">
            @csrf
            <h2 class="font-semibold">New document room</h2>
            <div><label for="r-name" class="mb-1 block text-sm text-ink-2">Project</label><input id="r-name" name="name" value="{{ input_string(old('name')) }}" class="a-input w-full" required maxlength="160"></div>
            <div><label for="r-client" class="mb-1 block text-sm text-ink-2">Client organization</label><input id="r-client" name="client_name" value="{{ input_string(old('client_name')) }}" class="a-input w-full" required maxlength="160"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="r-contact" class="mb-1 block text-sm text-ink-2">Client contact <span class="text-ink-3">(optional)</span></label><input id="r-contact" name="client_contact" value="{{ input_string(old('client_contact')) }}" class="a-input w-full" maxlength="120"></div>
                <div><label for="r-email" class="mb-1 block text-sm text-ink-2">Contact email <span class="text-ink-3">(optional)</span></label><input id="r-email" type="email" name="client_email" value="{{ input_string(old('client_email')) }}" class="a-input w-full"></div>
            </div>
            <div><label for="r-desc" class="mb-1 block text-sm text-ink-2">Note to the client <span class="text-ink-3">(optional)</span></label><textarea id="r-desc" name="description" class="a-input">{{ input_string(old('description')) }}</textarea></div>
            <button class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Create room</button>
        </form>
    </div>
</x-layouts.admin>
