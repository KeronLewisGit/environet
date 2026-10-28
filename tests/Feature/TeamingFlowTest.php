<?php

namespace Tests\Feature;

use App\Enums\DocumentKind;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\CertificateExpiring;
use App\Notifications\SubmissionReceived;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TeamingFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function form(string $type = 'subcontractor', array $overrides = []): array
    {
        $doc = fn (string $name, int $days = 120) => ['file' => UploadedFile::fake()->create($name, 200, 'application/pdf'), 'number' => 'N-1', 'expires_on' => today()->addDays($days)->format('Y-m-d')];

        return array_replace_recursive([
            'type' => $type,
            'company_name' => 'Sample Drilling LLC',
            'uei' => 'smpl 1234 abcd',
            'cage' => '9abc1',
            'naics' => '237110, 562910',
            'set_asides' => ['sb', 'hubzone'],
            'address' => '10 Sample Avenue',
            'city' => 'Hilo',
            'state' => 'HI',
            'contact_name' => 'Noa Kealoha',
            'email' => 'noa@example.com',
            'phone' => '808-555-'.random_int(1000, 9999), // a fresh number each time: forms allow 5 a minute per phone
            'trades' => ['drilling', 'geophysics'],
            'consent' => '1',
            'docs' => ['liability' => $doc('gl.pdf'), 'license' => $doc('license.pdf', 300), 'workers_comp' => $doc('wc.pdf', 200)],
        ], $overrides);
    }

    public function test_a_subcontractor_registers_with_insurance_and_license(): void
    {
        Storage::fake('local');
        Notification::fake();

        $this->post('/teaming', $this->form())->assertRedirect('/thanks');

        $vendor = Vendor::sole();
        $this->assertSame(sprintf('TP-%s-%05d', now()->format('y'), $vendor->id), $vendor->reference);
        $this->assertSame('SMPL1234ABCD', $vendor->uei);
        $this->assertSame('9ABC1', $vendor->cage);
        $this->assertSame(['sb', 'hubzone'], $vendor->set_asides);
        $this->assertSame('SB, HUBZone', $vendor->setAsideLabels());
        $this->assertSame(VendorStatus::New, $vendor->status);
        $this->assertNotNull($vendor->consented_at);
        $this->assertCount(3, $vendor->currentDocuments);
        $this->assertSame('valid', $vendor->complianceState());
        foreach ($vendor->documents as $d) {
            Storage::disk('local')->assertExists($d->path);
        }
        $this->assertSame(today()->addDays(120)->toDateString(), $vendor->current(DocumentKind::Liability)?->expires_on?->toDateString());
        Notification::assertSentOnDemand(SubmissionReceived::class);
    }

    public function test_a_supplier_needs_only_liability_insurance_and_no_uei(): void
    {
        Storage::fake('local');
        $form = $this->form('supplier');
        unset($form['docs']['license'], $form['docs']['workers_comp'], $form['uei'], $form['cage']);
        $this->post('/teaming', $form)->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $this->assertSame('valid', Vendor::sole()->complianceState());
    }

    public function test_missing_or_expired_certificates_are_refused(): void
    {
        Storage::fake('local');
        $form = $this->form();
        unset($form['docs']['license']);
        $this->post('/teaming', $form)->assertSessionHasErrors('docs.license.file');

        $this->post('/teaming', $this->form('subcontractor', ['docs' => ['liability' => ['expires_on' => today()->subDay()->format('Y-m-d')]]]))
            ->assertSessionHasErrors(['docs.liability.expires_on' => 'This General liability has expired. Upload the current one.']);
        $this->post('/teaming', $this->form('subcontractor', ['uei' => '12345']))->assertSessionHasErrors('uei');
        $this->post('/teaming', $this->form('teaming', ['uei' => '']))->assertSessionHasErrors(['uei' => 'Teaming partners and subcontractors need a SAM Unique Entity ID (UEI). It is free at SAM.gov.']);
        $this->post('/teaming', $this->form('subcontractor', ['set_asides' => ['unrestricted']]))->assertSessionHasErrors('set_asides.0');
        $this->post('/teaming', $this->form('subcontractor', ['trades' => ['nonsense']]))->assertSessionHasErrors('trades.0');
        $this->post('/teaming', $this->form('subcontractor', ['consent' => null]))->assertSessionHasErrors('consent');
        $this->post('/teaming', $this->form('subcontractor', ['docs' => ['liability' => ['file' => UploadedFile::fake()->create('x.docx', 10)]]]))->assertSessionHasErrors('docs.liability.file');
        $this->assertSame(0, Vendor::count());
    }

    public function test_reminder_emails_a_signed_renewal_link_once(): void
    {
        Notification::fake();
        $vendor = Vendor::factory()->approved()->withCertificates(20)->create();
        Vendor::factory()->withCertificates(20)->create(); // not approved: no reminder
        Vendor::factory()->approved()->withCertificates(200)->create(); // not due

        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(CertificateExpiring::class, 1);
        Notification::assertSentOnDemand(CertificateExpiring::class, function (CertificateExpiring $n, $ch, $notifiable) use ($vendor) {
            return $notifiable->routes['mail'] === [$vendor->email] && $n->documents->count() === 3 && str_contains($n->renewUrl, 'signature=');
        });
        $this->assertNotNull($vendor->documents()->first()?->reminded_at);

        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(CertificateExpiring::class, 1);
    }

    public function test_renewal_link_replaces_the_certificate_and_keeps_the_old_copy(): void
    {
        Storage::fake('local');
        Notification::fake();
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $this->assertSame('expiring', $vendor->complianceState());

        $this->get(route('teaming.renew', $vendor))->assertForbidden();
        $url = $vendor->renewalUrl($vendor->currentDocuments);
        $page = $this->get($url)->assertOk()->assertSee($vendor->company_name)->assertHeader('Referrer-Policy', 'no-referrer');
        preg_match('/action="([^"]+renew[^"]+)"/', (string) $page->getContent(), $m);
        $action = html_entity_decode($m[1]);

        $this->post($action, ['docs' => ['liability' => ['file' => UploadedFile::fake()->create('gl-2027.pdf', 100, 'application/pdf'), 'expires_on' => today()->addMonths(6)->format('Y-m-d')]]])
            ->assertRedirect('/thanks');

        $vendor->refresh()->load('currentDocuments');
        $this->assertSame(today()->addMonths(6)->toDateString(), $vendor->current(DocumentKind::Liability)?->expires_on?->toDateString());
        $this->assertSame(1, $vendor->documents()->whereNotNull('superseded_at')->count());
        $this->post(route('teaming.renew.store', $vendor), [])->assertForbidden();
    }

    public function test_renewal_needs_at_least_one_file(): void
    {
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $for = $vendor->documents()->pluck('id')->implode('.');
        $action = URL::temporarySignedRoute('teaming.renew.store', now()->addHour(), ['vendor' => $vendor, 'for' => $for]);
        $this->post($action, ['docs' => ['liability' => ['number' => 'x']]])->assertSessionHasErrors('docs');
    }

    /** Opens the renewal page from $url and returns the signed form action on it. */
    private function renewalAction(string $url): string
    {
        $page = $this->get($url)->assertOk();
        preg_match('/action="([^"]+renew[^"]+)"/', (string) $page->getContent(), $m);

        return html_entity_decode($m[1]);
    }

    public function test_a_renewal_link_stops_working_once_its_certificates_are_renewed(): void
    {
        Storage::fake('local');
        Notification::fake();
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $gl = $vendor->documents()->where('kind', 'liability')->get();
        $url = $vendor->renewalUrl($gl);
        $renewal = fn () => ['docs' => ['liability' => ['file' => UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'), 'expires_on' => today()->addYear()->format('Y-m-d')]]];

        $action = $this->renewalAction($url);
        $this->post($action, $renewal())->assertRedirect('/thanks');

        // The same email cannot replace the certificate again.
        $this->get($url)->assertForbidden()->assertSee('already been used');
        $this->post($action, $renewal())->assertForbidden();
        $this->assertSame(2, $vendor->documents()->where('kind', 'liability')->count());
    }

    public function test_a_renewal_link_names_its_certificates_and_expires(): void
    {
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $other = Vendor::factory()->approved()->withCertificates(10)->create();

        // A link without the certificates it was sent for, or for another vendor's, is refused.
        $this->get(URL::temporarySignedRoute('teaming.renew', now()->addDay(), ['vendor' => $vendor]))->assertForbidden();
        $this->get(URL::temporarySignedRoute('teaming.renew', now()->addDay(), ['vendor' => $vendor, 'for' => $other->documents()->value('id')]))->assertForbidden();
        $this->get($vendor->renewalUrl($vendor->currentDocuments, now()->subMinute()))->assertForbidden();

        // The form on the page is signed for as long as the emailed link, not two hours.
        $until = now()->addDays(Vendor::RENEWAL_LINK_DAYS);
        $action = $this->renewalAction($vendor->renewalUrl($vendor->currentDocuments, $until));
        parse_str((string) parse_url($action, PHP_URL_QUERY), $q);
        $this->assertSame($until->getTimestamp(), (int) $q['expires']);
    }

    public function test_a_declined_vendor_cannot_use_a_renewal_link(): void
    {
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $url = $vendor->renewalUrl($vendor->currentDocuments);
        $vendor->update(['status' => VendorStatus::Declined]);

        $this->get($url)->assertForbidden()->assertSee('no longer active');
    }

    public function test_supporting_documents_never_expire_or_trigger_reminders(): void
    {
        Storage::fake('local');
        Notification::fake();
        $form = $this->form(overrides: ['docs' => ['other' => ['file' => UploadedFile::fake()->create('nis.pdf', 50, 'application/pdf'), 'expires_on' => '2020-01-01']]]);
        $this->post('/teaming', $form)->assertRedirect('/thanks');
        $vendor = Vendor::sole();
        $this->assertNull($vendor->current(DocumentKind::Other)?->expires_on);

        // Even a dated "other" document on an approved vendor is not chased.
        $vendor->update(['status' => VendorStatus::Approved]);
        $vendor->documents()->where('kind', 'other')->update(['expires_on' => today()->subYear()]);
        $this->assertSame(0, Vendor::needsRenewal()->count());
        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemandTimes(CertificateExpiring::class, 0);
    }

    public function test_compliance_states(): void
    {
        $vendor = Vendor::factory()->approved()->withCertificates(200)->create();
        $this->assertSame('valid', $vendor->complianceState());
        $vendor->documents()->where('kind', 'workers_comp')->update(['expires_on' => today()->subDay()]);
        $this->assertSame('expired', $vendor->refresh()->complianceState());
        $vendor->documents()->where('kind', 'liability')->delete();
        $this->assertSame('missing', $vendor->refresh()->complianceState());
        $this->assertSame(1, Vendor::needsRenewal()->count());
    }

    public function test_junk_array_input_redisplays_the_teaming_form(): void
    {
        $junk = ['type' => ['x'], 'company_name' => ['y'], 'uei' => ['x'], 'set_asides' => 'hubzone', 'trades' => 'earthwork', 'regions_served' => [['x']], 'docs' => ['liability' => ['number' => ['x'], 'expires_on' => ['x']]]];
        $this->from('/teaming')->post('/teaming', $junk)->assertRedirect('/teaming')->assertSessionHasErrors(['type', 'company_name', 'trades']);
        $this->followingRedirects()->from('/teaming')->post('/teaming', $junk)->assertOk()->assertSee('Please check');
    }

    public function test_a_renewal_sent_twice_at_once_replaces_the_certificate_once(): void
    {
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        $stale = Vendor::findOrFail($vendor->id);

        // Both requests passed the link check while the policy was still current.
        $seen = $vendor->awaitingRenewal($vendor->documents()->pluck('id')->implode('.'));
        $this->assertTrue($vendor->supersedeCurrent(DocumentKind::Liability, $seen));
        $this->assertFalse($stale->supersedeCurrent(DocumentKind::Liability, $seen), 'the second request finds the copy already claimed');
        $this->assertSame(1, $vendor->documents()->where('kind', 'liability')->whereNotNull('superseded_at')->count());
    }

    public function test_overlapping_reminder_runs_email_each_certificate_once(): void
    {
        Notification::fake();
        $vendor = Vendor::factory()->approved()->withCertificates(10)->create();
        // Another run claimed the liability certificate a moment ago.
        $vendor->documents()->where('kind', 'liability')->update(['reminded_at' => now()]);

        $this->artisan('app:remind-expiring')->assertSuccessful();
        Notification::assertSentOnDemand(CertificateExpiring::class, fn (CertificateExpiring $n) => $n->documents->count() === 2
            && ! $n->documents->contains(fn ($d) => $d->kind === DocumentKind::Liability));
    }

    public function test_demo_data_never_seeds_a_live_site_but_does_seed_a_pitch_preview(): void
    {
        Storage::fake('local');
        $this->app['env'] = 'production';
        config(['environet.preview' => false]);
        app(DemoSeeder::class)->run();
        $this->assertSame(0, User::count());

        config(['environet.preview' => true]);
        app(DemoSeeder::class)->run();
        $this->assertTrue(User::where('email', 'admin@environet.test')->exists());
    }
}
