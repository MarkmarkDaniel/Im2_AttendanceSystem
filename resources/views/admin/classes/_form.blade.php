<div class="row g-3">

    <div class="col-md-6">
        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
        <select class="form-select @error('subject_id') is-invalid @enderror"
                id="subject_id" name="subject_id" required>
            <option value="">-- Select Subject --</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}"
                    {{ old('subject_id', $class->subject_id ?? request('subject_id')) == $subject->id ? 'selected' : '' }}>
                    {{ $subject->subject_code }} &mdash; {{ $subject->subject_name }}
                </option>
            @endforeach
        </select>
        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="instructor_id" class="form-label">Instructor <span class="text-danger">*</span></label>
        <select class="form-select @error('instructor_id') is-invalid @enderror"
                id="instructor_id" name="instructor_id" required>
            <option value="">-- Select Instructor --</option>
            @foreach ($instructors as $instructor)
                <option value="{{ $instructor->id }}"
                    data-subjects="{{ $instructor->subjects->pluck('id')->implode(',') }}"
                    {{ old('instructor_id', $class->instructor_id ?? request('instructor_id')) == $instructor->id ? 'selected' : '' }}>
                    {{ $instructor->full_name }} ({{ $instructor->employee_number }})
                </option>
            @endforeach
        </select>
        @error('instructor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Only instructors assigned to the selected subject are listed.
            <a href="{{ route('admin.instructors.index') }}">Assign subjects</a>
        </div>
    </div>

    <div class="col-md-4">
        <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('school_year') is-invalid @enderror"
               id="school_year" name="school_year"
               value="{{ old('school_year', $class->school_year ?? '2026-2027') }}"
               placeholder="2026-2027" required>
        @error('school_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
        <select class="form-select @error('semester') is-invalid @enderror"
                id="semester" name="semester" required>
            @foreach ($semesters as $semester)
                <option value="{{ $semester }}"
                    {{ old('semester', $class->semester ?? '1st Semester') === $semester ? 'selected' : '' }}>
                    {{ $semester }}
                </option>
            @endforeach
        </select>
        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('section') is-invalid @enderror"
               id="section" name="section"
               value="{{ old('section', $class->section ?? '') }}" placeholder="3A" required>
        @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="room" class="form-label">Room</label>
        <input type="text" class="form-control @error('room') is-invalid @enderror"
               id="room" name="room"
               value="{{ old('room', $class->room ?? '') }}" placeholder="Room 301">
        @error('room')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="schedule_day" class="form-label">Schedule Day <span class="text-danger">*</span></label>
        <select class="form-select @error('schedule_day') is-invalid @enderror"
                id="schedule_day" name="schedule_day" required>
            @foreach ($days as $dayOption)
                <option value="{{ $dayOption }}"
                    {{ old('schedule_day', $class->schedule_day ?? 'Monday') === $dayOption ? 'selected' : '' }}>
                    {{ $dayOption }}
                </option>
            @endforeach
        </select>
        @error('schedule_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2">
        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
        <input type="time" class="form-control @error('start_time') is-invalid @enderror"
               id="start_time" name="start_time"
               value="{{ old('start_time', isset($class) ? \Carbon\Carbon::parse($class->start_time)->format('H:i') : '08:00') }}"
               required>
        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2">
        <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
        <input type="time" class="form-control @error('end_time') is-invalid @enderror"
               id="end_time" name="end_time"
               value="{{ old('end_time', isset($class) ? \Carbon\Carbon::parse($class->end_time)->format('H:i') : '11:00') }}"
               required>
        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror"
                id="status" name="status" required>
            @foreach (['Active', 'Inactive'] as $state)
                <option value="{{ $state }}"
                    {{ old('status', $class->status ?? 'Active') === $state ? 'selected' : '' }}>
                    {{ $state }}
                </option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            The start time drives late detection. With the current setting
            (<strong>{{ config('attendance.late_grace_minutes') }} minute grace period</strong>),
            a student checking in after the start time plus the grace period is marked Late.
        </div>
    </div>

    <div class="col-12 d-flex gap-2 mt-4">
        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-brand">
            <i class="bi bi-save me-1"></i>{{ $submitLabel }}
        </button>
    </div>

</div>

@push('scripts')
<script>
    // Only list the instructors assigned to the selected subject
    (function () {
        const subjectSelect    = document.getElementById('subject_id');
        const instructorSelect = document.getElementById('instructor_id');

        function filterInstructors() {
            const subjectId = subjectSelect.value;

            instructorSelect.querySelectorAll('option[data-subjects]').forEach(option => {
                const assigned = !subjectId
                    || option.dataset.subjects.split(',').includes(subjectId);

                option.hidden   = !assigned;
                option.disabled = !assigned;

                if (!assigned && option.selected) {
                    instructorSelect.value = '';
                }
            });
        }

        subjectSelect.addEventListener('change', filterInstructors);
        filterInstructors();
    })();
</script>
@endpush
