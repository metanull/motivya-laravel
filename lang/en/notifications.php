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

    /*
    |--------------------------------------------------------------------------
    | New Application (admin)
    |--------------------------------------------------------------------------
    */

    'new_coach_application_subject' => 'New coach application',
    'new_coach_application_body' => ':name (:email) has submitted a coach application.',
    'new_coach_application_specialties' => 'Specialties: :specialties.',
    'new_coach_application_action' => 'View applications',
    'new_coach_application_body_short' => 'A new coach application has been submitted.',

    /*
    |--------------------------------------------------------------------------
    | Session Reminder
    |--------------------------------------------------------------------------
    */

    'session_reminder_subject' => 'Reminder: session tomorrow',
    'session_reminder_body' => 'Your :activity session is tomorrow at :time.',
    'view_session' => 'View session',

    /*
    |--------------------------------------------------------------------------
    | Booking Confirmation / Cancellation
    |--------------------------------------------------------------------------
    */

    'booking_confirmed_subject' => 'Booking confirmed',
    'booking_confirmed_body' => 'Your booking for :activity on :date at :time is confirmed.',
    'booking_cancelled_subject' => 'Booking cancelled',
    'booking_cancelled_body' => 'Your booking for :activity on :date has been cancelled.',
    'booking_cancelled_refund' => 'You are eligible for a refund.',
    'view_booking' => 'View my booking',

    /*
    |--------------------------------------------------------------------------
    | Session Confirmation / Cancellation
    |--------------------------------------------------------------------------
    */

    'session_confirmed_subject' => 'Session confirmed',
    'session_confirmed_body' => 'The :activity session on :date at :time is confirmed!',
    'session_cancelled_subject' => 'Session cancelled',
    'session_cancelled_body' => 'The :activity session on :date has been cancelled.',

];
