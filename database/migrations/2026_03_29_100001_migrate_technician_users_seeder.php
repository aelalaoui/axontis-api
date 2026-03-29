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
        $seederName = 'TechnicianUsersSeeder';

        // Skip if already executed
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            return;
        }

        $technicians = [
            [
                'name' => 'Technicien SAV 1',
                'email' => 'sav_one@axontis.com',
            ],
            [
                'name' => 'Technicien SAV 2',
                'email' => 'sav_two@axontis.com',
            ],
        ];

        foreach ($technicians as $technicianData) {
            // Check if technician user already exists
            if (User::where('email', $technicianData['email'])->exists()) {
                continue;
            }

            $temporaryPassword = Str::random(32);
            $invitationToken = Str::random(64);

            User::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $technicianData['name'],
                'email' => $technicianData['email'],
                'password' => Hash::make($temporaryPassword),
                'role' => UserRole::TECHNICIAN,
                'is_active' => true,
                'invitation_token' => Hash::make($invitationToken),
                'invitation_sent_at' => now(),
            ]);
        }

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
        $seederName = 'TechnicianUsersSeeder';

        // Delete technician users if they exist
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            User::where('email', 'sav_one@axontis.com')->delete();
            User::where('email', 'sav_two@axontis.com')->delete();
        }

        // Remove seeder log
        DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->delete();
    }
};

