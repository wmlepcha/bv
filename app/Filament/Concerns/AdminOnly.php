<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/**
 * Hides a Filament resource or page from everyone except admins (navigation, URLs and Livewire actions).
 * The sales team only gets the few resources and pages that do not use this trait.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->isAdmin() === true;
    }
}
