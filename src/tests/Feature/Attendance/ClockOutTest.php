<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
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
    public function user_can_clock_out_successfully_and_status_updates(): void
    {
        $clockInTime = $this->setTestTime(9, 0);
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
            'clock_out' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.clock_out'));

        $clockOutTime = $this->setTestTime(17, 30);
        $response = $this->post(route('attendance.clock_out'));

        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('success', '退勤を記録しました。');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $this->user->id,
            'clock_out' => $clockOutTime->toDateTimeString(),
        ]);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '退勤済');
        $response->assertDontSee(route('attendance.clock_out'));
        $response->assertDontSee(route('attendance.break_start'));
        $response->assertDontSee(route('attendance.break_end'));
    }

    #[Test]
    public function clock_out_time_is_recorded_correctly_in_database(): void
    {
        $this->actingAs($this->user);

        $clockInTime = $this->setTestTime(10, 0);
        $this->post(route('attendance.clock_in'));
        $attendance = Attendance::where('user_id', $this->user->id)->whereDate('date', $clockInTime->toDateString())->first();
        $this->assertNotNull($attendance, 'Attendance record should exist after clock-in.');
        $this->assertNotNull($attendance->clock_in, 'Clock-in time should be recorded.');
        $this->assertNull($attendance->clock_out, 'Clock-out time should be null initially.');

        $expectedClockOutTime = $this->setTestTime(18, 15);
        $this->post(route('attendance.clock_out'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $this->user->id,
            'date' => $expectedClockOutTime->toDateString(),
            'clock_out' => $expectedClockOutTime->toDateTimeString(),
        ]);
    }

    #[Test]
    public function user_cannot_clock_out_if_not_clocked_in(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '勤務外');
        $response->assertDontSee(route('attendance.clock_out'));

        $response = $this->post(route('attendance.clock_out'));
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    #[Test]
    public function user_cannot_clock_out_twice(): void
    {
        $clockInTime = $this->setTestTime(9, 0);
        $clockOutTime = $this->setTestTime(17, 0);
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '退勤済');
        $response->assertDontSee(route('attendance.clock_out'));

        $secondAttemptTime = $this->setTestTime(17, 5);
        $response = $this->post(route('attendance.clock_out'));

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_out' => $clockOutTime->toDateTimeString(),
        ]);
    }
}