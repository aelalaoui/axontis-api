<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seederName = 'AdminUserSeeder';

        // Skip if already executed
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            return;
        }

        // Check if admin user already exists
        if (User::where('email', 'admin@axontis.com')->exists()) {
            DB::table('seeder_logs')->insert([
                'seeder_name' => $seederName,
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        $temporaryPassword = Str::random(32);
        $invitationToken = Str::random(64);

        User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Administrateur Axontis',
            'email' => 'admin@axontis.com',
            'password' => Hash::make($temporaryPassword),
            'role' => UserRole::ADMINISTRATOR,
            'is_active' => true,
            'invitation_token' => Hash::make($invitationToken),
            'invitation_sent_at' => now(),
        ]);

        DB::table('seeder_logs')->insert([
            'seeder_name' => $seederName,
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $seederName = 'AdminUserSeeder';

        // Delete admin user if it exists
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            User::where('email', 'admin@axontis.com')->delete();
        }

        // Remove seeder log
        DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->delete();
    }
};

