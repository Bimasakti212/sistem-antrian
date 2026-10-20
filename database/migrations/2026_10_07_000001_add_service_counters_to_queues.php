<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_counters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('service_counter_id')->nullable()->after('category_id')->constrained('service_counters')->nullOnDelete();
            $table->foreignId('called_by')->nullable()->after('registered_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('queue_logs', function (Blueprint $table) {
            $table->foreignId('service_counter_id')->nullable()->after('user_id')->constrained('service_counters')->nullOnDelete();
        });

        $now = now();
        DB::table('service_counters')->insert([
            ['name' => 'Loket 1', 'code' => 'L1', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Loket 2', 'code' => 'L2', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Loket 3', 'code' => 'L3', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_counter_id');
        });
        Schema::table('queues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_counter_id');
            $table->dropConstrainedForeignId('called_by');
        });
        Schema::dropIfExists('service_counters');
    }
};
