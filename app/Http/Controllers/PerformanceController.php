<?php

namespace App\Http\Controllers;

use App\Support\Portfolio;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Past performance: federal awards as USAspending.gov publishes them. */
class PerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $sectors = Portfolio::sectors();
        $sector = input_string($request->query('sector'));
        $sector = array_key_exists($sector, $sectors) ? $sector : null;

        return view('pages.performance.index', [
            'awards' => Portfolio::inSector($sector),
            'sectors' => $sectors,
            'sector' => $sector,
            'counts' => Portfolio::all()->countBy('sector'),
        ]);
    }

    public function show(string $slug): View
    {
        $award = Portfolio::find($slug);
        abort_if($award === null, 404);

        return view('pages.performance.show', [
            'award' => $award,
            'related' => Portfolio::related($slug),
        ]);
    }
}
