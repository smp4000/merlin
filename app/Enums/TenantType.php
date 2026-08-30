<?php

namespace App\Enums;

/**
 * Unterscheidet einen einzelnen Betreiber von einer Unternehmensgruppe.
 */
enum TenantType: string
{
    case SingleOperator = 'single_operator';
    case CompanyGroup = 'company_group';
}
