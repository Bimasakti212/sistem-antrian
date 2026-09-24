<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => env('STAFF_EMAIL', 'petugas@kecamatan.local')], [
            'name' => env('STAFF_NAME', 'Petugas Kecamatan'),
            'password' => Hash::make(env('STAFF_PASSWORD', 'GantiPasswordIni123!')),
        ]);
    }
}
