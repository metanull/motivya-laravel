<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Profielpagina
    |--------------------------------------------------------------------------
    */

    'title' => 'Profiel',
    'heading' => 'Profielinstellingen',

    /*
    |--------------------------------------------------------------------------
    | Persoonlijke informatie
    |--------------------------------------------------------------------------
    */

    'info_heading' => 'Persoonlijke informatie',
    'info_description' => 'Werk uw naam en e-mailadres bij.',
    'info_name' => 'Volledige naam',
    'info_email' => 'E-mailadres',
    'info_submit' => 'Opslaan',
    'info_saved' => 'Opgeslagen.',

    /*
    |--------------------------------------------------------------------------
    | Wachtwoord
    |--------------------------------------------------------------------------
    */

    'password_heading' => 'Wachtwoord wijzigen',
    'password_description' => 'Zorg ervoor dat uw account een lang, willekeurig wachtwoord gebruikt om veilig te blijven.',
    'password_current' => 'Huidig wachtwoord',
    'password_new' => 'Nieuw wachtwoord',
    'password_confirm' => 'Bevestig wachtwoord',
    'password_submit' => 'Opslaan',
    'password_saved' => 'Opgeslagen.',

    /*
    |--------------------------------------------------------------------------
    | Taalvoorkeur
    |--------------------------------------------------------------------------
    */

    'locale_heading' => 'Taalvoorkeur',
    'locale_description' => 'Kies uw voorkeurstaal voor de interface.',
    'locale_label' => 'Taal',
    'locale_fr' => 'Français',
    'locale_en' => 'English',
    'locale_nl' => 'Nederlands',
    'locale_submit' => 'Opslaan',
    'locale_updated' => 'Taalvoorkeur bijgewerkt.',

    /*
    |--------------------------------------------------------------------------
    | Tweefactorauthenticatie
    |--------------------------------------------------------------------------
    */

    'twofa_heading' => 'Tweefactorauthenticatie',
    'twofa_description' => 'Voeg extra beveiliging toe aan uw account met tweefactorauthenticatie.',
    'twofa_not_available' => 'Tweefactorauthenticatie zal beschikbaar zijn in een toekomstige update.',
    'twofa_enabled_status' => 'Tweefactorauthenticatie is ingeschakeld.',
    'twofa_enable' => 'Inschakelen',
    'twofa_enabling' => 'Inschakelen…',
    'twofa_disable' => 'Uitschakelen',
    'twofa_scan_instructions' => 'Scan de volgende QR-code met uw authenticatie-app (Google Authenticator, Authy, enz.) en voer vervolgens de verificatiecode in om te bevestigen.',
    'twofa_confirm_code' => 'Verificatiecode',
    'twofa_confirm' => 'Bevestigen',
    'twofa_invalid_code' => 'De opgegeven code is ongeldig.',
    'twofa_recovery_description' => 'Bewaar deze herstelcodes in een veilige wachtwoordmanager. Ze kunnen worden gebruikt om toegang tot uw account te herstellen als uw authenticatie-apparaat verloren is.',
    'twofa_show_codes' => 'Herstelcodes tonen',
    'twofa_hide_codes' => 'Verbergen',
    'twofa_regenerate_codes' => 'Herstelcodes opnieuw genereren',
    'twofa_method_label' => 'Methode',
    'twofa_method_totp' => 'Authenticatie-app (TOTP)',
    'twofa_method_email' => 'E-mailcode',
    'twofa_email_enable' => 'E-mail 2FA inschakelen',
    'twofa_email_enabling' => 'Code verzenden…',
    'twofa_email_description' => 'Er wordt een verificatiecode naar uw e-mailadres gestuurd telkens wanneer u inlogt.',
    'twofa_email_confirm_description' => 'Voer de verificatiecode in die naar uw e-mail is gestuurd om de configuratie te bevestigen.',
    'twofa_email_confirm_code' => 'Verificatiecode',
    'twofa_email_confirm' => 'Bevestigen',
    'twofa_email_enabled_status' => 'Tweefactorauthenticatie via e-mail is ingeschakeld.',
    'twofa_email_resend' => 'Code opnieuw versturen',
    'twofa_email_resent' => 'Er is een nieuwe code gestuurd.',
    'twofa_totp_enabled_status' => 'Tweefactorauthenticatie via app is ingeschakeld.',

    /*
    |--------------------------------------------------------------------------
    | Account verwijderen
    |--------------------------------------------------------------------------
    */

    'delete_heading' => 'Account verwijderen',
    'delete_description' => 'Zodra uw account is verwijderd, worden alle bronnen en gegevens permanent verwijderd.',
    /*
    |--------------------------------------------------------------------------
    | MFA Required Alert
    |--------------------------------------------------------------------------
    */

    'mfa_required_title' => 'Tweefactorauthenticatie vereist',
    'mfa_required_description' => 'Uw rol vereist dat tweefactorauthenticatie is ingeschakeld. Stel hieronder 2FA in voordat u toegang krijgt tot beschermde functies.',
];
