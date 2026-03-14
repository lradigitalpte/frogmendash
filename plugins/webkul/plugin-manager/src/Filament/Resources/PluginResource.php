<?php

namespace Webkul\PluginManager\Filament\Resources;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DBSchema;
use RuntimeException;
use Throwable;
use Webkul\PluginManager\Filament\Resources\PluginResource\Pages\ListPlugins;
use Webkul\PluginManager\Models\CompanyPlugin;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;

class PluginResource extends Resource
{
    protected static ?string $model = Plugin::class;

    public static function getNavigationGroup(): string
    {
        return __('plugin-manager::filament/resources/plugin.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('plugin-manager::filament/resources/plugin.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('plugin-manager::filament/resources/plugin.title');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    IconColumn::make('package_icon')
                        ->label('')
                        ->state(true)
                        ->icon('heroicon-o-puzzle-piece')
                        ->size(IconSize::TwoExtraLarge)
                        ->color('primary')
                        ->visible(fn ($record) => ! $record?->package?->icon)
                        ->grow(false),

                    ImageColumn::make('package_image')
                        ->label('')
                        ->getStateUsing(fn ($record) => $record?->package?->icon
                            ? asset("svg/{$record->package->icon}.svg")
                            : null)
                        ->imageSize(100)
                        ->visible(fn ($record) => $record?->package?->icon)
                        ->grow(false),

                    Stack::make([
                        Split::make([
                            TextColumn::make('name')
                                ->weight('semibold')
                                ->searchable()
                                ->size(TextSize::Large)
                                ->formatStateUsing(fn (string $state) => ucfirst($state))
                                ->grow(false),

                            TextColumn::make('latest_version')
                                ->label(__('plugin-manager::filament/resources/plugin.table.version'))
                                ->default('1.0.0')
                                ->badge()
                                ->color('info'),
                        ]),

                        TextColumn::make('summary')
                            ->color('gray')
                            ->limit(80)
                            ->wrap(),

                        Split::make([
                            TextColumn::make('enabled_for_company')
                                ->badge()
                                ->inline()
                                ->grow(false)
                                ->label(__('Enabled for your company'))
                                ->formatStateUsing(fn ($record) => $record->isEnabledForCurrentCompany()
                                    ? __('plugin-manager::filament/resources/plugin.status.installed')
                                    : __('plugin-manager::filament/resources/plugin.status.not_installed'))
                                ->color(fn ($record) => $record->isEnabledForCurrentCompany() ? 'success' : 'gray'),

                            TextColumn::make('dependencies_count')
                                ->label(__('plugin-manager::filament/resources/plugin.table.dependencies'))
                                ->state(fn ($record) => count($record->getDependenciesFromConfig()))
                                ->badge()
                                ->color('warning')
                                ->suffix(__('plugin-manager::filament/resources/plugin.table.dependencies_suffix'))
                                ->default(0),
                        ]),
                    ])->space(1),
                ]),
            ])
            ->contentGrid([
                'sm'  => 1,
                'md'  => 2,
                'lg'  => 2,
                'xl'  => 3,
                '2xl' => 4,
            ])
            ->recordActions([
                ViewAction::make()->icon('heroicon-o-eye'),

                Action::make('install')
                    ->label(__('plugin-manager::filament/resources/plugin.actions.install.title'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->isEnabledForCurrentCompany())
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => __('plugin-manager::filament/resources/plugin.actions.install.heading', ['name' => $record->name]))
                    ->modalDescription(fn ($record) => __('Enable this plugin for your company. Other companies are not affected.'))
                    ->modalSubmitActionLabel(__('plugin-manager::filament/resources/plugin.actions.install.submit'))
                    ->action(function ($record) {
                        $companyId = \Illuminate\Support\Facades\Auth::user()?->default_company_id;
                        if (! $companyId) {
                            Notification::make()
                                ->title(__('No company'))
                                ->body(__('You must have a default company to enable plugins.'))
                                ->danger()
                                ->send();
                            return;
                        }
                        CompanyPlugin::firstOrCreate(
                            ['company_id' => $companyId, 'plugin_name' => $record->name]
                        );
                        Notification::make()
                            ->title(__('plugin-manager::filament/resources/plugin.notifications.installed.title'))
                            ->body(__('Enabled for your company. Other companies are not affected.'))
                            ->success()
                            ->send();
                    })
                    ->after(fn () => redirect(self::getUrl('index'))),

                Action::make('uninstall')
                    ->label(__('plugin-manager::filament/resources/plugin.actions.uninstall.title'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalWidth(Width::ExtraLarge)
                    ->visible(fn ($record) => $record->isEnabledForCurrentCompany())
                    ->modalHeading(__('plugin-manager::filament/resources/plugin.actions.uninstall.heading'))
                    ->modalSubmitActionLabel(__('plugin-manager::filament/resources/plugin.actions.uninstall.submit'))
                    ->modalDescription(__('Disable this plugin for your company only. Other companies are not affected.'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $companyId = \Illuminate\Support\Facades\Auth::user()?->default_company_id;
                        if ($companyId) {
                            CompanyPlugin::where('company_id', $companyId)
                                ->where('plugin_name', $record->name)
                                ->delete();
                        }
                        Notification::make()
                            ->title(__('plugin-manager::filament/resources/plugin.notifications.uninstalled.title'))
                            ->body(__('Disabled for your company. Other companies are not affected.'))
                            ->success()
                            ->send();
                    })
                    ->after(fn () => redirect(self::getUrl('index'))),
            ], position: RecordActionsPosition::BeforeColumns)
            ->recordActionsAlignment('end')
            ->defaultSort('sort', 'asc')
            ->paginated([16, 24, 32]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('plugin-manager::filament/resources/plugin.infolist.section.plugin'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('name')
                                ->label(__('plugin-manager::filament/resources/plugin.infolist.name'))
                                ->formatStateUsing(fn ($state) => ucfirst($state))
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('latest_version')
                                ->label(__('plugin-manager::filament/resources/plugin.infolist.version'))
                                ->badge()
                                ->color('info'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            IconEntry::make('enabled_for_company')
                                ->label(__('Enabled for your company'))
                                ->getStateUsing(fn ($record) => $record->isEnabledForCurrentCompany())
                                ->boolean()
                                ->trueIcon('heroicon-s-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            TextEntry::make('author')
                                ->label('Author')
                                ->badge(),
                        ]),

                    TextEntry::make('license')
                        ->label(__('plugin-manager::filament/resources/plugin.infolist.license'))
                        ->default('MIT')
                        ->badge()
                        ->color('success'),

                    TextEntry::make('summary')
                        ->label(__('plugin-manager::filament/resources/plugin.infolist.summary'))
                        ->columnSpanFull(),
                ]),

            Group::make([
                Section::make(__('plugin-manager::filament/resources/plugin.infolist.section.dependencies'))
                    ->schema([
                        self::repeatableEntry('dependencies', 'warning', 'dependencies-repeater'),
                        self::repeatableEntry('dependents', 'info', 'dependents-repeater'),
                    ]),
            ]),
        ]);
    }

    protected static function repeatableEntry(string $type, string $color, string $key): RepeatableEntry
    {
        return RepeatableEntry::make($type)
            ->label(__('plugin-manager::filament/resources/plugin.infolist.'.$key.'.title'))
            ->state(function ($record) use ($type) {
                return collect($record->{'get'.ucfirst($type).'FromConfig'}())->map(fn ($dep) => [
                    'name'         => $dep,
                    'is_installed' => Package::isPluginInstalled($dep),
                ]);
            })
            ->schema([
                TextEntry::make('name')
                    ->label(__('plugin-manager::filament/resources/plugin.infolist.'.$key.'.name'))
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->color($color),

                IconEntry::make('is_installed')
                    ->label(__('plugin-manager::filament/resources/plugin.infolist.'.$key.'.is_installed'))
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->columns(2)
            ->placeholder(__('plugin-manager::filament/resources/plugin.infolist.'.$key.'.placeholder'));
    }

    protected static function uninstallPlugin($record)
    {
        $errors = [];

        $dependents = $record->getDependentsFromConfig();

        collect($dependents)
            ->push($record->name)
            ->each(function ($pluginName) use (&$errors) {
                $plugin = Plugin::where('name', $pluginName)->first();

                if (! $plugin?->is_installed) {
                    return;
                }

                try {
                    if (! $plugin->package) {
                        throw new Exception("Package for '{$pluginName}' not found.");
                    }

                    collect(array_reverse($plugin->package->migrationFileNames))
                        ->each(function ($migration) use ($plugin) {
                            $fullPath = $plugin->package->basePath("database/migrations/{$migration}.php");

                            static::downMigration($fullPath, $migration);
                        });

                    collect($plugin->package->settingFileNames)
                        ->each(function ($setting) use ($plugin) {
                            $fullPath = $plugin->package->basePath("database/settings/{$setting}.php");

                            static::downMigration($fullPath, $setting);
                        });

                    $plugin->update(['is_installed' => false, 'is_active' => false]);
                } catch (Throwable $e) {
                    $errors[] = "Failed to uninstall '{$pluginName}': ".$e->getMessage();
                }
            });

        if (empty($errors)) {
            Notification::make()
                ->title(__('plugin-manager::filament/resources/plugin.notifications.uninstalled.title'))
                ->body(__('plugin-manager::filament/resources/plugin.notifications.uninstalled.body', ['name' => $record->name]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('plugin-manager::filament/resources/plugin.notifications.uninstalled-failed.title'))
                ->body(implode(' ', $errors))
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected static function downMigration(string $fullPath, string $migration): void
    {
        if (! file_exists($fullPath)) {
            return;
        }

        require_once $fullPath;

        $migrationInstance = require $fullPath;

        if (is_object($migrationInstance) && method_exists($migrationInstance, 'down')) {
            $migrationInstance->down();

            DB::table('migrations')->where('migration', $migration)->delete();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlugins::route('/'),
        ];
    }
}
