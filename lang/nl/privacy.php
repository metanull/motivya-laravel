<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GDPR / Privacy Policy — Dutch (nl-BE)
    |--------------------------------------------------------------------------
    */

    'title' => 'Privacybeleid',
    'print' => 'Afdrukken',
    'last_updated' => 'Laatst bijgewerkt: :date',

    'intro' => [
        'heading' => 'Inleiding',
        'body' => 'Motivya (hierna "het Platform") opereert in overeenstemming met de Algemene Verordening Gegevensbescherming (AVG — EU-Verordening 2016/679) en de toepasselijke Belgische wetgeving. Dit beleid beschrijft hoe wij uw persoonsgegevens verzamelen, gebruiken, opslaan en beschermen.',
    ],

    'controller' => [
        'heading' => 'Verwerkingsverantwoordelijke',
        'body' => 'De verwerkingsverantwoordelijke is het bedrijf dat Motivya beheert. Voor vragen over uw persoonsgegevens kunt u contact met ons opnemen via het e-mailadres vermeld op het platform.',
    ],

    'data_collected' => [
        'heading' => 'Verzamelde gegevens',
        'body' => 'Wij verzamelen de volgende categorieën gegevens:',
        'items' => [
            'identity' => 'Identiteitsgegevens: naam, e-mailadres',
            'auth' => 'Authenticatiegegevens: gehasht wachtwoord, OAuth-identifiers (Google, Facebook)',
            'role' => 'Rolgegevens: uw rol op het platform (atleet, coach, boekhouder, beheerder)',
            'payment' => 'Betalingsgegevens: verwerkt door Stripe; wij slaan uw kaartgegevens niet op',
            'coach' => 'Coachgegevens: ondernemingsnummer (KBO/BCE), specialiteiten, biografie, geografische zone',
            'booking' => 'Boekingsgegevens: geboekte sessies, betaalde bedragen, statussen',
            'technical' => 'Technische gegevens: IP-adres, browsertype, taalvoorkeur',
        ],
    ],

    'purpose' => [
        'heading' => 'Doeleinden van de verwerking',
        'body' => 'Uw gegevens worden verwerkt voor de volgende doeleinden:',
        'items' => [
            'account' => 'Accountbeheer en authenticatie',
            'booking' => 'Verwerking van boekingen en betalingen',
            'communication' => 'Communicatie over uw boekingen en account',
            'legal' => 'Naleving van onze wettelijke verplichtingen (facturatie, btw, antiwitwas)',
            'improvement' => 'Verbetering van het platform en de gebruikerservaring',
        ],
    ],

    'legal_basis' => [
        'heading' => 'Rechtsgrond',
        'body' => 'De verwerking van uw gegevens is gebaseerd op de volgende rechtsgronden: uitvoering van het contract (boekingen, betalingen), toestemming (niet-essentiële cookies), wettelijke verplichtingen (Belgische facturatie, PEPPOL) en gerechtvaardigd belang (platformbeveiliging, verbetering van de dienst).',
    ],

    'retention' => [
        'heading' => 'Bewaartermijn',
        'body' => 'Uw persoonsgegevens worden bewaard zolang uw account actief is. Facturatiegegevens worden 7 jaar bewaard in overeenstemming met de Belgische boekhoudwetgeving. U kunt op elk moment verzoeken om verwijdering van uw account.',
    ],

    'rights' => [
        'heading' => 'Uw rechten',
        'body' => 'Op grond van de AVG heeft u de volgende rechten:',
        'items' => [
            'access' => 'Recht op inzage van uw persoonsgegevens',
            'rectification' => 'Recht op rectificatie van onjuiste gegevens',
            'erasure' => 'Recht op wissing ("recht om vergeten te worden")',
            'restriction' => 'Recht op beperking van de verwerking',
            'portability' => 'Recht op overdraagbaarheid van gegevens',
            'objection' => 'Recht van bezwaar tegen de verwerking',
        ],
        'contact' => 'Om uw rechten uit te oefenen, kunt u contact met ons opnemen via het e-mailadres van het platform. U heeft ook het recht om een klacht in te dienen bij de Belgische Gegevensbeschermingsautoriteit (GBA).',
    ],

    'third_parties' => [
        'heading' => 'Delen met derden',
        'body' => 'Uw gegevens kunnen worden gedeeld met de volgende derden:',
        'items' => [
            'stripe' => 'Stripe — betalingsverwerking en beheer van coachaccounts (Stripe Connect)',
            'hosting' => 'Hostingprovider — veilige gegevensopslag op servers binnen de EU',
            'google' => 'Google — als u gebruik maakt van Google OAuth-aanmelding',
        ],
    ],

    'cookies' => [
        'heading' => 'Cookies',
        'body' => 'Het platform maakt gebruik van essentiële cookies voor de werking van de site (sessie, CSRF-token, taalvoorkeur). Er worden geen advertentie-trackingcookies gebruikt.',
    ],

    'security' => [
        'heading' => 'Beveiliging',
        'body' => 'Wij nemen passende technische en organisatorische maatregelen om uw persoonsgegevens te beschermen, waaronder versleuteling van wachtwoorden, HTTPS-verbindingen en tweefactorauthenticatie voor gevoelige rollen.',
    ],

    'changes' => [
        'heading' => 'Wijzigingen',
        'body' => 'Wij kunnen dit privacybeleid bijwerken. Eventuele wijzigingen worden op deze pagina gepubliceerd met een herziene datum.',
    ],

];
