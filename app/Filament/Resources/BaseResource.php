<?php

namespace App\Filament\Resources;

use App\Helpers\PermissionHelper;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseResource extends Resource
{
    public const VIEW_ACTION = "view";
    public const CREATE_ACTION = "create";
    public const UPDATE_ACTION = "update";
    public const DELETE_ACTION = "delete";

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return PermissionHelper::checkPermission(static::VIEW_ACTION, "admin/" . static::getSlug());
    }

    public static function canCreate(): bool
    {
        return PermissionHelper::checkPermission(static::CREATE_ACTION, 'admin/' . static::getSlug());
    }

    public static function canDelete(Model $record): bool
    {
        return PermissionHelper::checkPermission(static::DELETE_ACTION, 'admin/' . static::getSlug());
    }

    public static function canEdit(Model $record): bool
    {
        return PermissionHelper::checkPermission(static::UPDATE_ACTION, 'admin/' . static::getSlug());
    }

    public static function canBulkDelete(): bool
    {
        return PermissionHelper::checkPermission(static::DELETE_ACTION, 'admin/' . static::getSlug());
    }
}
