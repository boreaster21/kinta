<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = Carbon::today()->subDays(rand(0, 90));
        $date = $date->startOfDay();
        $clockIn = $date->copy()->setHour(9)->setMinute(rand(0, 59));
        $clockOut = $date->copy()->setHour(18)->setMinute(rand(0, 59));
        $breakMinutes = 60;
        $totalMinutes = $clockIn->diffInMinutes($clockOut);
        $workMinutes = max(0, $totalMinutes - $breakMinutes);

        $userRole = Role::where('name', 'user')->first();

        $user = User::where('role_id', $userRole?->id)->inRandomOrder()->first();
        if (!$user && $userRole) {
            $user = User::factory()->create(['role_id' => $userRole->id]);
        } elseif (!$userRole) {
            throw new \Exception("Role 'user' not found. Cannot create attendance.");
        }

        return [
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'total_break_time' => sprintf('%02d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60),
            'total_work_time' => sprintf('%02d:%02d', intdiv($workMinutes, 60), $workMinutes % 60),
        ];
    }
}