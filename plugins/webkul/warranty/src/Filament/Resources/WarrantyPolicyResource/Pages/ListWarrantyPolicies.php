<?php

namespace Webkul\Warranty\Filament\Resources\WarrantyPolicyResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Warranty\Filament\Resources\WarrantyPolicyResource;

class ListWarrantyPolicies extends ListRecords
{
    protected static string $resource = WarrantyPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
