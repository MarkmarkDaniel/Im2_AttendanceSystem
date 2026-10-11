<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Notification;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function open(Request $request, ClassModel $class): RedirectResponse
    {
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $validated = $request->validate([
            'session_date' => ['nullable', 'date'],
        ]);

        $date = $validated['session_date'] ?? now()->toDateString();

        $session = $this->attendance->openSession($class, $date);

        return redirect()->route('instructor.attendance.record', $class)
            ->with('success', 'Attendance session opened for '
                . $session->session_date->format('F j, Y') . '.');
    }

    /**
     * Close the session: expire the QR token and auto-mark absentees.
     */
    public function close(Request $request, AttendanceSession $session): RedirectResponse
    {
        $class = $session->classModel;

        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        if (! $session->isOpen()) {
            return back()->with('error', 'This session is already closed.');
        }

        $result = $this->attendance->closeSession($session);

        // Let the instructor know the final tally
        Notification::send(
            $request->user()->id,
            'Session Closed',
            'Attendance session for ' . $class->class_title . ' on '
                . $session->session_date->format('F j, Y') . ' was closed. '
                . $result['present'] . ' Present, ' . $result['late'] . ' Late, '
                . $result['absent'] . ' Absent.',
            'info'
        );

        return redirect()->route('instructor.attendance.record', $class)
            ->with('success', 'Session closed. ' . $result['absent_created']
                . ' student(s) were automatically marked Absent.');
    }
}
