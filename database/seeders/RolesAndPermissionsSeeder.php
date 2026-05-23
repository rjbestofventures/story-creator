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

        Role::firstOrCreate(['name' => 'admin'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'partner'])
            ->syncPermissions([
                'story.create', 'story.view', 'story.edit', 'story.delete',
                'episode.create', 'episode.view', 'episode.edit', 'episode.delete', 'episode.publish',
                'credit.view',
                'billing.view',
                'settings.view', 'settings.manage',
            ]);

        Role::firstOrCreate(['name' => 'subscriber'])
            ->syncPermissions([
                'story.create', 'story.view', 'story.edit',
                'episode.create', 'episode.view', 'episode.edit', 'episode.publish',
                'credit.view',
                'billing.view',
                'settings.view',
            ]);

        Role::firstOrCreate(['name' => 'viewer'])
            ->syncPermissions([
                'story.view',
                'episode.view',
                'credit.view',
            ]);
    }
}
