<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StampCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('role', fn($q) => $q->where('name', 'user'))->inRandomOrder()->limit(5)->get();
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->first();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                                ->whereNotNull('clock_in')
                                ->orderBy('date', 'desc')
                                ->first();

            if (!$attendance) {
                continue;
            }

            StampCorrectionRequest::factory()->create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'status' => 'pending',
                'reason' => '打刻修正のお願い（承認待ちサンプル）',
                'created_at' => Carbon::now()->subDays(rand(1, 2)),
                'date' => $attendance->date,
                'clock_in' => $attendance->clock_in->copy()->addMinutes(rand(-5, 5))->format('H:i:s'),
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->copy()->addMinutes(rand(-5, 5))->format('H:i:s') : $attendance->clock_in->copy()->addHours(9)->format('H:i:s'),
            ]);

            if ($admin) {
                StampCorrectionRequest::factory()->create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'status' => 'approved',
                    'reason' => '打刻修正しました（承認済みサンプル）',
                    'created_at' => Carbon::now()->subDays(rand(5, 10)),
                    'approved_at' => Carbon::now()->subDays(rand(1, 4)),
                    'approved_by' => $admin->id,
                    'date' => $attendance->date,
                    'clock_in' => $attendance->clock_in->copy()->addMinutes(rand(-5, 5))->format('H:i:s'),
                    'clock_out' => $attendance->clock_out ? $attendance->clock_out->copy()->addMinutes(rand(-5, 5))->format('H:i:s') : $attendance->clock_in->copy()->addHours(8)->format('H:i:s'),
                ]);
            }

            StampCorrectionRequest::factory()->create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'status' => 'rejected',
                'reason' => '申請内容不備のため却下（却下サンプル）',
                'created_at' => Carbon::now()->subDays(rand(15, 25)),
                'rejected_at' => Carbon::now()->subDays(rand(3, 14)),
                'date' => $attendance->date,
                'clock_in' => $attendance->clock_in->copy()->addMinutes(rand(-5, 5))->format('H:i:s'),
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->copy()->addMinutes(rand(-5, 5))->format('H:i:s') : $attendance->clock_in->copy()->addHours(7)->format('H:i:s'),
            ]);
        }
    }
}