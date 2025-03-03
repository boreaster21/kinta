<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $users = User::all();
        $startDate = Carbon::now()->subMonths(3);
        $endDate = Carbon::now();

        foreach ($users as $user) {
            $date = clone $startDate;
            while ($date <= $endDate) {
                if (!$this->isWeekend($date)) {
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                    ]);

                    StampCorrectionRequest::factory()->create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id,
                    ]);
                }
                $date->addDay();
            }
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    private function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }
}
