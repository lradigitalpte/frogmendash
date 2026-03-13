<?php

namespace Webkul\Warranty\Filament\Resources\WarrantyPolicyResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Warranty\Filament\Resources\WarrantyPolicyResource;

class EditWarrantyPolicy extends EditRecord
{
    protected static string $resource = WarrantyPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
