<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User; // Import User model
use App\Models\Role; // Import Role model
use Illuminate\Support\Facades\App; // Import App facade
use App\Models\Attendance; // Import Attendance model
use App\Models\BreakTime; // Import BreakTime model

class AttendancePageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user; // Add property to hold the user

    protected function setUp(): void
    {
        parent::setUp();
        // Create necessary roles (admin and user)
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create a regular user for login
        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(), // Assume user is verified
        ]);
    }


    #[Test]
    public function displays_current_date_and_time_correctly(): void
    {
        // (省略: 前回のテストメソッド)
        // Store original locale and set Japanese for this test
        $originalLocale = App::getLocale();
        App::setLocale('ja');
        Carbon::setLocale('ja');
        Carbon::setTestNow(Carbon::now());
        try {
            $now = Carbon::getTestNow();
            $expectedDate = $now->translatedFormat('Y年m月d日 (D)');
            $expectedDate = str_replace(
                ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                ['日', '月', '火', '水', '木', '金', '土'],
                $expectedDate
            );
            $expectedTime = $now->format('H:i');
            $this->actingAs($this->user);
            $response = $this->get('/attendance');
            $response->assertOk();
            $response->assertSee($expectedDate, false);
            $response->assertSee($expectedTime, false);
        } finally {
            App::setLocale($originalLocale);
            Carbon::setLocale(config('app.locale'));
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function displays_status_correctly_when_not_working(): void
    {
        // No specific attendance data needed for '勤務外'

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('勤務外');
        // Optionally be more specific if the structure is stable
        // $response->assertSeeIn('.c-status-label', '勤務外');
    }

    #[Test]
    public function displays_status_correctly_when_clocked_in(): void
    {
        Carbon::setTestNow(Carbon::now()); // Freeze time

        // Create attendance record for today, clocked in now
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow(),
            'clock_out' => null, // Ensure not clocked out
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('出勤中');

        Carbon::setTestNow(); // Unfreeze time
    }

    #[Test]
    public function displays_status_correctly_when_on_break(): void
    {
        Carbon::setTestNow(Carbon::now()); // Freeze time

        // Create attendance record for today, clocked in earlier
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow()->subHour(), // Clocked in 1 hour ago
            'clock_out' => null,
        ]);

        // Create a break record, started now, not ended
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::getTestNow(),
            'end_time' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩中');

        Carbon::setTestNow(); // Unfreeze time
    }

    #[Test]
    public function displays_status_correctly_when_clocked_out(): void
    {
        Carbon::setTestNow(Carbon::now()); // Freeze time

        // Create attendance record for today, clocked in and out
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow()->subHours(2), // Clocked in 2 hours ago
            'clock_out' => Carbon::getTestNow(),          // Clocked out now
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('退勤済');

        Carbon::setTestNow(); // Unfreeze time
    }
}