<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GDPR / Privacy Policy — French (fr-BE)
    |--------------------------------------------------------------------------
    */

    'title' => 'Politique de confidentialité',
    'print' => 'Imprimer',
    'last_updated' => 'Dernière mise à jour : :date',

    'intro' => [
        'heading' => 'Introduction',
        'body' => 'Motivya (ci-après « la Plateforme ») est exploitée conformément au Règlement Général sur la Protection des Données (RGPD — Règlement UE 2016/679) et à la législation belge applicable. La présente politique décrit comment nous collectons, utilisons, stockons et protégeons vos données personnelles.',
    ],

    'controller' => [
        'heading' => 'Responsable du traitement',
        'body' => 'Le responsable du traitement des données est la société exploitant Motivya. Pour toute question relative à vos données personnelles, vous pouvez nous contacter via l\'adresse e-mail indiquée sur la plateforme.',
    ],

    'data_collected' => [
        'heading' => 'Données collectées',
        'body' => 'Nous collectons les catégories de données suivantes :',
        'items' => [
            'identity' => 'Données d\'identité : nom, adresse e-mail',
            'auth' => 'Données d\'authentification : mot de passe haché, identifiants OAuth (Google, Facebook)',
            'role' => 'Données de rôle : votre rôle sur la plateforme (athlète, coach, comptable, administrateur)',
            'payment' => 'Données de paiement : traitées par Stripe ; nous ne stockons pas vos données de carte bancaire',
            'coach' => 'Données de coach : numéro d\'entreprise (BCE/KBO), spécialités, biographie, zone géographique',
            'booking' => 'Données de réservation : séances réservées, montants payés, statuts',
            'technical' => 'Données techniques : adresse IP, type de navigateur, préférence de langue',
        ],
    ],

    'purpose' => [
        'heading' => 'Finalités du traitement',
        'body' => 'Vos données sont traitées pour les finalités suivantes :',
        'items' => [
            'account' => 'Gestion de votre compte et authentification',
            'booking' => 'Traitement des réservations et paiements',
            'communication' => 'Communication relative à vos réservations et à votre compte',
            'legal' => 'Respect de nos obligations légales (facturation, TVA, lutte anti-blanchiment)',
            'improvement' => 'Amélioration de la plateforme et de l\'expérience utilisateur',
        ],
    ],

    'legal_basis' => [
        'heading' => 'Base juridique',
        'body' => 'Le traitement de vos données repose sur les bases juridiques suivantes : l\'exécution du contrat (réservations, paiements), le consentement (cookies non essentiels), les obligations légales (facturation belge, PEPPOL) et l\'intérêt légitime (sécurité de la plateforme, amélioration du service).',
    ],

    'retention' => [
        'heading' => 'Durée de conservation',
        'body' => 'Vos données personnelles sont conservées aussi longtemps que votre compte est actif. Les données de facturation sont conservées pendant 7 ans conformément à la législation comptable belge. Vous pouvez demander la suppression de votre compte à tout moment.',
    ],

    'rights' => [
        'heading' => 'Vos droits',
        'body' => 'Conformément au RGPD, vous disposez des droits suivants :',
        'items' => [
            'access' => 'Droit d\'accès à vos données personnelles',
            'rectification' => 'Droit de rectification des données inexactes',
            'erasure' => 'Droit à l\'effacement (« droit à l\'oubli »)',
            'restriction' => 'Droit à la limitation du traitement',
            'portability' => 'Droit à la portabilité des données',
            'objection' => 'Droit d\'opposition au traitement',
        ],
        'contact' => 'Pour exercer vos droits, contactez-nous via l\'adresse e-mail de la plateforme. Vous avez également le droit d\'introduire une réclamation auprès de l\'Autorité de Protection des Données (APD) belge.',
    ],

    'third_parties' => [
        'heading' => 'Partage avec des tiers',
        'body' => 'Vos données peuvent être partagées avec les tiers suivants :',
        'items' => [
            'stripe' => 'Stripe — traitement des paiements et gestion des comptes coach (Stripe Connect)',
            'hosting' => 'Hébergeur — stockage sécurisé des données sur des serveurs situés dans l\'UE',
            'google' => 'Google — si vous utilisez la connexion via Google OAuth',
        ],
    ],

    'cookies' => [
        'heading' => 'Cookies',
        'body' => 'La plateforme utilise des cookies essentiels pour le fonctionnement du site (session, jeton CSRF, préférence de langue). Aucun cookie de suivi publicitaire n\'est utilisé.',
    ],

    'security' => [
        'heading' => 'Sécurité',
        'body' => 'Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données personnelles, notamment le chiffrement des mots de passe, les connexions HTTPS, et l\'authentification à deux facteurs pour les rôles sensibles.',
    ],

    'changes' => [
        'heading' => 'Modifications',
        'body' => 'Nous pouvons mettre à jour cette politique de confidentialité. Toute modification sera publiée sur cette page avec une date de mise à jour révisée.',
    ],

];
