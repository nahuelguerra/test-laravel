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
    ];

    /**
     * Get the database name for this tenant.
     * Spatie's SwitchTenantDatabaseTask uses this to switch connections.
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }
}
