@props(['name', 'label', 'options', 'hint' => null, 'cols' => 'sm:grid-cols-2', 'optional' => false])
{{-- A group of checkbox tiles posting name[]. --}}
@php($chosen = array_filter((array) old($name, []), is_scalar(...)))
<fieldset>
    <legend class="label">{{ $label }}@if($optional) <span class="opt">(optional)</span>@endif</legend>
    @if ($hint)<p class="hint -mt-1 mb-2">{{ $hint }}</p>@endif
    <div class="grid gap-2 {{ $cols }}">
        @foreach ($options as $v => $t)
            <label class="choice"><input type="checkbox" name="{{ $name }}[]" value="{{ $v }}" @checked(in_array((string) $v, array_map('strval', $chosen), true))>{{ $t }}</label>
        @endforeach
    </div>
    <x-error :for="$name" />
</fieldset>
