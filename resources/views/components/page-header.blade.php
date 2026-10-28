@props(['title', 'lead' => null, 'kicker' => null, 'tone' => 'navy', 'photo' => null, 'position' => 'center', 'tag' => true, 'tagFields' => null, 'photoNote' => null])
{{--
    The one header band every inner page uses, so titles start at the same height and left edge
    site-wide: a navy band with survey lanes and anomaly rings (or a photo behind a scrim), a
    survey-flag kicker, the registration sample tag on the right, and a stratigraphy band
    closing it. Breadcrumbs, actions and chips go in the slot. The home hero is a taller variant.
--}}
<section {{ $attributes->merge(['class' => 'lanes relative isolate overflow-hidden bg-navy-900 text-white on-dark']) }}>
    @if ($photo)
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo :key="$photo" alt="" :eager="true" sizes="100vw" class="h-full w-full" :position="$position" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap grid gap-8 pb-10 pt-10 md:pb-14 md:pt-16 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-end lg:gap-12">
        <div class="min-w-0">
            @if ($kicker)<x-flag :tone="$tone">{{ $kicker }}</x-flag>@endif
            <h1 class="display-2 mt-5 max-w-4xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-white/85 md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        @if ($tag)<x-sample-tag :fields="$tagFields" class="hidden lg:block" />@endif
    </div>
    @if ($photo)
        <span class="photo-note" style="bottom: 3rem">{{ $photoNote ?? (in_array($photo, (array) enet('own_photos'), true) ? 'Environet photo' : 'Illustrative photo') }}</span>
    @endif
    <div class="strata" aria-hidden="true"></div>
</section>
