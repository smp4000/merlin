<?php

namespace App\Enums;

/**
 * Definiert die im ersten Partnerkern erlaubten Behörden- und Registerkennungen.
 */
enum LegalEntityIdentifierType: string
{
    case VatId = 'vat_id';
    case NationalTaxNumber = 'national_tax_number';
    case EconomicId = 'economic_id';
    case CommercialRegister = 'commercial_register';
    case EmployerNumber = 'employer_number';
}
