@props(['title' => 'Registration', 'fields' => null, 'tilt' => true, 'foot' => true])
{{--
    Chain-of-custody sample tag holding the firm's public registration identifiers. Every value
    comes from the SBA small business profile (config environet.registration).
--}}
@php
    $reg = (array) enet('registration');
    $fields ??= [
        'UEI' => $reg['uei'],
        'CAGE' => $reg['cage'],
        'NAICS' => $reg['naics_primary'].' (primary)',
        'SBA' => collect($reg['certifications'])->pluck('name')->implode(' · '),
    ];
@endphp
<div {{ $attributes->merge(['class' => 'sample-tag'.($tilt ? ' tilt' : '')]) }}>
    <p class="tag-head"><span>{{ $title }}</span><span class="num">Est. {{ enet('founded') }}</span></p>
    <dl>
        @foreach ($fields as $label => $value)
            <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
        @endforeach
    </dl>
    @if ($foot)
        <p class="tag-foot">As published on the <a href="{{ $reg['source']['url'] }}" target="_blank" rel="noopener">SBA profile<span class="sr-only"> (opens in a new tab)</span></a>, {{ us_date($reg['source']['checked'], true) }}.</p>
    @endif
</div>
