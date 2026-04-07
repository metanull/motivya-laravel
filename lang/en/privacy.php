<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GDPR / Privacy Policy — English (en-GB)
    |--------------------------------------------------------------------------
    */

    'title' => 'Privacy Policy',
    'print' => 'Print',
    'last_updated' => 'Last updated: :date',

    'intro' => [
        'heading' => 'Introduction',
        'body' => 'Motivya (hereinafter "the Platform") operates in compliance with the General Data Protection Regulation (GDPR — EU Regulation 2016/679) and applicable Belgian legislation. This policy describes how we collect, use, store, and protect your personal data.',
    ],

    'controller' => [
        'heading' => 'Data Controller',
        'body' => 'The data controller is the company operating Motivya. For any questions regarding your personal data, you can contact us via the email address provided on the platform.',
    ],

    'data_collected' => [
        'heading' => 'Data Collected',
        'body' => 'We collect the following categories of data:',
        'items' => [
            'identity' => 'Identity data: name, email address',
            'auth' => 'Authentication data: hashed password, OAuth identifiers (Google, Facebook)',
            'role' => 'Role data: your role on the platform (athlete, coach, accountant, administrator)',
            'payment' => 'Payment data: processed by Stripe; we do not store your card details',
            'coach' => 'Coach data: enterprise number (BCE/KBO), specialties, biography, geographic zone',
            'booking' => 'Booking data: booked sessions, amounts paid, statuses',
            'technical' => 'Technical data: IP address, browser type, language preference',
        ],
    ],

    'purpose' => [
        'heading' => 'Purpose of Processing',
        'body' => 'Your data is processed for the following purposes:',
        'items' => [
            'account' => 'Account management and authentication',
            'booking' => 'Processing bookings and payments',
            'communication' => 'Communication regarding your bookings and account',
            'legal' => 'Compliance with our legal obligations (invoicing, VAT, anti-money laundering)',
            'improvement' => 'Platform and user experience improvement',
        ],
    ],

    'legal_basis' => [
        'heading' => 'Legal Basis',
        'body' => 'The processing of your data is based on the following legal grounds: contract performance (bookings, payments), consent (non-essential cookies), legal obligations (Belgian invoicing, PEPPOL), and legitimate interest (platform security, service improvement).',
    ],

    'retention' => [
        'heading' => 'Data Retention',
        'body' => 'Your personal data is retained as long as your account is active. Invoicing data is retained for 7 years in accordance with Belgian accounting legislation. You may request deletion of your account at any time.',
    ],

    'rights' => [
        'heading' => 'Your Rights',
        'body' => 'Under the GDPR, you have the following rights:',
        'items' => [
            'access' => 'Right of access to your personal data',
            'rectification' => 'Right to rectification of inaccurate data',
            'erasure' => 'Right to erasure ("right to be forgotten")',
            'restriction' => 'Right to restriction of processing',
            'portability' => 'Right to data portability',
            'objection' => 'Right to object to processing',
        ],
        'contact' => 'To exercise your rights, contact us via the platform\'s email address. You also have the right to lodge a complaint with the Belgian Data Protection Authority (DPA).',
    ],

    'third_parties' => [
        'heading' => 'Third-Party Sharing',
        'body' => 'Your data may be shared with the following third parties:',
        'items' => [
            'stripe' => 'Stripe — payment processing and coach account management (Stripe Connect)',
            'hosting' => 'Hosting provider — secure data storage on EU-based servers',
            'google' => 'Google — if you use Google OAuth login',
        ],
    ],

    'cookies' => [
        'heading' => 'Cookies',
        'body' => 'The platform uses essential cookies for site operation (session, CSRF token, language preference). No advertising tracking cookies are used.',
    ],

    'security' => [
        'heading' => 'Security',
        'body' => 'We implement appropriate technical and organisational measures to protect your personal data, including password encryption, HTTPS connections, and two-factor authentication for sensitive roles.',
    ],

    'changes' => [
        'heading' => 'Changes',
        'body' => 'We may update this privacy policy. Any changes will be published on this page with a revised update date.',
    ],

];
