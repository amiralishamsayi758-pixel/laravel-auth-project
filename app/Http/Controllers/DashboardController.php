<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('verification.completed')) {
            return redirect()->route('register.create');
        }

        return view('dashboard.index');
    }
}
