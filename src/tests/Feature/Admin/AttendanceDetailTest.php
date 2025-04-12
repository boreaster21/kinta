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

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $targetUser;
    protected Attendance $attendance;
    protected BreakTime $breakTime;

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Test User',
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        $this->targetUser = User::factory()->create([
            'name' => 'Target User',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        $attendanceDate = Carbon::today()->subDays(3);
        $this->attendance = Attendance::factory()->for($this->targetUser)->create([
            'date' => $attendanceDate,
            'clock_in' => $attendanceDate->copy()->setHour(9)->setMinute(5),
            'clock_out' => $attendanceDate->copy()->setHour(18)->setMinute(12),
            'reason' => 'Regular work day',
        ]);
        $this->breakTime = BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $attendanceDate->copy()->setHour(12)->setMinute(1)->setSecond(0),
            'end_time' => $attendanceDate->copy()->setHour(13)->setMinute(2)->setSecond(0),
        ]);

        $this->attendance = $this->attendance->fresh();
        $this->attendance->calculateTotalBreakTime();
        $this->attendance->calculateTotalWorkTime();
        $this->attendance->save();
    }

    private function getValidUpdateData(array $overrides = []): array
    {
        $baseData = [
            'date' => $this->attendance->date->format('Y-m-d'),
            'clock_in' => $this->attendance->clock_in->format('H:i'),
            'clock_out' => $this->attendance->clock_out->format('H:i'),
            'breaks' => [
                [
                    'start_time' => $this->breakTime->start_time->format('H:i'),
                    'end_time' => $this->breakTime->end_time->format('H:i'),
                ]
            ],
            'reason' => $this->attendance->reason ?? 'Updated reason.',
        ];
        return array_merge($baseData, $overrides);
    }


    /**
     * 16. 選択した勤怠情報の詳細表示テスト
     */
    #[Test]
    public function admin_can_view_attendance_detail(): void
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('attendance.show', $this->attendance->id)); // Use correct route name

        $response->assertOk();
        $response->assertViewIs('attendance.detail'); // View name confirmation needed
        $response->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->id === $this->attendance->id;
        });
        $response->assertViewHas('isAdmin', true);
        $response->assertViewHas('displayData'); // Check if displayData exists

        // Check visible content
        $response->assertSee($this->targetUser->name);
        $response->assertSee($this->attendance->date->format('Y-m-d')); // Check date input value
        $response->assertSee('value="' . $this->attendance->clock_in->format('H:i') . '"', false); // Check clock in time input value
        $response->assertSee('value="' . $this->attendance->clock_out->format('H:i') . '"', false); // Check clock out time input value
        $response->assertSee('value="' . $this->breakTime->start_time->format('H:i') . '"', false); // Check break start input value
        $response->assertSee('value="' . $this->breakTime->end_time->format('H:i') . '"', false); // Check break end input value
        $response->assertSee($this->attendance->reason); // Check reason textarea content
    }

    /**
     * 17. （管理者）出勤時間が退勤時間より後の場合のエラーメッセージテスト
     */
    #[Test]
    public function admin_update_validation_fails_if_clock_in_after_clock_out(): void
    {
        $this->actingAs($this->adminUser);
        $invalidData = $this->getValidUpdateData([
            'clock_in' => '19:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->put(route('admin.attendance.update', $this->attendance->id), $invalidData);

        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です。']);
        $response->assertRedirect(); // Should redirect back
    }

    /**
     * 18. （管理者）休憩開始時間が退勤時間より後の場合のエラーメッセージテスト
     */
    #[Test]
    public function admin_update_validation_fails_if_break_start_after_clock_out(): void
    {
        $this->actingAs($this->adminUser);
        $invalidData = $this->getValidUpdateData([
            'clock_out' => '17:00',
            'breaks' => [
                [
                    'start_time' => '18:00', // Break starts after clock out
                    'end_time' => '19:00',
                ]
            ],
        ]);

        $response = $this->put(route('admin.attendance.update', $this->attendance->id), $invalidData);

        // The validator adds error to 'breaks.{$index}.start_time' based on the logic
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が勤務時間外です。']);
        $response->assertRedirect();
    }

    /**
     * 19. （管理者）休憩終了時間が退勤時間より後の場合のエラーメッセージテスト
     *    (Validator logic checks if start OR end is outside work hours, error key is start_time)
     */
    #[Test]
    public function admin_update_validation_fails_if_break_end_after_clock_out(): void
    {
        $this->actingAs($this->adminUser);
        $invalidData = $this->getValidUpdateData([
            'clock_out' => '17:00',
            'breaks' => [
                [
                    'start_time' => '16:00',
                    'end_time' => '18:00', // Break ends after clock out
                ]
            ],
        ]);

        $response = $this->put(route('admin.attendance.update', $this->attendance->id), $invalidData);

        // The validator adds error to 'breaks.{$index}.start_time' based on the logic
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が勤務時間外です。']);
        $response->assertRedirect();
    }

    /**
     * 20. （管理者）備考欄が未入力の場合のエラーメッセージテスト
     */
    #[Test]
    public function admin_update_validation_fails_if_reason_is_missing(): void
    {
        $this->actingAs($this->adminUser);
        $invalidData = $this->getValidUpdateData(['reason' => '']); // Empty reason

        $response = $this->put(route('admin.attendance.update', $this->attendance->id), $invalidData);

        $response->assertSessionHasErrors(['reason' => '備考を記入してください。']);
        $response->assertRedirect();
    }
} 