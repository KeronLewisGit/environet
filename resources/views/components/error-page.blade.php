@props(['code', 'message' => null])
@php
    $copy = [
        '404' => ['That page has moved', 'The link may be from our old website. Everything is here: services, past performance, RFQs, teaming and careers.'],
        '403' => ['You can\'t open that', 'This link has expired or been withdrawn, or your account does not have access. Ask your Environet contact for a new link.'],
        '419' => ['Your session timed out', 'For your security the form expired. Go back, refresh the page and send it again.'],
        '429' => ['Too many tries', 'Please wait a minute and try again, or call us.'],
        '500' => ['Something went wrong on our side', 'Please try again in a minute. If you were sending a form, email or call us and we will take it from there.'],
        '503' => ['Back shortly', 'We are updating the website. Please try again in a few minutes.'],
    ][$code] ?? ['Something went wrong', 'Please try again.'];
@endphp
<x-layouts.site :title="$copy[0]" :mobile-bar="false">
    <x-page-header photo="waimea" :title="$copy[0]" :lead="$message ?: $copy[1]" :kicker="'Error '.$code" :tag="false" />
    <section class="wrap mt-12 flex flex-wrap gap-3 md:mt-16">
        <a href="{{ route('home') }}" class="btn btn-primary">Go to the home page<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        @if ($code === '404')
            <a href="{{ route('performance.index') }}" class="btn btn-outline">See past performance</a>
        @endif
        <a href="{{ tel_href(enet('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(enet('phone')) }}</a>
    </section>
</x-layouts.site>
