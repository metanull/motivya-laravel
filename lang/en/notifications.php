<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Strings
    |--------------------------------------------------------------------------
    */

    'greeting' => 'Hello :name,',
    'thanks' => 'Thank you for using Motivya!',

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */

    'two_factor_code_subject' => 'Your verification code',
    'two_factor_code_body' => 'Your two-factor authentication code is: :code',
    'two_factor_code_expiry' => 'This code will expire in 10 minutes.',
    'two_factor_code_ignore' => 'If you did not request this code, please ignore this email.',

    /*
    |--------------------------------------------------------------------------
    | Coach Application
    |--------------------------------------------------------------------------
    */

    'coach_approved_subject' => 'Your coach application has been approved!',
    'coach_approved_body' => 'Congratulations! Your coach application on Motivya has been approved.',
    'coach_approved_specialties' => 'Your specialties: :specialties.',
    'coach_approved_action' => 'Go to my dashboard',

    'coach_rejected_subject' => 'Your coach application was not accepted',
    'coach_rejected_body' => 'We are sorry, your coach application on Motivya was not accepted.',
    'coach_rejected_reason' => 'Reason: :reason',

];
