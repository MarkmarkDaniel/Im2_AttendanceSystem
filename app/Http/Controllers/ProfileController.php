<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user    = $request->user();
        $summary = null;

        if ($user->isStudent() && $user->student) {
            $user->student->load('department');

            $row = Attendance::where('student_id', $user->student->id)
                ->selectRaw("
                    COUNT(*) AS total_count,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                    SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
                ")
                ->first();

            $summary = [
                'total'   => (int) ($row->total_count ?? 0),
                'present' => (int) ($row->present_count ?? 0),
                'late'    => (int) ($row->late_count ?? 0),
                'absent'  => (int) ($row->absent_count ?? 0),
            ];

            $summary['rate'] = Attendance::calculateRate(
                $summary['present'], $summary['late'], $summary['total']
            );
        }

        if ($user->isInstructor() && $user->instructor) {
            $user->instructor->load('department');
        }

        return view('profile.show', compact('user', 'summary'));
    }

    /**
     * Change own password. Requires the current password.
     *
     * Admins only. Students and instructors change their password through
     * the one-time link emailed by PasswordResetController::sendLinkToSelf().
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            return redirect()->route('profile.show')->with(
                'error',
                'Your password is changed through the link sent to your registered email address.'
            );
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->with('error', 'Your current password is incorrect.');
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Your password was updated.');
    }

    /**
     * Upload or replace own profile image. Admins only.
     */
    public function updateImage(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user->isAdmin(), 403, 'Only administrators can set a profile image.');

        $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_image.required' => 'Please choose an image to upload.',
            'profile_image.max'      => 'The profile image may not be larger than 2 MB.',
        ]);

        $user->storeProfileImage($request->file('profile_image'));

        return redirect()->route('profile.show')
            ->with('success', 'Your profile image was updated.');
    }

    /**
     * Remove own profile image. Admins only.
     */
    public function destroyImage(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user->isAdmin(), 403, 'Only administrators can set a profile image.');

        $user->removeProfileImage();

        return redirect()->route('profile.show')
            ->with('success', 'Your profile image was removed.');
    }
}
