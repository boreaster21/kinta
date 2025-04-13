<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        \Illuminate\Support\Facades\Log::info('UpdateAttendanceRequest authorize called.');
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'breaks' => 'nullable|array',
            'breaks.*.start_time' => 'nullable|date_format:H:i|required_with:breaks.*.end_time',
            'breaks.*.end_time' => 'nullable|date_format:H:i|required_with:breaks.*.start_time',
            'reason' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInInput = $this->input('clock_in');
            $clockOutInput = $this->input('clock_out');

            if ($clockInInput && $clockOutInput) {
                $clockIn = Carbon::parse($clockInInput);
                $clockOut = Carbon::parse($clockOutInput);

                if ($clockIn->gt($clockOut)) {
                    $validator->errors()->add('clock_out', '退勤時間は出勤時間より後に設定してください。');
                }
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $index => $break) {
                $breakStartInput = $break['start_time'] ?? null;
                $breakEndInput = $break['end_time'] ?? null;

                if ($breakStartInput && $breakEndInput) {
                    $breakStart = Carbon::parse($breakStartInput);
                    $breakEnd = Carbon::parse($breakEndInput);

                    if ($breakStart->gt($breakEnd)) {
                        $validator->errors()->add("breaks.{$index}.end_time", '休憩終了時間は休憩開始時間より後に設定してください。');
                    }

                    if ($clockInInput && $clockOutInput) {
                        $clockIn = Carbon::parse($clockInInput);
                        $clockOut = Carbon::parse($clockOutInput);
                        if ($breakStart->lt($clockIn) || $breakEnd->gt($clockOut)) {
                            $validator->errors()->add("breaks.{$index}.start_time", '休憩時間は勤務時間内に設定してください。');
                        }
                    }
                } elseif ($breakStartInput || $breakEndInput) {
                    $validator->errors()->add("breaks.{$index}.start_time", '休憩開始時間と終了時間の両方を入力してください。');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間は必須です。',
            'clock_in.date_format' => '出勤時間の形式が正しくありません (HH:MM)。',
            'clock_out.required' => '退勤時間は必須です。',
            'clock_out.date_format' => '退勤時間の形式が正しくありません (HH:MM)。',
            'breaks.*.start_time.date_format' => '休憩開始時間の形式が正しくありません (HH:MM)。',
            'breaks.*.start_time.required_with' => '休憩終了時間を入力する場合、休憩開始時間も入力してください。',
            'breaks.*.end_time.date_format' => '休憩終了時間の形式が正しくありません (HH:MM)。',
            'breaks.*.end_time.required_with' => '休憩開始時間を入力する場合、休憩終了時間も入力してください。',
            'reason.max' => '備考は1000文字以内で入力してください。',
        ];
    }
}