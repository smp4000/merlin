<?php

namespace App\Filament\Resources\Partners;

use App\Enums\TenantStatus;
use App\Enums\TenantType;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Models\Tenant;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stellt dem Plattform-Super-Admin ausschließlich Partner-Metadaten bereit.
 *
 * Die Resource enthält bewusst keine operativen Mandantendaten. Ein späterer Support-
 * Zugriff benötigt einen gesonderten, zeitlich begrenzten und auditierten Grant.
 */
final class PartnerResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 10;

    /**
     * Liefert die übersetzte Navigation ohne technische Modellbezeichnungen.
     */
    public static function getNavigationLabel(): string
    {
        return __('partners.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('partners.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('partners.labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('partners.labels.plural');
    }

    /**
     * Konfiguriert eine datenarme Plattformliste mit Suche und Lifecycle-Filtern.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label(__('partners.fields.partner_number'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('display_name')
                    ->label(__('partners.fields.display_name'))
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('partners.fields.type'))
                    ->formatStateUsing(fn (TenantType $state): string => __("partners.types.{$state->value}"))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('partners.fields.status'))
                    ->formatStateUsing(fn (TenantStatus $state): string => __("partners.statuses.{$state->value}"))
                    ->badge(),
                TextColumn::make('country_code')
                    ->label(__('partners.fields.country'))
                    ->formatStateUsing(fn (string $state): string => __("registration.countries.{$state}")),
                TextColumn::make('trial.ends_at')
                    ->label(__('partners.fields.trial_ends_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('partners.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('partners.fields.status'))
                    ->options(collect(TenantStatus::cases())->mapWithKeys(
                        fn (TenantStatus $status): array => [$status->value => __("partners.statuses.{$status->value}")],
                    )),
                SelectFilter::make('country_code')
                    ->label(__('partners.fields.country'))
                    ->options(collect(config('merlin.registration.supported_countries'))->mapWithKeys(
                        fn (string $country): array => [$country => __("registration.countries.{$country}")],
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    /**
     * Verhindert, dass normale Partnerbenutzer die globale Partnerliste entdecken.
     */
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->isPlatformSuperAdmin() === true;
    }

    /**
     * Die Anlage läuft ausschließlich über die sichere Owner-Einladung der Listenseite.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Erzwingt auch auf Query-Ebene die Plattformrolle als zweite Schutzschicht.
     */
    public static function getEloquentQuery(): Builder
    {
        abort_unless(self::canViewAny(), 403);

        return parent::getEloquentQuery()->with('trial');
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
        ];
    }
}
