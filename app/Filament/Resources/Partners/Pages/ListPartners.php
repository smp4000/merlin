<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Enums\TenantType;
use App\Filament\Resources\Partners\PartnerResource;
use App\Models\User;
use App\Modules\Registration\Application\Data\InvitePartnerData;
use App\Modules\Registration\Application\InvitePartner;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Str;

/**
 * Zeigt die Plattform-Partnerliste und startet die sichere Owner-Einladung.
 */
final class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    /**
     * Stellt die manuelle Anlage als frisches Tab-Formular im Modal bereit.
     *
     * Das Formular erzeugt noch keinen Mandanten. Erst der eingeladene Owner bestätigt
     * E-Mail, Rechtstexte und Passwort; anschließend beginnt die 14-Tage-Testphase.
     *
     * @return list<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('invitePartner')
                ->label(__('partners.actions.invite'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading(__('partners.invitation.title'))
                ->modalDescription(__('partners.invitation.description'))
                ->modalSubmitActionLabel(__('partners.actions.send_invitation'))
                ->schema([
                    Tabs::make('partnerInvitation')
                        ->tabs([
                            Tab::make(__('partners.tabs.partner'))
                                ->icon('heroicon-o-building-office-2')
                                ->schema([
                                    TextInput::make('partner_display_name')
                                        ->label(__('partners.fields.display_name'))
                                        ->required()
                                        ->maxLength(160),
                                    Select::make('tenant_type')
                                        ->label(__('partners.fields.type'))
                                        ->options(collect(TenantType::cases())->mapWithKeys(
                                            fn (TenantType $type): array => [$type->value => __("partners.types.{$type->value}")],
                                        ))
                                        ->default(TenantType::SingleOperator->value)
                                        ->required(),
                                    Select::make('country_code')
                                        ->label(__('partners.fields.country'))
                                        ->options(collect(config('merlin.registration.supported_countries'))->mapWithKeys(
                                            fn (string $country): array => [$country => __("registration.countries.{$country}")],
                                        ))
                                        ->default('DE')
                                        ->required(),
                                    Select::make('locale')
                                        ->label(__('partners.fields.locale'))
                                        ->options(collect(config('merlin.registration.supported_locales'))->mapWithKeys(
                                            fn (string $locale): array => [$locale => __("registration.locales.{$locale}")],
                                        ))
                                        ->default('de')
                                        ->required(),
                                ])
                                ->columns(2),
                            Tab::make(__('partners.tabs.owner'))
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TextInput::make('first_name')
                                        ->label(__('registration.attributes.first_name'))
                                        ->required()
                                        ->maxLength(80),
                                    TextInput::make('last_name')
                                        ->label(__('registration.attributes.last_name'))
                                        ->required()
                                        ->maxLength(80),
                                    TextInput::make('owner_email')
                                        ->label(__('partners.fields.owner_email'))
                                        ->email()
                                        ->required()
                                        ->maxLength(254)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, InvitePartner $invitePartner): void {
                    /** @var User $actor */
                    $actor = Filament::auth()->user();

                    $invitePartner->handle($actor, new InvitePartnerData(
                        $data['first_name'],
                        $data['last_name'],
                        $data['owner_email'],
                        $data['partner_display_name'],
                        TenantType::from($data['tenant_type']),
                        $data['country_code'],
                        $data['locale'],
                        (string) Str::uuid(),
                    ));

                    Notification::make()
                        ->title(__('partners.invitation.sent_title'))
                        ->body(__('partners.invitation.sent_body'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
