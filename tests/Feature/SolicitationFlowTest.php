<?php

namespace Tests\Feature;

use App\Enums\SolicitationStatus;
use App\Models\Solicitation;
use App\Notifications\NewSubmission;
use App\Notifications\SubmissionReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolicitationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function solicitation(array $overrides = []): array
    {
        return array_merge([
            'source' => 'federal',
            'organization' => 'Sample Engineer District',
            'contact_name' => 'Kai Nakamura',
            'email' => 'Contracting@Example.org',
            'phone' => '(808) 555-0101',
            'title' => 'Repair barracks fire sprinkler system',
            'solicitation_number' => 'W9128A-26-Q-0014',
            'type' => 'rfq',
            'set_aside' => 'hubzone',
            'naics' => '236220',
            'region' => 'Oʻahu',
            'scope' => 'Repair of the fire sprinkler system in a barracks building.',
            'due_date' => today()->addDays(12)->format('Y-m-d'),
            'due_time' => '14:00',
        ], $overrides);
    }

    public function test_a_contracting_office_can_send_an_rfq_with_documents(): void
    {
        Storage::fake('local');
        Notification::fake();
        config(['environet.notify.default' => 'office@example.com', 'environet.notify.solicitations' => 'bd@example.com']);

        $res = $this->post('/rfq', $this->solicitation([
            'files' => [UploadedFile::fake()->create('rfq.pdf', 300, 'application/pdf'), UploadedFile::fake()->create('pricing.xlsx', 50)],
            'bond_required' => '1',
        ]));

        $res->assertRedirect('/thanks');
        $solicitation = Solicitation::sole();
        $this->assertSame(sprintf('RQ-%s-%05d', now()->format('y'), $solicitation->id), $solicitation->reference);
        $this->assertSame(SolicitationStatus::New, $solicitation->status);
        $this->assertSame('contracting@example.org', $solicitation->email);
        $this->assertTrue($solicitation->bond_required);
        $this->assertSame('hubzone', $solicitation->set_aside?->value);
        $this->assertSame('236220', $solicitation->naics);
        $this->assertSame('14:00', $solicitation->response_due_at->format('H:i'));
        $this->assertCount(2, $solicitation->files);
        foreach ($solicitation->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            $this->assertStringStartsWith('solicitations/'.$solicitation->id.'/', $f->path);
            $this->assertFileDoesNotExist(public_path($f->path));
        }
        $this->assertSame('Received from the website.', $solicitation->notes()->first()?->body);

        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === ['bd@example.com']);
        Notification::assertSentOnDemand(SubmissionReceived::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === ['contracting@example.org']);

        $this->get('/thanks')->assertOk()->assertSee($solicitation->reference);
    }

    public function test_area_inbox_falls_back_to_the_default(): void
    {
        Notification::fake();
        config(['environet.notify.default' => 'office@example.com', 'environet.notify.solicitations' => '']);
        $this->post('/rfq', $this->solicitation())->assertRedirect('/thanks');
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === ['office@example.com']);
    }

    public function test_a_deadline_in_the_past_is_refused(): void
    {
        $this->from('/rfq')->post('/rfq', $this->solicitation(['due_date' => today()->format('Y-m-d'), 'due_time' => '00:01']))
            ->assertRedirect('/rfq')->assertSessionHasErrors('due_time');
        $this->post('/rfq', $this->solicitation(['due_date' => today()->subDay()->format('Y-m-d')]))->assertSessionHasErrors('due_date');
        $this->assertSame(0, Solicitation::count());
    }

    public function test_required_fields_and_file_rules(): void
    {
        $this->post('/rfq', [])->assertSessionHasErrors(['source', 'organization', 'contact_name', 'email', 'phone', 'title', 'type', 'scope', 'due_date', 'due_time']);
        $this->post('/rfq', $this->solicitation(['files' => [UploadedFile::fake()->create('run.exe', 10)]]))->assertSessionHasErrors('files.0');
        $this->post('/rfq', $this->solicitation(['files' => array_fill(0, 6, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))]))->assertSessionHasErrors('files');
        $this->post('/rfq', $this->solicitation(['questions_due_on' => today()->addDays(30)->format('Y-m-d')]))->assertSessionHasErrors('questions_due_on');
        $this->post('/rfq', $this->solicitation(['type' => 'bribe']))->assertSessionHasErrors('type');
    }

    public function test_set_aside_and_naics_are_checked(): void
    {
        $this->post('/rfq', $this->solicitation(['set_aside' => 'made-up']))->assertSessionHasErrors('set_aside');
        $this->post('/rfq', $this->solicitation(['naics' => '12345']))->assertSessionHasErrors('naics');
        $this->post('/rfq', $this->solicitation(['naics' => '562 910']))->assertRedirect('/thanks');
        $this->assertSame('562910', Solicitation::sole()->naics);
    }

    public function test_bots_get_a_thank_you_and_nothing_is_stored(): void
    {
        Notification::fake();
        $this->post('/rfq', $this->solicitation(['website' => 'spam']))->assertRedirect('/thanks');
        $this->assertSame(0, Solicitation::count());
        Notification::assertNothingSent();
        $this->get('/thanks')->assertSee('RQ-'.now()->format('y').'-00000');
    }

    public function test_thanks_without_a_submission_goes_home(): void
    {
        $this->get('/thanks')->assertRedirect('/');
    }

    public function test_due_soon_scope_and_labels(): void
    {
        $soon = Solicitation::factory()->dueIn(3)->create();
        Solicitation::factory()->dueIn(20)->create();
        Solicitation::factory()->dueIn(2)->create(['status' => SolicitationStatus::Submitted]);
        $past = Solicitation::factory()->create(['response_due_at' => now()->subDay()]);

        $this->assertSame([$soon->id], Solicitation::dueSoon()->pluck('id')->all());
        $this->assertSame('3 days left', $soon->dueLabel());
        $this->assertSame('critical', $past->dueTone());
        $this->assertSame('Deadline passed', $past->dueLabel());
    }

    public function test_junk_array_input_redisplays_the_form_instead_of_crashing(): void
    {
        $junk = $this->solicitation(['source' => ['federal'], 'organization' => ['x'], 'type' => ['rfq'], 'region' => ['Oʻahu'], 'due_date' => ['x'], 'bond_required' => ['1'], 'naics' => ['1']]);
        $this->from('/rfq')->post('/rfq', $junk)->assertRedirect('/rfq')->assertSessionHasErrors(['source', 'organization', 'type', 'due_date']);
        // Following the redirect re-renders the form with the junk as old input.
        $this->followingRedirects()->from('/rfq')->post('/rfq', $junk)->assertOk()->assertSee('Please check');
        $this->assertSame(0, Solicitation::count());
    }
}
