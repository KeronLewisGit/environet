<?php

namespace Tests\Feature;

use App\Enums\CertificationKind;
use App\Enums\JobCategory;
use App\Enums\Office;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareersAndContactTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function application(array $overrides = []): array
    {
        return array_merge([
            'category' => 'trades', 'office' => 'oahu', 'first_name' => 'Kehau', 'last_name' => 'Medeiros', 'phone' => '808-555-'.random_int(1000, 9999),
            'experience' => '5to10', 'region' => 'Oʻahu', 'consent' => '1',
        ], $overrides);
    }

    public function test_trades_can_apply_without_a_resume_or_email(): void
    {
        Notification::fake();
        config(['environet.notify.careers' => 'hr@example.com']);
        $this->post('/careers/apply', $this->application())->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(sprintf('JA-%s-%05d', now()->format('y'), $a->id), $a->reference);
        $this->assertNull($a->cv_path);
        $this->assertSame(Office::Oahu, $a->office);
        $this->assertFalse($a->can_travel);
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['hr@example.com']);
    }

    public function test_scientist_roles_need_a_resume_stored_privately(): void
    {
        Storage::fake('local');
        $this->post('/careers/apply', $this->application(['category' => 'scientist']))->assertSessionHasErrors(['cv', 'email']);

        $this->post('/careers/apply', $this->application(['category' => 'scientist', 'email' => 'e@example.com', 'cv' => UploadedFile::fake()->create('cv.pdf', 300, 'application/pdf')]))
            ->assertRedirect('/thanks');
        $a = JobApplication::sole();
        Storage::disk('local')->assertExists((string) $a->cv_path);
        $this->assertStringStartsWith('cvs/', (string) $a->cv_path);
        $this->post('/careers/apply', $this->application(['cv' => UploadedFile::fake()->create('cv.exe', 10)]))->assertSessionHasErrors('cv');
    }

    public function test_the_job_decides_the_category(): void
    {
        $job = JobPosting::factory()->create(['category' => JobCategory::Scientist]);
        // Claims trades but applies to a scientist post: the résumé rule follows the job.
        $this->post('/careers/apply', $this->application(['job_posting_id' => $job->id, 'category' => 'trades']))->assertSessionHasErrors('cv');
        $closed = JobPosting::factory()->create(['closes_on' => today()->subDay()]);
        $this->post('/careers/apply', $this->application(['job_posting_id' => $closed->id]))->assertSessionHasErrors('job_posting_id');
        $this->get('/careers/apply?job='.$job->slug)->assertOk()->assertSee($job->title);
    }

    public function test_contact_form_creates_an_enquiry(): void
    {
        Notification::fake();
        $this->post('/contact', ['name' => 'Malia Cruz', 'email' => 'T@Example.com', 'topic' => 'project', 'message' => 'We need a quote for a Phase I site assessment.'])->assertRedirect('/thanks');
        $e = Enquiry::sole();
        $this->assertSame('t@example.com', $e->email);
        $this->assertStringStartsWith('EN-', $e->reference);
        $this->post('/contact', ['name' => 'x', 'email' => 'bad', 'topic' => 'nope', 'message' => 'short'])->assertSessionHasErrors(['email', 'topic', 'message']);
    }

    public function test_forms_are_rate_limited(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $this->post('/contact', []);
        }
        $this->post('/contact', [])->assertStatus(429);
    }

    public function test_a_closed_opening_gets_a_plain_message(): void
    {
        $closed = JobPosting::factory()->create(['closes_on' => today()->subDay()]);
        $this->post('/careers/apply', $this->application(['job_posting_id' => $closed->id]))
            ->assertSessionHasErrors(['job_posting_id' => 'That opening has closed. Choose another opening, or send a general application.']);
    }

    public function test_one_email_address_cannot_be_sent_confirmations_in_bulk(): void
    {
        Notification::fake();
        for ($i = 0; $i < 10; $i++) {
            $this->post('/contact', ['name' => 'A', 'email' => 'victim@example.com', 'phone' => '808555'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'topic' => 'general', 'message' => 'Hello there, a message.'])->assertRedirect('/thanks');
        }
        $this->post('/contact', ['name' => 'A', 'email' => 'Victim@Example.com', 'topic' => 'general', 'message' => 'Hello there, a message.'])->assertStatus(429);
        $this->post('/contact', ['name' => 'A', 'email' => 'someone@example.com', 'topic' => 'general', 'message' => 'Hello there, a message.'])->assertRedirect('/thanks');
    }

    public function test_an_applicant_lists_certifications_with_expiry_dates(): void
    {
        Notification::fake();
        $this->post('/careers/apply', $this->application([
            'category' => 'uxo', 'office' => 'hawaii', 'can_travel' => '1',
            'certs' => [
                ['kind' => 'hazwoper40', 'expires_on' => today()->addMonths(8)->format('Y-m-d')],
                ['kind' => 'uxo2', 'detail' => 'Sample UXO school', 'expires_on' => today()->addDays(10)->format('Y-m-d')],
                ['kind' => 'osha10'], // no expiry needed
                ['kind' => '', 'expires_on' => ''], // a blank row is ignored
            ],
        ]))->assertRedirect('/thanks');

        $a = JobApplication::sole();
        $this->assertSame(Office::HawaiiIsland, $a->office);
        $this->assertTrue($a->can_travel);
        $this->assertSame(['hazwoper40', 'osha10', 'uxo2'], $a->certifications->map(fn ($c) => $c->kind->value)->sort()->values()->all());
        $uxo = $a->certifications->firstWhere('kind', CertificationKind::UxoTech2);
        $this->assertSame('expiring', $uxo?->state());
        $this->assertNull($a->certifications->firstWhere('kind', CertificationKind::Osha10)?->expires_on);
        $this->assertNull($uxo?->checked_at, 'self-declared until HR checks the card');
    }

    public function test_certifications_that_lapse_need_an_expiry_and_other_needs_a_name(): void
    {
        $this->post('/careers/apply', $this->application(['certs' => [['kind' => 'hazwoper40'], ['kind' => 'other'], ['kind' => 'made-up']]]))
            ->assertSessionHasErrors(['certs.0.expires_on', 'certs.1.detail', 'certs.2.kind']);
        $this->post('/careers/apply', $this->application(['office' => 'mars']))->assertSessionHasErrors('office');
        $this->post('/careers/apply', $this->application(['certs' => array_fill(0, 9, ['kind' => 'osha10'])]))->assertSessionHasErrors('certs');
        $this->assertSame(0, JobApplication::count());
        // The form re-renders with the rows the applicant typed.
        $this->followingRedirects()->from('/careers/apply')->post('/careers/apply', $this->application(['certs' => [['kind' => 'hazwoper40']]]))
            ->assertOk()->assertSee('Enter the expiry date for HAZWOPER 40.')->assertSee('value="hazwoper40" selected', false);
    }
}
