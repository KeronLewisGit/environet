<x-layouts.admin :title="$vendor->company_name">
    <x-slot:actions>
        <a href="{{ route('admin.vendors.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Register</a>
        <a href="mailto:{{ $vendor->email }}?subject={{ rawurlencode('Vendor registration '.$vendor->reference) }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>
        <a href="{{ tel_href($vendor->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
    </x-slot:actions>

    @php($manage = auth()->user()?->can('update', $vendor) ?? false)
    @unless ($manage)
        <p class="mb-5 flex gap-3 rounded border border-line bg-surface p-4 text-sm" role="status"><x-icon name="eye" class="h-5 w-5 flex-none text-link" />You can see this company as a possible teaming partner. Procurement checks its documents and decides on approval.</p>
    @endunless
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card" aria-labelledby="certs-title">
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-4"><h2 id="certs-title" class="font-semibold">Required insurance and certificates</h2><x-compliance :state="$vendor->complianceState()" /></div>
                <div class="mt-3 divide-y divide-line border-t border-line">
                    @foreach ($vendor->compliance() as $c)
                        @php($d = $c['document'])
                        <div class="grid gap-3 p-5 md:grid-cols-[minmax(0,1fr)_auto]">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $c['kind']->label() }}</p>
                                @if ($d)
                                    <p class="text-sm text-ink-2">{{ $d->number ?: 'No number given' }} · expires <span class="num font-semibold">{{ $d->expires_on?->format('M j, Y') ?? '—' }}</span>
                                        @if ($d->daysLeft() !== null)<span class="text-ink-3">({{ $d->daysLeft() >= 0 ? $d->daysLeft().' days left' : abs($d->daysLeft()).' days ago' }})</span>@endif</p>
                                    <p class="mt-1 text-xs text-ink-3">Uploaded {{ $d->created_at->format('M j, Y') }}@if($d->verified_at) · checked by {{ $d->verifier->name ?? 'staff' }} {{ $d->verified_at->format('M j, Y') }}@else · <strong>not yet checked</strong>@endif @if($d->reminded_at) · renewal reminder sent {{ $d->reminded_at->format('M j') }}@endif</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <x-chip :tone="match ($d->state()) { 'valid' => 'good', 'expiring' => 'attention', default => 'critical' }" :icon="$d->state() === 'valid' ? 'check-circle' : 'clock'">{{ ucfirst($d->state()) }}</x-chip>
                                        @if ($manage)<a href="{{ route('admin.vendors.document', [$vendor, $d]) }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener"><x-icon name="file" class="h-4 w-4" />Open {{ $d->kind->short() }}</a>@endif
                                    </div>
                                @else
                                    <p class="mt-1"><x-chip tone="critical" icon="alert">Not provided</x-chip></p>
                                @endif
                            </div>
                            @if ($d && $manage)
                                <form method="POST" action="{{ route('admin.vendors.verify', [$vendor, $d]) }}" class="flex flex-wrap items-end gap-2 md:w-64 md:flex-col md:items-stretch">
                                    @csrf @method('PATCH')
                                    <div><label class="mb-1 block text-xs text-ink-2" for="exp-{{ $d->id }}">Expiry on the certificate</label><input id="exp-{{ $d->id }}" type="date" name="expires_on" value="{{ $d->expires_on?->format('Y-m-d') }}" class="a-input w-full"></div>
                                    <div><label class="mb-1 block text-xs text-ink-2" for="num-{{ $d->id }}">{{ $d->kind->numberLabel() }}</label><input id="num-{{ $d->id }}" name="number" value="{{ $d->number }}" class="a-input w-full" maxlength="80"></div>
                                    <button class="a-btn a-btn-ghost"><x-icon name="check" class="h-4 w-4" />Mark checked</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                    @foreach ($vendor->currentDocuments->filter(fn ($d) => ! in_array($d->kind, $vendor->type->requiredDocuments(), true)) as $d)
                        <div class="flex flex-wrap items-center gap-3 p-5"><x-icon name="file" class="h-5 w-5 text-ink-3" /><span class="min-w-0 flex-1"><span class="block truncate">{{ $d->kind->short() }}: {{ $d->original_name }}</span>@if($d->expires_on)<span class="num block text-xs text-ink-3">Expires {{ $d->expires_on->format('M j, Y') }}</span>@endif</span>@if($manage)<a href="{{ route('admin.vendors.document', [$vendor, $d]) }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener">Open</a>@endif</div>
                    @endforeach
                </div>
                @php($old = $vendor->documents->whereNotNull('superseded_at'))
                @if ($old->isNotEmpty())
                    <details class="border-t border-line px-5 py-3 text-sm" @unless($manage) hidden @endunless><summary class="min-h-11 cursor-pointer py-2 font-semibold">Earlier copies ({{ $old->count() }})</summary>
                        <ul class="space-y-1 pb-2">@foreach ($old as $d)<li><a class="text-link underline" href="{{ route('admin.vendors.document', [$vendor, $d]) }}">{{ $d->kind->short() }}, expired {{ $d->expires_on?->format('M j, Y') }}</a> <span class="text-ink-3">replaced {{ $d->superseded_at?->format('M j, Y') }}</span></li>@endforeach</ul>
                    </details>
                @endif
                <p class="border-t border-line px-5 py-3 text-xs text-ink-3"><x-icon name="lock" class="inline h-3.5 w-3.5" /> Stored privately; procurement and administrators only. Each opening is logged.</p>
            </section>

            <section class="a-card p-5 md:p-6">
                <h2 class="font-semibold">Company</h2>
                <dl class="dl-grid mt-3">
                    <dt>Name</dt><dd>{{ $vendor->displayName() }}</dd>
                    <dt>Type</dt><dd>{{ $vendor->type->label() }}</dd>
                    <dt>UEI</dt><dd class="num">{{ $vendor->uei ?: 'Not given' }}@if($vendor->uei) <a class="text-link underline" href="https://sam.gov/search/?index=ent&keywords={{ $vendor->uei }}" target="_blank" rel="noopener">check on SAM.gov</a>@endif</dd>
                    @if ($vendor->cage)<dt>CAGE</dt><dd class="num">{{ $vendor->cage }}</dd>@endif
                    <dt>Self-declared status</dt><dd>{{ $vendor->setAsideLabels(false) ?: 'None declared' }}@if($vendor->set_asides)<span class="block text-xs text-ink-3">Confirm on SAM.gov and the SBA search before relying on it for a bid.</span>@endif</dd>
                    @if ($vendor->naics)<dt>NAICS</dt><dd class="num">{{ $vendor->naics }}</dd>@endif
                    @if ($vendor->year_established)<dt>Established</dt><dd>{{ $vendor->year_established }}</dd>@endif
                    <dt>Address</dt><dd>{{ $vendor->address }}, {{ $vendor->city }}, {{ $vendor->state }}</dd>
                    <dt>Work</dt><dd>{{ collect($vendor->trades)->map(fn ($t) => enet('trades.'.$t) ?? $t)->implode(', ') }}</dd>
                    @if ($vendor->regions_served)<dt>Works in</dt><dd>{{ implode(', ', $vendor->regions_served) }}</dd>@endif
                    @if ($vendor->employees)<dt>Employees</dt><dd>{{ \App\Models\Vendor::EMPLOYEE_BANDS[$vendor->employees] ?? $vendor->employees }}</dd>@endif
                    @if ($vendor->bonding)<dt>Bonding capacity (declared)</dt><dd>{{ $vendor->bonding }}</dd>@endif
                </dl>
                @if ($vendor->experience)<h3 class="mt-5 font-semibold">Relevant work</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $vendor->experience }}</p>@endif
                @if ($vendor->message)<h3 class="mt-5 font-semibold">Message</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $vendor->message }}</p>@endif
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Decision</h2>
                <p class="mt-1 text-sm text-ink-2"><x-status :status="$vendor->status" />@if($vendor->approved_at) since {{ $vendor->approved_at->format('M j, Y') }}@endif</p>
                @if ($manage)
                <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf @method('PATCH')
                    <label for="v-status" class="sr-only">Status</label>
                    <select id="v-status" name="status" class="a-input min-w-0 flex-1">@foreach (\App\Enums\VendorStatus::cases() as $s)<option value="{{ $s->value }}" @selected($vendor->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
                <p class="mt-3 text-xs text-ink-3">A vendor can only be approved while every required certificate and policy is on file and in date.</p>
                @endif
            </section>
            <section class="a-card p-5">
                <h2 class="font-semibold">Contact</h2>
                <dl class="dl-grid mt-3">
                    <dt>Name</dt><dd>{{ $vendor->contact_name }}@if($vendor->contact_role)<span class="text-ink-3">, {{ $vendor->contact_role }}</span>@endif</dd>
                    <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($vendor->phone) }}">{{ format_phone($vendor->phone) }}</a></dd>
                    <dt>Email</dt><dd><a class="text-link underline" href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a></dd>
                    <dt>Applied</dt><dd>{{ $vendor->created_at->format('M j, Y') }} ({{ $vendor->reference }})</dd>
                </dl>
            </section>
            @include('admin.partials.timeline', ['notes' => $vendor->notes, 'action' => route('admin.vendors.note', $vendor)])
        </div>
    </div>
</x-layouts.admin>
