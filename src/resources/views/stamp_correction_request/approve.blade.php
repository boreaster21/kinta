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

<style>
.approve-form {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.approve-form__title {
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
}

.approve-form__container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.approve-form__info {
    margin-bottom: 30px;
}

.approve-form__row {
    display: flex;
    border-bottom: 1px solid #eee;
    padding: 15px 0;
}

.approve-form__label {
    width: 120px;
    font-weight: bold;
    color: #666;
}

.approve-form__value {
    flex: 1;
}

.approve-form__actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    padding-top: 20px;
}

.approve-form__form {
    display: inline-block;
}

.approve-form__button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.approve-form__button--approve {
    background-color: #4CAF50;
    color: white;
}

.approve-form__button--approve:hover {
    background-color: #388E3C;
}

.approve-form__button--reject {
    background-color: #F44336;
    color: white;
}

.approve-form__button--reject:hover {
    background-color: #D32F2F;
}

.approve-form__button--back {
    background-color: #9e9e9e;
    color: white;
}

.approve-form__button--back:hover {
    background-color: #757575;
}
</style>
@endsection 