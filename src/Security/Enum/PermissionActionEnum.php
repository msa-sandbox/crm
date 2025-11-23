<?php

namespace App\Security\Enum;

enum PermissionActionEnum: string
{
    case READ = 'read';
    case WRITE = 'write';
    case DELETE = 'delete';
    case IMPORT = 'import';
    case EXPORT = 'export';
}
