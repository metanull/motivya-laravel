<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Profile Page
    |--------------------------------------------------------------------------
    */

    'title' => 'Profile',
    'heading' => 'Profile Settings',

    /*
    |--------------------------------------------------------------------------
    | Personal Information
    |--------------------------------------------------------------------------
    */

    'info_heading' => 'Personal Information',
    'info_description' => 'Update your name and email address.',
    'info_name' => 'Full name',
    'info_email' => 'Email address',
    'info_submit' => 'Save',
    'info_saved' => 'Saved.',

    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    */

    'password_heading' => 'Update Password',
    'password_description' => 'Ensure your account is using a long, random password to stay secure.',
    'password_current' => 'Current password',
    'password_new' => 'New password',
    'password_confirm' => 'Confirm password',
    'password_submit' => 'Save',
    'password_saved' => 'Saved.',

    /*
    |--------------------------------------------------------------------------
    | Locale Preference
    |--------------------------------------------------------------------------
    */

    'locale_heading' => 'Language Preference',
    'locale_description' => 'Choose your preferred language for the interface.',
    'locale_label' => 'Language',
    'locale_fr' => 'Français',
    'locale_en' => 'English',
    'locale_nl' => 'Nederlands',
    'locale_submit' => 'Save',
    'locale_updated' => 'Language preference updated.',

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */

    'twofa_heading' => 'Two-Factor Authentication',
    'twofa_description' => 'Add additional security to your account using two-factor authentication.',
    'twofa_not_available' => 'Two-factor authentication will be available in a future update.',
    'twofa_enabled_status' => 'Two-factor authentication is enabled.',
    'twofa_enable' => 'Enable',
    'twofa_enabling' => 'Enabling…',
    'twofa_disable' => 'Disable',
    'twofa_scan_instructions' => 'Scan the following QR code using your authenticator application (Google Authenticator, Authy, etc.), then enter the verification code to confirm.',
    'twofa_confirm_code' => 'Verification code',
    'twofa_confirm' => 'Confirm',
    'twofa_invalid_code' => 'The provided code is invalid.',
    'twofa_recovery_description' => 'Store these recovery codes in a secure password manager. They can be used to recover access to your account if your authenticator device is lost.',
    'twofa_show_codes' => 'Show Recovery Codes',
    'twofa_hide_codes' => 'Hide',
    'twofa_regenerate_codes' => 'Regenerate Recovery Codes',
    'twofa_method_label' => 'Method',
    'twofa_method_totp' => 'Authenticator App (TOTP)',
    'twofa_method_email' => 'Email Code',
    'twofa_email_enable' => 'Enable Email 2FA',
    'twofa_email_enabling' => 'Sending code…',
    'twofa_email_description' => 'A verification code will be sent to your email address each time you log in.',
    'twofa_email_confirm_description' => 'Enter the verification code sent to your email to confirm setup.',
    'twofa_email_confirm_code' => 'Verification code',
    'twofa_email_confirm' => 'Confirm',
    'twofa_email_enabled_status' => 'Email-based two-factor authentication is enabled.',
    'twofa_email_resend' => 'Resend code',
    'twofa_email_resent' => 'A new code has been sent.',
    'twofa_totp_enabled_status' => 'Authenticator app two-factor authentication is enabled.',

    /*
    |--------------------------------------------------------------------------
    | Delete Account
    |--------------------------------------------------------------------------
    */

    'delete_heading' => 'Delete Account',
    'delete_description' => 'Once your account is deleted, all of its resources and data will be permanently deleted.',

];
