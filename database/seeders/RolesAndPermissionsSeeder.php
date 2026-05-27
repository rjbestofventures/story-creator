<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Stories
            'story.create',
            'story.view',
            'story.edit',
            'story.delete',

            // Episodes
            'episode.create',
            'episode.view',
            'episode.edit',
            'episode.delete',
            'episode.publish',

            // Credits
            'credit.view',
            'credit.manage',

            // Billing
            'billing.view',
            'billing.manage',

            // Users (admin)
            'user.view',
            'user.manage',

            // Settings
            'settings.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin — full access to everything
        Role::firstOrCreate(['name' => 'admin'])
            ->syncPermissions(Permission::all());

        // User — access to their own stories and billing only
        Role::firstOrCreate(['name' => 'user'])
            ->syncPermissions([
                'story.create', 'story.view', 'story.edit', 'story.delete',
                'episode.create', 'episode.view', 'episode.edit', 'episode.delete', 'episode.publish',
                'credit.view',
                'billing.view',
                'settings.view',
            ]);
    }
}
