<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $oldPhoto = $user->tutorProfile?->photo;
            if ($oldPhoto) {
                \Storage::disk('public')->delete($oldPhoto);
            }

            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->tutorProfile()->updateOrCreate(['user_id' => $user->id], ['photo' => $path]);
        }

        $user->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'time_zone' => $validated['time_zone'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->attributes->get('sync_subscription_preference')) {
            $user->is_subscribed = $request->boolean('is_subscribed');
        }

        $user->save();

        // ── Update blurb on the appropriate profile table
        $blurb = $validated['blurb'] ?? null;
        if ($user->role === 'tutor' || $user->can_tutor) {
            $user->tutorProfile()->updateOrCreate(['user_id' => $user->id], ['blurb' => $blurb]);
        } elseif ($user->role === 'student') {
            $user->studentProfile()->updateOrCreate(['user_id' => $user->id], ['blurb' => $blurb]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
