<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | PEPPOL BIS 3.0 — Supplier (Motivya) Legal Entity
    |--------------------------------------------------------------------------
    |
    | These values are used to populate the AccountingSupplierParty element
    | in all generated PEPPOL BIS 3.0 UBL invoices and credit notes.
    |
    */

    'supplier' => [
        'name' => env('PEPPOL_SUPPLIER_NAME', 'Motivya'),
        'enterprise_number' => env('PEPPOL_SUPPLIER_ENTERPRISE_NUMBER', 'BE0000000000'),
        'street' => env('PEPPOL_SUPPLIER_STREET', ''),
        'city' => env('PEPPOL_SUPPLIER_CITY', 'Brussels'),
        'postal_code' => env('PEPPOL_SUPPLIER_POSTAL_CODE', '1000'),
        'country' => env('PEPPOL_SUPPLIER_COUNTRY', 'BE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PEPPOL BIS 3.0 — Specification Identifiers
    |--------------------------------------------------------------------------
    */

    'customization_id' => 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
    'profile_id' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',

];
