<x-layouts.admin :title="$enquiry->reference">
    <x-slot:actions>
        <a href="{{ route('admin.enquiries.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Enquiries</a>
        <a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: your inquiry '.$enquiry->reference) }}" class="a-btn a-btn-primary"><x-icon name="mail" class="h-4 w-4" />Reply by email</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <section class="a-card min-w-0 p-5 md:p-6">
            <x-status :status="$enquiry->status" />
            <h2 class="mt-3 text-xl font-bold">{{ $enquiry->topic->label() }}</h2>
            <dl class="dl-grid mt-4">
                <dt>From</dt><dd>{{ $enquiry->name }}@if($enquiry->organization), {{ $enquiry->organization }}@endif</dd>
                <dt>Email</dt><dd><a class="text-link underline" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
                @if ($enquiry->phone)<dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($enquiry->phone) }}">{{ format_phone($enquiry->phone) }}</a></dd>@endif
                <dt>Received</dt><dd>{{ $enquiry->created_at->format('M j, Y, g:i a') }}</dd>
            </dl>
            <p class="measure mt-5 whitespace-pre-line">{{ $enquiry->message }}</p>
        </section>
        <div class="min-w-0 space-y-6">
            <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="a-card space-y-3 p-5">
                @csrf @method('PATCH')
                <h2 class="font-semibold">Status and note</h2>
                <label for="e-status" class="sr-only">Status</label>
                <select id="e-status" name="status" class="a-input w-full">@foreach (\App\Enums\EnquiryStatus::cases() as $s)<option value="{{ $s->value }}" @selected($enquiry->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                <label for="e-body" class="sr-only">Note</label>
                <textarea id="e-body" name="body" class="a-input" placeholder="What was said or done (optional)"></textarea>
                <button class="a-btn a-btn-primary">Save</button>
            </form>
            @include('admin.partials.timeline', ['notes' => $enquiry->notes, 'action' => null])
        </div>
    </div>
</x-layouts.admin>
