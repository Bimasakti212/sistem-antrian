<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\QueueCategory;
use App\Models\ServiceCounter;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends Controller
{
    public function publicForm()
    {
        return view('queues.public', ['categories' => QueueCategory::where('is_active', true)->orderBy('name')->get()]);
    }

    public function storePublic(Request $request)
    {
        try {
            $queue = $this->register($this->validated($request), null, 'qr');

            return redirect()->temporarySignedRoute('queues.result', now()->addHours(12), $queue);
        } catch (QueryException) {
            return back()->withInput()->with('registration_error', 'Maaf, pendaftaran belum berhasil. Silakan coba kembali atau minta bantuan petugas.');
        }
    }

    public function result(Queue $queue)
    {
        abort_unless($queue->queue_date->isToday(), 404);
        $queue->load('category');

        return view('queues.result', compact('queue'));
    }

    public function dashboard(Request $request)
    {
        $data = $request->validate([
            'counter_id' => ['nullable', 'integer', Rule::exists('service_counters', 'id')->where('is_active', true)],
        ]);
        $counters = ServiceCounter::where('is_active', true)->orderBy('id')->get();
        $counterId = $data['counter_id'] ?? session('active_counter_id');
        $activeCounter = $counters->firstWhere('id', (int) $counterId) ?? $counters->first();
        $request->session()->put('active_counter_id', $activeCounter?->id);

        $today = today()->toDateString();
        $current = $activeCounter
            ? Queue::with('category', 'serviceCounter')
                ->whereDate('queue_date', $today)
                ->where('service_counter_id', $activeCounter->id)
                ->whereIn('status', ['called', 'serving'])
                ->orderByDesc('called_at')
                ->first()
            : null;
        $queues = Queue::with('category', 'serviceCounter')->whereDate('queue_date', $today)->orderBy('number')->get();
        $skipped = Queue::with('category')->whereDate('queue_date', $today)->where('status', 'skipped')->orderBy('number')->get();
        $activeCategories = QueueCategory::where('is_active', true)->orderBy('name')->get();
        $counts = $queues->countBy('status');

        return view('queues.dashboard', compact('current', 'queues', 'skipped', 'counts', 'activeCategories', 'counters', 'activeCounter'));
    }

    public function storeManual(Request $request)
    {
        try {
            $queue = $this->register($this->validated($request), $request->user()->id, 'manual');

            return response()->json(['message' => 'Antrean berhasil didaftarkan.', 'queue' => $queue->load('category')], Response::HTTP_CREATED);
        } catch (QueryException) {
            return response()->json(['message' => 'Pendaftaran belum berhasil. Silakan coba kembali.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'category_id' => ['required', 'integer', Rule::exists('queue_categories', 'id')->where('is_active', true)],
            'service' => ['required', 'string', 'min:3', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function register(array $data, ?int $userId, string $source): Queue
    {
        try {
            return DB::transaction(function () use ($data, $userId, $source) {
                $date = today()->toDateString();
                DB::table('daily_queue_counters')->insertOrIgnore(['queue_date' => $date, 'last_number' => 0]);
                $counter = DB::table('daily_queue_counters')->where('queue_date', $date)->lockForUpdate()->first();
                $number = $counter->last_number + 1;
                DB::table('daily_queue_counters')->where('queue_date', $date)->update(['last_number' => $number]);
                $queue = Queue::create([
                    'queue_date' => $date, 'number' => $number,
                    'code' => 'A-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                    'category_id' => $data['category_id'], 'name' => $data['name'],
                    'service' => $data['service'], 'phone' => $data['phone'] ?? null,
                    'source' => $source, 'status' => 'waiting', 'registered_at' => now(),
                    'registered_by' => $userId,
                ]);
                $this->log($queue, 'registered', null, 'waiting', $userId);

                return $queue;
            }, 3);
        } catch (QueryException $exception) {
            Log::error('Queue registration failed.', ['exception' => $exception]);
            throw $exception;
        }
    }

    public function callNext(Request $request)
    {
        $data = $request->validate([
            'counter_id' => ['required', 'integer', Rule::exists('service_counters', 'id')->where('is_active', true)],
        ]);

        $result = DB::transaction(function () use ($request, $data) {
            // One short shared lock serializes queue claims across counters while service remains parallel.
            DB::table('daily_queue_counters')->where('queue_date', today()->toDateString())->lockForUpdate()->first();
            $counter = ServiceCounter::whereKey($data['counter_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $active = Queue::whereDate('queue_date', today())
                ->where('service_counter_id', $counter->id)
                ->whereIn('status', ['called', 'serving'])
                ->lockForUpdate()
                ->first();
            if ($active) {
                return ['conflict' => true];
            }

            $item = Queue::whereDate('queue_date', today())->where('status', 'waiting')->orderBy('number')->lockForUpdate()->first();
            if (! $item) {
                return ['queue' => null];
            }
            $item->update([
                'status' => 'called', 'called_at' => now(),
                'service_counter_id' => $counter->id, 'called_by' => $request->user()->id,
            ]);
            $this->log($item, 'called', 'waiting', 'called', $request->user()->id, $counter->id);

            return ['queue' => $item->fresh('category'), 'counter' => $counter];
        }, 3);

        if (isset($result['conflict'])) {
            return response()->json(['message' => 'Loket ini masih memiliki antrean aktif. Selesaikan atau lewati antrean tersebut terlebih dahulu.'], Response::HTTP_CONFLICT);
        }

        return response()->json([
            'queue' => $result['queue'] ?? null,
            'counter' => $result['counter'] ?? null,
            'message' => ($result['queue'] ?? null) ? 'Antrean dipanggil.' : 'Tidak ada antrean menunggu.',
        ]);
    }

    public function action(Request $request, Queue $queue, string $action)
    {
        abort_unless($queue->queue_date->isToday(), 404);
        $data = $request->validate([
            'counter_id' => ['required', 'integer', Rule::exists('service_counters', 'id')->where('is_active', true)],
        ]);

        $result = DB::transaction(function () use ($request, $queue, $action, $data) {
            DB::table('daily_queue_counters')->where('queue_date', today()->toDateString())->lockForUpdate()->first();
            $counter = ServiceCounter::whereKey($data['counter_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $item = Queue::whereKey($queue->id)->lockForUpdate()->firstOrFail();

            if ($action === 'recall') {
                if (! in_array($item->status, ['called', 'skipped'], true)) {
                    return ['conflict' => true];
                }
                if ($item->status === 'called' && $item->service_counter_id !== $counter->id) {
                    return ['conflict' => true];
                }
                if ($item->status === 'skipped') {
                    $counterBusy = Queue::whereDate('queue_date', today())
                        ->where('service_counter_id', $counter->id)
                        ->whereIn('status', ['called', 'serving'])
                        ->exists();
                    if ($counterBusy) {
                        return ['conflict' => true];
                    }
                }
                $from = $item->status;
                $item->update([
                    'status' => 'called', 'called_at' => now(),
                    'serving_at' => null, 'completed_at' => null,
                    'service_counter_id' => $counter->id, 'called_by' => $request->user()->id,
                ]);
                $this->log($item, 'recall', $from, 'called', $request->user()->id, $counter->id);

                return ['queue' => $item->fresh('category'), 'counter' => $counter];
            }

            if ($item->service_counter_id !== $counter->id) {
                return ['conflict' => true];
            }
            $transitions = [
                'serve' => [['called'], 'serving'],
                'skip' => [['called', 'serving'], 'skipped'],
                'complete' => [['serving'], 'completed'],
            ];
            [$allowed, $next] = $transitions[$action];
            if (! in_array($item->status, $allowed, true)) {
                return ['conflict' => true];
            }

            $from = $item->status;
            $changes = ['status' => $next];
            if ($action === 'serve') {
                $changes['serving_at'] = now();
            }
            if ($action === 'complete') {
                $changes['completed_at'] = now();
            }
            $item->update($changes);
            $this->log($item, $action === 'serve' ? 'serving' : ($action === 'complete' ? 'completed' : $action), $from, $next, $request->user()->id, $counter->id);

            return ['queue' => $item->fresh('category'), 'counter' => $counter];
        }, 3);

        if (isset($result['conflict'])) {
            return response()->json(['message' => 'Status antrean atau loket sudah berubah. Muat ulang dashboard lalu coba lagi.'], Response::HTTP_CONFLICT);
        }

        return response()->json(['queue' => $result['queue'], 'counter' => $result['counter'], 'message' => 'Status antrean diperbarui.']);
    }

    private function log(Queue $queue, string $action, ?string $from, ?string $to, ?int $userId, ?int $counterId = null): void
    {
        DB::table('queue_logs')->insert([
            'queue_id' => $queue->id, 'user_id' => $userId,
            'service_counter_id' => $counterId,
            'action' => $action, 'from_status' => $from, 'to_status' => $to, 'created_at' => now(),
        ]);
    }

    public function history(Request $request)
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'date'])],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $period = $filters['period'] ?? ($request->filled('date') ? 'date' : 'today');
        $date = match ($period) {
            'yesterday' => today()->subDay()->toDateString(),
            'date' => $filters['date'] ?? today()->toDateString(),
            default => today()->toDateString(),
        };
        $queues = Queue::with('category', 'serviceCounter', 'caller')
            ->whereDate('queue_date', $date)->orderBy('number')->paginate(30)->withQueryString();

        return view('queues.history', compact('queues', 'date', 'period'));
    }

    public function qr(Request $request)
    {
        $host = strtolower(rtrim($request->getHost(), '.'));
        $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $isLocalOnlyHost = in_array($host, ['localhost', 'localhost.localdomain', '0.0.0.0', '::', '::1'], true)
            || preg_match('/\.(?:localhost|localdomain|test|invalid|example|local|lan|internal)$/', $host) === 1
            || (! $isIpAddress && ! str_contains($host, '.'))
            || (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && str_starts_with($host, '127.'))
            || (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && in_array(inet_ntop(inet_pton($host)), ['::', '::1'], true));

        $registrationUrl = $isLocalOnlyHost
            ? null
            : rtrim($request->getSchemeAndHttpHost(), '/').route('queues.public', [], false);

        return view('queues.qr', compact('registrationUrl'));
    }

    public function staff()
    {
        return view('queues.staff', ['staff' => User::orderBy('name')->paginate(30)]);
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'petugas']);

        return back()->with('success', 'Akun petugas berhasil dibuat.');
    }

    public function categories()
    {
        return view('queues.categories', ['categories' => QueueCategory::withCount('queues')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:queue_categories,name']]);
        QueueCategory::create($data);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, QueueCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('queue_categories', 'name')->ignore($category->id)],
            'is_active' => ['required', 'boolean'],
        ]);
        $category->update($data);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function counters()
    {
        return view('queues.counters', ['counters' => ServiceCounter::withCount('queues')->orderBy('id')->get()]);
    }

    public function storeCounter(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:service_counters,code'],
        ]);
        ServiceCounter::create($data + ['is_active' => true]);

        return back()->with('success', 'Loket berhasil ditambahkan.');
    }

    public function updateCounter(Request $request, ServiceCounter $counter)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('service_counters', 'code')->ignore($counter->id)],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! $data['is_active'] && Queue::where('service_counter_id', $counter->id)->whereIn('status', ['called', 'serving'])->exists()) {
            return back()->withErrors(['is_active' => 'Selesaikan atau lewati antrean aktif di loket ini sebelum menonaktifkannya.']);
        }
        $counter->update($data);

        return back()->with('success', 'Loket berhasil diperbarui.');
    }
}
