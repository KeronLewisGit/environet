<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DocumentRoom;
use App\Notifications\RoomLinkShared;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DocumentRoomTest extends TestCase
{
    use RefreshDatabase;

    private function room(): DocumentRoom
    {
        return DocumentRoom::create(['name' => 'Sample drainage works', 'client_name' => 'Sample Agency', 'client_email' => 'pm@example.org']);
    }

    public function test_staff_upload_share_and_the_client_downloads(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->actingAs($this->staff(Role::Bd));
        $room = $this->room();

        $this->post(route('admin.rooms.upload', $room), ['title' => 'Drawing C-101', 'category' => 'drawings', 'file' => UploadedFile::fake()->create('c101.pdf', 400, 'application/pdf')])->assertRedirect();
        $doc = $room->documents()->sole();
        Storage::disk('local')->assertExists($doc->path);

        $res = $this->post(route('admin.rooms.share', $room), ['days' => 30, 'email' => '1'])->assertRedirect();
        $url = (string) session('room_link');
        $this->assertStringContainsString('signature=', $url);
        Notification::assertSentOnDemand(RoomLinkShared::class, fn ($n, $c, $to) => $to->routes['mail'] === ['pm@example.org']);
        auth()->logout();

        $this->get($url)->assertOk()->assertSee('Drawing C-101')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get($room->refresh()->documentUrl($doc))->assertOk()->assertDownload('drawing-c-101.pdf');
        $this->assertSame(1, $doc->refresh()->downloads);
        $this->assertNotNull($room->refresh()->last_opened_at);
    }

    public function test_unsigned_tampered_expired_and_revoked_links_fail(): void
    {
        $room = $this->room();
        $url = $room->shareLink(7);

        $this->get(route('rooms.show', ['room' => $room->public_id, 'key' => $room->access_key]))->assertForbidden();
        $this->get(str_replace($room->access_key, str_repeat('a', 40), $url))->assertForbidden();
        $this->get($url)->assertOk();

        $this->travel(8)->days();
        $this->get($url)->assertForbidden();
        $this->travelBack();

        $url = $room->shareLink(30);
        $room->revoke();
        $this->get($url)->assertForbidden();
        // A signature made for the old key does not open the room with the new key either.
        $forged = URL::temporarySignedRoute('rooms.show', now()->addDay(), ['room' => $room->public_id, 'key' => 'old']);
        $this->get($forged)->assertForbidden();
    }

    public function test_a_document_from_another_room_is_not_served(): void
    {
        Storage::fake('local');
        $a = $this->room();
        $b = $this->room();
        Storage::disk('local')->put('rooms/x.pdf', 'x');
        $doc = $b->documents()->create(['title' => 'Other', 'category' => 'reports', 'path' => 'rooms/x.pdf', 'original_name' => 'x.pdf', 'size' => 1]);
        $a->shareLink(7);
        $url = URL::temporarySignedRoute('rooms.download', now()->addDay(), ['room' => $a->public_id, 'key' => $a->access_key, 'document' => $doc->id]);
        $this->get($url)->assertNotFound();
    }

    public function test_only_room_roles_manage_rooms(): void
    {
        $room = $this->room();
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.rooms.show', $room))->assertForbidden();
        $this->actingAs($this->staff(Role::Hr))->post(route('admin.rooms.share', $room), ['days' => 7])->assertForbidden();
        $this->actingAs($this->staff(Role::Bd))->post(route('admin.rooms.share', $room), ['days' => 5])->assertSessionHasErrors('days');
        // Procurement sends sub bid packages to vendors through rooms, so it can share them too.
        $this->actingAs($this->staff(Role::Procurement))->post(route('admin.rooms.share', $room), ['days' => 7])->assertRedirect();
        $this->assertTrue($room->refresh()->linkIsLive());
    }

    public function test_room_ids_are_not_guessable(): void
    {
        $room = $this->room();
        $this->assertSame(26, strlen($room->public_id));
        $this->assertSame(40, strlen($room->access_key));
        $this->assertFalse($room->linkIsLive());
        $this->assertNull($room->signedUrl());
    }

    public function test_downloads_expire_with_the_link_the_client_opened(): void
    {
        Storage::fake('local');
        $room = $this->room();
        Storage::disk('local')->put('rooms/x/a.pdf', 'pdf');
        $doc = $room->documents()->create(['title' => 'Sampling plan', 'category' => 'reports', 'path' => 'rooms/x/a.pdf', 'original_name' => 'a.pdf', 'size' => 3]);

        // A 90-day link went to the client; later a 7-day link went to someone else.
        $long = $room->shareLink(90);
        $short = URL::temporarySignedRoute('rooms.show', now()->addDays(7), ['room' => $room->public_id, 'key' => $room->access_key]);

        preg_match('/href="([^"]+\/documents\/[^"]+\/'.$doc->id.'\?[^"]+)"/', (string) $this->get($short)->assertOk()->getContent(), $m);
        $download = html_entity_decode($m[1]);
        $this->get($download)->assertOk();

        $this->travel(8)->days();
        $this->get($download)->assertForbidden();
        $this->get($long)->assertOk();
    }
}
