@props(['state'])
@php
    [$tone, $icon, $label] = match ($state) {
        'valid' => ['good', 'check-circle', 'Insurance and certificates current'],
        'expiring' => ['attention', 'clock', 'Renewal due'],
        'expired' => ['critical', 'alert', 'Certificate expired'],
        default => ['critical', 'alert', 'Certificate missing'],
    };
@endphp
<x-chip :tone="$tone" :icon="$icon" {{ $attributes }}>{{ $label }}</x-chip>
