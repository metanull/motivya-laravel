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
    'coach_approved_action' => 'Accéder à mon tableau de bord',

    'coach_rejected_subject' => 'Votre candidature de coach n\'a pas été retenue',
    'coach_rejected_body' => 'Nous sommes désolés, votre candidature de coach sur Motivya n\'a pas été retenue.',
    'coach_rejected_reason' => 'Motif : :reason',

];
