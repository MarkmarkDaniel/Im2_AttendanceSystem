@extends('layouts.app')

@section('title', 'Edit Instructor')
@section('page-title', 'Edit Instructor')
@section('page-subtitle', $instructor->employee_number . ' — ' . $instructor->full_name)

@section('content')

<div class="row">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.instructors.update', $instructor) }}"
                      enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.instructors._form', ['submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
