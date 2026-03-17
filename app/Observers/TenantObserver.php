<?php

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Models\Central\Tenant;
use App\Models\User;
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
        $previousTenant = Tenant::checkCurrent() ? Tenant::current() : null;
        $originalDatabase = config('database.connections.mariadb.database');

        $this->createDatabase($tenant);

        // Usar makeCurrent() de Spatie para cambiar la conexión mariadb al tenant nuevo.
        $tenant->makeCurrent();

        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => 'RolesAndPermissionsSeeder',
            '--force' => true,
        ]);

        $this->createAdminUser($tenant);

        // Restaurar la conexión al estado original.
        // Spatie forgetCurrent() pone database en null y registra un resolver via
        // app('db')->extend() que siempre devuelve database=null. Para restaurar
        // correctamente, usamos el método execute() pattern de Spatie.
        if ($previousTenant) {
            $previousTenant->makeCurrent();
        } else {
            Tenant::forgetCurrent();
            // forgetCurrent() dejó la conexión en null. Restaurar manualmente:
            // 1. Poner el valor correcto en config
            config(['database.connections.mariadb.database' => $originalDatabase]);
            // 2. Re-registrar el resolver de conexión con el database correcto
            app('db')->extend('mariadb', function ($config, $name) use ($originalDatabase) {
                $config['database'] = $originalDatabase;

                return app('db.factory')->make($config, $name);
            });
            // 3. Purgar la conexión cacheada para que se recree
            DB::purge('mariadb');
        }

        Log::info('Tenant provisioned', [
            'tenant_id' => $tenant->id,
            'database' => $tenant->database,
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

    private function createAdminUser(Tenant $tenant): void
    {
        $name = $tenant->admin_name ?: 'Administrador';
        $email = $tenant->admin_email ?: 'admin@' . explode('.', $tenant->domain)[0] . '.com';

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin->assignRole(RoleEnum::TenantAdmin->value);

        Log::info('Tenant admin user created', [
            'tenant_id' => $tenant->id,
            'email' => $email,
        ]);
    }

    private function createDatabase(Tenant $tenant): void
    {
        DB::connection('landlord')
            ->statement(
                "CREATE DATABASE `{$tenant->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
    }
}
