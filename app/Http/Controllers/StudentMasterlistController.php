<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\MasterlistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentMasterlistController extends Controller
{
    /** Column order of the import file. */
    private const CSV_COLUMNS = [
        'student_number', 'first_name', 'middle_name', 'last_name',
        'birth_date', 'department_code', 'year_level', 'section',
    ];

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $state  = $request->query('state');

        $entries = MasterlistEntry::query()
            ->with(['department', 'account'])
            ->search($search)
            ->when($state === 'registered',   fn ($q) => $q->has('account'))
            ->when($state === 'unregistered', fn ($q) => $q->doesntHave('account'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.masterlist.index', [
            'entries'     => $entries,
            'departments' => Department::orderBy('department_code')->get(),
            'search'      => $search,
            'state'       => $state,
            'csvColumns'  => self::CSV_COLUMNS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages(), ['department_id' => 'department']);

        MasterlistEntry::create($data);

        return redirect()->route('admin.masterlist.index')
            ->with('success', 'Student number "' . $data['student_number'] . '" was added to the masterlist.');
    }

    /**
     * Bulk import from a CSV file. Rows that are already on the list are
     * skipped; rows with bad data are reported and the rest still go in.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ], [
            'file.mimes' => 'Please upload a CSV file.',
            'file.max'   => 'The file may not be larger than 1 MB.',
        ]);

        $departments = Department::pluck('id', 'department_code')
            ->mapWithKeys(fn ($id, $code) => [mb_strtoupper($code) => $id]);

        $added   = 0;
        $skipped = 0;
        $errors  = [];

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $line   = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Blank line
            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            // Header row. Excel may put a UTF-8 BOM in front of the first cell.
            $firstCell = mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0])));
            if ($line === 1 && $firstCell === 'student_number') {
                continue;
            }

            if (count($row) < count(self::CSV_COLUMNS)) {
                $errors[] = "Line {$line}: expected " . count(self::CSV_COLUMNS) . ' columns.';
                continue;
            }

            $record = array_combine(
                self::CSV_COLUMNS,
                array_map(fn ($value) => trim((string) $value), array_slice($row, 0, count(self::CSV_COLUMNS)))
            );

            $record['department_id'] = $departments[mb_strtoupper($record['department_code'])] ?? null;
            $record['year_level']    = $this->normalizeYearLevel($record['year_level']);
            $record['middle_name']   = $record['middle_name'] !== '' ? $record['middle_name'] : null;

            if (MasterlistEntry::where('student_number', $record['student_number'])->exists()) {
                $skipped++;
                continue;
            }

            $validator = Validator::make($record, $this->rules(), $this->messages(), ['department_id' => 'department']);

            if ($validator->fails()) {
                $errors[] = "Line {$line}: " . $validator->errors()->first();
                continue;
            }

            $clean = $validator->validated();
            $clean['birth_date'] = Carbon::parse($clean['birth_date'])->toDateString();

            MasterlistEntry::create($clean);
            $added++;
        }

        fclose($handle);

        $summary = "{$added} added, {$skipped} already on the list, " . count($errors) . ' with errors.';

        if ($errors) {
            // Enough detail to fix the file without flooding the page
            $shown = array_slice($errors, 0, 5);
            $more  = count($errors) - count($shown);

            return redirect()->route('admin.masterlist.index')->with(
                'error',
                'Import finished: ' . $summary . ' ' . implode(' ', $shown)
                    . ($more > 0 ? " (and {$more} more)" : '')
            );
        }

        return redirect()->route('admin.masterlist.index')
            ->with('success', 'Import finished: ' . $summary);
    }

    /**
     * Blank CSV with the right header row and one sample line.
     */
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLUMNS);
            fputcsv($out, ['2026-00021', 'Maria', 'Santos', 'Dela Cruz', '2006-03-15', 'BSIT', '1st Year', 'A']);
            fclose($out);
        }, 'student-masterlist-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(MasterlistEntry $entry): RedirectResponse
    {
        if ($entry->account()->exists()) {
            return redirect()->route('admin.masterlist.index')
                ->with('error', 'Cannot remove "' . $entry->student_number
                    . '" because that student already has an account.');
        }

        $number = $entry->student_number;
        $entry->delete();

        return redirect()->route('admin.masterlist.index')
            ->with('success', 'Student number "' . $number . '" was removed from the masterlist.');
    }

    // ---------------- Helpers ----------------

    private function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:30', 'unique:student_masterlist,student_number'],
            'first_name'     => ['required', 'string', 'max:100'],
            'middle_name'    => ['nullable', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'birth_date'     => ['required', 'date', 'before:today'],
            'department_id'  => ['required', 'integer', 'exists:departments,id'],
            'year_level'     => ['required', Rule::in(MasterlistEntry::YEAR_LEVELS)],
            'section'        => ['required', 'string', 'max:20'],
        ];
    }

    private function messages(): array
    {
        return [
            'student_number.unique'  => 'That student number is already on the masterlist.',
            'department_id.required' => 'The department code was not recognised.',
            'birth_date.before'      => 'The birth date must be in the past.',
        ];
    }

    /**
     * Accepts "1", "1st", "1st year" and so on from spreadsheets.
     */
    private function normalizeYearLevel(string $value): string
    {
        $digit = (int) preg_replace('/\D/', '', $value);

        return MasterlistEntry::YEAR_LEVELS[$digit - 1] ?? $value;
    }
}
