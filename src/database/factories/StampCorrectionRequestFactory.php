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
        $clockIn = Carbon::parse($attendance->date . ' 09:00');
        $clockOut = Carbon::parse($attendance->date . ' 18:00');
        $breakStart = Carbon::parse($attendance->date . ' 12:00');
        $breakEnd = Carbon::parse($attendance->date . ' 13:00');

        return [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'clock_in' => $clockIn->addMinutes(rand(-30, 30))->format('H:i'),
            'clock_out' => $clockOut->addMinutes(rand(-30, 30))->format('H:i'),
            'break_start' => $breakStart->addMinutes(rand(-10, 10))->format('H:i'),
            'break_end' => $breakEnd->addMinutes(rand(-10, 10))->format('H:i'),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
            'updated_at' => Carbon::now(),
        ];
    }
}
