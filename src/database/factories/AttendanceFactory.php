<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = Carbon::today()->subDays(rand(0, 90));
        $clockIn = Carbon::parse($date->format('Y-m-d') . ' 09:00:00'); 
        $clockOut = Carbon::parse($date->format('Y-m-d') . ' 18:00:00');
        $totalMinutes = $clockOut->diffInMinutes($clockIn) - 60; 

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'date' => $date,
            'clock_in' => $clockIn->format('Y-m-d H:i:s'), 
            'clock_out' => $clockOut->format('Y-m-d H:i:s'), 
            'total_break_time' => '01:00', 
            'total_work_time' => sprintf('%02d:%02d', intdiv($totalMinutes, 60), abs($totalMinutes % 60)),
        ];
    }
}
