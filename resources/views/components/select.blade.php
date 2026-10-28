@props(['name', 'label', 'options' => [], 'required' => false, 'hint' => null, 'value' => null, 'placeholder' => 'Choose…', 'optional' => false])
@php
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $current = input_string(old($name, $value));
@endphp
<div>
    <label for="{{ $id }}" class="label">{{ $label }}@if($optional) <span class="opt">(optional)</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif @if($hint) aria-describedby="{{ $id }}-hint" @endif {{ $attributes->merge(['class' => 'input']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
        @foreach ($options as $v => $text)
            @if (is_array($text))
                <optgroup label="{{ $v }}">
                    @foreach ($text as $v2 => $t2)<option value="{{ $v2 }}" @selected($current === (string) $v2)>{{ $t2 }}</option>@endforeach
                </optgroup>
            @else
                <option value="{{ $v }}" @selected($current === (string) $v)>{{ $text }}</option>
            @endif
        @endforeach
    </select>
    @if ($hint)<span id="{{ $id }}-hint" class="hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
