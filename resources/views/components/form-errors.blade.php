@if ($errors->any())
    <div class="panel mb-8 border-l-[6px] border-l-[color:var(--critical)] p-5" role="alert" tabindex="-1" id="form-errors">
        <p class="flex items-center gap-2 font-bold"><x-icon name="alert" class="h-5 w-5 text-[color:var(--critical)]" />Please check {{ $errors->count() === 1 ? 'one thing' : $errors->count().' things' }} below</p>
        <ul class="mt-2 list-disc space-y-1 pl-12 text-ink-2">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
