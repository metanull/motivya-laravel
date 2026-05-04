<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin — Coach Approval
    |--------------------------------------------------------------------------
    */

    'coach_approval_title' => 'Coachvalidatie',
    'coach_approval_heading' => 'Lopende coachaanvragen',
    'no_pending_applications' => 'Geen lopende aanvragen.',

    'applied_at' => 'Datum van aanvraag',

    'approve' => 'Goedkeuren',
    'reject' => 'Afwijzen',
    'approve_confirm' => 'Weet u zeker dat u deze coach wilt goedkeuren?',
    'confirm_reject' => 'Afwijzing bevestigen',
    'rejection_reason_label' => 'Reden van afwijzing',
    'rejection_reason_placeholder' => 'Geef de reden voor de afwijzing op…',

    'coach_approved' => 'De coach is succesvol goedgekeurd.',
    'coach_rejected' => 'De aanvraag is afgewezen.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Activity Images
    |--------------------------------------------------------------------------
    */

    'activity_images_title' => 'Activiteitafbeeldingen',
    'activity_images_heading' => 'Omslagafbeeldingen voor activiteiten',
    'upload_image' => 'Nieuwe afbeelding uploaden',
    'activity_type_label' => 'Type activiteit',
    'select_activity_type' => 'Selecteer een type activiteit…',
    'alt_text_label' => 'Alternatieve tekst',
    'alt_text_placeholder' => 'Beschrijf de afbeelding…',
    'image_file_label' => 'Afbeeldingsbestand (JPG, PNG, WebP — max 2 MB)',
    'upload_button' => 'Uploaden',
    'existing_images' => 'Bestaande afbeeldingen',
    'no_images' => 'Nog geen afbeeldingen geüpload.',
    'confirm_delete_image' => 'Weet u zeker dat u deze afbeelding wilt verwijderen?',
    'image_uploaded' => 'Afbeelding succesvol geüpload.',
    'image_deleted' => 'Afbeelding succesvol verwijderd.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Database Export
    |--------------------------------------------------------------------------
    */

    'data_export_title' => 'Database-export',
    'data_export_heading' => 'Database-export',
    'data_export_description' => 'Download volledige CSV-exports van de database voor coaches, sessies en betalingen.',

    'export_coaches_title' => 'Coaches',
    'export_coaches_description' => 'Alle geregistreerde coaches en hun profielgegevens.',

    'export_sessions_title' => 'Sessies',
    'export_sessions_description' => 'Alle sporten sessies met coach- en planningsinformatie.',

    'export_payments_title' => 'Betalingen',
    'export_payments_description' => 'Alle boekingen en betalingstransacties.',

    'export_csv' => 'Exporteren als CSV',

    /*
    |--------------------------------------------------------------------------
    | Admin — Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard_title' => 'Admin Dashboard',
    'dashboard_heading' => 'Admin Dashboard',
    'dashboard_subtitle' => 'Overzicht van platformactiviteiten.',
    'dashboard_card_coach_approval' => 'Coachaanvragen',
    'dashboard_card_coach_approval_desc' => 'Bekijk en keur lopende coachaanvragen goed.',
    'dashboard_card_users' => 'Gebruikersbeheer',
    'dashboard_card_users_desc' => 'Beheer gebruikers, rollen en accountstatus.',
    'dashboard_suspended_count' => ':count geschorst(e) account(s)',
    'dashboard_mfa_missing_count' => ':count admin/accountant(s) zonder MFA',
    'dashboard_unverified_count' => ':count niet-geverifieerd(e) e-mail(s)',
    'dashboard_card_activity_images' => 'Activiteitafbeeldingen',
    'dashboard_card_activity_images_desc' => 'Beheer omslagafbeeldingen voor activiteitstypen.',
    'dashboard_card_data_export' => 'Data exporteren',
    'dashboard_card_data_export_desc' => 'Download CSV-exports van de platformgegevens.',

    /*
    |--------------------------------------------------------------------------
    | Admin — User Management
    |--------------------------------------------------------------------------
    */

    'users_title' => 'Gebruikersbeheer',
    'users_heading' => 'Gebruikers',
    'no_users_found' => 'Geen gebruikers gevonden.',

    'create_user_title' => 'Gebruiker aanmaken',
    'create_user_heading' => 'Back-office gebruiker aanmaken',
    'create_user_role_label' => 'Rol',
    'create_user_submit' => 'Gebruiker aanmaken',
    'user_created' => 'Gebruiker aangemaakt. Er is een onboarding-e-mail verzonden.',

    'col_role' => 'Rol',
    'col_email_verified' => 'E-mail geverifieerd',
    'col_mfa' => 'MFA',
    'col_status' => 'Status',
    'col_created_at' => 'Aangemaakt',
    'col_actions' => 'Acties',

    'filter_all_roles' => 'Alle rollen',
    'filter_all_statuses' => 'Alle statussen',
    'filter_active' => 'Actief',
    'filter_suspended' => 'Geschorst',

    'status_active' => 'Actief',
    'status_suspended' => 'Geschorst',

    'action_reset_password' => 'Wachtwoord resetten',
    'action_change_role' => 'Rol wijzigen',
    'action_suspend' => 'Schorsen',
    'action_reactivate' => 'Reactiveren',

    'suspend_confirm_title' => 'Account schorsen',
    'suspension_reason_label' => 'Reden',
    'suspension_reason_placeholder' => 'Geef de reden voor de schorsing op…',

    'change_role_title' => 'Rol wijzigen',

    'user_suspended' => 'Account geschorst.',
    'user_reactivated' => 'Account gereactiveerd.',
    'role_changed' => 'Rol bijgewerkt.',
    'password_reset_sent' => 'Link voor wachtwoordherstel verzonden.',

    'role_change_coach_blocked' => 'De Coach-rol kan niet direct worden toegewezen. Gebruik de goedkeuringsprocedure voor coaches.',
    'role_change_last_admin' => 'De laatste actieve beheerder kan niet worden teruggedegradeerd.',

];
