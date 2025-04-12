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
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'nullable|array',
            'break_start.*' => 'nullable|date_format:H:i|required_with:break_end.*',
            'break_end' => 'nullable|array',
            'break_end.*' => 'nullable|date_format:H:i|required_with:break_start.*',
            'reason' => 'required|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $dateInput = $this->input('date');
            $clockInInput = $this->input('clock_in');
            $clockOutInput = $this->input('clock_out');

            if (!$dateInput || !$clockInInput || !$clockOutInput || $validator->errors()->hasAny(['date', 'clock_in', 'clock_out'])) {
                return;
            }

            try {
                $date = Carbon::parse($dateInput);
                $clockIn = $date->copy()->setTimeFromTimeString($clockInInput);
                $clockOut = $date->copy()->setTimeFromTimeString($clockOutInput);

                if ($clockIn->gt($clockOut)) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です。');
                }

                if ($this->has('break_start') && $this->has('break_end')) {
                    foreach ($this->break_start as $index => $start) {
                        if (!empty($start) && isset($this->break_end[$index]) && !empty($this->break_end[$index])) {
                            $breakStartInput = $this->break_start[$index];
                            $breakEndInput = $this->break_end[$index];

                            if (!$validator->errors()->has("break_start.{$index}") && !$validator->errors()->has("break_end.{$index}")) {
                                $breakStart = $date->copy()->setTimeFromTimeString($breakStartInput);
                                $breakEnd = $date->copy()->setTimeFromTimeString($breakEndInput);

                                if ($breakStart->lt($clockIn) || $breakEnd->gt($clockOut)) {
                                    $validator->errors()->add(
                                        "break_start.{$index}",
                                        '休憩時間が勤務時間外です。'
                                    );
                                }

                                if ($breakStart->gt($breakEnd)) {
                                    $validator->errors()->add(
                                        "break_start.{$index}",
                                        '休憩開始時間と終了時間が不適切な値です。'
                                    );
                                }
                            }
                        } elseif (!empty($start) || (isset($this->break_end[$index]) && !empty($this->break_end[$index]))) {
                            $validator->errors()->add("break_start.{$index}", '休憩開始時間と終了時間の両方を入力してください。');
                        }
                    }
                }
            } catch (\Exception $e) {
                $validator->errors()->add('date', '日付または時刻の形式が無効です。');
            }
        });
    }

    public function messages()
    {
        return [
            'date.required' => '日付は必須です。',
            'date.date' => '日付の形式が正しくありません。',
            'clock_in.required' => '出勤時間は必須です。',
            'clock_in.date_format' => '出勤時間の形式が正しくありません (HH:MM)。',
            'clock_out.required' => '退勤時間は必須です。',
            'clock_out.date_format' => '退勤時間の形式が正しくありません (HH:MM)。',
            'break_start.*.date_format' => '休憩開始時間の形式が正しくありません (HH:MM)。',
            'break_start.*.required_with' => '休憩終了時間を入力する場合、休憩開始時間も入力してください。',
            'break_end.*.date_format' => '休憩終了時間の形式が正しくありません (HH:MM)。',
            'break_end.*.required_with' => '休憩開始時間を入力する場合、休憩終了時間も入力してください。',
            'reason.required' => '備考を記入してください。',
            'reason.max' => '備考は1000文字以内で入力してください。',
        ];
    }
}