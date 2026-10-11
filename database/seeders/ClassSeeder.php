<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Instructor;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $subjects    = Subject::pluck('id', 'subject_code');
        $instructors = Instructor::pluck('id', 'employee_number');

        $classes = [
            // subject, instructor,     section, room,     day,        start,      end,        school_year, semester
            ['ITP104', 'EMP-2026-001', '3A',    'Room 301', 'Monday',    '08:00:00', '11:00:00', '2026-2027', '1st Semester'],
            ['ITP111', 'EMP-2026-001', '3A',    'Room 302', 'Tuesday',   '13:00:00', '16:00:00', '2026-2027', '1st Semester'],
            ['ITP102', 'EMP-2026-002', '2A',    'Room 303', 'Wednesday', '08:00:00', '11:00:00', '2026-2027', '1st Semester'],
            ['ITP105', 'EMP-2026-002', '2B',    'Lab 1',    'Thursday',  '10:00:00', '13:00:00', '2026-2027', '1st Semester'],
            ['CS201',  'EMP-2026-003', '3A',    'Room 401', 'Monday',    '13:00:00', '16:00:00', '2026-2027', '1st Semester'],
            ['CPE101', 'EMP-2026-004', '1A',    'Lab 2',    'Friday',    '08:00:00', '11:00:00', '2026-2027', '1st Semester'],
        ];

        foreach ($classes as [$subjectCode, $employeeNumber, $section, $room, $day, $start, $end, $schoolYear, $semester]) {
            // An instructor can only teach a subject that is assigned to them
            Instructor::find($instructors[$employeeNumber])
                ->subjects()
                ->syncWithoutDetaching([$subjects[$subjectCode]]);

            ClassModel::updateOrCreate(
                [
                    'subject_id'  => $subjects[$subjectCode],
                    'section'     => $section,
                    'school_year' => $schoolYear,
                    'semester'    => $semester,
                ],
                [
                    'instructor_id' => $instructors[$employeeNumber],
                    'room'          => $room,
                    'schedule_day'  => $day,
                    'start_time'    => $start,
                    'end_time'      => $end,
                    'status'        => 'Active',
                ]
            );
        }
    }
}
