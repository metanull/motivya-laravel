<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Page Profil
    |--------------------------------------------------------------------------
    */

    'title' => 'Profil',
    'heading' => 'Paramètres du profil',

    /*
    |--------------------------------------------------------------------------
    | Informations personnelles
    |--------------------------------------------------------------------------
    */

    'info_heading' => 'Informations personnelles',
    'info_description' => 'Mettez à jour votre nom et votre adresse e-mail.',
    'info_name' => 'Nom complet',
    'info_email' => 'Adresse e-mail',
    'info_submit' => 'Enregistrer',
    'info_saved' => 'Enregistré.',

    /*
    |--------------------------------------------------------------------------
    | Mot de passe
    |--------------------------------------------------------------------------
    */

    'password_heading' => 'Modifier le mot de passe',
    'password_description' => 'Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sécurisé.',
    'password_current' => 'Mot de passe actuel',
    'password_new' => 'Nouveau mot de passe',
    'password_confirm' => 'Confirmer le mot de passe',
    'password_submit' => 'Enregistrer',
    'password_saved' => 'Enregistré.',

    /*
    |--------------------------------------------------------------------------
    | Préférence linguistique
    |--------------------------------------------------------------------------
    */

    'locale_heading' => 'Préférence linguistique',
    'locale_description' => 'Choisissez votre langue préférée pour l\'interface.',
    'locale_label' => 'Langue',
    'locale_fr' => 'Français',
    'locale_en' => 'English',
    'locale_nl' => 'Nederlands',
    'locale_submit' => 'Enregistrer',
    'locale_updated' => 'Préférence linguistique mise à jour.',

    /*
    |--------------------------------------------------------------------------
    | Authentification à deux facteurs
    |--------------------------------------------------------------------------
    */

    'twofa_heading' => 'Authentification à deux facteurs',
    'twofa_description' => 'Ajoutez une sécurité supplémentaire à votre compte en utilisant l\'authentification à deux facteurs.',
    'twofa_not_available' => 'L\'authentification à deux facteurs sera disponible dans une prochaine mise à jour.',
    'twofa_enabled_status' => 'L\'authentification à deux facteurs est activée.',
    'twofa_enable' => 'Activer',
    'twofa_enabling' => 'Activation…',
    'twofa_disable' => 'Désactiver',
    'twofa_scan_instructions' => 'Scannez le code QR suivant avec votre application d\'authentification (Google Authenticator, Authy, etc.), puis entrez le code de vérification pour confirmer.',
    'twofa_confirm_code' => 'Code de vérification',
    'twofa_confirm' => 'Confirmer',
    'twofa_invalid_code' => 'Le code fourni est invalide.',
    'twofa_recovery_description' => 'Conservez ces codes de récupération dans un gestionnaire de mots de passe sécurisé. Ils peuvent être utilisés pour récupérer l\'accès à votre compte si votre dispositif d\'authentification est perdu.',
    'twofa_show_codes' => 'Afficher les codes de récupération',
    'twofa_hide_codes' => 'Masquer',
    'twofa_regenerate_codes' => 'Régénérer les codes de récupération',
    'twofa_method_label' => 'Méthode',
    'twofa_method_totp' => 'Application d\'authentification (TOTP)',
    'twofa_method_email' => 'Code par e-mail',
    'twofa_email_enable' => 'Activer la 2FA par e-mail',
    'twofa_email_enabling' => 'Envoi du code…',
    'twofa_email_description' => 'Un code de vérification sera envoyé à votre adresse e-mail à chaque connexion.',
    'twofa_email_confirm_description' => 'Entrez le code de vérification envoyé à votre e-mail pour confirmer la configuration.',
    'twofa_email_confirm_code' => 'Code de vérification',
    'twofa_email_confirm' => 'Confirmer',
    'twofa_email_enabled_status' => 'L\'authentification à deux facteurs par e-mail est activée.',
    'twofa_email_resend' => 'Renvoyer le code',
    'twofa_email_resent' => 'Un nouveau code a été envoyé.',
    'twofa_totp_enabled_status' => 'L\'authentification à deux facteurs par application est activée.',

    /*
    |--------------------------------------------------------------------------
    | Supprimer le compte
    |--------------------------------------------------------------------------
    */

    'delete_heading' => 'Supprimer le compte',
    'delete_description' => 'Une fois que votre compte est supprimé, toutes ses ressources et données seront définitivement supprimées.',
    /*
    |--------------------------------------------------------------------------
    | MFA Required Alert
    |--------------------------------------------------------------------------
    */

    'mfa_required_title' => 'Authentification à deux facteurs requise',
    'mfa_required_description' => 'Votre rôle nécessite l\'activation de l\'authentification à deux facteurs. Veuillez configurer la 2FA ci-dessous avant d\'accéder aux fonctionnalités protégées.',
];
