<x-layouts.admin :title="$room->name">
    <x-slot:actions><a href="{{ route('admin.rooms.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Rooms</a></x-slot:actions>

    @if ($link)
        <div class="a-card mb-6 border-l-4 p-5" style="border-left-color: var(--good)" role="status">
            <p class="font-semibold">Share this link with {{ $room->client_contact ?? $room->client_name }}</p>
            <p class="mt-1 text-sm text-ink-2">It works until {{ $room->link_expires_at?->format('l, F j, Y') }}. Copy it now: for security it is not shown again.</p>
            <label for="share-url" class="sr-only">Share link</label>
            <input id="share-url" readonly value="{{ $link }}" class="a-input mt-3 w-full font-mono text-sm" onfocus="this.select()">
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card min-w-0" aria-labelledby="docs-title">
                <h2 id="docs-title" class="px-5 pt-4 font-semibold">Documents ({{ $room->documents->count() }})</h2>
                <div class="table-box mt-3">
                    <table class="data-table">
                        <thead><tr><th>Document</th><th>Category</th><th class="r">Size</th><th class="r">Client downloads</th><th><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($room->documents as $d)
                                <tr>
                                    <td class="min-w-[14rem]"><a class="font-semibold text-link underline-offset-2 hover:underline" href="{{ route('admin.rooms.download', [$room, $d]) }}">{{ $d->title }}</a><p class="text-xs text-ink-3">{{ $d->extension() }} · {{ $d->created_at->format('M j, Y') }} · {{ $d->uploader->name ?? 'staff' }}</p></td>
                                    <td>{{ $d->category->label() }}</td>
                                    <td class="r">{{ file_size($d->size) }}</td>
                                    <td class="r">{{ $d->downloads }}</td>
                                    <td class="r"><form method="POST" action="{{ route('admin.rooms.documents.destroy', [$room, $d]) }}" onsubmit="return confirm('Remove this document from the room?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost w-11 px-0" aria-label="Remove {{ $d->title }}"><x-icon name="trash" class="h-4 w-4" /></button></form></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-ink-3">No documents yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('admin.rooms.upload', $room) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-line p-5 md:grid-cols-[minmax(0,1fr)_12rem]">
                    @csrf
                    <div><label for="d-title" class="mb-1 block text-sm text-ink-2">Title</label><input id="d-title" name="title" class="a-input w-full" required maxlength="160" placeholder="e.g. Drawing C-101 rev B"></div>
                    <div><label for="d-cat" class="mb-1 block text-sm text-ink-2">Category</label><select id="d-cat" name="category" class="a-input w-full">@foreach (\App\Enums\RoomCategory::cases() as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach</select></div>
                    <div class="md:col-span-2"><label for="d-file" class="mb-1 block text-sm text-ink-2">File <span class="text-ink-3">(PDF, DWG, Office, images or ZIP, up to 20 MB)</span></label><input id="d-file" type="file" name="file" required class="input" accept=".{{ implode(',.', \App\Models\DocumentRoom::FILE_TYPES) }}"></div>
                    <div><button class="a-btn a-btn-primary"><x-icon name="upload" class="h-4 w-4" />Upload</button></div>
                </form>
            </section>
        </div>
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Client link</h2>
                @if ($room->linkIsLive())
                    <p class="mt-2"><x-chip tone="good" icon="link">Live until {{ $room->link_expires_at?->format('M j, Y') }}</x-chip></p>
                    <p class="mt-2 text-sm text-ink-2">Last opened {{ $room->last_opened_at?->diffForHumans() ?? 'never' }}.</p>
                @else
                    <p class="mt-2"><x-chip tone="muted" icon="lock">No live link</x-chip></p>
                @endif
                <form method="POST" action="{{ route('admin.rooms.share', $room) }}" class="mt-4 space-y-3">
                    @csrf
                    <fieldset><legend class="mb-1 text-sm text-ink-2">New link valid for</legend>
                        <div class="flex flex-wrap gap-2">@foreach (\App\Models\DocumentRoom::LINK_DAYS as $days)<label class="choice min-h-11 py-2"><input type="radio" name="days" value="{{ $days }}" @checked($days === 30)>{{ $days }} days</label>@endforeach</div>
                    </fieldset>
                    @if ($room->client_email)<label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="email" value="1" checked class="h-5 w-5 accent-[var(--primary)]">Email it to {{ $room->client_email }}</label>@endif
                    <button class="a-btn a-btn-primary"><x-icon name="link" class="h-4 w-4" />Create link</button>
                </form>
                @if ($room->linkIsLive())
                    <form method="POST" action="{{ route('admin.rooms.revoke', $room) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('Stop every link sent so far from working?')">@csrf<button class="a-btn a-btn-danger">Withdraw all links</button></form>
                @endif
            </section>
            <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="a-card space-y-3 p-5">
                @csrf @method('PATCH')
                <h2 class="font-semibold">Room details</h2>
                <div><label for="e-name" class="mb-1 block text-sm text-ink-2">Project</label><input id="e-name" name="name" value="{{ input_string(old('name', $room->name)) }}" class="a-input w-full" required maxlength="160"></div>
                <div><label for="e-client" class="mb-1 block text-sm text-ink-2">Client organization</label><input id="e-client" name="client_name" value="{{ input_string(old('client_name', $room->client_name)) }}" class="a-input w-full" required maxlength="160"></div>
                <div><label for="e-contact" class="mb-1 block text-sm text-ink-2">Client contact</label><input id="e-contact" name="client_contact" value="{{ input_string(old('client_contact', $room->client_contact)) }}" class="a-input w-full" maxlength="120"></div>
                <div><label for="e-email" class="mb-1 block text-sm text-ink-2">Contact email</label><input id="e-email" type="email" name="client_email" value="{{ input_string(old('client_email', $room->client_email)) }}" class="a-input w-full"></div>
                <div><label for="e-desc" class="mb-1 block text-sm text-ink-2">Note to the client</label><textarea id="e-desc" name="description" class="a-input">{{ input_string(old('description', $room->description)) }}</textarea></div>
                <button class="a-btn a-btn-ghost">Save details</button>
            </form>
            @include('admin.partials.timeline', ['notes' => $room->notes, 'action' => null])
        </div>
    </div>
</x-layouts.admin>
