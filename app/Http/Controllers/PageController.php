<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Support\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'featured' => Portfolio::featured(),
            'awardCount' => Portfolio::all()->count(),
            'openJobs' => JobPosting::open()->count(),
        ]);
    }

    public function services(): View
    {
        return view('pages.services');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    /** Capability statement for contracting officers and primes: print-styled, built from config. */
    public function capability(): View
    {
        return view('pages.capability', ['awards' => Portfolio::all()]);
    }

    /** The PDF made from the page above (npm run capability); falls back to the print page. */
    public function capabilityPdf(): RedirectResponse|BinaryFileResponse
    {
        $file = public_path('downloads/'.enet('capability_pdf'));

        return is_file($file)
            ? response()->download($file, (string) enet('capability_pdf'), ['Content-Type' => 'application/pdf'])
            : to_route('capability');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        return view('pages.credits');
    }

    public function sitemapPage(): View
    {
        return view('pages.sitemap', [
            'awards' => Portfolio::all(),
            'jobs' => JobPosting::open()->orderBy('title')->get(['slug', 'title']),
        ]);
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'services', 'performance.index', 'about', 'capability', 'contact', 'solicitations.create', 'teaming.create', 'careers.index', 'careers.apply', 'privacy', 'credits'])
            ->map(fn (string $r) => ['loc' => route($r), 'lastmod' => null])
            ->concat(Portfolio::all()->map(fn (array $p) => ['loc' => route('performance.show', $p['slug']), 'lastmod' => null])->values())
            ->concat(JobPosting::open()->get(['slug', 'updated_at'])->map(fn (JobPosting $j) => ['loc' => route('careers.show', $j), 'lastmod' => $j->updated_at->toAtomString()]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
