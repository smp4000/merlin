<?php

namespace App\Filament\Resources\BankDirectorySources\Pages;

use App\Filament\Resources\BankDirectorySources\BankDirectorySourceResource;
use App\Foundation\Audit\AuditRecorder;
use App\Modules\Banking\Application\ImportBankDirectory;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

/**
 * Ändert die freigegebene Quell-URL und bietet einen kontrollierten Sofortimport an.
 */
final class EditBankDirectorySource extends EditRecord
{
    protected static string $resource = BankDirectorySourceResource::class;

    /** @return list<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Jetzt importieren')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (ImportBankDirectory $importer, AuditRecorder $audit): void {
                    $version = $importer->handle($this->record);
                    $audit->record(
                        'platform.bank_directory.imported',
                        'bank_directory_version',
                        (string) $version->getKey(),
                        (string) Str::uuid(),
                        ['entry_count' => $version->entry_count, 'sha256' => $version->sha256],
                        actor: Filament::auth()->user(),
                    );
                    Notification::make()->title("{$version->entry_count} Bankdatensätze importiert")->success()->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        app(AuditRecorder::class)->record(
            'platform.bank_directory.source_updated',
            'bank_directory_source',
            (string) $this->record->getKey(),
            (string) Str::uuid(),
            ['host' => $this->record->allowed_host, 'is_active' => $this->record->is_active],
            actor: Filament::auth()->user(),
        );
    }
}
