<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Manager => 'Gestionnaire',
            self::Accountant => 'Comptable',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Manager => [
                Permission::ClientsView, Permission::ClientsManage,
                Permission::ArticlesView, Permission::ArticlesManage,
                Permission::DocumentsView, Permission::DocumentsManage,
                Permission::DocumentsDelete, Permission::DocumentsStatus,
                Permission::DocumentsExport,
            ],
            self::Accountant => [
                Permission::ClientsView, Permission::ArticlesView,
                Permission::DocumentsView, Permission::DocumentsStatus,
                Permission::DocumentsExport,
            ],
        };
    }
}
