@extends('layouts.app')

@section('content')
<div class="approve-form">
    <h2 class="approve-form__title">修正申請の確認</h2>

    <div class="approve-form__container">
        <div class="approve-form__info">
            <div class="approve-form__row">
                <div class="approve-form__label">申請者</div>
                <div class="approve-form__value">{{ $request->user->name }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">申請日時</div>
                <div class="approve-form__value">{{ Carbon\Carbon::parse($request->created_at)->format('Y/m/d H:i') }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">対象日</div>
                <div class="approve-form__value">{{ Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">修正項目</div>
                <div class="approve-form__value">{{ $request->correction_type }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">修正前</div>
                <div class="approve-form__value">{{ $request->original_value }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">修正後</div>
                <div class="approve-form__value">{{ $request->requested_value }}</div>
            </div>
            <div class="approve-form__row">
                <div class="approve-form__label">申請理由</div>
                <div class="approve-form__value">{{ $request->reason }}</div>
            </div>
        </div>

        <div class="approve-form__actions">
            <form method="POST" action="{{ route('stamp_correction_request.approve', ['id' => $request->id]) }}" class="approve-form__form">
                @csrf
                <button type="submit" class="approve-form__button approve-form__button--approve">承認する</button>
            </form>
            <form method="POST" action="{{ route('stamp_correction_request.reject', ['id' => $request->id]) }}" class="approve-form__form">
                @csrf
                <button type="submit" class="approve-form__button approve-form__button--reject">却下する</button>
            </form>
            <a href="{{ route('stamp_correction_request.list') }}" class="approve-form__button approve-form__button--back">
                戻る
            </a>
        </div>
    </div>
</div>
@endsection 