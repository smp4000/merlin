<?php

namespace App\Filament\Resources\BankDirectorySources\Pages;

use App\Filament\Resources\BankDirectorySources\BankDirectorySourceResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Zeigt die bewusst einzelne, zentral kontrollierte Bankverzeichnisquelle.
 */
final class ListBankDirectorySources extends ListRecords
{
    protected static string $resource = BankDirectorySourceResource::class;
}
