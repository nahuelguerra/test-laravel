<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'super_admin';
    case TenantAdmin = 'tenant_admin';
    case Manager = 'manager';
    case AgenteComercial = 'agente_comercial';
    case AtencionCliente = 'atencion_cliente';
    case Contador = 'contador';
    case Auditor = 'auditor';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrador',
            self::TenantAdmin => 'Administrador',
            self::Manager => 'Gerente',
            self::AgenteComercial => 'Agente Comercial',
            self::AtencionCliente => 'Atención al Cliente',
            self::Contador => 'Contador',
            self::Auditor => 'Auditor',
            self::Cliente => 'Cliente',
        };
    }

    /** Roles that exist only in tenant databases (not in landlord) */
    public static function tenantRoles(): array
    {
        return [
            self::TenantAdmin,
            self::Manager,
            self::AgenteComercial,
            self::AtencionCliente,
            self::Contador,
            self::Auditor,
            self::Cliente,
        ];
    }
}