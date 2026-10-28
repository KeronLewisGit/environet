{{-- Timeline of notes for any record. $notes, and $action (note POST url) when staff can add one. --}}
<section class="a-card min-w-0" id="timeline" aria-labelledby="timeline-title">
    <h2 id="timeline-title" class="px-5 pt-4 font-semibold">Timeline</h2>
    @if ($action ?? null)
        <form method="POST" action="{{ $action }}" class="space-y-3 px-5 pt-3">
            @csrf
            <label for="note-body" class="sr-only">Add a note</label>
            <textarea id="note-body" name="body" class="a-input" required maxlength="3000" placeholder="Add a note: a call, a clarification, a decision"></textarea>
            <button class="a-btn a-btn-primary">Add note</button>
        </form>
    @endif
    <ol class="mt-4">
        @forelse ($notes as $note)
            <li class="flex gap-3 border-t border-line px-5 py-3">
                <x-icon :name="match ($note->kind) { 'note' => 'message', 'status' => 'check-circle', 'assign' => 'users', 'file' => 'file', default => 'dot' }" class="mt-0.5 h-4 w-4 flex-none text-ink-3" />
                <div class="min-w-0">
                    <p class="whitespace-pre-line break-words">{{ $note->body }}</p>
                    <p class="mt-0.5 text-xs text-ink-3">{{ $note->user->name ?? 'System' }} · <time datetime="{{ $note->created_at->toIso8601String() }}">{{ $note->created_at->format('M j, Y, g:i a') }}</time></p>
                </div>
            </li>
        @empty
            <li class="border-t border-line px-5 py-4 text-ink-3">Nothing yet.</li>
        @endforelse
    </ol>
</section>
