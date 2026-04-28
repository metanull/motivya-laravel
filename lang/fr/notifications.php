<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Chaînes de notification
    |--------------------------------------------------------------------------
    */

    'greeting' => 'Bonjour :name,',
    'thanks' => 'Merci d\'utiliser Motivya !',

    /*
    |--------------------------------------------------------------------------
    | Authentification à deux facteurs
    |--------------------------------------------------------------------------
    */

    'two_factor_code_subject' => 'Votre code de vérification',
    'two_factor_code_body' => 'Votre code d\'authentification à deux facteurs est : :code',
    'two_factor_code_expiry' => 'Ce code expirera dans 10 minutes.',
    'two_factor_code_ignore' => 'Si vous n\'avez pas demandé ce code, veuillez ignorer cet e-mail.',

    /*
    |--------------------------------------------------------------------------
    | Candidature Coach
    |--------------------------------------------------------------------------
    */

    'coach_approved_subject' => 'Votre candidature de coach a été approuvée !',
    'coach_approved_body' => 'Félicitations ! Votre candidature de coach sur Motivya a été approuvée.',
    'coach_approved_specialties' => 'Vos spécialités : :specialties.',
    'coach_approved_action' => 'Configurer les paiements et démarrer',
    'coach_approved_stripe_hint' => 'Pour accepter des réservations et recevoir des paiements, vous devez connecter votre compte Stripe. Cliquez sur le bouton ci-dessus pour commencer — cela ne prend que quelques minutes.',

    'coach_rejected_subject' => 'Votre candidature de coach n\'a pas été retenue',
    'coach_rejected_body' => 'Nous sommes désolés, votre candidature de coach sur Motivya n\'a pas été retenue.',
    'coach_rejected_reason' => 'Motif : :reason',

    /*
    |--------------------------------------------------------------------------
    | Nouvelle candidature (admin)
    |--------------------------------------------------------------------------
    */

    'new_coach_application_subject' => 'Nouvelle candidature de coach',
    'new_coach_application_body' => ':name (:email) a soumis une candidature de coach.',
    'new_coach_application_specialties' => 'Spécialités : :specialties.',
    'new_coach_application_action' => 'Voir les candidatures',
    'new_coach_application_body_short' => 'Une nouvelle candidature de coach a été soumise.',

    /*
    |--------------------------------------------------------------------------
    | Rappel de séance
    |--------------------------------------------------------------------------
    */

    'session_reminder_subject' => 'Rappel : séance demain',
    'session_reminder_body' => 'Votre séance :activity a lieu demain à :time.',
    'view_session' => 'Voir la séance',

    /*
    |--------------------------------------------------------------------------
    | Confirmation / annulation de réservation
    |--------------------------------------------------------------------------
    */

    'booking_confirmed_subject' => 'Réservation confirmée',
    'booking_confirmed_body' => 'Votre réservation pour :activity le :date à :time est confirmée.',
    'booking_cancelled_subject' => 'Réservation annulée',
    'booking_cancelled_body' => 'Votre réservation pour :activity le :date a été annulée.',
    'booking_cancelled_refund' => 'Vous êtes éligible à un remboursement.',
    'view_booking' => 'Voir ma réservation',

    /*
    |--------------------------------------------------------------------------
    | Confirmation / annulation de séance
    |--------------------------------------------------------------------------
    */

    'session_confirmed_subject' => 'Séance confirmée',
    'session_confirmed_body' => 'La séance :activity du :date à :time est confirmée !',
    'session_cancelled_subject' => 'Séance annulée',
    'session_cancelled_body' => 'La séance :activity du :date a été annulée.',

    /*
    |--------------------------------------------------------------------------
    | Paiement du coach
    |--------------------------------------------------------------------------
    */

    'payout_processed_subject' => 'Paiement effectué',
    'payout_processed_body' => 'Un virement de :amount a été effectué sur votre compte.',
    'payout_processed_invoice' => 'Numéro de facture : :invoice_number',
    'view_dashboard' => 'Voir le tableau de bord',

];
