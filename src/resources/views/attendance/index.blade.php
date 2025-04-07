@extends('layouts.app')

@section('content')
<div class="l-container l-container--narrow p-attendance-stamp">
    {{-- Alert component usage --}}
    <x-alert type="success" :message="session('success')" />
    <x-alert type="danger" :message="session('error')" />
    <x-alert type="warning" :message="session('warning')" />

    <div class="p-attendance-stamp__status">
        <span class="c-status-label">{{ $status }}</span>
    </div>
    <div>
        <h2  class="p-attendance-stamp__title">{{ $date }}</h2>
    </div>

    <div class="p-attendance-stamp__time">
        {{ $currentTime }}
    </div>

    <div class="p-attendance-stamp__actions">
        @if ($status === '勤務外')
        <form method="POST" action="{{ route('attendance.clock_in') }}" class="p-attendance-stamp__form">
            @csrf
            {{-- Button component usage --}}
            <x-button type="submit" variant="primary" size="lg">出勤</x-button>
        </form>
        @elseif ($status === '出勤中')
        <form method="POST" action="{{ route('attendance.clock_out') }}" class="p-attendance-stamp__form p-attendance-stamp__form--inline">
            @csrf
            <x-button type="submit" variant="danger" size="lg">退勤</x-button>
        </form>

        <form method="POST" action="{{ route('attendance.break_start') }}" class="p-attendance-stamp__form p-attendance-stamp__form--inline">
            @csrf
            <x-button type="submit" variant="secondary" size="lg">休憩入</x-button>
        </form>
        @elseif ($status === '休憩中')
        <form method="POST" action="{{ route('attendance.break_end') }}" class="p-attendance-stamp__form p-attendance-stamp__form--inline">
            @csrf
            <x-button type="submit" variant="secondary" size="lg">休憩戻</x-button>
        </form>
        @elseif ($status === '退勤済')
        <p class="p-attendance-stamp__message">お疲れさまでした。</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/attendance.js')
@endpush

