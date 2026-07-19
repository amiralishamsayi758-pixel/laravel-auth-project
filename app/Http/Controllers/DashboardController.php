<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $registeredUserId = $request->session()->get('registered_user_id');

        if (! is_numeric($registeredUserId)) {
            return redirect()->route('register.create');
        }

        $user = User::find($registeredUserId);

        if ($user === null) {
            $request->session()->forget('registered_user_id');

            return redirect()->route('register.create');
        }

        return view('dashboard.index', ['user' => $user]);
    }
}
