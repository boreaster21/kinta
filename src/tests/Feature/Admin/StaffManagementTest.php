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

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $user1;
    protected User $user2;
    protected Attendance $targetAttendanceUser1;
    protected array $daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Staff User',
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user1 = User::factory()->create([
            'name' => 'Staff User One',
            'email' => 'staff1@example.com',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user2 = User::factory()->create([
            'name' => 'Staff User Two',
            'email' => 'staff2@example.com',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        // Create attendance data for different months
        $today = Carbon::today();
        $prevMonth = $today->copy()->subMonth();
        $nextMonth = $today->copy()->addMonth();

        // User 1 - Previous Month
        Attendance::factory()->for($this->user1)->create(['date' => $prevMonth->copy()->setDay(10)]);
        // User 1 - This Month (and target for detail test)
        $this->targetAttendanceUser1 = Attendance::factory()->for($this->user1)->create([
            'date' => $today->copy()->setDay(5),
            'clock_in' => $today->copy()->setDay(5)->setHour(9)->setMinute(15),
            'clock_out' => $today->copy()->setDay(5)->setHour(17)->setMinute(45),
        ]);
        BreakTime::factory()->for($this->targetAttendanceUser1)->create([
            'start_time' => $today->copy()->setDay(5)->setHour(12)->setMinute(0),
            'end_time' => $today->copy()->setDay(5)->setHour(13)->setMinute(0),
        ]);
        $this->targetAttendanceUser1 = $this->targetAttendanceUser1->fresh();
        $this->targetAttendanceUser1->calculateTotalBreakTime();
        $this->targetAttendanceUser1->calculateTotalWorkTime();
        $this->targetAttendanceUser1->save();
        // User 1 - Next Month
        Attendance::factory()->for($this->user1)->create(['date' => $nextMonth->copy()->setDay(20)]);

        // User 2 - This Month
        Attendance::factory()->for($this->user2)->create(['date' => $today->copy()->setDay(8)]);

    }

    private function formatTime(?Carbon $time): string
    {
        return $time ? $time->format('H:i') : '-';
    }

    private function formatDisplayDate(Carbon $date): string
    {
        return $date->format('m/d') . ' (' . $this->daysOfWeek[$date->dayOfWeek] . ')';
    }

    /**
     * 21. 全一般ユーザー情報確認テスト
     */
    #[Test]
    public function admin_can_view_all_staff_info(): void
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.staff.list'));

        $response->assertOk();
        $response->assertViewIs('admin.staff.list');
        $response->assertSee($this->user1->name);
        $response->assertSee($this->user1->email);
        $response->assertSee($this->user2->name);
        $response->assertSee($this->user2->email);
        $response->assertDontSee($this->adminUser->name);
        $response->assertDontSee($this->adminUser->email);
    }

    /**
     * 22. 特定ユーザーの勤怠情報確認テスト
     */
    #[Test]
    public function admin_can_view_specific_staff_monthly_attendance(): void
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.staff.monthly_attendance', ['id' => $this->user1->id]));

        $response->assertOk();
        $response->assertViewIs('admin.staff.monthly_attendance');
        $response->assertViewHas('user', $this->user1);
        $response->assertViewHas('attendances', function ($viewAttendances) {
            // Ensure it's iterable and contains the target ID
             if (!is_iterable($viewAttendances)) return false;
             foreach ($viewAttendances as $att) {
                 if (($att['id'] ?? null) === $this->targetAttendanceUser1->id) {
                     return true;
                 }
             }
             return false;
        });
        $response->assertSee($this->user1->name);
        $expectedDateString = $this->formatDisplayDate(Carbon::parse($this->targetAttendanceUser1->date));
        $response->assertSee($expectedDateString);
        $response->assertSee($this->formatTime($this->targetAttendanceUser1->clock_in));
        $response->assertSee($this->formatTime($this->targetAttendanceUser1->clock_out));
    }

    /**
     * 23. 勤怠一覧画面「前月」ボタン動作テスト
     */
    #[Test]
    public function admin_can_navigate_to_previous_month_attendance(): void
    {
        $this->actingAs($this->adminUser);
        $prevMonthString = Carbon::today()->subMonth()->format('Y-m');
        $prevMonthAttendance = Attendance::where('user_id', $this->user1->id)
                                    ->whereYear('date', Carbon::parse($prevMonthString)->year)
                                    ->whereMonth('date', Carbon::parse($prevMonthString)->month)
                                    ->first();

        $response = $this->get(route('admin.staff.monthly_attendance', ['id' => $this->user1->id, 'month' => $prevMonthString]));

        $response->assertOk();
        $response->assertViewIs('admin.staff.monthly_attendance');
        $response->assertViewHas('month', $prevMonthString);
        $response->assertSee($this->user1->name);
        $response->assertSee(Carbon::parse($prevMonthString)->format('Y年m月'));
        $expectedDateString = $this->formatDisplayDate(Carbon::parse($prevMonthAttendance->date));
        $response->assertSee($expectedDateString);
        $expectedDontSeeDateString = $this->formatDisplayDate(Carbon::parse($this->targetAttendanceUser1->date));
        $response->assertDontSee($expectedDontSeeDateString);
    }

    /**
     * 24. 勤怠一覧画面「翌月」ボタン動作テスト
     */
    #[Test]
    public function admin_can_navigate_to_next_month_attendance(): void
    {
        $this->actingAs($this->adminUser);
        $nextMonthString = Carbon::today()->addMonth()->format('Y-m');
        $nextMonthAttendance = Attendance::where('user_id', $this->user1->id)
                                    ->whereYear('date', Carbon::parse($nextMonthString)->year)
                                    ->whereMonth('date', Carbon::parse($nextMonthString)->month)
                                    ->first();

        $response = $this->get(route('admin.staff.monthly_attendance', ['id' => $this->user1->id, 'month' => $nextMonthString]));

        $response->assertOk();
        $response->assertViewIs('admin.staff.monthly_attendance');
        $response->assertViewHas('month', $nextMonthString);
        $response->assertSee($this->user1->name);
        $response->assertSee(Carbon::parse($nextMonthString)->format('Y年m月'));
        $expectedDateString = $this->formatDisplayDate(Carbon::parse($nextMonthAttendance->date));
        $response->assertSee($expectedDateString);
        $expectedDontSeeDateString = $this->formatDisplayDate(Carbon::parse($this->targetAttendanceUser1->date));
        $response->assertDontSee($expectedDontSeeDateString);
    }

    /**
     * 25. 勤怠一覧画面「詳細」ボタン遷移テスト
     */
    #[Test]
    public function admin_can_navigate_to_daily_detail_from_staff_attendance(): void
    {
        $this->actingAs($this->adminUser);
        $targetMonthString = Carbon::parse($this->targetAttendanceUser1->date)->format('Y-m');

        // First, go to the monthly attendance page
        $responseMonthly = $this->get(route('admin.staff.monthly_attendance', ['id' => $this->user1->id, 'month' => $targetMonthString]));
        $responseMonthly->assertOk();

        // Find the detail link (assuming standard route name 'attendance.show')
        $detailUrl = route('attendance.show', $this->targetAttendanceUser1->id);
        $responseMonthly->assertSee($detailUrl);

        // Then, navigate to the detail page
        $responseDetail = $this->get($detailUrl);
        $responseDetail->assertOk();
        $responseDetail->assertViewIs('attendance.detail');
        $responseDetail->assertViewHas('attendance', function ($viewAttendance) {
            return $viewAttendance->id === $this->targetAttendanceUser1->id;
        });
        $responseDetail->assertViewHas('isAdmin', true); // Ensure admin context is passed

        // Check detail content
        $responseDetail->assertSee($this->user1->name);
        $responseDetail->assertSee($this->targetAttendanceUser1->date->format('Y-m-d'));
        $responseDetail->assertSee('value="' . $this->targetAttendanceUser1->clock_in->format('H:i') . '"', false);
        $responseDetail->assertSee('value="' . $this->targetAttendanceUser1->clock_out->format('H:i') . '"', false);
    }
} 