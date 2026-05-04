<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Meldingsberichten
    |--------------------------------------------------------------------------
    */

    'greeting' => 'Hallo :name,',
    'thanks' => 'Bedankt voor het gebruik van Motivya!',

    /*
    |--------------------------------------------------------------------------
    | Tweefactorauthenticatie
    |--------------------------------------------------------------------------
    */

    'two_factor_code_subject' => 'Uw verificatiecode',
    'two_factor_code_body' => 'Uw tweefactorauthenticatiecode is: :code',
    'two_factor_code_expiry' => 'Deze code vervalt over 10 minuten.',
    'two_factor_code_ignore' => 'Als u deze code niet heeft aangevraagd, kunt u deze e-mail negeren.',

    /*
    |--------------------------------------------------------------------------
    | Coachaanvraag
    |--------------------------------------------------------------------------
    */

    'coach_approved_subject' => 'Uw coachaanvraag is goedgekeurd!',
    'coach_approved_body' => 'Gefeliciteerd! Uw coachaanvraag op Motivya is goedgekeurd.',
    'coach_approved_specialties' => 'Uw specialiteiten: :specialties.',
    'coach_approved_action' => 'Betalingen instellen en aan de slag',
    'coach_approved_stripe_hint' => 'Om boekingen te accepteren en betalingen te ontvangen, moet u uw Stripe-account verbinden. Klik op de knop hierboven om te beginnen — het duurt slechts een paar minuten.',

    'coach_rejected_subject' => 'Uw coachaanvraag is niet aanvaard',
    'coach_rejected_body' => 'Het spijt ons, uw coachaanvraag op Motivya is niet aanvaard.',
    'coach_rejected_reason' => 'Reden: :reason',

    /*
    |--------------------------------------------------------------------------
    | Nieuwe aanvraag (admin)
    |--------------------------------------------------------------------------
    */

    'new_coach_application_subject' => 'Nieuwe coachaanvraag',
    'new_coach_application_body' => ':name (:email) heeft een coachaanvraag ingediend.',
    'new_coach_application_specialties' => 'Specialiteiten: :specialties.',
    'new_coach_application_action' => 'Aanvragen bekijken',
    'new_coach_application_body_short' => 'Er is een nieuwe coachaanvraag ingediend.',

    /*
    |--------------------------------------------------------------------------
    | Sessieherinnering
    |--------------------------------------------------------------------------
    */

    'session_reminder_subject' => 'Herinnering: sessie morgen',
    'session_reminder_body' => 'Uw :activity sessie is morgen om :time.',
    'view_session' => 'Sessie bekijken',

    /*
    |--------------------------------------------------------------------------
    | Reserveringsbevestiging / -annulering
    |--------------------------------------------------------------------------
    */

    'booking_confirmed_subject' => 'Reservering bevestigd',
    'booking_confirmed_body' => 'Uw reservering voor :activity op :date om :time is bevestigd.',
    'booking_cancelled_subject' => 'Reservering geannuleerd',
    'booking_cancelled_body' => 'Uw reservering voor :activity op :date is geannuleerd.',
    'booking_cancelled_refund' => 'U komt in aanmerking voor een terugbetaling.',
    'view_booking' => 'Mijn reservering bekijken',

    /*
    |--------------------------------------------------------------------------
    | Sessiebevestiging / -annulering
    |--------------------------------------------------------------------------
    */

    'session_confirmed_subject' => 'Sessie bevestigd',
    'session_confirmed_body' => 'De :activity sessie op :date om :time is bevestigd!',
    'session_cancelled_subject' => 'Sessie geannuleerd',
    'session_cancelled_body' => 'De :activity sessie op :date is geannuleerd.',

    /*
    |--------------------------------------------------------------------------
    | Coach uitbetaling
    |--------------------------------------------------------------------------
    */

    'payout_processed_subject' => 'Uitbetaling verwerkt',
    'payout_processed_body' => 'Een overschrijving van :amount is gedaan op uw rekening.',
    'payout_processed_invoice' => 'Factuurnummer: :invoice_number',
    'view_dashboard' => 'Dashboard bekijken',

    /*
    |--------------------------------------------------------------------------
    | Admin gebruikersinvoering
    |--------------------------------------------------------------------------
    */

    'admin_onboarding_subject' => 'Welkom bij Motivya — stel uw wachtwoord in',
    'admin_onboarding_body' => 'Een beheerder heeft een back-office account voor u aangemaakt op Motivya. Klik op de knop hieronder om uw wachtwoord in te stellen en uw account te activeren.',
    'admin_onboarding_action' => 'Mijn wachtwoord instellen',
    'admin_onboarding_hint' => 'Na het instellen van uw wachtwoord wordt u gevraagd om meervoudige verificatie in te stellen voordat u toegang krijgt tot uw dashboard.',

];
