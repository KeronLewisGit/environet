<x-layouts.admin :title="$job->exists ? 'Edit posting' : 'New posting'">
    <x-slot:actions>
        <a href="{{ route('admin.jobs.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Postings</a>
        @if ($job->exists && $job->isOpen())<a href="{{ route('careers.show', $job) }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener"><x-icon name="external" class="h-4 w-4" />View</a>@endif
    </x-slot:actions>
    <form method="POST" action="{{ $job->exists ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="a-card max-w-3xl space-y-5 p-5 md:p-6">
        @csrf
        @if ($job->exists) @method('PUT') @endif
        <div><label for="j-title" class="mb-1 block font-semibold">Title</label><input id="j-title" name="title" value="{{ input_string(old('title', $job->title)) }}" class="a-input w-full" required maxlength="160"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="j-cat" class="mb-1 block font-semibold">Type of work</label><select id="j-cat" name="category" class="a-input w-full">@foreach (\App\Enums\JobCategory::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $job->category?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="j-type" class="mb-1 block font-semibold">Employment</label><select id="j-type" name="employment_type" class="a-input w-full">@foreach (\App\Enums\EmploymentType::cases() as $c)<option value="{{ $c->value }}" @selected(old('employment_type', $job->employment_type?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="j-loc" class="mb-1 block font-semibold">Location</label><input id="j-loc" name="location" value="{{ input_string(old('location', $job->location)) }}" class="a-input w-full" required maxlength="160"></div>
            <div><label for="j-open" class="mb-1 block font-semibold">Openings</label><input id="j-open" type="number" min="1" max="500" name="openings" value="{{ input_string(old('openings', $job->openings)) }}" class="a-input w-full" required></div>
            <div><label for="j-status" class="mb-1 block font-semibold">Status</label><select id="j-status" name="status" class="a-input w-full">@foreach (\App\Enums\JobStatus::cases() as $c)<option value="{{ $c->value }}" @selected(old('status', $job->status?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label for="j-close" class="mb-1 block font-semibold">Closes on <span class="font-normal text-ink-3">(optional)</span></label><input id="j-close" type="date" name="closes_on" value="{{ input_string(old('closes_on', $job->closes_on?->format('Y-m-d'))) }}" class="a-input w-full"></div>
        </div>
        <div><label for="j-sum" class="mb-1 block font-semibold">Summary</label><textarea id="j-sum" name="summary" class="a-input" required maxlength="1000">{{ input_string(old('summary', $job->summary)) }}</textarea></div>
        <div><label for="j-duties" class="mb-1 block font-semibold">Duties <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="j-duties" name="duties" class="a-input" rows="6">{{ input_string(old('duties', $job->duties)) }}</textarea></div>
        <div><label for="j-req" class="mb-1 block font-semibold">Requirements <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="j-req" name="requirements" class="a-input" rows="6">{{ input_string(old('requirements', $job->requirements)) }}</textarea></div>
        <button class="a-btn a-btn-primary">Save posting</button>
    </form>
</x-layouts.admin>
