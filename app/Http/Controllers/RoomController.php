<?php

namespace App\Http\Controllers;

use App\Models\DocumentRoom;
use App\Models\RoomDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The client side of a project document room. Routes carry the `signed` middleware; the key in
 * the URL must also match the room's current access key, so revoking (a new key) kills old links.
 */
class RoomController extends Controller
{
    public function show(Request $request, DocumentRoom $room, string $key): View
    {
        $this->guard($room, $key);
        if (! $room->last_opened_at || $room->last_opened_at->lt(now()->subMinutes(30))) {
            $room->log('Opened by the client link from '.($request->ip() ?? 'unknown address').'.');
        }
        $room->forceFill(['last_opened_at' => now()])->save();

        // Downloads expire with the link that was opened (the `signed` middleware has checked it).
        return view('pages.room', ['room' => $room->load('documents'), 'until' => Carbon::createFromTimestamp((int) $request->query('expires'), config('app.timezone'))]);
    }

    public function download(DocumentRoom $room, string $key, RoomDocument $document): StreamedResponse
    {
        $this->guard($room, $key);
        abort_unless($document->document_room_id === $room->id && Storage::disk('local')->exists($document->path), 404);

        $document->increment('downloads');
        $room->log("Client downloaded “{$document->title}”.");
        $name = Str::slug($document->title).'.'.strtolower($document->extension());

        return Storage::disk('local')->download($document->path, $name, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex']);
    }

    private function guard(DocumentRoom $room, string $key): void
    {
        abort_unless(hash_equals($room->access_key, $key) && $room->linkIsLive(), 403, 'This link has expired or been withdrawn. Ask your Environet contact for a new one.');
    }
}
