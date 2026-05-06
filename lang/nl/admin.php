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
    'image_uploading' => 'Bestand uploaden...',
    'uploading' => 'Uploaden...',
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
    'refunds_no_attempts' => 'Nog geen terugbetalingspogingen.',
    'refund_audit_status_attempted' => 'Geprobeerd',
    'refund_audit_status_succeeded' => 'Geslaagd',
    'refund_audit_status_failed' => 'Mislukt',
    'refunds_col_eligibility' => 'Geschiktheid',
    'refunds_col_last_audit' => 'Laatste poging',
    'refunds_badge_eligible' => 'Geschikt',
    'refunds_badge_already_refunded' => 'Al terugbetaald',
    'refunds_badge_missing_payment_intent' => 'Afstemming nodig',
    'refunds_badge_unpaid' => 'Niet betaald',
    'refunds_badge_pending_payment' => 'Betaling in afwachting',
    'refunds_badge_cancelled' => 'Geannuleerd',
    'refunds_success' => 'Terugbetaling succesvol verwerkt.',
    'refunds_error' => 'Verwerking van terugbetaling mislukt.',
    'refunds_error_already_refunded' => 'Deze boeking is al terugbetaald.',
    'refunds_error_not_confirmed' => 'Alleen bevestigde boekingen kunnen worden terugbetaald.',
    'refunds_error_no_amount' => 'Voor deze boeking is geen bedrag betaald.',
    'refunds_error_missing_payment_intent' => 'Deze boeking heeft geen gekoppeld Stripe-betalingsintentie. Voer payments:reconcile-bookings --repair uit voordat u het opnieuw probeert.',
    'refunds_error_stripe_failure' => 'Het terugbetalingsverzoek is geweigerd door Stripe. Raadpleeg het auditlogboek voor meer informatie.',
    'refunds_missing_payment_intent_label' => 'Reconciliatie vereist',
    'refunds_no_results' => 'Geen boekingen gevonden.',
    'dashboard_card_refunds' => 'Uitzonderlijke terugbetalingen',
    'dashboard_card_refunds_desc' => 'Verwerk uitzonderlijke terugbetalingen voor bevestigde, betaalde boekingen.',

    // Betalingsanomalieën (Story #269)
    'anomalies_heading' => 'Betalingsanomalie-wachtrij',
    'anomalies_subtitle' => 'Open betalingsanomalieën die aandacht vereisen.',
    'anomalies_col_type' => 'Type',
    'anomalies_col_description' => 'Beschrijving',
    'anomalies_col_recommended_action' => 'Aanbevolen actie',
    'anomalies_col_actions' => 'Acties',
    'anomalies_action_resolve' => 'Oplossen',
    'anomalies_action_ignore' => 'Negeren',
    'anomalies_confirm_resolve' => 'Anomalie oplossen',
    'anomalies_confirm_ignore' => 'Anomalie negeren',
    'anomalies_resolve_reason_label' => 'Oplossingsnotitie',
    'anomalies_resolve_reason_placeholder' => 'Beschrijf hoe deze anomalie is opgelost…',
    'anomalies_ignore_reason_label' => 'Reden voor negeren',
    'anomalies_ignore_reason_placeholder' => 'Geef de reden op voor het negeren van deze anomalie…',
    'anomalies_no_results' => 'Geen open anomalieën gevonden.',
    'anomalies_filter_all_types' => 'Alle types',
    'anomalies_type_confirmed_booking_missing_payment' => 'Bevestigde boeking zonder betaling',
    'anomalies_type_paid_booking_cancelled_without_refund' => 'Betaalde boeking geannuleerd zonder terugbetaling',
    'anomalies_type_completed_session_without_invoice' => 'Voltooide sessie zonder factuur',
    'anomalies_type_invoice_total_mismatch' => 'Factuurtotaal klopt niet',
    'anomalies_type_coach_stripe_incomplete' => 'Onvolledige Stripe-account voor coach',
    'dashboard_card_anomalies' => 'Betalingsanomalieën',
    'dashboard_card_anomalies_desc' => 'Open betalingsanomalieën die beoordeling vereisen.',

    /*
    |--------------------------------------------------------------------------
    | Admin — MVP-gereedheidscontrolelijst
    |--------------------------------------------------------------------------
    */

    'dashboard_card_readiness' => 'MVP-gereedheid',
    'dashboard_card_readiness_desc' => 'Controleer de gereedheid van het platform vóór de lancering.',

    'readiness_title' => 'MVP-gereedheid',
    'readiness_heading' => 'MVP-gereedheidscontrolelijst',
    'readiness_subtitle' => 'Groen/geel/rood status voor elke kritieke platformvereiste. Er worden geen geheimen blootgesteld.',
    'readiness_back_to_dashboard' => 'Terug naar dashboard',

    'readiness_status_ok' => 'OK',
    'readiness_status_warning' => 'Waarschuwing',
    'readiness_status_error' => 'Fout',

    'readiness_check_stripe' => 'Stripe-sleutels geconfigureerd',
    'readiness_check_mail' => 'E-mail geconfigureerd',
    'readiness_check_database' => 'Database bereikbaar',
    'readiness_check_cache' => 'Cache bereikbaar',
    'readiness_check_queue' => 'Wachtrij geconfigureerd',
    'readiness_check_scheduler' => 'Uitvoering geplande taken',
    'readiness_check_postal_codes' => 'Postcode-coördinaten geladen',
    'readiness_check_admin_mfa' => 'Beheerder met MFA',
    'readiness_check_accountant' => 'Accountantgebruiker bestaat',
    'readiness_check_activity_images' => 'Activiteitsafbeeldingen geüpload',
    'readiness_check_billing_config' => 'Factuurconfiguratiepagina bereikbaar',
    'readiness_check_google_maps_key' => 'Google Maps API-sleutel',
    'readiness_check_geocoding_cache' => 'Geocoderingscachetabel',
    'readiness_check_public_storage' => 'Publieke opslag symlink',

    'readiness_stripe_ok' => 'Stripe publiceerbare en geheime sleutels zijn geconfigureerd.',
    'readiness_stripe_missing' => 'Stripe-sleutels zijn niet geconfigureerd. Stel STRIPE_KEY en STRIPE_SECRET in.',
    'readiness_stripe_unexpected_format' => 'Stripe-sleutels zijn ingesteld maar komen niet overeen met het verwachte pk_/sk_-formaat.',
    'readiness_mail_ok' => 'E-maildriver ":driver" is geconfigureerd.',
    'readiness_mail_log_driver' => 'E-mail gebruikt de "log"-driver. Stel een echte mailprovider in voor productie.',
    'readiness_mail_smtp_missing_host' => 'SMTP-driver is geselecteerd maar MAIL_HOST is niet geconfigureerd.',
    'readiness_database_ok' => 'Databaseverbinding is gezond.',
    'readiness_database_error' => 'Kan geen verbinding maken met de database.',
    'readiness_cache_ok' => 'Cachedriver ":driver" is bereikbaar.',
    'readiness_cache_error' => 'Cache is niet bereikbaar.',
    'readiness_queue_ok' => 'Wachtrijdriver ":driver" is geconfigureerd.',
    'readiness_queue_error' => 'Wachtrijdriver is niet geconfigureerd.',
    'readiness_scheduler_all_ok' => 'Alle kritieke geplande taken zijn recentelijk uitgevoerd.',
    'readiness_scheduler_warning' => 'Sommige geplande taken zijn niet recentelijk uitgevoerd.',
    'readiness_scheduler_error' => 'Een of meer kritieke geplande taken zijn nooit uitgevoerd.',
    'readiness_scheduler_service_not_configured' => 'Geen enkele geplande taak is ooit uitgevoerd. De service motivya-scheduler.timer is waarschijnlijk niet geïnstalleerd op de server. Voer uit: systemctl status motivya-scheduler.timer',
    'readiness_scheduler_ok' => 'Laatste uitvoering :time.',
    'readiness_scheduler_stale' => 'Laatste uitvoering :time — kan verouderd zijn.',
    'readiness_scheduler_never_run' => 'Nooit uitgevoerd. De planner (motivya-scheduler.timer) is niet geconfigureerd in productie of heeft deze opdracht nog niet geactiveerd. Voer uit: systemctl status motivya-scheduler.timer',
    'readiness_postal_codes_ok' => ':count postcode-coördinaten geladen.',
    'readiness_postal_codes_missing' => 'Geen postcode-coördinaten gevonden. Laad de Belgische postcode-referentiegegevens: php artisan geo:load-postal-codes. Vul daarna bestaande sessies aan: php artisan sessions:backfill-coordinates.',
    'readiness_public_storage_ok' => 'De public/storage symbolische koppeling is aanwezig en bereikbaar.',
    'readiness_public_storage_missing' => 'De public/storage symbolische koppeling ontbreekt. Elke release moet deze koppeling aanmaken via deploy.sh. Voer de implementatie opnieuw uit.',
    'readiness_public_storage_broken' => 'De public/storage symbolische koppeling verwijst naar een ongeldige locatie (:target). Controleer de gedeelde opslagconfiguratie.',
    'readiness_public_storage_image_missing' => 'De koppeling bestaat maar een afbeelding is niet lokaal gevonden (:url). Controleer de machtigingen van de gedeelde opslag.',
    'readiness_public_storage_no_images' => 'De publieke opslagsymlink is aanwezig maar er zijn nog geen activiteitsafbeeldingen geüpload. Upload ten minste één afbeelding om de publieke bereikbaarheid te verifiëren.',
    'readiness_postal_code_reference_ok' => ':count Belgische postcode-coördinaten geladen.',
    'readiness_postal_code_reference_missing' => 'Geen postcode-coördinaten gevonden. Laad referentiegegevens: php artisan geo:load-postal-codes. Vul daarna sessies aan: php artisan sessions:backfill-coordinates.',
    'readiness_session_coordinates_ok' => 'Alle :count sessies hebben GPS-coördinaten.',
    'readiness_session_coordinates_all_missing' => ':count sessie(s) hebben geen GPS-coördinaten. Voer uit: php artisan sessions:backfill-coordinates na het laden van postcodes.',
    'readiness_session_coordinates_some_missing' => ':missing van :total sessie(s) missen GPS-coördinaten. Voer uit: php artisan sessions:backfill-coordinates.',
    'readiness_session_coordinates_no_sessions' => 'Nog geen sessies aangemaakt. Coördinaten worden gecontroleerd zodra sessies bestaan.',
    'readiness_payment_anomalies_ok' => 'Geen bevestigde betaalde reserveringen zonder Stripe betalingsintentie.',
    'readiness_payment_anomalies_found' => ':count bevestigde reservering(en) heeft amount_paid > 0 maar geen stripe_payment_intent_id. Voer uit: php artisan payments:reconcile-bookings --dry-run dan --repair.',
    'readiness_stripe_connect_ok' => 'Alle goedgekeurde coaches met actieve sessies hebben Stripe-onboarding voltooid.',
    'readiness_stripe_connect_incomplete' => ':count goedgekeurde coach(es) met gepubliceerde of bevestigde sessies hebben Stripe Connect onboarding niet voltooid.',
    'readiness_stripe_connect_placeholder' => ':count goedgekeurde coach(es) met gepubliceerde of bevestigde sessies hebben een nep Stripe-account-ID. Vervang dit door een echt Stripe Express-account in gebruikersbeheer.',
    'readiness_check_public_storage' => 'Publieke opslag',
    'readiness_check_postal_code_reference' => 'Postcode-referentie',
    'readiness_check_session_coordinates' => 'Sessie-coördinaten',
    'readiness_check_payment_anomalies' => 'Betalingsanomalieën',
    'readiness_check_stripe_connect' => 'Coach Stripe Connect',
    'readiness_admin_mfa_ok' => ':count actieve beheerder(s) met MFA geconfigureerd.',
    'readiness_admin_mfa_missing' => 'Geen actieve beheerder heeft MFA geconfigureerd.',
    'readiness_accountant_ok' => ':count actieve accountant(s) bestaat/bestaan.',
    'readiness_accountant_missing' => 'Geen actieve accountantgebruiker gevonden. Maak er een aan in gebruikersbeheer.',
    'readiness_activity_images_ok' => ':count activiteitsafbeelding(en) geüpload.',
    'readiness_activity_images_missing' => 'Geen activiteitsafbeeldingen geüpload. Voeg afbeeldingen toe in Activiteitsafbeeldingen.',
    'readiness_billing_config_ok' => 'Factuurconfiguratiepagina is bereikbaar.',
    'readiness_billing_config_missing' => 'Factuurconfiguratieroute is niet geregistreerd.',
    'readiness_google_maps_key_ok' => 'Google Maps API-sleutel is geconfigureerd.',
    'readiness_google_maps_key_missing' => 'GOOGLE_MAPS_API_KEY is niet ingesteld. Google geocodering fallback is uitgeschakeld. Kaartweergave (MapLibre/OpenFreeMap) werkt nog steeds.',
    'readiness_google_maps_key_format' => 'GOOGLE_MAPS_API_KEY komt niet overeen met het verwachte AIza...-formaat.',
    'readiness_geocoding_cache_ok' => 'Geocoderingscachetabel is beschikbaar.',
    'readiness_geocoding_cache_missing' => 'Geocoderingscachetabel ontbreekt. Voer php artisan migrate uit.',
    'readiness_public_storage_ok' => 'Publieke opslag symlink bestaat.',
    'readiness_public_storage_missing' => 'Publieke opslag symlink ontbreekt. Voer php artisan storage:link uit of controleer het deploy-script.',
    'readiness_public_storage_not_symlink' => 'public/storage bestaat maar is geen symlink. Activiteitsafbeeldingen worden mogelijk niet correct aangeboden.',

    'readiness_scheduler_detail_heading' => 'Status geplande taken',
    'readiness_scheduler_detail_subtitle' => 'Laatste geregistreerde succesvolle uitvoering voor elke kritieke geplande taak. Als een taak nooit is uitgevoerd, controleer dan of motivya-scheduler.timer actief is op de server.',
    'readiness_scheduler_col_command' => 'Opdracht',
    'readiness_scheduler_col_status' => 'Laatste uitvoering',

    'readiness_action_billing' => 'Factuurconfiguratie bekijken',
    'readiness_action_users' => 'Gebruikers beheren',
    'readiness_action_activity_images' => 'Afbeeldingen beheren',
    'readiness_action_anomalies' => 'Anomalieën bekijken',
    'readiness_action_coach_approval' => 'Goedkeuring coaches',
    'readiness_operations_heading' => 'Operationele reparatiehulpmiddelen',
    'readiness_operations_subtitle' => 'Alleen-lezen opdrachten om op de server uit te voeren voor gedetecteerde problemen. Geen van deze opdrachten wordt automatisch uitgevoerd vanuit deze interface.',
    'readiness_operations_load_postal_codes' => 'Belgische postcodes laden',
    'readiness_operations_backfill_coordinates' => 'Sessie-coördinaten aanvullen',
    'readiness_operations_reconcile_dry_run' => 'Betalingscontrole (geen wijzigingen)',
    'readiness_operations_reconcile_repair' => 'Betalingsreparatie (leest Stripe, schrijft)',
    'readiness_operations_health_snapshot' => 'Gezondheidsstatus (alleen lezen)',
    'readiness_operations_scheduler_status' => 'Plannerstatus',

    /*
    |--------------------------------------------------------------------------
    | Admin — Auditlogboek-viewer
    |--------------------------------------------------------------------------
    */

    'audit_events_title' => 'Auditlogboek',
    'audit_events_heading' => 'Auditgebeurtenissenlogboek',
    'audit_events_subtitle' => 'Alleen-lezen logboek van alle domein-auditgebeurtenissen.',
    'audit_events_detail_title' => 'Detail auditgebeurtenis',
    'audit_events_detail_heading' => 'Detail auditgebeurtenis',
    'audit_events_back' => 'Terug naar auditlogboek',
    'audit_events_action_view' => 'Bekijken',
    'audit_events_no_results' => 'Geen auditgebeurtenissen gevonden die overeenkomen met uw filters.',
    'audit_events_filter_all' => 'Alle',
    'audit_events_filter_occurred_from' => 'Begindatum',
    'audit_events_filter_occurred_to' => 'Einddatum',
    'audit_events_filter_event_type' => 'Gebeurtenistype',
    'audit_events_filter_operation' => 'Operatie',
    'audit_events_filter_actor_type' => 'Actortype',
    'audit_events_filter_actor_id' => 'Actor-ID',
    'audit_events_filter_actor_id_placeholder' => 'Gebruikers-ID…',
    'audit_events_filter_actor_role' => 'Actorrol',
    'audit_events_filter_source' => 'Bron',
    'audit_events_filter_model_type' => 'Primair modeltype',
    'audit_events_filter_model_type_placeholder' => 'bijv. SportSession…',
    'audit_events_filter_model_id' => 'Primair model-ID',
    'audit_events_filter_model_id_placeholder' => 'ID…',
    'audit_events_filter_subject_type' => 'Onderwerptype',
    'audit_events_filter_subject_type_placeholder' => 'bijv. Booking…',
    'audit_events_filter_subject_id' => 'Onderwerp-ID',
    'audit_events_filter_subject_id_placeholder' => 'ID…',
    'audit_events_filter_subject_relation' => 'Onderwerprelatie',
    'audit_events_filter_subject_relation_placeholder' => 'bijv. booking…',
    'audit_events_filter_request_id' => 'Verzoek-ID',
    'audit_events_filter_request_id_placeholder' => 'UUID…',
    'audit_events_filter_reset' => 'Filters resetten',
    'audit_events_col_occurred_at' => 'Tijdstip',
    'audit_events_col_event_type' => 'Gebeurtenistype',
    'audit_events_col_operation' => 'Operatie',
    'audit_events_col_actor' => 'Actor',
    'audit_events_col_source' => 'Bron',
    'audit_events_col_primary_model' => 'Primair model',
    'audit_events_col_subjects' => 'Onderwerpen',
    'audit_events_col_request_id' => 'Verzoek-ID',
    'audit_events_detail_actor_type' => 'Actortype',
    'audit_events_detail_actor_id' => 'Actor-ID',
    'audit_events_detail_actor_role' => 'Actorrol',
    'audit_events_detail_request_id' => 'Verzoek-ID',
    'audit_events_detail_ip_address' => 'IP-adres',
    'audit_events_detail_route_name' => 'Routenaam',
    'audit_events_detail_job_uuid' => 'Job-UUID',
    'audit_events_detail_old_values' => 'Oude waarden',
    'audit_events_detail_new_values' => 'Nieuwe waarden',
    'audit_events_detail_metadata' => 'Metagegevens',
    'audit_events_detail_subjects' => 'Onderwerpen',
    'audit_events_detail_no_subjects' => 'Geen onderwerpen gekoppeld aan deze gebeurtenis.',
    'audit_events_detail_subject_relation' => 'Relatie',
    'audit_events_detail_subject_relation_primary' => 'primair',
    'audit_events_detail_subject_type' => 'Onderwerptype',
    'audit_events_detail_subject_id' => 'Onderwerp-ID',
    'dashboard_card_audit_events' => 'Auditlogboek',
    'dashboard_card_audit_events_desc' => 'Blader door en filter het volledige auditgebeurtenissenlogboek.',

];
