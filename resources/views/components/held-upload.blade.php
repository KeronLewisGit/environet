@props(['form', 'field'])
{{-- Files kept from an earlier try at this form (App\Support\HeldUploads). Leave the input empty to keep them. --}}
@php($names = \App\Support\HeldUploads::names($form, $field))
@if ($names !== [])
    <p class="held mt-2 flex items-start gap-2 text-ink-2" id="{{ str_replace(['.', '[', ']'], '-', $field) }}-held">
        <x-icon name="check" class="mt-1 h-4 w-4 flex-none text-[color:var(--good)]" />
        <span><strong class="font-semibold text-ink">Attached:</strong> {{ implode(', ', $names) }}. Leave this empty to keep {{ count($names) === 1 ? 'it' : 'them' }}, or choose {{ count($names) === 1 ? 'a file' : 'files' }} to replace {{ count($names) === 1 ? 'it' : 'them' }}.</span>
    </p>
@endif
