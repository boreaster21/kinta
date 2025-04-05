@extends('layouts.app')

@section('content')
<div class="attendance">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    <h2 class="attendance__title">{{ $date }}</h2>

    <div class="attendance__status">
        <span class="status-label">{{ $status }}</span>
    </div>

    <div class="attendance__time">
        {{ $currentTime }}
    </div>

    <div class="attendance__actions">
        @if ($status === '勤務外')
        <form method="POST" action="{{ route('attendance.clock_in') }}" class="attendance-form">
            @csrf
            <button type="submit" class="btn btn-primary">出勤</button>
        </form>
        @elseif ($status === '出勤中')
        <form method="POST" action="{{ route('attendance.break_start') }}" class="attendance-form inline">
            @csrf
            <button type="submit" class="btn btn-secondary">休憩</button>
        </form>
        <form method="POST" action="{{ route('attendance.clock_out') }}" class="attendance-form inline">
            @csrf
            <button type="submit" class="btn btn-danger">退勤</button>
        </form>
        @elseif ($status === '休憩中')
        <form method="POST" action="{{ route('attendance.break_end') }}" class="attendance-form inline">
            @csrf
            <button type="submit" class="btn btn-secondary">休憩戻</button>
        </form>
        @elseif ($status === '退勤済')
        <p class="attendance__message">お疲れさまでした！</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/attendance.js')
@endpush