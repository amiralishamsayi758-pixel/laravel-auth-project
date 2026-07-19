<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AccountDeletionController extends Controller
{
    public function __invoke(AccountDeletionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $avatarPath = $user->avatar_path;

        Auth::logout();

        if (is_string($avatarPath) && str_starts_with($avatarPath, 'avatars/')) {
            Storage::disk('public')->delete($avatarPath);
        }

        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'account-deleted');
    }
}
