<x-layouts.site title="Thank you" :mobile-bar="false">
    <x-page-header photo="waimea" position="center 60%" title="Thank you. We have it." :kicker="$kind" tone="green" :tag="false" lead="Your reference number is below. We have emailed it to you as well." />
    <section class="wrap mt-12 md:mt-16">
        <div class="max-w-md">
            <p class="logged mb-5"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3a13 13 0 1 1 0 26a13 13 0 1 1 0-26" pathLength="1" /><path d="M10 16.5l4.2 4.2L22.5 12" pathLength="1" /></svg>Logged as {{ $reference }}</p>
            <x-sample-tag class="receipt" :tilt="false" title="Receipt" :foot="false" :fields="['Reference' => $reference, 'Received' => now()->format('M j, Y, g:i a').' '.enet('timezone_label'), 'Type' => $kind, 'Received by' => enet('name')]" />
            <p class="mt-6 text-ink-2">Quote the reference if you call or email, and we will find your submission straight away.</p>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ tel_href(enet('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(enet('phone')) }}</a>
            <a href="{{ route('performance.index') }}" class="btn btn-outline">See past performance</a>
            <a href="{{ route('home') }}" class="btn btn-primary">Back to the home page</a>
        </div>
    </section>
</x-layouts.site>
