<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ClockInTest extends TestCase
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

    private function setTestTime(int $hour = 9, int $minute = 0): Carbon
    {
        if ($hour < 4 || $hour >= 22) {
            throw new \InvalidArgumentException("Test time must be between 4:00 and 21:59.");
        }
        $now = Carbon::today()->setHour($hour)->setMinute($minute)->setSecond(0);
        Carbon::setTestNow($now);
        return $now;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function user_can_clock_in_successfully_and_status_updates(): void
    {
        $clockInTime = $this->setTestTime(9, 15);
        $this->actingAs($this->user);
        $response = $this->post(route('attendance.clock_in'));
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('success', '出勤を記録しました。');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => $clockInTime->toDateString(),
            'clock_in' => $clockInTime->toDateTimeString(),
            'clock_out' => null,
        ]);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertSeeText('出勤中');
        $response->assertDontSee(route('attendance.clock_in'));
        $response->assertSeeText('退勤');
        $response->assertSeeText('休憩入');
    }

    #[Test]
    public function user_cannot_clock_in_if_already_clocked_in(): void
    {
        $this->setTestTime(10, 0);
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow(),
            'clock_out' => null,
        ]);
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertDontSee(route('attendance.clock_in'));
        $response->assertSeeText('出勤中');
        $response = $this->post(route('attendance.clock_in'));
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('error', '本日の出勤打刻は既に行われています。');
    }

    #[Test]
    public function user_can_see_clock_in_button_when_not_working(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertSeeText('出勤');
        $response->assertSee(route('attendance.clock_in'));
        $response->assertDontSeeText('退勤');
        $response->assertDontSeeText('休憩入');
        $response->assertDontSeeText('休憩戻');
    }

    #[Test]
    public function user_cannot_clock_in_if_already_clocked_out_today(): void
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
        $response->assertDontSee(route('attendance.clock_in'));
        $response->assertSeeText('退勤済');
        $response = $this->post(route('attendance.clock_in'));
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('error', '本日の出勤打刻は既に行われています。');
    }

    #[Test]
    public function clock_in_time_is_recorded_correctly_in_database(): void
    {
        $expectedTime = $this->setTestTime(11, 45);
        $this->actingAs($this->user);
        $this->post(route('attendance.clock_in'));
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => $expectedTime->toDateString(),
            'clock_in' => $expectedTime->toDateTimeString(),
        ]);
    }

    #[Test]
    public function user_cannot_clock_in_outside_allowed_hours(): void
    {
        $earlyTime = Carbon::today()->setHour(3)->setMinute(59)->setSecond(59);
        Carbon::setTestNow($earlyTime);
        $this->actingAs($this->user);
        $responseEarly = $this->post(route('attendance.clock_in'));
        $responseEarly->assertRedirect(route('attendance.index'));
        $responseEarly->assertSessionHas('error', '打刻可能時間外です（4:00-22:00）');
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->user->id,
            'date' => $earlyTime->toDateString(),
        ]);
        Carbon::setTestNow();

        $lateTime = Carbon::today()->setHour(22)->setMinute(0)->setSecond(1);
        Carbon::setTestNow($lateTime);
        $this->actingAs($this->user);
        $responseLate = $this->post(route('attendance.clock_in'));
        $responseLate->assertRedirect(route('attendance.index'));
        $responseLate->assertSessionHas('error', '打刻可能時間外です（4:00-22:00）');
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->user->id,
            'date' => $lateTime->toDateString(),
        ]);
    }
}