<?php

namespace App\Enums;

enum Permission: string
{
    case ClientsView = 'clients.view';
    case ClientsManage = 'clients.manage';
    case ArticlesView = 'articles.view';
    case ArticlesManage = 'articles.manage';
    case DocumentsView = 'documents.view';
    case DocumentsManage = 'documents.manage';
    case DocumentsDelete = 'documents.delete';
    case DocumentsStatus = 'documents.status';
    case DocumentsExport = 'documents.export';
    case UsersManage = 'users.manage';
}
