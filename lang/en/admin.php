<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin — Coach Approval
    |--------------------------------------------------------------------------
    */

    'coach_approval_title' => 'Coach Validation',
    'coach_approval_heading' => 'Pending Coach Applications',
    'no_pending_applications' => 'No pending applications.',

    'applied_at' => 'Applied at',

    'approve' => 'Approve',
    'reject' => 'Reject',
    'approve_confirm' => 'Are you sure you want to approve this coach?',
    'confirm_reject' => 'Confirm rejection',
    'rejection_reason_label' => 'Rejection reason',
    'rejection_reason_placeholder' => 'Provide the reason for rejection…',

    'coach_approved' => 'Coach has been approved successfully.',
    'coach_rejected' => 'Application has been rejected.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Activity Images
    |--------------------------------------------------------------------------
    */

    'activity_images_title' => 'Activity Images',
    'activity_images_heading' => 'Activity Cover Images',
    'upload_image' => 'Upload New Image',
    'activity_type_label' => 'Activity Type',
    'select_activity_type' => 'Select an activity type…',
    'alt_text_label' => 'Alt Text',
    'alt_text_placeholder' => 'Describe the image…',
    'image_file_label' => 'Image File (JPG, PNG, WebP — max 2 MB)',
    'upload_button' => 'Upload',
    'existing_images' => 'Existing Images',
    'no_images' => 'No images uploaded yet.',
    'confirm_delete_image' => 'Are you sure you want to delete this image?',
    'image_uploaded' => 'Image uploaded successfully.',
    'image_deleted' => 'Image deleted successfully.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Database Export
    |--------------------------------------------------------------------------
    */

    'data_export_title' => 'Database Export',
    'data_export_heading' => 'Database Export',
    'data_export_description' => 'Download full CSV dumps of the database for coaches, sessions, and payments.',

    'export_coaches_title' => 'Coaches',
    'export_coaches_description' => 'All registered coaches and their profile details.',

    'export_sessions_title' => 'Sessions',
    'export_sessions_description' => 'All sport sessions with coach and schedule information.',

    'export_payments_title' => 'Payments',
    'export_payments_description' => 'All bookings and payment transactions.',

    'export_csv' => 'Export CSV',

    /*
    |--------------------------------------------------------------------------
    | Admin — Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard_title' => 'Admin Dashboard',
    'dashboard_heading' => 'Admin Dashboard',
    'dashboard_subtitle' => 'Overview of platform operations.',
    'dashboard_card_coach_approval' => 'Coach Applications',
    'dashboard_card_coach_approval_desc' => 'Review and approve pending coach applications.',
    'dashboard_card_users' => 'User Management',
    'dashboard_card_users_desc' => 'Manage users, roles, and account status.',
    'dashboard_suspended_count' => ':count suspended account(s)',
    'dashboard_mfa_missing_count' => ':count admin/accountant(s) without MFA',
    'dashboard_unverified_count' => ':count unverified email(s)',
    'dashboard_card_activity_images' => 'Activity Images',
    'dashboard_card_activity_images_desc' => 'Manage cover images for activity types.',
    'dashboard_card_data_export' => 'Data Export',
    'dashboard_card_data_export_desc' => 'Download CSV exports of the platform data.',

    /*
    |--------------------------------------------------------------------------
    | Admin — User Management
    |--------------------------------------------------------------------------
    */

    'users_title' => 'User Management',
    'users_heading' => 'Users',
    'no_users_found' => 'No users found.',

    'create_user_title' => 'Create User',
    'create_user_heading' => 'Create Back-Office User',
    'create_user_role_label' => 'Role',
    'create_user_submit' => 'Create User',
    'user_created' => 'User created. An onboarding email has been sent.',

    'col_role' => 'Role',
    'col_email_verified' => 'Email Verified',
    'col_mfa' => 'MFA',
    'col_status' => 'Status',
    'col_created_at' => 'Created',
    'col_actions' => 'Actions',

    'filter_all_roles' => 'All roles',
    'filter_all_statuses' => 'All statuses',
    'filter_active' => 'Active',
    'filter_suspended' => 'Suspended',

    'status_active' => 'Active',
    'status_suspended' => 'Suspended',

    'action_reset_password' => 'Reset password',
    'action_change_role' => 'Change role',
    'action_suspend' => 'Suspend',
    'action_reactivate' => 'Reactivate',

    'suspend_confirm_title' => 'Suspend Account',
    'suspension_reason_label' => 'Reason',
    'suspension_reason_placeholder' => 'Provide the reason for suspension…',

    'change_role_title' => 'Change Role',

    'user_suspended' => 'Account suspended.',
    'user_reactivated' => 'Account reactivated.',
    'role_changed' => 'Role updated.',
    'password_reset_sent' => 'Password reset link sent.',

    'role_change_coach_blocked' => 'The Coach role cannot be directly assigned. Use the coach approval workflow.',
    'role_change_last_admin' => 'Cannot demote the last active administrator.',

];
