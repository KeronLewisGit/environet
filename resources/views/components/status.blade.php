@props(['status'])
<x-chip :tone="$status->tone()" :icon="$status->icon()" {{ $attributes }}>{{ $status->label() }}</x-chip>
