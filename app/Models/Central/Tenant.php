<?php

namespace App\Models\Central;

use App\Observers\TenantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

#[ObservedBy(TenantObserver::class)]
class Tenant extends BaseTenant
{
    protected $connection = 'landlord';

    protected $fillable = [
        'name',
        'domain',
        'database',
        'is_active',
    ];

    /**
     * Datos temporales para provisioning (no se persisten en BD).
     * Se usan en TenantObserver para crear el usuario admin inicial.
     */
    public ?string $admin_name = null;

    public ?string $admin_email = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the database name for this tenant.
     * Spatie's SwitchTenantDatabaseTask uses this to switch connections.
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }
}
