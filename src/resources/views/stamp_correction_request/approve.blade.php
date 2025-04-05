@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">勤怠修正申請の確認</h2>
                </div>
                <div class="card-body">
                    <x-correction-request-table :request="$request" :showActions="true" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 