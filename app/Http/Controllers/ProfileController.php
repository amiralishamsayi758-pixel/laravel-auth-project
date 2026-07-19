<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->safe()->only(['username', 'gmail', 'phone']);
        $gmailChanged = $validated['gmail'] !== $user->gmail;

        $user->fill($validated);

        if ($gmailChanged) {
            $user->forceFill(['gmail_verified_at' => null]);
        }

        $user->save();

        if ($gmailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')->with('status', 'gmail-changed');
        }

        return back()->with('status', 'profile-updated');
    }
}
