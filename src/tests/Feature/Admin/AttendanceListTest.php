<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user1 = User::factory()->create([
            'name' => 'Test User One',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user2 = User::factory()->create([
            'name' => 'Test User Two',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
    }
    private function formatTime(?Carbon $time): string
    {
        return $time ? $time->format('H:i') : '-';
    }

    private function formatTotalTime(?string $timeString): string
    {
        if (empty($timeString) || $timeString === '00:00' || $timeString === '0:00') {
            return '0:00';
        }
        if (str_starts_with($timeString, '0') && strlen($timeString) > 4) {
            return substr($timeString, 1);
        }
        return $timeString;
    }

    #[Test]
    public function displays_all_users_attendance_for_today(): void
    {
        $today = Carbon::today();
        Carbon::setTestNow($today->copy()->setHour(10));

        $att1 = Attendance::factory()->for($this->user1)->create([
            'date' => $today,
            'clock_in' => $today->copy()->setHour(9)->setMinute(0),
            'clock_out' => null,
            'total_break_time' => '00:00',
            'total_work_time' => '00:00',
        ]);

        $att2 = Attendance::factory()->for($this->user2)->create([
            'date' => $today,
            'clock_in' => $today->copy()->setHour(8)->setMinute(30),
            'clock_out' => $today->copy()->setHour(17)->setMinute(30),
        ]);
        BreakTime::factory()->for($att2)->create([
            'start_time' => $today->copy()->setHour(12)->setMinute(0),
            'end_time' => $today->copy()->setHour(13)->setMinute(0),
            'duration' => 60
        ]);
        $att2->calculateTotalBreakTime();
        $att2->calculateTotalWorkTime();
        $att2->save();

        $expectedBreakTimeUser2 = $this->formatTotalTime('01:00');
        $expectedWorkTimeUser2 = $this->formatTotalTime('08:00');

        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.attendance.list'));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.list');
        $response->assertViewHas('date', function ($viewDate) use ($today) {
            return $viewDate instanceof \Carbon\Carbon && $viewDate->isSameDay($today);
        });

        $response->assertViewHas('attendances', function ($viewAttendances) use ($att1, $att2) {
            return $viewAttendances instanceof \Illuminate\Support\Collection &&
                $viewAttendances->contains('id', $att1->id) &&
                $viewAttendances->contains('id', $att2->id);
        });

        $response->assertSee($this->user1->name);
        $response->assertSee($this->formatTime($att1->clock_in));
        $response->assertSee($this->user2->name);
        $response->assertSee($this->formatTime($att2->clock_in));
        $response->assertSee($this->formatTime($att2->clock_out));
        $response->assertSee($expectedBreakTimeUser2);
        $response->assertSee($expectedWorkTimeUser2);

        Carbon::setTestNow();
    }

    #[Test]
    public function displays_current_date_on_initial_load(): void
    {
        $today = Carbon::today();
        Carbon::setTestNow($today->copy()->setHour(11));

        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.attendance.list'));

        $response->assertOk();
        $response->assertViewHas('date', function ($viewDate) use ($today) {
            return $viewDate instanceof \Carbon\Carbon && $viewDate->isSameDay($today);
        });
        $response->assertSee($today->format('Y/m/d'));

        Carbon::setTestNow();
    }

    #[Test]
    public function displays_previous_day_data_when_navigated(): void
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        Carbon::setTestNow($today->copy()->setHour(14));

        $attToday = Attendance::factory()->for($this->user1)->create([
            'date' => $today,
            'clock_in' => $today->copy()->setHour(9),
        ]);
        $attYesterday = Attendance::factory()->for($this->user2)->create([
            'date' => $yesterday,
            'clock_in' => $yesterday->copy()->setHour(8),
            'clock_out' => $yesterday->copy()->setHour(17),
            'total_break_time' => '00:00',
            'total_work_time' => '09:00',
        ]);
        $expectedWorkTimeYesterday = $this->formatTotalTime('09:00');

        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.attendance.list'));
        $response->assertOk();

        $previousDayUrl = route('admin.attendance.list', ['date' => $yesterday->format('Y-m-d')]);
        $response->assertSee($previousDayUrl);

        $response = $this->get($previousDayUrl);

        $response->assertOk();
        $response->assertViewHas('date', function ($viewDate) use ($yesterday) {
            return $viewDate instanceof \Carbon\Carbon && $viewDate->isSameDay($yesterday);
        });
        $response->assertSee($yesterday->format('Y/m/d'));

        $response->assertViewHas('attendances', function ($viewAttendances) use ($attYesterday) {
            return $viewAttendances instanceof \Illuminate\Support\Collection &&
                    !$viewAttendances->isEmpty() &&
                    $viewAttendances->contains(function ($attendance) use ($attYesterday) {
                        return $attendance->id === $attYesterday->id && $attendance->user_id === $this->user2->id;
                    });
        });

        $response->assertDontSee($this->user1->name);
        $response->assertSee($this->user2->name, false);
        $response->assertSee($this->formatTime($attYesterday->clock_in));
        $response->assertSee($this->formatTime($attYesterday->clock_out));
        $response->assertSee($expectedWorkTimeYesterday);

        Carbon::setTestNow();
    }

    #[Test]
    public function displays_next_day_data_when_navigated(): void
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        Carbon::setTestNow($today->copy()->setHour(16));

        $attToday = Attendance::factory()->for($this->user1)->create([
            'date' => $today,
            'clock_in' => $today->copy()->setHour(9),
        ]);
        $attTomorrow = Attendance::factory()->for($this->user2)->create([
            'date' => $tomorrow,
            'clock_in' => $tomorrow->copy()->setHour(10),
            'clock_out' => null,
            'total_break_time' => '00:00',
            'total_work_time' => '00:00',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.attendance.list'));
        $response->assertOk();

        $nextDayUrl = route('admin.attendance.list', ['date' => $tomorrow->format('Y-m-d')]);
        $response->assertSee($nextDayUrl);

        $response = $this->get($nextDayUrl);

        $response->assertOk();
        $response->assertViewHas('date', function ($viewDate) use ($tomorrow) {
            return $viewDate instanceof \Carbon\Carbon && $viewDate->isSameDay($tomorrow);
        });
        $response->assertSee($tomorrow->format('Y/m/d'));

        $response->assertViewHas('attendances', function ($viewAttendances) use ($attTomorrow) {
            return $viewAttendances instanceof \Illuminate\Support\Collection &&
                    !$viewAttendances->isEmpty() &&
                    $viewAttendances->contains(function ($attendance) use ($attTomorrow) {
                        return $attendance->id === $attTomorrow->id && $attendance->user_id === $this->user2->id;
                    });
        });

        $response->assertDontSee($this->user1->name);
        $response->assertSee($this->user2->name, false);
        $response->assertSee($this->formatTime($attTomorrow->clock_in));

        Carbon::setTestNow();
    }
}
