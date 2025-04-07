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
        Log::info('calculateTotalBreakTime started', [
            'attendance_id' => $this->id,
            'breaks_count' => $this->breaks()->count()
        ]);

        $totalBreakMinutes = 0;
        $breaks = $this->breaks()->get();

        foreach ($breaks as $break) {
            if ($break->start_time && $break->end_time) {
                $startTime = Carbon::parse($break->start_time);
                $endTime = Carbon::parse($break->end_time);
                $minutes = $startTime->diffInMinutes($endTime);

                Log::info('Break time calculation', [
                    'attendance_id' => $this->id,
                    'break_id' => $break->id,
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'minutes' => $minutes
                ]);

                $totalBreakMinutes += $minutes;
            }
        }

        $this->total_break_time = sprintf('%02d:%02d', 
            intdiv($totalBreakMinutes, 60), 
            $totalBreakMinutes % 60
        );

        Log::info('Total break time calculated', [
            'attendance_id' => $this->id,
            'total_break_minutes' => $totalBreakMinutes,
            'total_break_time' => $this->total_break_time
        ]);

        return $totalBreakMinutes;
    }

    public function calculateTotalWorkTime()
    {
        Log::info('calculateTotalWorkTime started', [
            'attendance_id' => $this->id,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'total_break_time' => $this->total_break_time
        ]);

        if (!$this->clock_in || !$this->clock_out) {
            $this->total_work_time = '00:00';
            Log::info('calculateTotalWorkTime: No clock in/out time', [
                'attendance_id' => $this->id
            ]);
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

        Log::info('Work time calculated', [
            'attendance_id' => $this->id,
            'clock_in' => $clockInTime->format('H:i'),
            'clock_out' => $clockOutTime->format('H:i'),
            'total_minutes' => $totalMinutes,
            'break_minutes' => $breakMinutes,
            'work_minutes' => $workMinutes,
            'total_work_time' => $this->total_work_time,
            'total_break_time' => $this->total_break_time
        ]);

        return $workMinutes;
    }

    public function correctionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'attendance_id');
    }
}
