<?php

namespace App\Filament\Pages;

use App\Services\DatabaseBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Security\Models\User;

/**
 * Platform-admin-only page to download an on-demand database backup. The dump is
 * streamed straight to the browser (see DatabaseBackupService) — never stored in
 * the public S3 bucket.
 */
class DatabaseBackups extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-circle-stack';

    protected string $view = 'filament.pages.database-backups';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('Database Backups');
    }

    public function getTitle(): string
    {
        return __('Database Backups');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.setting');
    }

    public static function canAccess(): bool
    {
        return User::isPlatformAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return User::isPlatformAdmin();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label(__('Download backup'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(fn (): StreamedResponse => $this->downloadBackup()),
        ];
    }

    public function downloadBackup(): StreamedResponse
    {
        abort_unless(User::isPlatformAdmin(), 403);

        $service = app(DatabaseBackupService::class);

        return response()->streamDownload(
            function () use ($service): void {
                $service->streamTo(fopen('php://output', 'w'));
            },
            $service->filename(),
            ['Content-Type' => 'application/sql'],
        );
    }
}
