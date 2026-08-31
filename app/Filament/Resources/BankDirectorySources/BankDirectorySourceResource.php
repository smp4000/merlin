<?php

namespace App\Filament\Resources\BankDirectorySources;

use App\Filament\Resources\BankDirectorySources\Pages\EditBankDirectorySource;
use App\Filament\Resources\BankDirectorySources\Pages\ListBankDirectorySources;
use App\Models\BankDirectorySource;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ermöglicht ausschließlich Plattform-Super-Admins die sichere Bundesbank-Quelle zu
 * pflegen und den Stand ihrer versionierten Importe einzusehen.
 */
final class BankDirectorySourceResource extends Resource
{
    protected static ?string $model = BankDirectorySource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return 'Bankverzeichnis';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Systemkataloge';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Bezeichnung')->required()->maxLength(160),
            TextInput::make('provider')->label('Herausgeber')->disabled(),
            TextInput::make('url')
                ->label('Bundesbank-CSV-URL')
                ->url()
                ->required()
                ->rules(['regex:/^https:\/\/www\.bundesbank\.de\/.*\.csv$/i'])
                ->columnSpanFull(),
            Toggle::make('is_active')->label('Quelle aktiv'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Quelle')->weight('semibold'),
            TextColumn::make('last_imported_at')->label('Letzter Import')->dateTime('d.m.Y H:i')->placeholder('Noch nicht importiert'),
            TextColumn::make('versions.entry_count')->label('Einträge')->state(fn (BankDirectorySource $record): int => (int) ($record->versions()->where('status', 'active')->value('entry_count') ?? 0)),
            IconColumn::make('is_active')->label('Aktiv')->boolean(),
        ])->recordActions([
            EditAction::make(),
        ]);
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->isPlatformSuperAdmin() === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListBankDirectorySources::route('/'),
            'edit' => EditBankDirectorySource::route('/{record}/edit'),
        ];
    }
}
