<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('code')->constrained('queue_categories')->nullOnDelete();
            $table->string('source', 20)->nullable()->after('service');
            $table->timestamp('serving_at')->nullable()->after('called_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('petugas')->after('password');
        });

        $now = now();
        DB::table('queue_categories')->insert([
            ['name' => 'Administrasi Kependudukan', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Surat Keterangan', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pelayanan Umum', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Layanan Lainnya', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $firstUserId = DB::table('users')->min('id');
        if ($firstUserId !== null) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['source', 'serving_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::dropIfExists('queue_categories');
    }
};
