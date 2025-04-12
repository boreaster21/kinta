<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $attendanceDate = Carbon::today()->subDays(10);
        $this->attendance = Attendance::factory()->for($this->user)->create([
            'date' => $attendanceDate,
            'clock_in' => $attendanceDate->copy()->setHour(9)->setMinute(0),
            'clock_out' => $attendanceDate->copy()->setHour(18)->setMinute(0),
        ]);
        BreakTime::factory()->for($this->attendance)->create([
            'start_time' => $attendanceDate->copy()->setHour(12)->setMinute(0),
            'end_time' => $attendanceDate->copy()->setHour(13)->setMinute(0),
        ]);
    }

    private function getValidCorrectionData(array $overrides = []): array
    {
        $baseData = [
            'date' => $this->attendance->date->format('Y-m-d'),
            'clock_in' => '09:05',
            'clock_out' => '18:05',
            'break_start' => ['12:05'],
            'break_end' => ['13:05'],
            'reason' => '打刻修正のため申請します。',
        ];
        return array_merge($baseData, $overrides);
    }

    #[Test]
    public function validation_fails_if_clock_in_is_after_clock_out(): void
    {
        $this->actingAs($this->user);
        $invalidData = $this->getValidCorrectionData([
            'clock_in' => '19:00',
            'clock_out' => '18:00',
        ]);
        $response = $this->post(route('attendance.request', $this->attendance->id), $invalidData);
        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です。']);
    }

    #[Test]
    public function validation_fails_if_break_start_is_after_clock_out(): void
    {
        $this->actingAs($this->user);
        $invalidData = $this->getValidCorrectionData([
            'clock_out' => '17:00',
            'break_start' => ['18:00'],
            'break_end' => ['19:00'],
        ]);
        $response = $this->post(route('attendance.request', $this->attendance->id), $invalidData);
        $response->assertSessionHasErrors(['break_start.0' => '休憩時間が勤務時間外です。']);
    }

    #[Test]
    public function validation_fails_if_break_end_is_after_clock_out(): void
    {
        $this->actingAs($this->user);
        $invalidData = $this->getValidCorrectionData([
            'clock_out' => '17:00',
            'break_start' => ['16:00'],
            'break_end' => ['18:00'],
        ]);
        $response = $this->post(route('attendance.request', $this->attendance->id), $invalidData);
        $response->assertSessionHasErrors(['break_start.0' => '休憩時間が勤務時間外です。']);
    }

    #[Test]
    public function validation_fails_if_reason_is_missing(): void
    {
        $this->actingAs($this->user);
        $invalidData = $this->getValidCorrectionData(['reason' => '']);
        $response = $this->post(route('attendance.request', $this->attendance->id), $invalidData);
        $response->assertSessionHasErrors(['reason' => '備考を記入してください。']);
    }

    #[Test]
    public function user_request_appears_on_admin_list_and_approval_page(): void
    {
        $this->actingAs($this->user);
        $validData = $this->getValidCorrectionData();
        $responseUser = $this->post(route('attendance.request', $this->attendance->id), $validData);
        $responseUser->assertRedirect(route('attendance.list'));
        $responseUser->assertSessionHas('message', '修正申請を送信しました');

        $requestRecord = StampCorrectionRequest::where('attendance_id', $this->attendance->id)->latest()->first();
        $this->assertNotNull($requestRecord);
        $this->assertEquals('pending', $requestRecord->status);

        $this->actingAs($this->adminUser);

        $responseAdminList = $this->get(route('stamp_correction_request.list', ['status' => 'pending']));
        $responseAdminList->assertOk();
        $responseAdminList->assertSee($this->user->name);
        $responseAdminList->assertSee($requestRecord->date->format('Y/m/d'));
        $responseAdminList->assertSee('承認待ち');
        $adminDetailUrl = route('admin.stamp_correction_request.show', $requestRecord->id);
        $responseAdminList->assertSee($adminDetailUrl);

        $responseAdminDetail = $this->get($adminDetailUrl);
        $responseAdminDetail->assertOk();
        $responseAdminDetail->assertViewHas('request', function ($viewRequest) use ($requestRecord) {
            return $viewRequest->id === $requestRecord->id;
        });
        $responseAdminDetail->assertSee($this->user->name);
        $responseAdminDetail->assertSee($requestRecord->date->format('Y年m月d日'));
        $responseAdminDetail->assertSee($requestRecord->reason);
        $responseAdminDetail->assertSee('修正内容比較');
        $responseAdminDetail->assertSee(route('admin.stamp_correction_request.approve', $requestRecord->id));
        $responseAdminDetail->assertSee(route('admin.stamp_correction_request.reject', $requestRecord->id));
    }


    #[Test]
    public function user_can_see_their_pending_requests_on_list_page(): void
    {
        $request = StampCorrectionRequest::factory()->for($this->user)->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('stamp_correction_request.list', ['status' => 'pending'])); // Use status=pending explicitly

        $response->assertOk();
        $response->assertSee($request->date->format('Y/m/d'));
        $response->assertSee('承認待ち');
        $response->assertSee(route('stamp_correction_request.pending', $request->id));
    }

        #[Test]
    public function user_can_see_their_approved_requests_on_approved_tab(): void
    {
        $this->actingAs($this->user);
        $validData = $this->getValidCorrectionData();
        $this->post(route('attendance.request', $this->attendance->id), $validData);
        $requestRecord = StampCorrectionRequest::where('attendance_id', $this->attendance->id)->latest()->first();
        $this->assertNotNull($requestRecord);

        $this->actingAs($this->adminUser);
        $approveResponse = $this->post(route('admin.stamp_correction_request.approve', $requestRecord->id));
        $approveResponse->assertRedirect();

        $requestRecord->refresh();
        $this->assertEquals('approved', $requestRecord->status, 'Request status should be updated to approved in DB.');

        $this->actingAs($this->user);
        $response = $this->get(route('stamp_correction_request.list', ['status' => 'approved']));

        $response->assertOk();
        $response->assertViewHas('requests', function ($viewRequests) use ($requestRecord) {
            $collectionToCheck = null;
            if ($viewRequests instanceof \Illuminate\Contracts\Pagination\Paginator) { 
                $collectionToCheck = $viewRequests->getCollection();
            } elseif ($viewRequests instanceof \Illuminate\Support\Collection) {
                $collectionToCheck = $viewRequests;
            }
            if ($collectionToCheck) {
                return $collectionToCheck->contains('id', $requestRecord->id);
            }

            return false;
        });
        $response->assertSee('承認済');
        $response->assertSee(route('stamp_correction_request.approved', $requestRecord->id));
    }

    #[Test]
    public function user_can_navigate_to_request_detail_page_from_list(): void
    {
        $pendingRequest = StampCorrectionRequest::factory()->for($this->user)->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
            'reason' => '承認待ち詳細確認',
        ]);
        $approvedRequest = StampCorrectionRequest::factory()->for($this->user)->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'approved',
            'reason' => '承認済み詳細確認',
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);


        $this->actingAs($this->user);

        $listResponsePending = $this->get(route('stamp_correction_request.list', ['status' => 'pending']));
        $listResponsePending->assertOk();
        $pendingDetailUrl = route('stamp_correction_request.pending', $pendingRequest->id);
        $listResponsePending->assertSee($pendingDetailUrl);

        $detailResponsePending = $this->get($pendingDetailUrl);
        $detailResponsePending->assertOk();
        $detailResponsePending->assertViewHas('request', function ($viewRequest) use ($pendingRequest) {
            return $viewRequest->id === $pendingRequest->id;
        });
        $detailResponsePending->assertSee($pendingRequest->reason);
        $detailResponsePending->assertSee('承認待ち');


        $listResponseApproved = $this->get(route('stamp_correction_request.list', ['status' => 'approved']));
        $listResponseApproved->assertOk();
        $approvedDetailUrl = route('stamp_correction_request.approved', $approvedRequest->id);
        $listResponseApproved->assertSee($approvedDetailUrl); 

        $detailResponseApproved = $this->get($approvedDetailUrl);
        $detailResponseApproved->assertOk();
        $detailResponseApproved->assertViewHas('request', function ($viewRequest) use ($approvedRequest) {
            return $viewRequest->id === $approvedRequest->id;
        });
        $detailResponseApproved->assertSee($approvedRequest->reason);
        $detailResponseApproved->assertSee('承認済み');
    }
}