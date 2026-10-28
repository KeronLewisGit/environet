@props(['label' => 'Send'])
<button type="submit" class="btn btn-primary w-full sm:w-auto" :disabled="submitting" :aria-busy="submitting.toString()">
    <span x-show="!submitting">{{ $label }}</span><span hidden :hidden="!submitting">Sending…</span>
    <x-icon name="arrow-right" class="nudge h-4 w-4" x-show="!submitting" />
</button>
