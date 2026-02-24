<?php

namespace App\Observers;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->database)) {
            $tenant->database = 'tenant_' . time();
        }
    }

    public function created(Tenant $tenant): void
    {
        $this->createDatabase($tenant);
        $this->runMigrations($tenant);
        $this->seedRolesAndPermissions($tenant);

        Log::info('Tenant provisioned', [
            'tenant_id' => $tenant->id,
            'database' => $tenant->database,
        ]);
    }

    private function seedRolesAndPermissions(Tenant $tenant): void
    {
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'db:seed --class=RolesAndPermissionsSeeder --force',
            '--tenant' => $tenant->id,
        ]);
    }

    public function deleted(Tenant $tenant): void
    {
        $dbName = $tenant->database;

        DB::connection('landlord')
            ->statement("DROP DATABASE IF EXISTS `{$dbName}`");

        Log::info('Tenant database dropped', [
            'tenant_id' => $tenant->id,
            'database' => $dbName,
        ]);
    }

    private function createDatabase(Tenant $tenant): void
    {
        DB::connection('landlord')
            ->statement(
                "CREATE DATABASE `{$tenant->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
    }

    private function runMigrations(Tenant $tenant): void
    {
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --path=database/migrations/tenant --force',
            '--tenant' => $tenant->id,
        ]);
    }
}
