<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class StampCorrectionRequestFactory extends Factory
{
    protected $model = StampCorrectionRequest::class;

    public function definition(): array
    {
        $attendance = Attendance::inRandomOrder()->first() ?? Attendance::factory()->create();
        $date = $attendance->date->format('Y-m-d');

        return [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'clock_in' => Carbon::parse($date . ' 09:00')->addMinutes(rand(-30, 30))->format('H:i'),
            'clock_out' => Carbon::parse($date . ' 18:00')->addMinutes(rand(-30, 30))->format('H:i'),
            'break_start' => Carbon::parse($date . ' 12:00')->addMinutes(rand(-10, 10))->format('H:i'),
            'break_end' => Carbon::parse($date . ' 13:00')->addMinutes(rand(-10, 10))->format('H:i'),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
            'updated_at' => Carbon::now(),
        ];
    }
}
