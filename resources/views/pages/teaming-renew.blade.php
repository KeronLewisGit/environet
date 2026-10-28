<x-layouts.site title="Renew your certificates" :mobile-bar="false">
    <x-page-header photo="crew-concrete" position="center 35%" title="Renew your insurance and certificates" kicker="Teaming and vendors" tone="green" :tag="false" :lead="'Upload the renewed copies for '.$vendor->company_name.' ('.$vendor->reference.'). Only the documents you upload are replaced.'" />
    <section class="wrap mt-12 max-w-3xl md:mt-16">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            @foreach (\App\Enums\DocumentKind::certificates() as $kind)
                @php($current = $vendor->current($kind))
                @continue(! $current && ! in_array($kind, $vendor->type->requiredDocuments(), true))
                <div class="panel space-y-4 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h2 class="font-display text-lg font-semibold">{{ $kind->label() }}</h2>
                        @if ($current)
                            <x-chip :tone="match ($current->state()) { 'valid' => 'good', 'expiring' => 'attention', default => 'critical' }" icon="clock">{{ $current->state() === 'expired' ? 'Expired' : 'Expires' }} {{ $current->expires_on?->format('M j, Y') }}</x-chip>
                        @else
                            <x-chip tone="critical" icon="alert">Not on file</x-chip>
                        @endif
                    </div>
                    <div><label for="r-{{ $kind->value }}" class="label">Renewed document</label><input id="r-{{ $kind->value }}" type="file" name="docs[{{ $kind->value }}][file]" class="input" accept=".pdf,.jpg,.jpeg,.png"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="rn-{{ $kind->value }}" class="label">{{ $kind->numberLabel() }} <span class="opt">(optional)</span></label><input id="rn-{{ $kind->value }}" name="docs[{{ $kind->value }}][number]" class="input" maxlength="80"></div>
                        <div><label for="re-{{ $kind->value }}" class="label">New expiration date</label><input id="re-{{ $kind->value }}" type="date" name="docs[{{ $kind->value }}][expires_on]" class="input" min="{{ today()->addDay()->format('Y-m-d') }}"></div>
                    </div>
                </div>
            @endforeach
            <x-error for="docs" />
            <x-submit label="Upload renewals" />
        </form>
    </section>
</x-layouts.site>
