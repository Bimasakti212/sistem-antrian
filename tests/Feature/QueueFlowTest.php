<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registrations_receive_sequential_daily_numbers(): void
    {
        $this->post('/antrian', ['name' => 'Siti Aminah', 'service' => 'Surat domisili'])
            ->assertRedirect();
        $this->post('/antrian', ['name' => 'Andi', 'service' => 'Surat usaha'])
            ->assertRedirect();

        $this->assertDatabaseHas('queues', ['code' => 'A-001', 'name' => 'Siti Aminah', 'status' => 'waiting']);
        $this->assertDatabaseHas('queues', ['code' => 'A-002', 'name' => 'Andi', 'status' => 'waiting']);
    }

    public function test_staff_can_call_and_complete_the_next_queue(): void
    {
        $user = User::factory()->create();
        $this->post('/antrian', ['name' => 'Budi', 'service' => 'Perubahan KK']);

        $this->actingAs($user)->postJson('/dashboard/queues/next')
            ->assertOk()->assertJsonPath('queue.code', 'A-001')->assertJsonPath('queue.status', 'called');

        $id = \App\Models\Queue::firstOrFail()->id;
        $this->actingAs($user)->postJson("/dashboard/queues/{$id}/complete")
            ->assertOk()->assertJsonPath('queue.status', 'completed');
        $this->assertDatabaseHas('queue_logs', ['queue_id' => $id, 'action' => 'completed']);
    }

    public function test_dashboard_requires_staff_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
