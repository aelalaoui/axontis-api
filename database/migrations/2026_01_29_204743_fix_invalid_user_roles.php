<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix invalid 'user' role to 'operator' (or you can choose another default role)
        // Check for any invalid roles and update them
        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'client']);

        // Also fix any other potential invalid roles
        $validRoles = ['client', 'technician', 'operator', 'manager', 'administrator'];

        DB::table('users')
            ->whereNotIn('role', $validRoles)
            ->update(['role' => 'client']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data fix
    }
};

