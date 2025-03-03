<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_id', 'start_time', 'end_time', 'duration'];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function endBreak()
    {
        $this->end_time = Carbon::now();
        $this->duration = Carbon::parse($this->end_time)->diffInMinutes(Carbon::parse($this->start_time));
        $this->save();

        $totalBreak = $this->attendance->breaks()->sum('duration');
        $hours = floor($totalBreak / 60);
        $minutes = $totalBreak % 60;
        $this->attendance->update([
            'total_break_time' => sprintf('%02d:%02d:00', $hours, $minutes)
        ]);

        $this->attendance->calculateTotalWorkTime();
    }

}
