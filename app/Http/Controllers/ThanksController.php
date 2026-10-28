<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThanksController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $last = $request->session()->get('last_submission');

        return is_array($last) ? view('pages.thanks', $last) : to_route('home');
    }
}
