<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeThisMonth();
        $endTime = Carbon::parse($startTime)->addMinutes($this->faker->numberBetween(15, 60));

        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => Carbon::parse($startTime)->diffInMinutes($endTime),
        ];
    }
}
