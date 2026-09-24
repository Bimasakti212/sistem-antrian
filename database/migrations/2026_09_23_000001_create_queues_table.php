<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_queue_counters', function (Blueprint $table) {
            $table->date('queue_date')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->date('queue_date')->index();
            $table->unsignedInteger('number');
            $table->string('code', 12);
            $table->string('name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('service', 180);
            $table->enum('status', ['waiting', 'called', 'serving', 'skipped', 'completed'])->default('waiting')->index();
            $table->timestamp('registered_at');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['queue_date', 'number']);
        });

        Schema::create('queue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_logs');
        Schema::dropIfExists('queues');
        Schema::dropIfExists('daily_queue_counters');
    }
};
