@props(['label', 'value', 'previous' => null, 'period' => null, 'note' => null, 'href' => null, 'upIsGood' => true])
@php
    $delta = $previous !== null ? $value - $previous : null;
    $tone = $delta === null || $delta === 0 ? 'neutral' : (($delta > 0) === $upIsGood ? 'good' : 'critical');
@endphp
<a href="{{ $href }}" class="a-card block p-5 transition hover:shadow-lg focus-visible:shadow-lg">
    <p class="text-sm text-ink-2">{{ $label }}</p>
    <p class="mt-2 text-[2rem] font-semibold leading-none tracking-tight">{{ compact_number($value) }}</p>
    @if ($delta !== null)
        <p class="mt-3 flex items-center gap-1.5 text-sm">
            <span class="chip num" data-tone="{{ $tone }}">
                <x-icon :name="$delta > 0 ? 'arrow-right' : ($delta < 0 ? 'arrow-right' : 'dot')" @class(['-rotate-45' => $delta > 0, 'rotate-45' => $delta < 0]) />{{ $delta > 0 ? '+' : '' }}{{ number_format($delta) }}
            </span>
            <span class="text-ink-3">{{ $period }}</span>
        </p>
    @elseif ($note)
        <p class="mt-3 text-sm text-ink-3">{{ $note }}</p>
    @endif
</a>
