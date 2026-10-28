<x-layouts.admin :title="$application->full_name">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Applications</a>
        @if ($application->email)<a href="mailto:{{ $application->email }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>@endif
        <a href="{{ tel_href($application->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <section class="a-card min-w-0 p-5 md:p-6">
            <x-status :status="$application->status" />
            <h2 class="mt-3 text-xl font-bold">{{ $application->position() }}</h2>
            <dl class="dl-grid mt-5">
                <dt>Reference</dt><dd class="num">{{ $application->reference }}</dd>
                <dt>Type of work</dt><dd>{{ $application->category->label() }}</dd>
                <dt>Experience</dt><dd>{{ $application->experience->label() }}</dd>
                <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($application->phone) }}">{{ format_phone($application->phone) }}</a></dd>
                <dt>Email</dt><dd>{{ $application->email ?? '—' }}</dd>
                <dt>Office</dt><dd>{{ $application->office->label() }}@if($application->can_travel)<span class="text-ink-3"> · can travel to project sites</span>@endif</dd>
                @if ($application->region)<dt>Lives in</dt><dd>{{ $application->region }}</dd>@endif
                <dt>Available from</dt><dd>{{ $application->available_from?->format('F j, Y') ?? 'Not given' }}</dd>
                <dt>Received</dt><dd>{{ $application->created_at->format('M j, Y, g:i a') }}</dd>
            </dl>
            @if ($application->qualifications)<h3 class="mt-6 font-semibold">Other qualifications and trades</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $application->qualifications }}</p>@endif
            @if ($application->message)<h3 class="mt-5 font-semibold">Message</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $application->message }}</p>@endif
            <div class="mt-6 border-t border-line pt-5">
                @if ($application->cv_path)
                    <a href="{{ route('admin.applications.cv', $application) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Download résumé ({{ file_size($application->cv_size) }})</a>
                    <p class="mt-2 text-xs text-ink-3"><x-icon name="lock" class="inline h-3.5 w-3.5" /> HR and administrators only; each download is logged.</p>
                @else
                    <p class="text-ink-3">No résumé attached.</p>
                @endif
            </div>
        </section>
        <div class="min-w-0 space-y-6">
            <section class="a-card" aria-labelledby="certs-title">
                <h2 id="certs-title" class="px-5 pt-4 font-semibold">Certifications <span class="text-sm font-normal text-ink-3">(self-declared)</span></h2>
                @forelse ($application->certifications as $c)
                    <div class="mt-3 border-t border-line px-5 py-3">
                        <div class="flex flex-wrap items-center gap-2"><span class="font-medium">{{ $c->kind->label() }}</span>@if($c->detail)<span class="text-ink-3">· {{ $c->detail }}</span>@endif</div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <x-chip :tone="$c->tone()" icon="{{ $c->state() === 'valid' ? 'check-circle' : 'clock' }}">{{ $c->stateLabel() }}</x-chip>
                            @if ($c->checked_at)<x-chip tone="good" icon="shield">Checked by {{ $c->checker->name ?? 'staff' }}, {{ $c->checked_at->format('M j') }}</x-chip>@else<x-chip tone="neutral" icon="eye">Not yet checked</x-chip>@endif
                        </div>
                        @unless ($c->checked_at)
                            <form method="POST" action="{{ route('admin.applications.certifications.check', [$application, $c]) }}" class="mt-3 flex flex-wrap items-end gap-2">
                                @csrf @method('PATCH')
                                <div><label for="c-exp-{{ $c->id }}" class="mb-1 block text-xs text-ink-2">Expiry on the card</label><input id="c-exp-{{ $c->id }}" type="date" name="expires_on" value="{{ $c->expires_on?->format('Y-m-d') }}" class="a-input"></div>
                                <button class="a-btn a-btn-ghost"><x-icon name="check" class="h-4 w-4" />Seen the card</button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <p class="px-5 py-4 text-ink-3">None listed.</p>
                @endforelse
                <p class="border-t border-line px-5 py-3 text-xs text-ink-3">Applicants declare these themselves. Check the card or certificate before anyone goes to site.</p>
            </section>
            <section class="a-card p-5">
                <h2 class="font-semibold">Status</h2>
                <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf @method('PATCH')
                    <label for="a-status" class="sr-only">Status</label>
                    <select id="a-status" name="status" class="a-input min-w-0 flex-1">@foreach (\App\Enums\ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($application->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </section>
            @include('admin.partials.timeline', ['notes' => $application->notes, 'action' => route('admin.applications.note', $application)])
        </div>
    </div>
</x-layouts.admin>
