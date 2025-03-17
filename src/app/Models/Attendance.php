<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\StampCorrectionRequest;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_break_time',
        'total_work_time',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function calculateTotalBreakTime()
    {
        $totalBreakMinutes = $this->breaks()
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get()
            ->sum(function ($break) {
                $startTime = Carbon::parse($break->start_time);
                $endTime = Carbon::parse($break->end_time);
                return max(0, $endTime->diffInMinutes($startTime));
            });

        $formattedBreakTime = sprintf('%02d:%02d', 
            intdiv($totalBreakMinutes, 60), 
            $totalBreakMinutes % 60
        );

        $this->total_break_time = $formattedBreakTime;
        $this->save();

        return $totalBreakMinutes;
    }

    public function calculateTotalWorkTime()
    {
        if (!empty($this->clock_in) && !empty($this->clock_out)) {
            $clockIn = Carbon::parse($this->clock_in);
            $clockOut = Carbon::parse($this->clock_out);

            $totalWorkMinutes = $clockOut->diffInMinutes($clockIn);

            // 休憩時間の計算
            $totalBreakMinutes = $this->breaks()
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get()
                ->sum(function ($break) {
                    $startTime = Carbon::parse($break->start_time);
                    $endTime = Carbon::parse($break->end_time);
                    return max(0, $endTime->diffInMinutes($startTime));
                });

            $actualWorkMinutes = max(0, $totalWorkMinutes - $totalBreakMinutes);

            $formattedWorkTime = sprintf('%02d:%02d', 
                intdiv($actualWorkMinutes, 60), 
                $actualWorkMinutes % 60
            );

            $this->total_work_time = $formattedWorkTime;
            $this->save();
        }
    }

    public function correctionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'attendance_id');
    }
}
