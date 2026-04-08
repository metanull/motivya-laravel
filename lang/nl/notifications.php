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
    'coach_approved_action' => 'Ga naar mijn dashboard',

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

];
