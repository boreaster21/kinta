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

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::today()->setHour(9));
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow(),
            'clock_out' => null,
        ]);
        Carbon::setTestNow();
    }

    private function setTestTime(int $hour, int $minute = 0, int $second = 0): Carbon
    {
        $now = Carbon::today()->setHour($hour)->setMinute($minute)->setSecond($second);
        Carbon::setTestNow($now);
        return $now;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function user_can_see_break_start_button_when_clocked_in(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.break_start'));
        $response->assertDontSee(route('attendance.break_end'));
    }

    #[Test]
    public function user_can_start_break_and_status_updates(): void
    {
        $breakStartTime = $this->setTestTime(10, 30);

        $this->actingAs($this->user);
        $response = $this->post(route('attendance.break_start'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => $breakStartTime->toDateTimeString(),
            'end_time' => null,
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '休憩中');
        $response->assertDontSee(route('attendance.break_start'));
        $response->assertSee(route('attendance.break_end'));
    }

    #[Test]
    public function user_can_start_break_again_after_ending_one(): void
    {
        $break1StartTime = $this->setTestTime(11, 0);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $break1StartTime,
            'end_time' => null,
        ]);
        $break1EndTime = $this->setTestTime(11, 15);
        $this->actingAs($this->user)->post(route('attendance.break_end'));

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.break_start'));

        $break2StartTime = $this->setTestTime(14, 0);
        $response = $this->post(route('attendance.break_start'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => $break2StartTime->toDateTimeString(),
            'end_time' => null,
        ]);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => $break1StartTime->toDateTimeString(),
            'end_time' => $break1EndTime->toDateTimeString(),
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '休憩中');
        $response->assertDontSee(route('attendance.break_start'));
        $response->assertSee(route('attendance.break_end'));
    }

    #[Test]
    public function user_can_see_break_end_button_when_on_break(): void
    {
        $this->setTestTime(12, 0);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => Carbon::getTestNow(),
            'end_time' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));

        $response->assertOk();
        $response->assertViewHas('status', '休憩中');
        $response->assertDontSee(route('attendance.break_start'));
        $response->assertSee(route('attendance.break_end'));
    }

    #[Test]
    public function user_can_end_break_and_status_updates(): void
    {
        $breakStartTime = $this->setTestTime(15, 0);
        $breakRecord = BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $breakStartTime,
            'end_time' => null,
        ]);

        $breakEndTime = $this->setTestTime(15, 30);

        $this->actingAs($this->user);
        $response = $this->post(route('attendance.break_end'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'id' => $breakRecord->id,
            'attendance_id' => $this->attendance->id,
            'start_time' => $breakStartTime->toDateTimeString(),
            'end_time' => $breakEndTime->toDateTimeString(),
        ]);
        $this->attendance->refresh();

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.break_start'));
        $response->assertDontSee(route('attendance.break_end'));
    }

    #[Test]
    public function user_can_end_second_break(): void
    {
        $break1StartTime = $this->setTestTime(10, 0);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $break1StartTime,
            'end_time' => $this->setTestTime(10, 10),
        ]);
        $break2StartTime = $this->setTestTime(13, 0);
        $secondBreakRecord = BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $break2StartTime,
            'end_time' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '休憩中');
        $response->assertSee(route('attendance.break_end'));

        $break2EndTime = $this->setTestTime(13, 45);
        $response = $this->post(route('attendance.break_end'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'id' => $secondBreakRecord->id,
            'attendance_id' => $this->attendance->id,
            'start_time' => $break2StartTime->toDateTimeString(),
            'end_time' => $break2EndTime->toDateTimeString(),
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.break_start'));
        $response->assertDontSee(route('attendance.break_end'));
    }

    #[Test]
    public function break_start_and_end_times_are_recorded_correctly(): void
    {
        $breakStartTime = $this->setTestTime(16, 0);

        $this->actingAs($this->user);
        $this->post(route('attendance.break_start'));

        $breakEndTime = $this->setTestTime(16, 25);
        $this->post(route('attendance.break_end'));

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => $breakStartTime->toDateTimeString(),
            'end_time' => $breakEndTime->toDateTimeString(),
        ]);
    }
}