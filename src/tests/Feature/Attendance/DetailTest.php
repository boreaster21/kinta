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

class DetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Attendance $attendance;
    protected Attendance $attendanceNoClockOut;
    protected Attendance $attendanceNoBreaks;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create([
            'name' => 'テストユーザー',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        $attendanceDate = Carbon::today()->subDays(5);

        $this->attendance = Attendance::factory()->for($this->user)->create([
            'date' => $attendanceDate,
            'clock_in' => $attendanceDate->copy()->setHour(9)->setMinute(5),
            'clock_out' => $attendanceDate->copy()->setHour(18)->setMinute(12),
        ]);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $attendanceDate->copy()->setHour(12)->setMinute(1),
            'end_time' => $attendanceDate->copy()->setHour(13)->setMinute(2),
        ]);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $attendanceDate->copy()->setHour(15)->setMinute(0),
            'end_time' => $attendanceDate->copy()->setHour(15)->setMinute(15),
        ]);

        $this->attendanceNoClockOut = Attendance::factory()->for($this->user)->create([
            'date' => $attendanceDate->copy()->addDay(),
            'clock_in' => $attendanceDate->copy()->addDay()->setHour(10)->setMinute(0),
            'clock_out' => null,
        ]);

        $this->attendanceNoBreaks = Attendance::factory()->for($this->user)->create([
            'date' => $attendanceDate->copy()->addDays(2),
            'clock_in' => $attendanceDate->copy()->addDays(2)->setHour(8)->setMinute(55),
            'clock_out' => $attendanceDate->copy()->addDays(2)->setHour(17)->setMinute(30),
        ]);
    }

    private function formatForInput(Carbon $dateTime, string $format): string
    {
        return $dateTime->format($format);
    }

    #[Test]
    public function displays_correct_user_name(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.show', $this->attendance->id));

        $response->assertOk();
        $response->assertViewHas('attendance.user.name', $this->user->name);
        $response->assertSeeText($this->user->name);
    }

    #[Test]
    public function displays_correct_date(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.show', $this->attendance->id));

        $response->assertOk();
        $expectedDate = $this->attendance->date;
        $response->assertViewHas('attendance.date', function(Carbon $viewDate) use ($expectedDate) {
            return $viewDate->isSameDay($expectedDate);
        });
        $response->assertSee('name="date"', false);
        $response->assertSee('value="' . $expectedDate->format('Y-m-d') . '"', false);
    }

    #[Test]
    public function displays_correct_clock_in_and_out_times(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.show', $this->attendance->id));

        $response->assertOk();
        $expectedClockIn = $this->attendance->clock_in;
        $expectedClockOut = $this->attendance->clock_out;

        $response->assertViewHas('displayData.clock_in', function(?Carbon $viewClockIn) use ($expectedClockIn) {
            return $viewClockIn && $viewClockIn->equalTo($expectedClockIn);
        });
        $response->assertViewHas('displayData.clock_out', function(?Carbon $viewClockOut) use ($expectedClockOut) {
            return $viewClockOut && $viewClockOut->equalTo($expectedClockOut);
        });
        $response->assertSee('name="clock_in"', false);
        $response->assertSee('value="' . $expectedClockIn->format('H:i') . '"', false);
        $response->assertSee('name="clock_out"', false);
        $response->assertSee('value="' . $expectedClockOut->format('H:i') . '"', false);

        $responseNoClockOut = $this->get(route('attendance.show', $this->attendanceNoClockOut->id));
        $responseNoClockOut->assertOk();
        $expectedClockInNoClockOut = $this->attendanceNoClockOut->clock_in;
        $responseNoClockOut->assertViewHas('displayData.clock_in', function(?Carbon $viewClockIn) use ($expectedClockInNoClockOut) {
            return $viewClockIn && $viewClockIn->equalTo($expectedClockInNoClockOut);
        });
        $responseNoClockOut->assertViewHas('displayData.clock_out', null);
        $responseNoClockOut->assertSee('name="clock_in"', false);
        $responseNoClockOut->assertSee('value="' . $expectedClockInNoClockOut->format('H:i') . '"', false);
        $responseNoClockOut->assertSee('name="clock_out"', false);
        $responseNoClockOut->assertSee('value=""', false);
    }

    #[Test]
    public function displays_correct_break_times(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.show', $this->attendance->id));

        $response->assertOk();

        $expectedBreaks = $this->attendance->breaks()->orderBy('start_time')->get();

        $response->assertViewHas('displayData.breaks', function($viewBreaks) use ($expectedBreaks) {
            if ($viewBreaks->count() !== $expectedBreaks->count()) {
                return false;
            }
            for ($i = 0; $i < $expectedBreaks->count(); $i++) {
                $viewBreak = $viewBreaks[$i];
                $expectedBreak = $expectedBreaks[$i];
                if (!$viewBreak->start_time || !$viewBreak->start_time->equalTo(Carbon::parse($expectedBreak->start_time))) return false;
                if (!$viewBreak->end_time || !$viewBreak->end_time->equalTo(Carbon::parse($expectedBreak->end_time))) return false;
            }
            return true;
        });

        foreach ($expectedBreaks as $index => $break) {
            $response->assertSee('id="break_start_' . $index . '"', false);
            $response->assertSee('value="' . Carbon::parse($break->start_time)->format('H:i') . '"', false);
            $response->assertSee('id="break_end_' . $index . '"', false);
            $response->assertSee('value="' . Carbon::parse($break->end_time)->format('H:i') . '"', false);
        }

        $responseNoBreaks = $this->get(route('attendance.show', $this->attendanceNoBreaks->id));
        $responseNoBreaks->assertOk();
        $responseNoBreaks->assertViewHas('displayData.breaks', function($viewBreaks) {
            return $viewBreaks->isEmpty();
        });
        $responseNoBreaks->assertSee('id="break_start_0"', false);
        $responseNoBreaks->assertSee('value=""', false);
        $responseNoBreaks->assertSee('id="break_end_0"', false);
        $responseNoBreaks->assertSee('value=""', false);
    }
}