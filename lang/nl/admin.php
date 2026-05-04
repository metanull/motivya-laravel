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
    'dashboard_card_billing_config' => 'Facturatieconfiguratie',
    'dashboard_card_billing_config_desc' => 'Bekijk actieve abonnementsplannen, commissietarieven en de uitbetalingsformule.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Billing Configuration
    |--------------------------------------------------------------------------
    */

    'billing_config_title' => 'Facturatieconfiguratie',
    'billing_config_heading' => 'Configuratie abonnementen & commissies',
    'billing_config_subtitle' => 'Alleen-lezen overzicht van actieve facturatieregels. Wijzigingen vereisen een code- of configuratie-deployment.',
    'billing_config_read_only_notice' => 'Deze waarden zijn alleen-lezen. Om facturatieregels te wijzigen is een deployment vereist.',
    'billing_config_plan_heading' => 'Abonnementsplannen',
    'billing_config_col_plan' => 'Plan',
    'billing_config_col_commission' => 'Commissietarief',
    'billing_config_col_subscription_fee' => 'Maandelijkse kosten',
    'billing_config_col_description' => 'Omschrijving',
    'billing_config_free' => 'Gratis',
    'billing_config_plan_desc_freemium' => 'Geen maandelijkse kosten. Hoogste commissietarief van toepassing.',
    'billing_config_plan_desc_active' => 'Gemiddelde maandelijkse kosten. Verlaagd commissietarief.',
    'billing_config_plan_desc_premium' => 'Hoogste maandelijkse kosten. Laagste commissietarief.',
    'billing_config_vat_heading' => 'BTW-configuratie',
    'billing_config_vat_rate_label' => 'BTW-tarief platform',
    'billing_config_vat_subject' => 'BTW-plichtige coach',
    'billing_config_vat_subject_note' => 'Standaard Belgisch tarief (art. 21% BTW-wetboek).',
    'billing_config_vat_franchise' => 'Vrijstellingsregeling',
    'billing_config_vat_franchise_note' => 'Vrijgesteld op grond van art. 56bis BTW-wetboek.',
    'billing_config_vat_source' => 'Bron: hardgecodeerd Belgisch standaardtarief (21%), uitsluitend toegepast op BTW-plichtige coaches.',
    'billing_config_stripe_heading' => 'Betalingsverwerking',
    'billing_config_stripe_fee_label' => 'Stripe-tarief (geschat)',
    'billing_config_stripe_source' => 'Bron: deploy-time configuratie (STRIPE_COMMISSION_RATE of standaard 1,5%).',
    'billing_config_payout_heading' => 'Uitbetalingsformule',
    'billing_config_payout_description' => 'Het platform selecteert het plan dat de hoogste netto-uitbetaling oplevert voor de coach (auto-best-plan algoritme). Netto-uitbetaling = Omzet (excl. BTW) − Commissie − Abonnementskosten − Stripe-kosten.',
    'billing_config_payout_note' => 'Alle uitbetalingsberekeningen werken op bedragen excl. BTW, zodat de marge van Motivya gelijk blijft ongeacht de BTW-status van de coach.',

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

    /*
    |--------------------------------------------------------------------------
    | Admin — Session Supervision
    |--------------------------------------------------------------------------
    */

    'sessions_title' => 'Sessiebeheer',
    'sessions_heading' => 'Sessiebeheer',
    'sessions_subtitle' => 'Zoek, inspecteer, annuleer en voltooi sessies op het hele platform.',
    'sessions_filter_status' => 'Status',
    'sessions_filter_all_statuses' => 'Alle statussen',
    'sessions_filter_coach' => 'Coach',
    'sessions_filter_coach_placeholder' => 'Naam van de coach…',
    'sessions_filter_activity_type' => 'Type activiteit',
    'sessions_filter_all_types' => 'Alle typen',
    'sessions_filter_date_from' => 'Datum van',
    'sessions_filter_date_to' => 'Datum tot',
    'sessions_filter_pending_payment' => 'Alleen openstaande betalingen',
    'sessions_filter_past_end_time' => 'Alleen voorbij eindtijd',
    'sessions_filter_reset' => 'Filters resetten',
    'sessions_col_title' => 'Titel',
    'sessions_col_coach' => 'Coach',
    'sessions_col_activity' => 'Activiteit',
    'sessions_col_datetime' => 'Datum / Tijd',
    'sessions_col_status' => 'Status',
    'sessions_col_participants' => 'Deelnemers',
    'sessions_col_confirmed_bookings' => 'Bevestigd',
    'sessions_col_pending_bookings' => 'In behandeling',
    'sessions_col_actions' => 'Acties',
    'sessions_action_view' => 'Bekijken',
    'sessions_action_cancel' => 'Annuleren',
    'sessions_action_complete' => 'Voltooien',
    'sessions_action_complete_confirm' => 'Deze sessie als voltooid markeren? Dit zal het aanmaken van een factuur activeren.',
    'sessions_cancel_reason_label' => 'Reden voor annulering',
    'sessions_cancel_reason_placeholder' => 'Geef de reden voor de annulering op…',
    'sessions_cancel_confirm_btn' => 'Annulering bevestigen',
    'sessions_cancelled_success' => 'Sessie succesvol geannuleerd.',
    'sessions_cancelled_error' => 'Annulering van sessie mislukt.',
    'sessions_completed_success' => 'Sessie gemarkeerd als voltooid.',
    'sessions_completed_error' => 'Voltooiing van sessie mislukt.',
    'sessions_stripe_ready' => 'Stripe gereed',
    'sessions_stripe_not_ready' => 'Stripe niet gereed',
    'sessions_no_results' => 'Geen sessies gevonden die overeenkomen met uw filters.',
    'dashboard_card_sessions' => 'Sessiebeheer',
    'dashboard_card_sessions_desc' => 'Zoek, inspecteer, annuleer en voltooi sessies.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Wachtrij voor uitzonderlijke terugbetalingen
    |--------------------------------------------------------------------------
    */

    'refunds_title' => 'Wachtrij voor uitzonderlijke terugbetalingen',
    'refunds_heading' => 'Wachtrij voor uitzonderlijke terugbetalingen',
    'refunds_subtitle' => 'Bekijk boekingen en verwerk uitzonderlijke terugbetalingen voor bevestigde, betaalde boekingen.',
    'refunds_filter_status' => 'Boekingsstatus',
    'refunds_filter_all_statuses' => 'Alle statussen',
    'refunds_col_booking' => 'Boeking',
    'refunds_col_athlete' => 'Atleet',
    'refunds_col_session' => 'Sessie',
    'refunds_col_status' => 'Status',
    'refunds_col_amount' => 'Betaald bedrag',
    'refunds_col_date' => 'Datum',
    'refunds_col_actions' => 'Acties',
    'refunds_action_refund' => 'Terugbetalen',
    'refunds_refund_reason_label' => 'Reden voor terugbetaling',
    'refunds_refund_reason_placeholder' => 'Geef de reden voor deze uitzonderlijke terugbetaling op…',
    'refunds_confirm_btn' => 'Terugbetaling bevestigen',
    'refunds_not_eligible' => 'Niet in aanmerking',
    'refunds_ineligibility_reason' => 'De boeking komt niet in aanmerking voor een uitzonderlijke terugbetaling (moet bevestigd zijn met een positief betaald bedrag).',
    'refunds_success' => 'Terugbetaling succesvol verwerkt.',
    'refunds_error' => 'Verwerking van terugbetaling mislukt.',
    'refunds_no_results' => 'Geen boekingen gevonden.',
    'dashboard_card_refunds' => 'Uitzonderlijke terugbetalingen',
    'dashboard_card_refunds_desc' => 'Verwerk uitzonderlijke terugbetalingen voor bevestigde, betaalde boekingen.',

];
