<?php

namespace Tests\Feature\Attendance; // Group related tests

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
        // Ensure necessary roles exist
        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create a user for the tests
        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper to freeze time to a specific point.
     */
    private function setTestTime(int $hour, int $minute = 0, int $second = 0): Carbon
    {
        // Clock-out can happen anytime after clock-in, no strict 4-22 range needed here
        $now = Carbon::today()->setHour($hour)->setMinute($minute)->setSecond($second);
        Carbon::setTestNow($now);
        return $now;
    }

    /**
     * Reset Carbon's test time after each test.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Unfreeze time after tests
        parent::tearDown();
    }

    // Test Case 1: Basic Clock-out Functionality
    #[Test]
    public function user_can_clock_out_successfully_and_status_updates(): void
    {
        // Setup: User clocked in earlier
        $clockInTime = $this->setTestTime(9, 0); // Clock in at 9:00 AM
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
            'clock_out' => null,
        ]);

        // Step 1 & 2: Login and verify button is visible
        $this->actingAs($this->user);
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '出勤中');
        $response->assertSee(route('attendance.clock_out')); // Verify clock-out form action URL is present

        // Step 3: Perform clock-out action
        $clockOutTime = $this->setTestTime(17, 30); // Clock out at 5:30 PM
        $response = $this->post(route('attendance.clock_out'));

        // Expected Result 1: Redirection and Success Message
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('success', '退勤を記録しました。');

        // Expected Result 3: Database update check
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $this->user->id,
            'clock_out' => $clockOutTime->toDateTimeString(), // Verify clock_out time is recorded
        ]);

        // Expected Result 2: Follow redirect and check updated page status
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '退勤済'); // Verify status is updated
        $response->assertDontSee(route('attendance.clock_out')); // Verify clock-out button is gone
        $response->assertDontSee(route('attendance.break_start')); // Verify break buttons are gone
        $response->assertDontSee(route('attendance.break_end'));
    }

    // Test Case 2: Clock-out Time Recording Confirmation
    #[Test]
    public function clock_out_time_is_recorded_correctly_in_database(): void
    {
        // Step 1: Login
        $this->actingAs($this->user);

        // Step 2: Perform clock-in
        $clockInTime = $this->setTestTime(10, 0); // Clock in at 10:00 AM
        $this->post(route('attendance.clock_in'));
        // Find the created attendance record
        $attendance = Attendance::where('user_id', $this->user->id)->whereDate('date', $clockInTime->toDateString())->first();
        $this->assertNotNull($attendance, 'Attendance record should exist after clock-in.');
        $this->assertNotNull($attendance->clock_in, 'Clock-in time should be recorded.');
        $this->assertNull($attendance->clock_out, 'Clock-out time should be null initially.');

        // Step 3: Perform clock-out
        $expectedClockOutTime = $this->setTestTime(18, 15); // Clock out at 6:15 PM
        $this->post(route('attendance.clock_out'));

        // Step 4 & Expected Result: Verify database record
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id, // Ensure we are checking the same record
            'user_id' => $this->user->id,
            'date' => $expectedClockOutTime->toDateString(),
            // Verify the exact clock-out timestamp
            'clock_out' => $expectedClockOutTime->toDateTimeString(),
        ]);
    }

     // Additional Test: Cannot clock out if not clocked in
    #[Test]
    public function user_cannot_clock_out_if_not_clocked_in(): void
    {
        $this->actingAs($this->user);

        // Verify button is not visible initially
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '勤務外');
        $response->assertDontSee(route('attendance.clock_out'));

        // Attempt clock-out action (Controller logic might just redirect without error)
        $response = $this->post(route('attendance.clock_out'));
        // The controller might just redirect without error if no attendance record found,
        // or it might throw an error if it tries to access properties of null.
        // Let's assume it redirects safely based on the provided controller code.
        $response->assertRedirect(route('attendance.index'));
        // We don't expect a specific success/error message in this case based on controller code.
        // Ensure no clock_out time was accidentally recorded (though no record should exist).
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            // Check specifically that clock_out is not null, just in case
            // 'clock_out' => fn ($query) => $query->whereNotNull('clock_out'), // More advanced check if needed
        ]);
    }

     // Additional Test: Cannot clock out twice
    #[Test]
    public function user_cannot_clock_out_twice(): void
    {
         // Setup: User clocked in and out earlier today
        $clockInTime = $this->setTestTime(9, 0);
        $clockOutTime = $this->setTestTime(17, 0);
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime, // Already clocked out
        ]);

        $this->actingAs($this->user);

         // Verify button is not visible
        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $response->assertViewHas('status', '退勤済');
        $response->assertDontSee(route('attendance.clock_out'));

        // Attempt clock-out action again
        $secondAttemptTime = $this->setTestTime(17, 5); // Try again 5 mins later
        $response = $this->post(route('attendance.clock_out'));

        // The controller logic checks `if ($attendance && !$attendance->clock_out)`
        // so it shouldn't try to update again. It should just redirect.
        $response->assertRedirect(route('attendance.index'));
        // No specific error message is set in the controller for this case.
        // Crucially, ensure the original clock_out time wasn't overwritten.
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_out' => $clockOutTime->toDateTimeString(), // Should still be the original time
        ]);
    }
}