@extends('admin.layouts.app')

@section('page-title', 'BRANCH DETAILS')

@section('content')

    <div>
        <strong>Name:</strong> {{ $branch->name }}
    </div>
    <div>
        <strong>Location:</strong> {{ $branch->location }}
    </div>
    <a href="{{ route('admin.branches.edit', $branch) }}">Edit</a>
    <a href="{{ route('admin.branches.index') }}">Back to list</a>
@endsection
