@extends('layouts.app')

@section('title', 'Department Attendance Report')
@section('page-title', 'Department Attendance Report')
@section('page-subtitle', 'Attendance statistics grouped by department')

@section('page-actions')
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
    </button>
    <a href="{{ route('admin.reports.csv', array_merge(['report' => 'department'], array_filter($filters))) }}"
       class="btn btn-brand btn-sm">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </a>
        <a href="{{ route($routePrefix . '.reports.pdf', array_merge(['report' => 'daily'], array_filter($filters))) }}"
       class="btn btn-danger btn-sm">
        <i class="bi bi-filetype-pdf me-1"></i>Export PDF
    </a>
@endsection

@section('content')

{{-- ==================== FILTERS ==================== --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.department') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="department_id" class="form-label small">Department</label>
                <select class="form-select form-select-sm" id="department_id" name="department_id">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ $filters['department_id'] == $department->id ? 'selected' : '' }}>
                            {{ $department->department_code }} &mdash; {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label small">From</label>
                <input type="date" class="form-control form-control-sm"
                       id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label small">To</label>
                <input type="date" class="form-control form-control-sm"
                       id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Generate</button>
                <a href="{{ route('admin.reports.department') }}"
                   class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@php
    $appliedLabels = [];

    if ($filters['department_id']) {
        $selected = $departments->firstWhere('id', (int) $filters['department_id']);
        if ($selected) $appliedLabels['Department'] = $selected->department_code;
    }
    if ($filters['date_from']) {
        $appliedLabels['From'] = \Carbon\Carbon::parse($filters['date_from'])->format('M j, Y');
    }
    if ($filters['date_to']) {
        $appliedLabels['To'] = \Carbon\Carbon::parse($filters['date_to'])->format('M j, Y');
    }
@endphp

@include('reports._header', ['reportTitle' => 'Department Attendance Report'])
@include('reports._summary')

{{-- ==================== CHARTS ==================== --}}
@if ($rows->isNotEmpty())
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white fw-medium">Attendance Rate by Department</div>
                <div class="card-body">
                    <canvas id="deptRateChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-medium">Status Distribution</div>
                <div class="card-body">
                    <canvas id="deptStackChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ==================== TABLE ==================== --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Department Name</th>
                    <th class="text-center">Students</th>
                    <th class="text-center">Total Records</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">Absent</th>
                    <th class="text-end">Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @php $minimum = config('attendance.minimum_rate', 75); @endphp
                @forelse ($rows as $row)
                    <tr>
                        <td><span class="badge bg-dark">{{ $row->department_code }}</span></td>
                        <td class="small fw-medium">{{ $row->department_name }}</td>
                        <td class="text-center small">{{ $row->student_count }}</td>
                        <td class="text-center small">{{ number_format($row->total_count) }}</td>
                        <td class="text-center small text-success">{{ $row->present_count }}</td>
                        <td class="text-center small text-warning">{{ $row->late_count }}</td>
                        <td class="text-center small text-danger">{{ $row->absent_count }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $row->attendance_rate >= $minimum ? 'success' : 'danger' }}">
                                {{ $row->attendance_rate }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                            No attendance records match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->isNotEmpty())
        <div class="card-footer bg-white small text-muted">
            Departments with no enrolled students or no attendance records are not listed.
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    const deptRows = {{ Js::from($rows->map(fn ($r) => [
        'label'   => $r->department_code,
        'rate'    => (float) $r->attendance_rate,
        'present' => (int) $r->present_count,
        'late'    => (int) $r->late_count,
        'absent'  => (int) $r->absent_count,
    ])->values()) }};

    // ---------- Bar: rate per department ----------
    const rateCanvas = document.getElementById('deptRateChart');
    if (rateCanvas && deptRows.length > 0) {
        new Chart(rateCanvas, {
            type: 'bar',
            data: {
                labels: deptRows.map(r => r.label),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: deptRows.map(r => r.rate),
                    backgroundColor: '#14304f',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }

    // ---------- Stacked bar: status counts ----------
    const stackCanvas = document.getElementById('deptStackChart');
    if (stackCanvas && deptRows.length > 0) {
        new Chart(stackCanvas, {
            type: 'bar',
            data: {
                labels: deptRows.map(r => r.label),
                datasets: [
                    { label: 'Present', data: deptRows.map(r => r.present), backgroundColor: '#198754' },
                    { label: 'Late',    data: deptRows.map(r => r.late),    backgroundColor: '#ffc107' },
                    { label: 'Absent',  data: deptRows.map(r => r.absent),  backgroundColor: '#dc3545' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
</script>
@endpush
