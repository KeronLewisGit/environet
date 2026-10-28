<?php

namespace Tests\Feature;

use App\Support\PacificMap;
use App\Support\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The revamp's motion pieces: their markup renders, and everything they animate is already in
 * the HTML in its final state, so visitors without JavaScript (or with reduced motion) miss nothing.
 */
class MotionMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_the_map_strip_timeline_and_count_ups(): void
    {
        $res = $this->get('/')->assertOk();

        // Pacific map: one route per place except the hub and its neighbour, and every place named in the key.
        $res->assertSee('data-map', false);
        $this->assertSame(count(PacificMap::places()) - 2, substr_count($res->getContent(), 'class="pmap-route"'));
        foreach (PacificMap::places() as $place) {
            $res->assertSee($place['label']);
        }

        // Capability strip: every registered NAICS code, once, in the server HTML (JS adds the copy).
        $res->assertSee('data-marquee', false);
        foreach (array_keys((array) enet('registration.naics')) as $code) {
            $this->assertSame(1, substr_count($res->getContent(), '<b class="num">'.$code.'</b>'));
        }
        // The pause button only appears once JS starts the motion.
        $res->assertSee('data-marquee-toggle aria-pressed="false" hidden', false);

        // Timeline: one award per year on the record, each linking to its page.
        $years = Portfolio::all()->groupBy(fn (array $a) => substr($a['signed'], 0, 4));
        $this->assertSame($years->count(), substr_count($res->getContent(), 'class="tl-item"'));
        foreach ($years as $awards) {
            $top = $awards->sortByDesc('value')->first();
            $res->assertSee(route('performance.show', $top['slug']), false);
        }

        // Count-ups hold the final, sourced figure as text.
        $res->assertSee('data-count="19.38"', false)
            ->assertSee('$19.38 million</span>', false)
            ->assertSee('data-count="4" data-suffix=" offices">4 offices</span>', false);

        // Scroll progress and the survey sweep are decorative.
        $res->assertSee('class="scroll-progress" aria-hidden="true"', false)
            ->assertSee('class="sonar" viewBox="0 0 400 400" aria-hidden="true"', false);
    }

    public function test_nothing_is_hidden_in_the_server_html(): void
    {
        foreach (['/', '/services', '/past-performance', '/about', '/contact', '/careers'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/style="[^"]*(opacity:\s*0|visibility:\s*hidden|display:\s*none)/i', $html, $url);
            // The "before" states live in CSS behind the .js class, which only JavaScript sets.
            $this->assertStringContainsString("document.documentElement.classList.add('js')", $html, $url);
        }
    }

    public function test_brand_wordmark_tagline_and_theme_wipe_hook(): void
    {
        $this->get('/')
            ->assertSee('Environet, Inc.</span>', false)
            ->assertSee("Preserving Earth's Resources for the Future")
            ->assertSee('onclick="toggleTheme(event)"', false);
    }

    public function test_every_map_place_sits_inside_the_drawing(): void
    {
        foreach (PacificMap::places() as $p) {
            $this->assertGreaterThan(0, $p['x'], $p['key']);
            $this->assertLessThan(PacificMap::WIDTH, $p['x'], $p['key']);
            $this->assertGreaterThan(0, $p['y'], $p['key']);
            $this->assertLessThan(PacificMap::HEIGHT, $p['y'], $p['key']);
        }
        // Honolulu, as projected for the land file (scripts/map/pacific.mjs uses the same numbers).
        $this->assertSame([754.7, 336.9], PacificMap::project(21.31, -157.85));
    }

    public function test_the_receipt_shows_the_logged_reference_without_js(): void
    {
        $this->post('/contact', ['name' => 'Malia Cruz', 'email' => 'malia@example.com', 'topic' => 'project', 'message' => 'We need a quote for a Phase I site assessment.'])
            ->assertRedirect('/thanks');

        $this->get('/thanks')->assertOk()
            ->assertSee('class="logged mb-5"', false)
            ->assertSeeInOrder(['Logged as', 'EN-']);
    }
}
