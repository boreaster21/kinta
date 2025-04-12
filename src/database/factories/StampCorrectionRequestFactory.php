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
        $date = Carbon::parse($attendance->date);

        $original_break_start = $attendance->breaks()->orderBy('start_time')->pluck('start_time')->map(fn($t) => Carbon::parse($t)->format('H:i'))->toArray();
        $original_break_end = $attendance->breaks()->orderBy('start_time')->pluck('end_time')->map(fn($t) => Carbon::parse($t)->format('H:i'))->toArray();

        return [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'date' => $date,
            'clock_in' => $date->copy()->setHour(9)->addMinutes(rand(-30, 30)),
            'clock_out' => $date->copy()->setHour(18)->addMinutes(rand(-30, 30)),
            'break_start' => [$date->copy()->setHour(12)->addMinutes(rand(-10, 10))->format('H:i')],
            'break_end' => [$date->copy()->setHour(13)->addMinutes(rand(-10, 10))->format('H:i')],
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
            'updated_at' => Carbon::now(),
            'original_date' => $attendance->date,
            'original_clock_in' => $attendance->clock_in,
            'original_clock_out' => $attendance->clock_out,
            'original_break_start' => $original_break_start,
            'original_break_end' => $original_break_end,
            'original_reason' => $attendance->reason,
        ];
    }
}