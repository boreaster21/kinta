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
        $totalBreakMinutes = $this->breaks()->whereNotNull('start_time')->whereNotNull('end_time')->sum(function ($break) {
            return max(0, Carbon::parse($break->end_time)->diffInMinutes(Carbon::parse($break->start_time)));
        });

        $formattedBreakTime = sprintf('%02d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);

        $this->update(['total_break_time' => $formattedBreakTime]);

        return $totalBreakMinutes;
    }

    public function calculateTotalWorkTime()
    {
        if (!empty($this->clock_in) && !empty($this->clock_out)) {
            $clockIn = Carbon::parse($this->clock_in);
            $clockOut = Carbon::parse($this->clock_out);

            $totalBreakMinutes = $this->calculateTotalBreakTime();

            $workDuration = max(0, $clockOut->diffInMinutes($clockIn) - $totalBreakMinutes);

            $formattedWorkTime = sprintf('%02d:%02d', intdiv($workDuration, 60), $workDuration % 60);

            $this->update(['total_work_time' => $formattedWorkTime]);
        }
    }

    public function correctionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'attendance_id');
    }
}
