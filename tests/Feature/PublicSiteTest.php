<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Support\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/'], 'services' => ['/services'], 'performance' => ['/past-performance'], 'about' => ['/about'],
            'capability' => ['/capability-statement'], 'contact' => ['/contact'], 'rfq' => ['/rfq'],
            'teaming' => ['/teaming'], 'careers' => ['/careers'], 'apply' => ['/careers/apply'],
            'privacy' => ['/privacy'], 'credits' => ['/photo-credits'], 'sitemap' => ['/sitemap'], 'login' => ['/login'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_pages_render(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('Environet', false);
    }

    public function test_every_inner_page_opens_with_the_shared_header(): void
    {
        foreach (self::pages() as [$url]) {
            if (in_array($url, ['/', '/login', '/capability-statement'], true)) {
                continue;
            }
            $this->get($url)->assertSee('class="lanes relative isolate', false)->assertSee('class="strata"', false)->assertSee('class="pinflag"', false);
        }
        foreach (Portfolio::all()->keys() as $slug) {
            $this->get('/past-performance/'.$slug)->assertSee('class="strata"', false)->assertSee('sample-tag', false);
        }
        // The home hero is the same band, taller, with the registration tag.
        $this->get('/')->assertSee('class="lanes relative isolate', false)->assertSee('sample-tag', false);
    }

    public function test_every_award_has_a_page_with_its_usaspending_source(): void
    {
        $this->assertGreaterThanOrEqual(10, Portfolio::all()->count());
        foreach (Portfolio::all() as $slug => $a) {
            $this->assertStringStartsWith('https://www.usaspending.gov/award/', $a['source'], "{$slug} needs a USAspending source");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $a['signed']);
            $this->assertFileExists(public_path('img/'.$a['photo'].'-960.webp'), "{$slug} photo missing");
            $this->get(route('performance.show', $slug))->assertOk()
                ->assertSee($a['title'])->assertSee($a['published'])->assertSee($a['piid'])
                ->assertSee(usd($a['value'], false), false)->assertSee($a['source'], false)->assertSee('Illustrative photo');
        }
        $this->get('/past-performance/not-an-award')->assertNotFound();
    }

    public function test_past_performance_filter_by_sector_ignores_junk(): void
    {
        $sector = array_key_first(Portfolio::sectors());
        $this->get('/past-performance?sector='.$sector)->assertOk()->assertSee('aria-current="true"', false);
        $this->get('/past-performance?sector[]=x')->assertOk();
        $this->get('/past-performance?sector=nope')->assertOk()->assertSee(Portfolio::all()->count().' awards');
    }

    public function test_every_photo_is_credited_described_and_exists(): void
    {
        $credits = config('photo-credits');
        foreach ((array) config('environet.photos') as $key => $meta) {
            $this->assertArrayHasKey($key, $credits, "{$key} has no credit");
            $this->assertNotEmpty($meta['alt'] ?? null, "{$key} has no alt text");
            foreach ([480, 960, 1920] as $w) {
                $this->assertFileExists(public_path("img/{$key}-{$w}.webp"));
            }
        }
        foreach ((array) config('environet.own_photos') as $key) {
            $this->assertStringContainsString('to be confirmed', $credits[$key]['licence']);
        }
        $this->get('/photo-credits')->assertSee('Wikimedia Commons')->assertSee('reuse to be confirmed');
    }

    /**
     * The firm's old website made claims that no public source confirms (licences, staff numbers,
     * safety records, contract capacities). None of them may reach the new site or its PDFs.
     */
    public function test_no_unsourced_claims_on_the_firms_pages(): void
    {
        $banned = ['explosives license', 'atf', 'staff of', 'without incident', 'security clearance', 'cleared personnel', 'iso 9001',
            'emr', 'experience modification', 'bonding capacity of', 'zero lost', 'lost-time', 'satoc', 'matoc', 'acres', 'testimonial', 'award-winning'];
        $urls = array_merge(['/', '/about', '/services', '/past-performance', '/capability-statement', '/contact', '/careers'],
            Portfolio::all()->keys()->map(fn ($s) => '/past-performance/'.$s)->all());
        foreach ($urls as $url) {
            $html = strtolower(strip_tags((string) $this->get($url)->getContent()));
            foreach ($banned as $phrase) {
                $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($phrase, '/').'\b/', $html, "{$url} says \"{$phrase}\"");
            }
        }
    }

    public function test_registration_identifiers_are_the_published_ones(): void
    {
        $this->get('/')->assertSee('EEWLLJUH4SC9')->assertSee('1NQM6')->assertSee('HUBZone');
        $this->get('/about')->assertSee('Joseph G. Pickard')->assertSee('search.certifications.sba.gov', false);
        // The SAM address differs from the website's; the contact page says so.
        $this->get('/contact')->assertSee('91-799 Komohana Street')->assertSee('1286 Queen Emma Street')->assertSee('(808) 833-2225');
    }

    public function test_munitions_safety_message_is_the_dod_3rs(): void
    {
        $this->get('/services')->assertSee('Recognize')->assertSee('Retreat')->assertSee('Report')->assertSee('tel:911', false);
    }

    public function test_every_public_page_has_a_photo_and_stock_photos_are_labelled(): void
    {
        foreach (self::pages() as [$url]) {
            if (in_array($url, ['/login', '/capability-statement'], true)) {
                continue; // the sign-in screen and the printable one-pager
            }
            $html = (string) $this->get($url)->getContent();
            $this->assertMatchesRegularExpression('/<img [^>]*src="[^"]+\/img\//', $html, "{$url} has no photograph");
        }
        $this->get('/thanks')->assertRedirect('/'); // no submission: covered by the form tests
        $this->get('/')->assertSee('Illustrative photo')->assertSee('Environet photo');
        $this->get('/past-performance')->assertSee('Illustrative photo');
        $this->get('/no-such-page')->assertNotFound()->assertSee('Illustrative photo');
    }

    public function test_submit_buttons_read_correctly_without_javascript(): void
    {
        $html = (string) $this->get('/contact')->getContent();
        $this->assertMatchesRegularExpression('/<span hidden[^>]*>Sending…<\/span>/u', $html);
    }

    public function test_old_website_urls_redirect(): void
    {
        foreach ((array) config('environet.legacy_redirects') as $from => $to) {
            $this->get($from)->assertRedirect($to)->assertStatus(301);
        }
    }

    public function test_open_jobs_list_and_closed_jobs_404(): void
    {
        $open = JobPosting::factory()->create(['title' => 'Field geologist']);
        $closed = JobPosting::factory()->create(['title' => 'Old role', 'closes_on' => today()->subDay()]);

        $this->get('/careers')->assertSee('Field geologist')->assertDontSee('Old role')->assertSee('equal opportunity employer');
        $this->get('/careers/'.$open->slug)->assertOk()->assertSee('JobPosting', false);
        $this->get('/careers/'.$closed->slug)->assertNotFound();
    }

    public function test_sitemap_lists_awards_and_jobs(): void
    {
        $job = JobPosting::factory()->create();
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->getContent();
        foreach (Portfolio::all()->keys() as $slug) {
            $this->assertStringContainsString(route('performance.show', $slug), (string) $xml);
        }
        $this->assertStringContainsString(route('careers.show', $job), (string) $xml);
    }

    public function test_capability_pdf_downloads_or_falls_back_to_the_print_page(): void
    {
        $res = $this->get('/capability-statement.pdf');
        if (is_file(public_path('downloads/'.config('environet.capability_pdf')))) {
            $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        } else {
            $res->assertRedirect('/capability-statement');
        }
    }

    public function test_capability_statement_has_identifiers_naics_and_past_performance(): void
    {
        $res = $this->get('/capability-statement')->assertOk()->assertSee(config('environet.name'))
            ->assertSee('EEWLLJUH4SC9')->assertSee('1NQM6')->assertSee('236220')->assertSee('USAspending.gov');
        foreach ((array) config('environet.services') as $service) {
            $res->assertSee($service['title']);
        }
        foreach (Portfolio::all()->sortByDesc('value')->take(6) as $a) {
            $res->assertSee($a['piid'])->assertSee(usd($a['value']), false);
        }
    }

    public function test_preview_mode_adds_banner_and_noindex(): void
    {
        config(['environet.preview' => true]);
        $this->get('/')->assertSee('Concept preview')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
