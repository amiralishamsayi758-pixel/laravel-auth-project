<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvatarUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AvatarController extends Controller
{
    public function store(AvatarUpdateRequest $request): RedirectResponse
    {
        $path = $request->file('avatar')->store('avatars', 'public');

        if (! is_string($path)) {
            throw new RuntimeException('Avatar storage failed.');
        }

        $user = $request->user();
        $oldPath = $user->avatar_path;

        try {
            $user->forceFill(['avatar_path' => $path])->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        $this->deleteManagedAvatar($oldPath);

        return back()->with('status', 'avatar-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;

        $user->forceFill(['avatar_path' => null])->save();
        $this->deleteManagedAvatar($oldPath);

        return back()->with('status', 'avatar-removed');
    }

    private function deleteManagedAvatar(?string $path): void
    {
        if ($path !== null && str_starts_with($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
