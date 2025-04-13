<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
        $this->otherUser = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
    }

    private function formatTimeForView(?Carbon $time): string
    {
        return $time ? $time->format('H:i') : '-';
    }

    private function formatDateForView(Carbon $date): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeekJp = $days[$date->dayOfWeek];
        return $date->format('m/d') . ' (' . $dayOfWeekJp . ')';
    }

    #[Test]
    public function user_can_only_see_their_own_attendance_data(): void
    {
        $currentMonth = Carbon::today();
        $previousMonth = Carbon::today()->subMonth();

        $userAttendanceCurrent = Attendance::factory()->for($this->user)->create([
            'date' => $currentMonth->copy()->startOfMonth()->addDays(5),
            'clock_in' => $currentMonth->copy()->startOfMonth()->addDays(5)->setHour(9),
            'clock_out' => $currentMonth->copy()->startOfMonth()->addDays(5)->setHour(17),
        ]);
        $userAttendancePrevious = Attendance::factory()->for($this->user)->create([
            'date' => $previousMonth->copy()->startOfMonth()->addDays(10),
            'clock_in' => $previousMonth->copy()->startOfMonth()->addDays(10)->setHour(9),
            'clock_out' => $previousMonth->copy()->startOfMonth()->addDays(10)->setHour(17),
        ]);

        $otherUserClockInTime = $currentMonth->copy()->startOfMonth()->addDays(7)->setHour(10)->setMinute(3);
        $otherUserAttendance = Attendance::factory()->for($this->otherUser)->create([
            'date' => $currentMonth->copy()->startOfMonth()->addDays(7),
            'clock_in' => $otherUserClockInTime,
            'clock_out' => $currentMonth->copy()->startOfMonth()->addDays(7)->setHour(18),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list'));

        $response->assertOk();
        $response->assertSee($this->formatDateForView(Carbon::parse($userAttendanceCurrent->date)));
        $response->assertSee($this->formatTimeForView($userAttendanceCurrent->clock_in));
        $response->assertSee($this->formatTimeForView($userAttendanceCurrent->clock_out));

        $response->assertDontSee($this->formatDateForView(Carbon::parse($otherUserAttendance->date)));
        $response->assertDontSeeText($this->formatTimeForView($otherUserClockInTime));

        $response->assertDontSee($this->formatDateForView(Carbon::parse($userAttendancePrevious->date)));
    }

    #[Test]
    public function attendance_list_defaults_to_current_month(): void
    {
        $now = Carbon::now();
        $currentMonthString = $now->format('Y-m');
        $currentMonthDisplay = $now->format('Y/m');

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list'));

        $response->assertOk();
        $response->assertViewHas('month', $currentMonthString);
        $response->assertSee($currentMonthDisplay);
    }

    #[Test]
    public function user_can_navigate_to_previous_month(): void
    {
        $currentMonth = Carbon::today();
        $previousMonth = $currentMonth->copy()->subMonth();
        $previousMonthString = $previousMonth->format('Y-m');
        $previousMonthDisplay = $previousMonth->format('Y/m');

        $prevMonthAttendance = Attendance::factory()->for($this->user)->create([
            'date' => $previousMonth->copy()->startOfMonth()->addDay(),
            'clock_in' => $previousMonth->copy()->startOfMonth()->addDay()->setHour(9),
            'clock_out' => $previousMonth->copy()->startOfMonth()->addDay()->setHour(17),
        ]);
        $currMonthAttendance = Attendance::factory()->for($this->user)->create([
            'date' => $currentMonth->copy()->startOfMonth()->addDay(),
            'clock_in' => $currentMonth->copy()->startOfMonth()->addDay()->setHour(9),
            'clock_out' => $currentMonth->copy()->startOfMonth()->addDay()->setHour(17),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => $previousMonthString]));

        $response->assertOk();
        $response->assertViewHas('month', $previousMonthString);
        $response->assertSee($previousMonthDisplay);
        $response->assertSee($this->formatDateForView(Carbon::parse($prevMonthAttendance->date)));
        $response->assertSee($this->formatTimeForView($prevMonthAttendance->clock_in));
        $response->assertDontSee($this->formatDateForView(Carbon::parse($currMonthAttendance->date)));
    }

    #[Test]
    public function user_can_navigate_to_next_month(): void
    {
        $currentMonth = Carbon::today();
        $nextMonth = $currentMonth->copy()->addMonth();
        $nextMonthString = $nextMonth->format('Y-m');
        $nextMonthDisplay = $nextMonth->format('Y/m');

        $currMonthAttendance = Attendance::factory()->for($this->user)->create([
            'date' => $currentMonth->copy()->startOfMonth()->addDay(),
            'clock_in' => $currentMonth->copy()->startOfMonth()->addDay()->setHour(9),
            'clock_out' => $currentMonth->copy()->startOfMonth()->addDay()->setHour(17),
        ]);
        $nextMonthAttendance = Attendance::factory()->for($this->user)->create([
            'date' => $nextMonth->copy()->startOfMonth()->addDay(),
            'clock_in' => $nextMonth->copy()->startOfMonth()->addDay()->setHour(9),
            'clock_out' => $nextMonth->copy()->startOfMonth()->addDay()->setHour(17),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => $nextMonthString]));

        $response->assertOk();
        $response->assertViewHas('month', $nextMonthString);
        $response->assertSee($nextMonthDisplay);
        $response->assertSee($this->formatDateForView(Carbon::parse($nextMonthAttendance->date)));
        $response->assertSee($this->formatTimeForView($nextMonthAttendance->clock_in));
        $response->assertDontSee($this->formatDateForView(Carbon::parse($currMonthAttendance->date)));
    }

    #[Test]
    public function user_can_navigate_to_daily_detail_page_from_list(): void
    {
        $attendanceDate = Carbon::today()->startOfMonth()->addDays(3);
        $attendance = Attendance::factory()->for($this->user)->create([
            'date' => $attendanceDate,
            'clock_in' => $attendanceDate->copy()->setHour(9)->setMinute(30),
            'clock_out' => $attendanceDate->copy()->setHour(17)->setMinute(45),
        ]);
        BreakTime::factory()->for($attendance)->create([
            'start_time' => $attendanceDate->copy()->setHour(12)->setMinute(0),
            'end_time' => $attendanceDate->copy()->setHour(13)->setMinute(0),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => $attendanceDate->format('Y-m')]));

        $response->assertOk();
        $detailUrl = route('attendance.show', $attendance->id);
        $response->assertSee($detailUrl);

        $response = $this->get($detailUrl);

        $response->assertOk();
        $response->assertViewHas('attendance', function ($viewAttendance) use ($attendance) {
            return $viewAttendance->id === $attendance->id;
        });
        $response->assertSee($this->formatTimeForView($attendance->clock_in));
        $response->assertSee($this->formatTimeForView($attendance->clock_out));
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}