@extends('layouts.app')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('page-subtitle', $student->student_number . ' — ' . $student->full_name)

@section('content')

<div class="row">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.students.update', $student) }}"
                      enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.students._form', ['submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
