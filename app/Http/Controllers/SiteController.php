<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View|RedirectResponse
    {
        return auth()->check() ? redirect()->route('dashboard') : view('site.home');
    }
}
