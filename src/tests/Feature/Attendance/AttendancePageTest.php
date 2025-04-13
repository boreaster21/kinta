<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\App;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendancePageTest extends TestCase
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


    #[Test]
    public function displays_current_date_and_time_correctly(): void
    {
        $originalLocale = App::getLocale();
        App::setLocale('ja');
        Carbon::setLocale('ja');
        Carbon::setTestNow(Carbon::create(2024, 7, 5, 9, 5, 0));
        try {
            $now = Carbon::getTestNow();

            $expectedDate = $now->format('Y年n月j日') . ' (' . $now->translatedFormat('D') . ')';

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
        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('勤務外');
    }

    #[Test]
    public function displays_status_correctly_when_clocked_in(): void
    {
        Carbon::setTestNow(Carbon::now());

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow(),
            'clock_out' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    #[Test]
    public function displays_status_correctly_when_on_break(): void
    {
        Carbon::setTestNow(Carbon::now());

        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow()->subHour(),
            'clock_out' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::getTestNow(),
            'end_time' => null,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    #[Test]
    public function displays_status_correctly_when_clocked_out(): void
    {
        Carbon::setTestNow(Carbon::now());

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::getTestNow()->subHours(2),
            'clock_out' => Carbon::getTestNow(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance');
        $response->assertOk();
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}