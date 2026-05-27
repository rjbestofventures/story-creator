<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the two canonical roles exist and have correct permissions
        Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);

        // Move users with legacy roles → 'user'
        foreach (['partner', 'subscriber', 'viewer'] as $oldRole) {
            $role = Role::where('name', $oldRole)->where('guard_name', 'web')->first();
            if ($role) {
                foreach ($role->users as $user) {
                    $user->syncRoles(['user']);
                }
                $role->delete();
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
