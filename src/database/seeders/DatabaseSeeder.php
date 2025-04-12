<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $users = User::all();
        $startDate = Carbon::now()->subMonths(3)->startOfDay();
        $endDate = Carbon::now()->startOfDay();

        Log::info('Starting dummy data generation for ' . $users->count() . ' users.');

        foreach ($users as $user) {
            Log::info('Generating data for user: ' . $user->email);
            $currentDate = clone $startDate;
            while ($currentDate->lte($endDate)) {
                if (!$this->isWeekend($currentDate)) {
                    $clockInTime = $currentDate->copy()->setHour(9)->addMinutes(rand(-30, 30));
                    $clockOutTime = $currentDate->copy()->setHour(18)->addMinutes(rand(-30, 30));

                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $currentDate->toDateString(),
                        'clock_in' => $clockInTime,
                        'clock_out' => $clockOutTime,
                        'total_break_time' => '00:00',
                        'total_work_time' => '00:00',
                    ]);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $currentDate->copy()->setHour(12)->setMinute(0),
                        'end_time' => $currentDate->copy()->setHour(13)->setMinute(0),
                        'duration' => 60,
                    ]);

                    $attendance = $attendance->fresh();
                    $attendance->calculateTotalBreakTime();
                    $attendance->calculateTotalWorkTime();
                    $attendance->save();

                    /*
                    if (rand(1, 10) <= 2) {
                        StampCorrectionRequest::factory()->create([
                            'user_id' => $user->id,
                            'attendance_id' => $attendance->id,
                        ]);
                    }
                    */
                }
                $currentDate->addDay();
            }
        }

        // --- Add Today's Data Explicitly --- 
        $today = Carbon::today();
        foreach ($users as $user) {
            // Skip if today's data already exists for the user
            if (!Attendance::where('user_id', $user->id)->whereDate('date', $today)->exists()) {
                 Log::info('Generating explicit data for today for user: ' . $user->email);
                 $clockInToday = $today->copy()->setHour(9)->addMinutes(rand(-15, 15));
                 $clockOutToday = $today->copy()->setHour(17)->addMinutes(rand(-15, 15));
                 
                 $attendanceToday = Attendance::create([
                     'user_id' => $user->id,
                     'date' => $today->toDateString(),
                     'clock_in' => $clockInToday,
                     'clock_out' => $clockOutToday,
                     'total_break_time' => '00:00', // Initialize
                     'total_work_time' => '00:00',  // Initialize
                 ]);

                 // Add a break for today's attendance
                 BreakTime::create([
                     'attendance_id' => $attendanceToday->id,
                     'start_time' => $today->copy()->setHour(12)->setMinute(0),
                     'end_time' => $today->copy()->setHour(13)->setMinute(0),
                     'duration' => 60,
                 ]);

                 // Recalculate times
                 $attendanceToday = $attendanceToday->fresh(); // Refresh to get breaks
                 $attendanceToday->calculateTotalBreakTime();
                 $attendanceToday->calculateTotalWorkTime();
                 $attendanceToday->save();
            }
        }
        // --- End of Added Section ---

        Log::info('Dummy data generation finished.');
    }

    private function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }
}
