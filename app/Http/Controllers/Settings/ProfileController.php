<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AvatarAnimal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('app/settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'photo_url' => $this->photoUrl($user->avatar_path),
            'avatar_animal' => $user->avatarAnimal()->value,
            'avatar_animals' => AvatarAnimal::values(),
            'placement' => $user->isStudent() && $user->studentProfile !== null ? [
                'purpose_label' => $user->studentProfile->registration_purpose->label(),
                'wants_placement' => $user->studentProfile->wantsPlacement(),
                'requested' => $user->studentProfile->placement_requested_at !== null,
            ] : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $avatarPath = $user->avatar_path;

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeAvatar(
                $request->file('avatar'),
                $user->id,
                $avatarPath,
            );
        } elseif ($request->boolean('remove_avatar') && filled($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
            $avatarPath = null;
        }

        $user->avatar_path = $avatarPath;

        if ($request->has('avatar_animal')) {
            $user->avatar_animal = $request->input('avatar_animal');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function storeAvatar(UploadedFile $file, int $userId, ?string $previous): string
    {
        if (filled($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return $file->store("avatars/{$userId}", 'public');
    }

    private function photoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
