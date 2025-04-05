<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'nullable|array',
            'break_start.*' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|array',
            'break_end.*' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = Carbon::parse($this->clock_in);
            $clockOut = Carbon::parse($this->clock_out);

            if ($clockIn->gt($clockOut)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            if ($this->has('break_start') && $this->has('break_end')) {
                foreach ($this->break_start as $index => $start) {
                    if ($start && isset($this->break_end[$index])) {
                        $breakStart = Carbon::parse($start);
                        $breakEnd = Carbon::parse($this->break_end[$index]);

                        if ($breakStart->lt($clockIn) || $breakEnd->gt($clockOut)) {
                            $validator->errors()->add(
                                "break_start.{$index}",
                                '休憩時間が勤務時間外です'
                            );
                        }

                        if ($breakStart->gt($breakEnd)) {
                            $validator->errors()->add(
                                "break_start.{$index}",
                                '休憩開始時間と終了時間が不適切な値です'
                            );
                        }
                    }
                }
            }

            if (empty($this->reason)) {
                $validator->errors()->add('reason', '備考を記入してください');
            }
        });
    }

    public function messages()
    {
        return [
            'clock_in.required' => '出勤時間は必須です',
            'clock_in.date_format' => '出勤時間の形式が正しくありません',
            'clock_out.required' => '退勤時間は必須です',
            'clock_out.date_format' => '退勤時間の形式が正しくありません',
            'break_start.*.date_format' => '休憩開始時間の形式が正しくありません',
            'break_end.*.date_format' => '休憩終了時間の形式が正しくありません',
            'reason.required' => '備考を記入してください',
            'reason.max' => '備考は1000文字以内で入力してください',
        ];
    }
} 