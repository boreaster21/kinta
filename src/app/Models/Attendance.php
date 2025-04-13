<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Log;

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

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
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
        $totalBreakMinutes = 0;
        $breaks = $this->breaks()->get();

        foreach ($breaks as $break) {
            if ($break->start_time && $break->end_time) {
                $startTime = Carbon::parse($break->start_time);
                $endTime = Carbon::parse($break->end_time);
                $minutes = $startTime->diffInMinutes($endTime);

                $totalBreakMinutes += $minutes;
            }
        }

        $this->total_break_time = sprintf('%02d:%02d', 
            intdiv($totalBreakMinutes, 60), 
            $totalBreakMinutes % 60
        );

        return $totalBreakMinutes;
    }

    public function calculateTotalWorkTime()
    {
        if (!$this->clock_in || !$this->clock_out) {
            $this->total_work_time = '00:00';
            return;
        }

        $clockInTime = Carbon::parse($this->clock_in);
        $clockOutTime = Carbon::parse($this->clock_out);

        $totalMinutes = $clockInTime->diffInMinutes($clockOutTime);
        $breakMinutes = $this->calculateTotalBreakTime();

        $workMinutes = max(0, $totalMinutes - $breakMinutes);

        $this->total_work_time = sprintf('%02d:%02d', 
            intdiv($workMinutes, 60), 
            $workMinutes % 60
        );

        return $workMinutes;
    }

    public function correctionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'attendance_id');
    }
}