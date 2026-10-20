<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\QueueCategory;
use App\Models\ServiceCounter;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Siti Aminah',
            'category_id' => QueueCategory::query()->value('id'),
            'service' => 'Perubahan data KK',
        ], $overrides);
    }

    private function counter(int $number = 1): ServiceCounter
    {
        return ServiceCounter::where('code', 'L'.$number)->firstOrFail();
    }

    public function test_public_registrations_receive_sequential_numbers_and_a_valid_private_ticket_link(): void
    {
        $firstResponse = $this->post('/antrian', $this->registrationData())->assertRedirect();
        $this->get($firstResponse->headers->get('Location'))->assertOk()->assertSee('A-001');
        $this->post('/antrian', $this->registrationData(['name' => 'Andi']))->assertRedirect();

        $this->assertDatabaseHas('queues', ['code' => 'A-001', 'name' => 'Siti Aminah', 'status' => 'waiting', 'source' => 'qr']);
        $this->assertDatabaseHas('queues', ['code' => 'A-002', 'name' => 'Andi', 'status' => 'waiting']);
    }

    public function test_ticket_result_requires_a_temporary_signature(): void
    {
        $this->post('/antrian', $this->registrationData());
        $queue = Queue::firstOrFail();

        $this->get("/antrian/{$queue->id}/hasil")->assertForbidden();
    }

    public function test_staff_can_register_call_serve_and_complete_a_queue_at_selected_counter(): void
    {
        $user = User::factory()->create();
        $counter = $this->counter();
        $this->actingAs($user)->postJson('/dashboard/queues', $this->registrationData())
            ->assertCreated()->assertJsonPath('queue.source', 'manual')->assertJsonPath('queue.code', 'A-001');

        $this->postJson('/dashboard/queues/next', ['counter_id' => $counter->id])
            ->assertOk()->assertJsonPath('queue.status', 'called')->assertJsonPath('counter.name', 'Loket 1');

        $queue = Queue::firstOrFail();
        $this->assertSame($counter->id, $queue->service_counter_id);
        $this->assertSame($user->id, $queue->called_by);
        $this->postJson("/dashboard/queues/{$queue->id}/serve", ['counter_id' => $counter->id])
            ->assertOk()->assertJsonPath('queue.status', 'serving');
        $this->postJson("/dashboard/queues/{$queue->id}/complete", ['counter_id' => $counter->id])
            ->assertOk()->assertJsonPath('queue.status', 'completed');
        $this->assertDatabaseHas('queue_logs', ['queue_id' => $queue->id, 'action' => 'completed', 'service_counter_id' => $counter->id, 'user_id' => $user->id]);
    }

    public function test_each_counter_can_have_its_own_active_queue(): void
    {
        $user = User::factory()->create();
        $this->post('/antrian', $this->registrationData());
        $this->post('/antrian', $this->registrationData(['name' => 'Andi']));

        $this->actingAs($user)->postJson('/dashboard/queues/next', ['counter_id' => $this->counter(1)->id])
            ->assertOk()->assertJsonPath('queue.code', 'A-001');
        $this->postJson('/dashboard/queues/next', ['counter_id' => $this->counter(2)->id])
            ->assertOk()->assertJsonPath('queue.code', 'A-002');

        $this->assertDatabaseHas('queues', ['code' => 'A-001', 'service_counter_id' => $this->counter(1)->id, 'status' => 'called']);
        $this->assertDatabaseHas('queues', ['code' => 'A-002', 'service_counter_id' => $this->counter(2)->id, 'status' => 'called']);
    }

    public function test_a_counter_cannot_call_another_queue_until_its_active_queue_is_handled(): void
    {
        $user = User::factory()->create();
        $counter = $this->counter();
        $this->post('/antrian', $this->registrationData());
        $this->post('/antrian', $this->registrationData(['name' => 'Andi']));
        $this->actingAs($user)->postJson('/dashboard/queues/next', ['counter_id' => $counter->id])->assertOk();

        $this->postJson('/dashboard/queues/next', ['counter_id' => $counter->id])->assertStatus(409);
        $this->assertDatabaseHas('queues', ['code' => 'A-002', 'status' => 'waiting']);
    }

    public function test_staff_cannot_call_without_an_active_counter(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/dashboard/queues/next')->assertUnprocessable();
    }

    public function test_skipped_queue_stays_in_history_and_can_be_recalled_at_another_counter(): void
    {
        $user = User::factory()->create();
        $counterOne = $this->counter(1);
        $counterTwo = $this->counter(2);
        $this->post('/antrian', $this->registrationData());
        $queue = Queue::firstOrFail();

        $this->actingAs($user)->postJson('/dashboard/queues/next', ['counter_id' => $counterOne->id]);
        $this->postJson("/dashboard/queues/{$queue->id}/skip", ['counter_id' => $counterOne->id])
            ->assertOk()->assertJsonPath('queue.status', 'skipped');
        $this->assertDatabaseHas('queues', ['id' => $queue->id, 'status' => 'skipped']);
        $this->get('/dashboard?counter_id='.$counterOne->id)->assertOk()->assertSee('Antrean dilewati')->assertSee('A-001');

        $this->postJson("/dashboard/queues/{$queue->id}/recall", ['counter_id' => $counterTwo->id])
            ->assertOk()->assertJsonPath('queue.status', 'called')->assertJsonPath('counter.name', 'Loket 2');
        $this->assertDatabaseHas('queues', ['id' => $queue->id, 'code' => 'A-001', 'service_counter_id' => $counterTwo->id, 'called_by' => $user->id]);
        $this->assertDatabaseHas('queue_logs', ['queue_id' => $queue->id, 'action' => 'recall', 'from_status' => 'skipped', 'service_counter_id' => $counterTwo->id, 'user_id' => $user->id]);

        $this->postJson("/dashboard/queues/{$queue->id}/serve", ['counter_id' => $counterTwo->id])->assertOk();
        $this->postJson("/dashboard/queues/{$queue->id}/skip", ['counter_id' => $counterTwo->id])
            ->assertOk()->assertJsonPath('queue.status', 'skipped');
    }

    public function test_inactive_counters_cannot_be_selected_for_calling(): void
    {
        $counter = $this->counter();
        $counter->update(['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->postJson('/dashboard/queues/next', ['counter_id' => $counter->id])
            ->assertUnprocessable();
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $user = User::factory()->create();
        $counter = $this->counter();
        $this->post('/antrian', $this->registrationData());
        $queue = Queue::firstOrFail();

        $this->actingAs($user)->postJson("/dashboard/queues/{$queue->id}/complete", ['counter_id' => $counter->id])
            ->assertStatus(409);
        $this->assertDatabaseHas('queues', ['id' => $queue->id, 'status' => 'waiting']);
    }

    public function test_staff_dashboard_requires_authentication_and_admin_pages_require_admin_role(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $staff = User::factory()->create(['role' => 'petugas']);
        $this->actingAs($staff)->get('/loket')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/loket')->assertOk();
    }
}
