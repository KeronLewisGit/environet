@php
    $ld = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $job->summary,
        'datePosted' => $job->created_at->toDateString(), 'employmentType' => $job->employment_type->schema(),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => enet('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location, 'addressCountry' => 'US']]]
        + ($job->closes_on ? ['validThrough' => $job->closes_on->endOfDay()->toIso8601String()] : []);
@endphp
<x-layouts.site :title="$job->title" :description="$job->summary" :json-ld="[$ld]">
    <x-page-header :title="$job->title" :kicker="$job->category->label()" tone="orange" photo="crew-concrete" position="center 35%" :lead="$job->summary"
        :tag-fields="['Location' => $job->location, 'Openings' => (string) $job->openings, 'Type' => $job->employment_type->label(), 'Closes' => $job->closes_on ? us_date($job->closes_on, true) : 'Open until filled']">
        <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-light">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a><a href="{{ route('careers.index') }}" class="btn btn-on-dark">All openings</a></div>
    </x-page-header>
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-2">
        @if ($job->dutyList())
            <div><x-flag>The work</x-flag><ul class="ticked mt-5 space-y-2.5">@foreach ($job->dutyList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
        @if ($job->requirementList())
            <div><x-flag tone="orange">You bring</x-flag><ul class="ticked mt-5 space-y-2.5">@foreach ($job->requirementList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
    </section>
    @if ($job->is_demo)<p class="wrap mt-10"><span class="demo-badge">Sample posting for the demo</span></p>@endif
    <section class="wrap mt-12">
        <a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-primary">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        <p class="hint measure mt-6">{{ enet('careers.eeo') }}</p>
    </section>
</x-layouts.site>
