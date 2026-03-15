<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    private const SEEDER_NAME = 'AdminUserSeeder';

    /**
     * Seed the first administrator user.
     *
     * The user is created with a temporary password and an invitation token
     * so that they are forced to set their own password on first login.
     * Execution is limited to once via the seeder_logs table.
     */
    public function run(): void
    {
        if (DB::table('seeder_logs')->where('seeder_name', self::SEEDER_NAME)->exists()) {
            $this->command->warn("Seeder '" . self::SEEDER_NAME . "' already executed. Skipping...");
            return;
        }

        $temporaryPassword = Str::random(32);
        $invitationToken   = Str::random(64);

        $user = User::create([
            'uuid'               => Str::uuid()->toString(),
            'name'               => 'Administrateur Axontis',
            'email'              => 'admin@axontis.com',
            'password'           => Hash::make($temporaryPassword),
            'role'               => UserRole::ADMINISTRATOR,
            'is_active'          => true,
            'invitation_token'   => Hash::make($invitationToken),
            'invitation_sent_at' => now(),
        ]);

        // Envoyer l'email d'invitation pour définir le mot de passe
        $user->notify(new UserInvitation($invitationToken));

        DB::table('seeder_logs')->insert([
            'seeder_name' => self::SEEDER_NAME,
            'executed_at' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->command->info('Administrateur créé : ' . $user->email);
        $this->command->info('Un email d\'invitation a été envoyé pour configurer le mot de passe.');
    }
}


