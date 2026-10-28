<x-layouts.admin :title="$solicitation->reference">
    <x-slot:actions>
        <a href="{{ route('admin.solicitations.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Register</a>
        <a href="mailto:{{ $solicitation->email }}?subject={{ rawurlencode('Re: '.$solicitation->title.' ('.$solicitation->reference.')') }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>
        <a href="{{ tel_href($solicitation->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call {{ $solicitation->contact_name }}</a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status :status="$solicitation->status" /><x-chip :tone="$solicitation->dueTone()" icon="clock">{{ $solicitation->dueLabel() }}</x-chip>@if($solicitation->bond_required)<x-chip tone="neutral" icon="shield">Bid bond required</x-chip>@endif @if($solicitation->set_aside)<x-chip tone="neutral" icon="flag">{{ $solicitation->set_aside->short() }}</x-chip>@endif</div>
                <h2 class="mt-3 text-xl font-bold">{{ $solicitation->title }}</h2>
                <p class="text-ink-2">{{ $solicitation->organization }} · {{ \App\Models\Solicitation::SOURCES[$solicitation->source] ?? $solicitation->source }}</p>
                <dl class="dl-grid mt-5">
                    <dt>Response due</dt><dd class="font-semibold">{{ $solicitation->response_due_at->format('l, F j, Y, g:i a') }} HST</dd>
                    <dt>Type</dt><dd>{{ $solicitation->type->label() }}</dd>
                    @if ($solicitation->solicitation_number)<dt>Solicitation number</dt><dd class="num">{{ $solicitation->solicitation_number }}</dd>@endif
                    <dt>Set-aside</dt><dd>{{ $solicitation->set_aside?->label() ?? 'Not stated' }}</dd>
                    @if ($solicitation->naics)<dt>NAICS</dt><dd class="num">{{ $solicitation->naics }}</dd>@endif
                    <dt>Place of performance</dt><dd>{{ trim(($solicitation->location ?? '').($solicitation->region ? ', '.$solicitation->region : ''), ', ') ?: '—' }}</dd>
                    @if ($solicitation->estimated_value)<dt>Estimated value</dt><dd>{{ $solicitation->estimated_value }} <span class="text-ink-3">(as stated by the buyer)</span></dd>@endif
                    @if ($solicitation->questions_due_on)<dt>Questions due</dt><dd>{{ $solicitation->questions_due_on->format('F j, Y') }}</dd>@endif
                    @if ($solicitation->site_visit_at)<dt>Site visit / pre-proposal</dt><dd>{{ $solicitation->site_visit_at->format('l, F j, Y, g:i a') }}</dd>@endif
                    @if ($solicitation->documents_url)<dt>Documents online</dt><dd><a class="text-link underline" href="{{ $solicitation->documents_url }}" rel="noopener noreferrer" target="_blank">{{ \Illuminate\Support\Str::limit($solicitation->documents_url, 60) }}</a></dd>@endif
                    <dt>Received</dt><dd>{{ $solicitation->created_at->format('M j, Y, g:i a') }}</dd>
                    @if ($solicitation->submitted_at)<dt>Bid submitted</dt><dd>{{ $solicitation->submitted_at->format('M j, Y, g:i a') }}</dd>@endif
                </dl>
                <h3 class="mt-6 font-semibold">Scope of work</h3>
                <p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $solicitation->scope }}</p>
                @if ($solicitation->message)<h3 class="mt-5 font-semibold">Message</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $solicitation->message }}</p>@endif
            </section>

            <section class="a-card" aria-labelledby="files-title">
                <h2 id="files-title" class="px-5 pt-4 font-semibold">Solicitation documents</h2>
                @forelse ($solicitation->files as $f)
                    <a href="{{ route('admin.solicitations.file', [$solicitation, $f]) }}" class="mt-3 flex items-center gap-3 border-t border-line px-5 py-3 hover:bg-surface-2">
                        <x-icon name="file" class="h-5 w-5 flex-none text-ink-3" /><span class="min-w-0 flex-1 truncate font-medium">{{ $f->original_name }}</span><span class="num text-sm text-ink-3">{{ file_size($f->size) }}</span><x-icon name="download" class="h-4 w-4 text-ink-3" />
                    </a>
                @empty
                    <p class="px-5 py-4 text-ink-3">No files attached{{ $solicitation->documents_url ? '; see the link above' : '' }}.</p>
                @endforelse
                <p class="border-t border-line px-5 py-3 text-xs text-ink-3"><x-icon name="lock" class="inline h-3.5 w-3.5" /> Stored privately; each opening is logged on the timeline.</p>
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Bid / no-bid</h2>
                <form method="POST" action="{{ route('admin.solicitations.update', $solicitation) }}" class="mt-3 space-y-4">
                    @csrf @method('PATCH')
                    <div><label for="t-status" class="mb-1 block text-sm text-ink-2">Status</label>
                        <select id="t-status" name="status" class="a-input w-full">@foreach (\App\Enums\SolicitationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($solicitation->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                    <div><label for="t-assignee" class="mb-1 block text-sm text-ink-2">Owner</label>
                        <select id="t-assignee" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected($solicitation->assigned_to === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </section>
            <section class="a-card p-5">
                <h2 class="font-semibold">Buyer contact</h2>
                <dl class="dl-grid mt-3">
                    <dt>Name</dt><dd>{{ $solicitation->contact_name }}@if($solicitation->contact_role)<span class="text-ink-3">, {{ $solicitation->contact_role }}</span>@endif</dd>
                    <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($solicitation->phone) }}">{{ format_phone($solicitation->phone) }}</a></dd>
                    <dt>Email</dt><dd><a class="text-link underline" href="mailto:{{ $solicitation->email }}">{{ $solicitation->email }}</a></dd>
                </dl>
            </section>
            @include('admin.partials.timeline', ['notes' => $solicitation->notes, 'action' => route('admin.solicitations.note', $solicitation)])
        </div>
    </div>
</x-layouts.admin>
