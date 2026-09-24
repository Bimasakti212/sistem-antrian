<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function publicForm() { return view('queues.public'); }

    public function storePublic(Request $request)
    {
        $data = $this->validated($request);
        $queue = $this->register($data, null);
        return redirect()->route('queues.result', $queue);
    }

    public function result(Queue $queue)
    {
        abort_unless($queue->queue_date->isToday(), 404);
        return view('queues.result', compact('queue'));
    }

    public function dashboard()
    {
        $today = today()->toDateString();
        $current = Queue::whereDate('queue_date', $today)->whereIn('status', ['called', 'serving'])->orderByDesc('called_at')->first();
        $queues = Queue::whereDate('queue_date', $today)->orderBy('number')->get();
        $counts = $queues->countBy('status');
        return view('queues.dashboard', compact('current', 'queues', 'counts'));
    }

    public function storeManual(Request $request)
    {
        $queue = $this->register($this->validated($request), $request->user()->id);
        return response()->json(['message' => 'Antrean berhasil didaftarkan.', 'queue' => $queue]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'service' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function register(array $data, ?int $userId): Queue
    {
        return DB::transaction(function () use ($data, $userId) {
            $date = today()->toDateString();
            DB::table('daily_queue_counters')->insertOrIgnore(['queue_date' => $date, 'last_number' => 0]);
            $counter = DB::table('daily_queue_counters')->where('queue_date', $date)->lockForUpdate()->first();
            $number = $counter->last_number + 1;
            DB::table('daily_queue_counters')->where('queue_date', $date)->update(['last_number' => $number]);
            $queue = Queue::create($data + ['queue_date' => $date, 'number' => $number, 'code' => 'A-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT), 'status' => 'waiting', 'registered_at' => now(), 'registered_by' => $userId]);
            $this->log($queue, 'registered', null, 'waiting', $userId);
            return $queue;
        }, 3);
    }

    public function callNext(Request $request)
    {
        $queue = DB::transaction(function () use ($request) {
            $item = Queue::whereDate('queue_date', today())->where('status', 'waiting')->orderBy('number')->lockForUpdate()->first();
            if (! $item) return null;
            $item->update(['status' => 'called', 'called_at' => now()]);
            $this->log($item, 'called', 'waiting', 'called', $request->user()->id);
            return $item;
        }, 3);
        return response()->json(['queue' => $queue, 'message' => $queue ? 'Antrean dipanggil.' : 'Tidak ada antrean menunggu.']);
    }

    public function action(Request $request, Queue $queue, string $action)
    {
        abort_unless($queue->queue_date->isToday(), 404);
        $from = $queue->status;
        $map = ['recall' => ['called', 'called'], 'skip' => ['skipped', 'skipped'], 'complete' => ['completed', 'completed']];
        [$status, $verb] = $map[$action];
        $queue->update(['status' => $status, 'called_at' => $action === 'recall' ? now() : $queue->called_at, 'completed_at' => $action === 'complete' ? now() : $queue->completed_at]);
        $this->log($queue, $verb, $from, $status, $request->user()->id);
        return response()->json(['queue' => $queue->fresh(), 'message' => 'Status antrean diperbarui.']);
    }

    private function log(Queue $queue, string $action, ?string $from, ?string $to, ?int $userId): void
    {
        DB::table('queue_logs')->insert(['queue_id' => $queue->id, 'user_id' => $userId, 'action' => $action, 'from_status' => $from, 'to_status' => $to, 'created_at' => now()]);
    }

    public function history(Request $request)
    {
        $date = $request->validate(['date' => ['nullable', 'date']])['date'] ?? today()->toDateString();
        $queues = Queue::whereDate('queue_date', $date)->orderBy('number')->get();
        return view('queues.history', compact('queues', 'date'));
    }

    public function qr() { return view('queues.qr'); }
}
