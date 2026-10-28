<x-layouts.site title="Site map" description="Every page on the Environet, Inc. website.">
    <x-page-header photo="honolulu" title="Site map" kicker="Site map" :tag="false" lead="Every public page on this website." />
    <section class="wrap mt-12 grid gap-10 md:mt-16 md:grid-cols-3">
        <div><h2 class="font-display text-lg font-semibold">The firm</h2><ul class="mt-3 space-y-1">
            @foreach (['home' => 'Home', 'services' => 'Services', 'about' => 'About the firm', 'capability' => 'Capability statement', 'contact' => 'Contact', 'privacy' => 'Privacy notice', 'credits' => 'Photo credits'] as $r => $l)
                <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route($r) }}">{{ $l }}</a></li>
            @endforeach
        </ul></div>
        <div><h2 class="font-display text-lg font-semibold">Past performance</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('performance.index') }}">All federal awards</a></li>
            @foreach ($awards as $a)<li><a class="inline-flex min-h-11 items-center py-1 text-link" href="{{ route('performance.show', $a['slug']) }}">{{ $a['title'] }}</a></li>@endforeach
        </ul></div>
        <div><h2 class="font-display text-lg font-semibold">Work with us</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('solicitations.create') }}">RFQs and bid inquiries</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('teaming.create') }}">Teaming partners and vendors</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.index') }}">Careers</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.apply') }}">Apply</a></li>
            @foreach ($jobs as $j)<li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.show', $j->slug) }}">{{ $j->title }}</a></li>@endforeach
        </ul></div>
    </section>
</x-layouts.site>
