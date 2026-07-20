<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\Auth\RegisterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    public function __construct(private RegisterService $registerService) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registerService->register($request->validated());

        $request->session()->put('registration_attempt_id', $user->registration_attempt_id);

        return redirect()->route('verification.create');
    }
}
