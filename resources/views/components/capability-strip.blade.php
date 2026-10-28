@php
    $naics = (array) enet('registration.naics');
@endphp
{{--
    Capability strip: the firm's registered NAICS codes, from its SBA profile. Without JS, or with
    reduced motion, it is a plain wrapped list. With motion, JS copies the list once (the copy is
    aria-hidden) and it drifts sideways; it stops on hover, on focus and with the pause button.
--}}
<section {{ $attributes->merge(['class' => 'capstrip on-dark']) }} aria-labelledby="capstrip-title" data-marquee>
    <div class="capstrip-inner">
        <h2 id="capstrip-title" class="capstrip-title">NAICS codes<span class="sr-only"> registered with the SBA</span></h2>
        <div class="capstrip-viewport">
            <ul class="capstrip-track" role="list">
                @foreach ($naics as $code => $label)
                    <li><b class="num">{{ $code }}</b> {{ $label }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="capstrip-pause" data-marquee-toggle aria-pressed="false" hidden>
            <span class="sr-only">Pause the moving list</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="when-running"><path d="M8 5v14M16 5v14" /></svg>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="when-paused"><path d="M8 5l11 7-11 7z" /></svg>
        </button>
    </div>
</section>
