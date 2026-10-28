<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomCategory;
use App\Http\Controllers\Controller;
use App\Models\DocumentRoom;
use App\Models\RoomDocument;
use App\Notifications\RoomLinkShared;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Client project document rooms: upload documents, share an expiring signed link, revoke it. */
class RoomController extends Controller
{
    use Concerns;

    public function index(): View
    {
        Gate::authorize('viewAny', DocumentRoom::class);

        return view('admin.rooms.index', ['rooms' => DocumentRoom::withCount('documents')->orderByDesc('updated_at')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', DocumentRoom::class);
        $room = DocumentRoom::create($this->validated($request) + ['created_by' => $this->user($request)->id]);
        $room->log('Room created.', 'system', $this->user($request));

        return to_route('admin.rooms.show', $room)->with('status', 'Room created. Add documents, then share the link.');
    }

    public function show(Request $request, DocumentRoom $room): View
    {
        Gate::authorize('view', $room);

        return view('admin.rooms.show', [
            'room' => $room->load(['documents.uploader', 'notes.user']),
            // A link made a moment ago is shown in full once, so it can be copied.
            'link' => $request->session()->get('room_link'),
        ]);
    }

    public function update(Request $request, DocumentRoom $room): RedirectResponse
    {
        Gate::authorize('update', $room);
        $room->update($this->validated($request));

        return back()->with('status', 'Room details saved.');
    }

    public function upload(Request $request, DocumentRoom $room): RedirectResponse
    {
        Gate::authorize('update', $room);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(RoomCategory::class)],
            'file' => ['required', 'file', 'max:20480', 'mimes:'.implode(',', DocumentRoom::FILE_TYPES)],
        ], ['file.max' => 'Documents must be 20 MB or smaller.', 'file.mimes' => 'That file type is not accepted.']);
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);
        $room->documents()->create([
            'title' => $data['title'],
            'category' => $data['category'],
            'path' => $file->store('rooms/'.$room->id, 'local'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'uploaded_by' => $this->user($request)->id,
        ]);
        $room->log("Uploaded “{$data['title']}”.", 'file', $this->user($request));
        $room->touch();

        return back()->with('status', 'Document added.');
    }

    public function destroyDocument(Request $request, DocumentRoom $room, RoomDocument $document): RedirectResponse
    {
        Gate::authorize('update', $room);
        abort_unless($document->document_room_id === $room->id, 404);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        $room->log("Removed “{$document->title}”.", 'file', $this->user($request));

        return back()->with('status', 'Document removed.');
    }

    public function download(Request $request, DocumentRoom $room, RoomDocument $document): StreamedResponse
    {
        Gate::authorize('view', $room);
        abort_unless($document->document_room_id === $room->id && Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, Str::slug($document->title).'.'.strtolower($document->extension()), ['Cache-Control' => 'private, no-store']);
    }

    /** A new expiring link. Optionally emailed to the client contact. */
    public function share(Request $request, DocumentRoom $room): RedirectResponse
    {
        Gate::authorize('update', $room);
        $data = $request->validate(['days' => ['required', 'integer', Rule::in(DocumentRoom::LINK_DAYS)], 'email' => ['nullable', 'boolean']]);
        $user = $this->user($request);
        $url = $room->shareLink((int) $data['days']);
        $room->log("Share link created, valid until {$room->link_expires_at?->format('M j, Y')}.", 'system', $user);

        if (! empty($data['email']) && $room->client_email) {
            Notify::sender($room->client_email, new RoomLinkShared($room, $url, $user->name));
            $room->log("Link emailed to {$room->client_email}.", 'system', $user);
        }

        return back()->with('status', 'Link ready.')->with('room_link', $url);
    }

    public function revoke(Request $request, DocumentRoom $room): RedirectResponse
    {
        Gate::authorize('update', $room);
        $room->revoke();
        $room->log('All links withdrawn.', 'system', $this->user($request));

        return back()->with('status', 'Every link sent so far has stopped working.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'client_name' => ['required', 'string', 'max:160'],
            'client_contact' => ['nullable', 'string', 'max:120'],
            'client_email' => ['nullable', 'email:rfc', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
