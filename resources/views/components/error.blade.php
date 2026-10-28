@props(['for'])
@error($for)
    <p class="error" id="{{ str_replace(['.', '[', ']'], '-', $for) }}-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" />{{ $message }}</p>
@enderror
