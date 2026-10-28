{{--
    Survey sweep behind the home hero's registration tag: range rings on a survey grid, one slow
    sweep and the anomaly marks it picks up, then it stays still. Decorative, so aria-hidden;
    with reduced motion (or no JS) the rings and marks are shown without the sweep.
--}}
<svg {{ $attributes->merge(['class' => 'sonar']) }} viewBox="0 0 400 400" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="sonar-fade" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#3fc35a" stop-opacity="0" />
            <stop offset="1" stop-color="#3fc35a" stop-opacity=".32" />
        </linearGradient>
    </defs>
    <g class="sonar-grid">
        @for ($i = 0; $i <= 400; $i += 40)
            <path d="M{{ $i }} 0V400M0 {{ $i }}H400" />
        @endfor
    </g>
    <g class="sonar-rings">
        <circle cx="200" cy="200" r="60" /><circle cx="200" cy="200" r="120" /><circle cx="200" cy="200" r="180" />
        <path d="M200 10V390M10 200H390" />
    </g>
    <path class="sonar-sweep" d="M200 200L380 200A180 180 0 0 0 355.9 110Z" fill="url(#sonar-fade)" />
    <g class="sonar-marks">
        <circle cx="290" cy="150" r="7" style="--d: 0.92" /><circle cx="128" cy="96" r="5" style="--d: 0.65" />
        <circle cx="112" cy="268" r="6" style="--d: 0.39" /><circle cx="248" cy="300" r="5" style="--d: 0.18" />
    </g>
</svg>
