<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Tenants
    case TenantManage = 'tenant.manage';
    case TenantView = 'tenant.view';

    // Users
    case UserCreate = 'user.create';
    case UserEdit = 'user.edit';
    case UserDelete = 'user.delete';
    case UserView = 'user.view';

    // Subscribers
    case SubscriberCreate = 'subscriber.create';
    case SubscriberEdit = 'subscriber.edit';
    case SubscriberDelete = 'subscriber.delete';
    case SubscriberView = 'subscriber.view';

    // Contracts
    case ContractCreate = 'contract.create';
    case ContractEdit = 'contract.edit';
    case ContractView = 'contract.view';

    // Payments
    case PaymentCreate = 'payment.create';
    case PaymentView = 'payment.view';

    // Draws
    case DrawExecute = 'draw.execute';
    case DrawView = 'draw.view';

    // Reports
    case ReportGenerate = 'report.generate';
    case ReportView = 'report.view';

    // Audit
    case AuditView = 'audit.view';
}