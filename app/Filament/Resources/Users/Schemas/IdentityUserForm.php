<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;

class IdentityUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(UserIdentityForm::components());
    }
}
