<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class CorrectionRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $user1;
    protected User $user2;
    protected Attendance $attendanceUser1;
    protected Attendance $attendanceUser2;
    protected StampCorrectionRequest $pendingRequestUser1;
    protected StampCorrectionRequest $pendingRequestUser2;
    protected StampCorrectionRequest $approvedRequestUser1;
    protected StampCorrectionRequest $rejectedRequestUser2;

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Request Manager',
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user1 = User::factory()->create([
            'name' => 'Request User One',
            'email' => 'requester1@example.com',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
        $this->user2 = User::factory()->create([
            'name' => 'Request User Two',
            'email' => 'requester2@example.com',
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        $dateUser1 = Carbon::today()->subDays(5);
        $dateUser2 = Carbon::today()->subDays(6);
        $dateUser2Rejected = Carbon::today()->subDays(7);

        $this->attendanceUser1 = Attendance::factory()->for($this->user1)->create([
            'date' => $dateUser1,
            'clock_in' => $dateUser1->copy()->setHour(9)->setMinute(0),
            'clock_out' => $dateUser1->copy()->setHour(18)->setMinute(0),
        ]);
        BreakTime::factory()->for($this->attendanceUser1)->create([
            'start_time' => $dateUser1->copy()->setHour(12)->setMinute(0),
            'end_time' => $dateUser1->copy()->setHour(13)->setMinute(0),
        ]);

        $this->attendanceUser2 = Attendance::factory()->for($this->user2)->create([
            'date' => $dateUser2,
            'clock_in' => $dateUser2->copy()->setHour(10)->setMinute(0),
            'clock_out' => $dateUser2->copy()->setHour(19)->setMinute(0),
        ]);
        $attendanceUser2Rejected = Attendance::factory()->for($this->user2)->create([
            'date' => $dateUser2Rejected,
            'clock_in' => $dateUser2Rejected->copy()->setHour(11)->setMinute(0),
            'clock_out' => $dateUser2Rejected->copy()->setHour(20)->setMinute(0),
        ]);

        $this->pendingRequestUser1 = StampCorrectionRequest::factory()->create([
            'user_id' => $this->user1->id,
            'attendance_id' => $this->attendanceUser1->id,
            'status' => 'pending',
            'reason' => 'User 1 Pending Reason',
            'created_at' => now()->subDay(),
        ]);
        $this->pendingRequestUser2 = StampCorrectionRequest::factory()->create([
            'user_id' => $this->user2->id,
            'attendance_id' => $this->attendanceUser2->id,
            'status' => 'pending',
            'reason' => 'User 2 Pending Reason',
            'created_at' => now()->subHours(2),
        ]);

        $this->approvedRequestUser1 = StampCorrectionRequest::factory()->create([
            'user_id' => $this->user1->id,
            'attendance_id' => $this->attendanceUser1->id,
            'status' => 'approved',
            'reason' => 'User 1 Approved Reason',
            'created_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->rejectedRequestUser2 = StampCorrectionRequest::factory()->create([
            'user_id' => $this->user2->id,
            'attendance_id' => $attendanceUser2Rejected->id,
            'status' => 'rejected',
            'reason' => 'User 2 Rejected Reason',
            'created_at' => now()->subDays(3),
            'rejected_at' => now()->subDays(2),
        ]);
    }

    #[Test]
    public function admin_can_view_pending_requests(): void
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('stamp_correction_request.list', ['status' => 'pending']));

        $response->assertOk();
        $response->assertViewIs('stamp_correction_request.list');
        $response->assertViewHas('status', 'pending');

        $response->assertViewHas('requests', function ($viewRequests) {
            $collection = $viewRequests instanceof Paginator ? $viewRequests->getCollection() : $viewRequests;
            $this->assertTrue($collection->contains('id', $this->pendingRequestUser1->id));
            $this->assertTrue($collection->contains('id', $this->pendingRequestUser2->id));
            $this->assertFalse($collection->contains('id', $this->approvedRequestUser1->id));
            $this->assertFalse($collection->contains('id', $this->rejectedRequestUser2->id));
            $collection->each(function ($request) {
                $this->assertEquals('pending', $request['status']);
            });
            return true;
        });
    }

    #[Test]
    public function admin_can_view_approved_requests(): void
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('stamp_correction_request.list', ['status' => 'approved']));

        $response->assertOk();
        $response->assertViewIs('stamp_correction_request.list');
        $response->assertViewHas('status', 'approved');

        $response->assertViewHas('requests', function ($viewRequests) {
            $collection = $viewRequests instanceof Paginator ? $viewRequests->getCollection() : $viewRequests;
            $this->assertTrue($collection->contains('id', $this->approvedRequestUser1->id));
            $this->assertFalse($collection->contains('id', $this->pendingRequestUser1->id));
            $this->assertFalse($collection->contains('id', $this->pendingRequestUser2->id));
            $this->assertFalse($collection->contains('id', $this->rejectedRequestUser2->id));
            $collection->each(function ($request) {
                $this->assertEquals('approved', $request['status']);
            });
            return true;
        });
    }

    #[Test]
    public function admin_can_view_request_detail(): void
    {
        $this->actingAs($this->adminUser);
        $request = $this->pendingRequestUser1->fresh()->load(['user', 'attendance.breaks']);

        $response = $this->get(route('admin.stamp_correction_request.show', $request->id));

        $response->assertOk();
        $response->assertViewIs('admin.stamp_correction_request.approve');
        $response->assertViewHas('request', function ($viewRequest) use ($request) {
            return $viewRequest->id === $request->id;
        });

        $response->assertSee($request->user->name);
        $response->assertSee($request->attendance->date->format('Y年m月d日'));
        $response->assertSee($request->created_at->format('Y/m/d H:i'));
        $response->assertSee('承認待ち');

        $originalClockInDisplay = $request->original_clock_in ? Carbon::parse($request->original_clock_in)->format('H:i') : '-';
        $originalClockOutDisplay = $request->original_clock_out ? Carbon::parse($request->original_clock_out)->format('H:i') : '-';

        $response->assertSee('修正前');
        $response->assertSee($originalClockInDisplay);
        $response->assertSee($originalClockOutDisplay);

        $response->assertSee('修正後');
        $response->assertSee(Carbon::parse($request->clock_in)->format('H:i'));
        $response->assertSee(Carbon::parse($request->clock_out)->format('H:i'));

        if (!empty($request->original_break_start)) {
            foreach ($request->original_break_start as $index => $startTime) {
                $endTime = $request->original_break_end[$index] ?? null;
                if ($endTime) {
                    $response->assertSee($startTime . ' 〜 ' . $endTime);
                }
            }
        } else {
            $response->assertSee('休憩なし');
        }

        if (!empty($request->break_start)) {
            foreach ($request->break_start as $index => $startTime) {
                $endTime = $request->break_end[$index] ?? null;
                if ($endTime) {
                    $response->assertSee($startTime . ' 〜 ' . $endTime);
                }
            }
        } else {
            $response->assertSee('申請休憩なし');
        }

        $response->assertSee($request->original_reason ?? '-');

        $response->assertSee($request->reason);

        $response->assertSee('承認する');
        $response->assertSee('却下する');
    }

    #[Test]
    public function admin_can_approve_request_and_attendance_updates(): void
    {
        $this->actingAs($this->adminUser);
        $requestToApprove = $this->pendingRequestUser1->fresh();
        $originalAttendance = $this->attendanceUser1->fresh();
        $requestedClockIn = $requestToApprove->clock_in;
        $requestedClockOut = $requestToApprove->clock_out;

        $this->assertEquals('pending', $requestToApprove->status);
        $this->assertFalse($originalAttendance->clock_in->equalTo($requestedClockIn));
        $this->assertFalse($originalAttendance->clock_out->equalTo($requestedClockOut));


        $response = $this->post(route('admin.stamp_correction_request.approve', $requestToApprove->id));

        $response->assertRedirect(route('stamp_correction_request.list'));
        $response->assertSessionHas('message', '修正申請を承認しました');

        $requestToApprove->refresh();
        $this->assertEquals('approved', $requestToApprove->status);
        $this->assertNotNull($requestToApprove->approved_at);
        $this->assertEquals($this->adminUser->id, $requestToApprove->approved_by);

        $originalAttendance->refresh();
        $this->assertTrue($originalAttendance->clock_in->equalTo($requestedClockIn));
        $this->assertTrue($originalAttendance->clock_out->equalTo($requestedClockOut));
    }
}