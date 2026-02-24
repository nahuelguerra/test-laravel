<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from the Enum
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // Super Admin — gets everything (only in landlord DB)
        Role::findOrCreate(RoleEnum::SuperAdmin->value)
            ->givePermissionTo(Permission::all());

        // Tenant Admin — full access within tenant
        Role::findOrCreate(RoleEnum::TenantAdmin->value)
            ->givePermissionTo(Permission::all());

        // Manager — supervises operations, approves transactions
        Role::findOrCreate(RoleEnum::Manager->value)
            ->givePermissionTo([
                PermissionEnum::UserView->value,
                PermissionEnum::SubscriberCreate->value,
                PermissionEnum::SubscriberEdit->value,
                PermissionEnum::SubscriberView->value,
                PermissionEnum::ContractCreate->value,
                PermissionEnum::ContractEdit->value,
                PermissionEnum::ContractView->value,
                PermissionEnum::PaymentCreate->value,
                PermissionEnum::PaymentView->value,
                PermissionEnum::DrawExecute->value,
                PermissionEnum::DrawView->value,
                PermissionEnum::ReportGenerate->value,
                PermissionEnum::ReportView->value,
            ]);

        // Agente Comercial — creates subscribers, contracts, registers payments
        Role::findOrCreate(RoleEnum::AgenteComercial->value)
            ->givePermissionTo([
                PermissionEnum::SubscriberCreate->value,
                PermissionEnum::SubscriberEdit->value,
                PermissionEnum::SubscriberView->value,
                PermissionEnum::ContractCreate->value,
                PermissionEnum::ContractView->value,
                PermissionEnum::PaymentCreate->value,
                PermissionEnum::PaymentView->value,
            ]);

        // Atencion al Cliente — queries subscriber info
        Role::findOrCreate(RoleEnum::AtencionCliente->value)
            ->givePermissionTo([
                PermissionEnum::SubscriberView->value,
                PermissionEnum::ContractView->value,
                PermissionEnum::PaymentView->value,
            ]);

        // Contador — payments, financial reports
        Role::findOrCreate(RoleEnum::Contador->value)
            ->givePermissionTo([
                PermissionEnum::PaymentCreate->value,
                PermissionEnum::PaymentView->value,
                PermissionEnum::ReportGenerate->value,
                PermissionEnum::ReportView->value,
                PermissionEnum::SubscriberView->value,
                PermissionEnum::ContractView->value,
            ]);

        // Auditor — read only + audit logs
        Role::findOrCreate(RoleEnum::Auditor->value)
            ->givePermissionTo([
                PermissionEnum::UserView->value,
                PermissionEnum::SubscriberView->value,
                PermissionEnum::ContractView->value,
                PermissionEnum::PaymentView->value,
                PermissionEnum::DrawView->value,
                PermissionEnum::ReportView->value,
                PermissionEnum::AuditView->value,
            ]);

        // Cliente — sees own profile, plans, payments
        Role::findOrCreate(RoleEnum::Cliente->value)
            ->givePermissionTo([
                PermissionEnum::ContractView->value,
                PermissionEnum::PaymentView->value,
            ]);
    }
}