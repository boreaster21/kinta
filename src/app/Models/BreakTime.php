<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'start_time', 'end_time', 'duration'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function endBreak()
    {
        $this->end_time = Carbon::now();
        $this->duration = Carbon::parse($this->start_time)
            ->diffInMinutes(Carbon::parse($this->end_time));
        $this->save();

        $this->attendance->calculateTotalBreakTime();
        $this->attendance->calculateTotalWorkTime();
    }
}
