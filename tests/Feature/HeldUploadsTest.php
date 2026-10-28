<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\Solicitation;
use App\Models\Vendor;
use App\Support\HeldUploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A form that comes back with an error keeps the files that were fine; nobody attaches them twice. */
class HeldUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    /** A small file with real PDF bytes, so its type is recognised from its content. */
    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    }

    /** @return array<string, mixed> */
    private function solicitation(array $overrides = []): array
    {
        return array_merge([
            'source' => 'federal', 'organization' => 'Sample Engineer District', 'contact_name' => 'Kai Nakamura', 'email' => 'p@example.org',
            'phone' => '(808) 555-0101', 'title' => 'Sprinkler repair', 'type' => 'rfq', 'scope' => 'Repair work.',
            'due_date' => today()->addDays(12)->format('Y-m-d'), 'due_time' => '14:00',
        ], $overrides);
    }

    public function test_solicitation_documents_survive_a_validation_error(): void
    {
        $files = [$this->pdf('sow.pdf'), $this->pdf('pricing.pdf')];
        $this->from('/rfq')->post('/rfq', $this->solicitation(['title' => '', 'files' => $files]))->assertSessionHasErrors('title');
        $this->assertCount(2, Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->get('/rfq')->assertSee('Attached:')->assertSee('sow.pdf, pricing.pdf');

        // Sent again with the input left empty: the held files go with it, then are cleared.
        $this->post('/rfq', $this->solicitation())->assertRedirect('/thanks');
        $solicitation = Solicitation::sole();
        $this->assertSame(['pricing.pdf', 'sow.pdf'], $solicitation->files()->orderBy('original_name')->pluck('original_name')->all());
        foreach ($solicitation->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            $this->assertStringStartsWith('solicitations/'.$solicitation->id.'/', $f->path);
        }
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->assertNull(session('held_uploads.solicitation'));
    }

    public function test_a_rejected_file_is_not_held_and_a_new_choice_replaces_the_held_one(): void
    {
        $this->post('/rfq', $this->solicitation(['title' => '', 'files' => [UploadedFile::fake()->create('virus.exe', 10)]]))->assertSessionHasErrors(['title', 'files.0']);
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));

        $this->post('/rfq', $this->solicitation(['title' => '', 'files' => [$this->pdf('old.pdf')]]));
        $this->post('/rfq', $this->solicitation(['title' => '', 'files' => [$this->pdf('new.pdf')]]));
        $this->assertSame(['new.pdf'], HeldUploads::names('solicitation', 'files'));
        $this->assertCount(1, Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_vendor_certificates_survive_a_validation_error(): void
    {
        $doc = fn (string $n) => ['file' => $this->pdf($n), 'expires_on' => today()->addYear()->format('Y-m-d')];
        $form = [
            'type' => 'subcontractor', 'company_name' => 'Sample Drilling LLC', 'uei' => 'SMPL1234ABCD', 'address' => '1 Street', 'city' => 'Hilo', 'state' => 'HI',
            'contact_name' => 'Noa Kealoha', 'email' => 'k@example.com', 'phone' => '808-555-0199', 'trades' => ['drilling'], 'consent' => '1',
        ];
        $this->from('/teaming')->post('/teaming', array_merge($form, ['uei' => '12']) + ['docs' => ['liability' => $doc('gl.pdf'), 'license' => $doc('lic.pdf'), 'workers_comp' => $doc('wc.pdf')]])
            ->assertSessionHasErrors('uei');
        $page = $this->get('/teaming')->assertSee('Attached:')->assertSee('gl.pdf');
        // The browser must not demand the held certificates again.
        $this->assertDoesNotMatchRegularExpression('/name="docs\[liability\]\[file\]"[^>]*required/', (string) $page->getContent());

        $expiry = ['liability' => ['expires_on' => today()->addYear()->format('Y-m-d')], 'license' => ['expires_on' => today()->addYear()->format('Y-m-d')], 'workers_comp' => ['expires_on' => today()->addYear()->format('Y-m-d')]];
        $this->post('/teaming', $form + ['docs' => $expiry])->assertRedirect('/thanks');
        $vendor = Vendor::sole();
        $this->assertCount(3, $vendor->currentDocuments);
        $this->assertSame('valid', $vendor->complianceState());
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_a_cv_survives_a_validation_error(): void
    {
        $form = ['category' => 'scientist', 'office' => 'oahu', 'first_name' => 'Leilani', 'last_name' => 'Akana', 'phone' => '808-555-0142', 'email' => 'd@example.com', 'experience' => '5to10', 'consent' => '1'];
        $this->post('/careers/apply', array_merge($form, ['first_name' => '', 'cv' => $this->pdf('cv.pdf')]))->assertSessionHasErrors('first_name');
        $this->post('/careers/apply', $form)->assertRedirect('/thanks');
        $this->assertSame('cv.pdf', JobApplication::sole()->cv_name);
    }

    public function test_held_files_are_pruned_after_a_day(): void
    {
        $this->post('/careers/apply', ['category' => 'scientist', 'cv' => $this->pdf('cv.pdf')]);
        $this->assertCount(1, Storage::disk('local')->allFiles(HeldUploads::DIR));

        $this->artisan('app:prune-held-uploads')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->allFiles(HeldUploads::DIR), 'a fresh file is kept');

        touch(Storage::disk('local')->path(Storage::disk('local')->allFiles(HeldUploads::DIR)[0]), now()->subHours(25)->getTimestamp());
        $this->artisan('app:prune-held-uploads')->assertSuccessful();
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->assertSame([], HeldUploads::names('application', 'cv'));
    }
}
