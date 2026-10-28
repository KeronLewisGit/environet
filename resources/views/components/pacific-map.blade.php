@php
    use App\Support\PacificMap;
    $places = collect(PacificMap::places());
    $routes = PacificMap::routes();
    $w = PacificMap::WIDTH;
    $h = PacificMap::HEIGHT;
    $groups = [
        'office' => ['title' => 'Offices', 'note' => 'As listed on the firm\'s website and SBA profile'],
        'award' => ['title' => 'Award sites beyond Hawaiʻi and Guam', 'note' => 'Places of performance on USAspending.gov'],
        'earlier' => ['title' => 'Earlier work', 'note' => 'Named on the firm\'s own website'],
    ];
@endphp
{{--
    Pacific map: offices in Hawaiʻi, Guam and Colorado, with routes from Honolulu that draw out
    when the map scrolls into view. Land is resources/img/pacific-land.svg (Natural Earth, public
    domain) shown as a mask so it takes the theme's colour; every place is in App\Support\PacificMap
    with its source. Without JS, or with reduced motion, the routes are simply drawn. The list
    below the map carries the same places as text.
--}}
<figure {{ $attributes->merge(['class' => 'pmap']) }} data-map>
    <div class="pmap-stage">
        <div class="pmap-land" aria-hidden="true"></div>
        <svg class="pmap-svg" viewBox="0 0 {{ $w }} {{ $h }}" aria-hidden="true" focusable="false">
            <g class="pmap-grid">
                @for ($lon = 120; $lon <= 290; $lon += 10)
                    @php([$gx] = PacificMap::project(0, $lon > 180 ? $lon - 360 : $lon))
                    <path d="M{{ $gx }} 0V{{ $h }}" />
                @endfor
                @for ($lat = 10; $lat <= 50; $lat += 10)
                    @php([, $gy] = PacificMap::project($lat, 150))
                    <path d="M0 {{ $gy }}H{{ $w }}" />
                @endfor
            </g>
            <g class="pmap-routes">
                @foreach ($routes as $i => $r)
                    <path class="pmap-route" data-kind="{{ $r['kind'] }}" d="{{ $r['d'] }}" pathLength="1" style="--i: {{ $i }}" />
                @endforeach
            </g>
            <g class="pmap-sites">
                @foreach ($places as $i => $p)
                    <g class="pmap-site" data-kind="{{ $p['kind'] }}" style="--i: {{ $p['key'] === PacificMap::HUB ? 0 : $i }}">
                        @if ($p['kind'] === 'office')<circle class="pmap-ping" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="9" />@endif
                        <circle class="pmap-dot" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $p['kind'] === 'office' ? 9 : 6 }}" />
                        <text class="pmap-label" x="{{ $p['x'] + ($p['dx'] ?? 14) }}" y="{{ $p['y'] + ($p['dy'] ?? 8) }}" text-anchor="{{ $p['anchor'] ?? 'start' }}">{{ $p['label'] }}</text>
                    </g>
                @endforeach
            </g>
        </svg>
    </div>
    <figcaption class="pmap-key">
        @foreach ($groups as $kind => $g)
            <div>
                <p class="pmap-key-title"><span class="pmap-swatch" data-kind="{{ $kind }}" aria-hidden="true"></span>{{ $g['title'] }}</p>
                <p class="mt-1 text-ink-2">{{ $places->where('kind', $kind)->pluck('label')->implode(', ') }}</p>
                <p class="mt-1 text-sm text-ink-3">{{ $g['note'] }}</p>
            </div>
        @endforeach
    </figcaption>
</figure>
