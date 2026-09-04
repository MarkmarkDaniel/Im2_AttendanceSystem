<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QRAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    /* ==================================================================
     | INSTRUCTOR SIDE
     ================================================================== */

    /**
     * Class picker for QR attendance.
     */
    public function index(Request $request): View
    {
        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        $today = now()->toDateString();

        $classes = ClassModel::with('subject')
            ->where('instructor_id', $instructor->id)
            ->where('status', 'Active')
            ->withCount('activeEnrollments')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get();

        $sessions = AttendanceSession::whereIn('class_id', $classes->pluck('id'))
            ->whereDate('session_date', $today)
            ->get()
            ->keyBy('class_id');

        return view('instructor.qr.index', compact('classes', 'sessions'));
    }

    /**
     * The live QR display for one class.
     */
    public function show(Request $request, ClassModel $class): View
    {
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $class->load('subject');

        $date = $request->query('date', now()->toDateString());

        $session = AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', $date)
            ->first();

        // Make sure the code on screen is current the moment the page loads
        if ($session && $session->isOpen()) {
            $session = $this->attendance->rotateToken($session);
        }

        $enrolledCount = Enrollment::where('class_id', $class->id)
            ->where('enrollment_status', 'Enrolled')
            ->count();

        // The QR encodes ONLY this URL -- no names, no student numbers,
        // no class IDs. Just a random token.
        $scanUrl = $session && $session->qr_token
            ? route('student.qr.scan', $session->qr_token)
            : null;

        return view('instructor.qr.show', compact(
            'class', 'session', 'date', 'enrolledCount', 'scanUrl'
        ));
    }

    /**
     * Returns the current QR code as SVG. The instructor's page polls this
     * so the code on screen rotates automatically.
     */
    public function refreshQr(Request $request, AttendanceSession $session): JsonResponse
    {
        abort_unless($session->classModel->belongsToInstructor($request->user()), 403);

        if (! $session->isOpen()) {
            return response()->json([
                'ok'      => false,
                'message' => 'This session is already closed.',
            ], 410);
        }

        $session = $this->attendance->rotateToken($session);

        $scanUrl = route('student.qr.scan', $session->qr_token);

        // The (string) cast is required. generate() returns an HtmlString
        // object, which becomes "[object Object]" once it passes through JSON.
        $svg = (string) \QrCode::size(280)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($scanUrl);

        return response()->json([
            'ok'         => true,
            'svg'        => $svg,
            'token'      => $session->qr_token,
            'expires_in' => $session->qr_token_expires_at
                ? max(0, (int) now()->diffInSeconds($session->qr_token_expires_at, false))
                : null,
        ]);
    }

    /**
     * Live counters, polled by JavaScript every few seconds.
     */
    public function stats(Request $request, AttendanceSession $session): JsonResponse
    {
        abort_unless($session->classModel->belongsToInstructor($request->user()), 403);

        $enrolled = Enrollment::where('class_id', $session->class_id)
            ->where('enrollment_status', 'Enrolled')
            ->count();

        $present = $session->presentCount();
        $late    = $session->lateCount();
        $checked = $present + $late;

        return response()->json([
            'status'    => $session->status,
            'present'   => $present,
            'late'      => $late,
            'absent'    => $session->absentCount(),
            'checkedIn' => $checked,
            'remaining' => max(0, $enrolled - $checked),
            'enrolled'  => $enrolled,
            'recent'    => $session->attendances()
                ->with('student')
                ->whereIn('status', ['Present', 'Late'])
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'name'   => $row->student->full_name,
                    'number' => $row->student->student_number,
                    'time'   => $row->time_in
                        ? \Carbon\Carbon::parse($row->time_in)->format('g:i A')
                        : '—',
                    'status' => $row->status,
                ]),
        ]);
    }

    /* ==================================================================
     | STUDENT SIDE
     ================================================================== */

    /**
     * The camera scanner page.
     */
    public function scanner(Request $request): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        return view('student.qr.scanner');
    }

    /**
     * Landing route for a scanned QR code. Phone cameras open this URL
     * directly, so it must work as a plain GET request too.
     */
    public function scan(Request $request, string $token): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        // $request is passed so the service can check the network and
        // save the IP address for the audit trail
        $result = $this->attendance->processScan($token, $student, $request);

        $this->logScanAttempt($request, $student->id, $result);

        return view('student.qr.result', compact('result'));
    }

    /**
     * AJAX endpoint used by the in-page camera scanner.
     */
    public function submit(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'ok'      => false,
                'message' => 'No student profile is linked to this account.',
            ], 403);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        // A scanned QR may contain the full URL, so pull out the last segment
        $token = $this->extractToken($validated['token']);

        $result = $this->attendance->processScan($token, $student, $request);

        $this->logScanAttempt($request, $student->id, $result);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Accepts either a bare token or a full scan URL. Tokens are generated
     * uppercase-only, so a hand-typed code is normalised the same way.
     */
    private function extractToken(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '/')) {
            $path = parse_url($raw, PHP_URL_PATH) ?? $raw;

            return strtoupper(basename(rtrim($path, '/')));
        }

        return strtoupper($raw);
    }

    /**
     * Write every scan attempt to the log, successful or not.
     * A burst of failures from one IP is a sign someone is sharing codes.
     */
    private function logScanAttempt(Request $request, int $studentId, array $result): void
    {
        Log::channel('single')->info('QR scan attempt', [
            'student_id' => $studentId,
            'ip'         => $request->ip(),
            'result'     => $result['ok'] ? 'SUCCESS' : 'FAILED',
            'message'    => $result['message'] ?? null,
            'at'         => now()->toDateTimeString(),
        ]);
    }
}
