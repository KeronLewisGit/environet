@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null, 'value' => null, 'optional' => false])
{{-- Label, input, hint and error wired together with ids and aria. Extra attributes go on the input. --}}
@php
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($errors->has($name) ? str_replace(['.', '[', ']'], '-', $name).'-error' : ''));
@endphp
<div>
    <label for="{{ $id }}" class="label">{{ $label }}@if($optional) <span class="opt">(optional)</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => 'input']) }}>{{ input_string(old($name, $value)) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ input_string(old($name, $value)) }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => 'input']) }}>
    @endif
    @if ($hint)<span id="{{ $id }}-hint" class="hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
