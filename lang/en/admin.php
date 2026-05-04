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
    'dashboard_card_billing_config' => 'Billing Configuration',
    'dashboard_card_billing_config_desc' => 'Review active subscription plans, commission rates, and payout formula.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Billing Configuration
    |--------------------------------------------------------------------------
    */

    'billing_config_title' => 'Billing Configuration',
    'billing_config_heading' => 'Subscription & Commission Configuration',
    'billing_config_subtitle' => 'Read-only view of active billing rules. Changes require a code or configuration deployment.',
    'billing_config_read_only_notice' => 'These values are read-only. To change billing rules, a deployment is required.',
    'billing_config_plan_heading' => 'Subscription Plans',
    'billing_config_col_plan' => 'Plan',
    'billing_config_col_commission' => 'Commission Rate',
    'billing_config_col_subscription_fee' => 'Monthly Fee',
    'billing_config_col_description' => 'Description',
    'billing_config_free' => 'Free',
    'billing_config_plan_desc_freemium' => 'No monthly fee. Highest commission rate applies.',
    'billing_config_plan_desc_active' => 'Mid-tier monthly fee. Reduced commission rate.',
    'billing_config_plan_desc_premium' => 'Highest monthly fee. Lowest commission rate.',
    'billing_config_vat_heading' => 'VAT Configuration',
    'billing_config_vat_rate_label' => 'Platform VAT Rate',
    'billing_config_vat_subject' => 'VAT-Subject Coach',
    'billing_config_vat_subject_note' => 'Standard Belgian rate (art. 21% CTVA).',
    'billing_config_vat_franchise' => 'Franchise Regime',
    'billing_config_vat_franchise_note' => 'Exempt under art. 56bis CTVA.',
    'billing_config_vat_source' => 'Source: hardcoded Belgian standard rate (21%) applied to VAT-subject coaches only.',
    'billing_config_stripe_heading' => 'Payment Processing',
    'billing_config_stripe_fee_label' => 'Stripe Fee Rate (estimated)',
    'billing_config_stripe_source' => 'Source: deploy-time config (STRIPE_COMMISSION_RATE or default 1.5%).',
    'billing_config_payout_heading' => 'Payout Formula',
    'billing_config_payout_description' => 'The platform selects the plan that yields the highest net payout for the coach (auto-best-plan algorithm). Net payout = Revenue (HTVA) − Commission − Subscription Fee − Stripe Fees.',
    'billing_config_payout_note' => 'All payout calculations operate on HTVA (tax-exclusive) amounts to ensure Motivya\'s margin is identical regardless of the coach\'s VAT status.',

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

    /*
    |--------------------------------------------------------------------------
    | Admin — Session Supervision
    |--------------------------------------------------------------------------
    */

    'sessions_title' => 'Session Supervision',
    'sessions_heading' => 'Session Supervision',
    'sessions_subtitle' => 'Search, inspect, cancel, and complete sessions across the platform.',
    'sessions_filter_status' => 'Status',
    'sessions_filter_all_statuses' => 'All statuses',
    'sessions_filter_coach' => 'Coach',
    'sessions_filter_coach_placeholder' => 'Coach name…',
    'sessions_filter_activity_type' => 'Activity Type',
    'sessions_filter_all_types' => 'All types',
    'sessions_filter_date_from' => 'Date from',
    'sessions_filter_date_to' => 'Date to',
    'sessions_filter_pending_payment' => 'Pending payment only',
    'sessions_filter_past_end_time' => 'Past end time only',
    'sessions_filter_reset' => 'Reset filters',
    'sessions_col_title' => 'Title',
    'sessions_col_coach' => 'Coach',
    'sessions_col_activity' => 'Activity',
    'sessions_col_datetime' => 'Date / Time',
    'sessions_col_status' => 'Status',
    'sessions_col_participants' => 'Participants',
    'sessions_col_confirmed_bookings' => 'Confirmed',
    'sessions_col_pending_bookings' => 'Pending',
    'sessions_col_actions' => 'Actions',
    'sessions_action_view' => 'View',
    'sessions_action_cancel' => 'Cancel',
    'sessions_action_complete' => 'Complete',
    'sessions_action_complete_confirm' => 'Mark this session as completed? This will trigger invoice generation.',
    'sessions_cancel_reason_label' => 'Cancellation reason',
    'sessions_cancel_reason_placeholder' => 'Provide the reason for cancellation…',
    'sessions_cancel_confirm_btn' => 'Confirm Cancel',
    'sessions_cancelled_success' => 'Session cancelled successfully.',
    'sessions_cancelled_error' => 'Failed to cancel session.',
    'sessions_completed_success' => 'Session marked as completed.',
    'sessions_completed_error' => 'Failed to complete session.',
    'sessions_stripe_ready' => 'Stripe ready',
    'sessions_stripe_not_ready' => 'Stripe not ready',
    'sessions_no_results' => 'No sessions found matching your filters.',
    'dashboard_card_sessions' => 'Session Supervision',
    'dashboard_card_sessions_desc' => 'Search, inspect, cancel, and complete sessions.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Exceptional Refund Queue
    |--------------------------------------------------------------------------
    */

    'refunds_title' => 'Exceptional Refund Queue',
    'refunds_heading' => 'Exceptional Refund Queue',
    'refunds_subtitle' => 'Review bookings and process exceptional refunds for confirmed paid bookings.',
    'refunds_filter_status' => 'Booking Status',
    'refunds_filter_all_statuses' => 'All statuses',
    'refunds_col_booking' => 'Booking',
    'refunds_col_athlete' => 'Athlete',
    'refunds_col_session' => 'Session',
    'refunds_col_status' => 'Status',
    'refunds_col_amount' => 'Amount Paid',
    'refunds_col_date' => 'Date',
    'refunds_col_actions' => 'Actions',
    'refunds_action_refund' => 'Refund',
    'refunds_refund_reason_label' => 'Refund reason',
    'refunds_refund_reason_placeholder' => 'Provide the reason for this exceptional refund…',
    'refunds_confirm_btn' => 'Confirm Refund',
    'refunds_not_eligible' => 'Not eligible',
    'refunds_ineligibility_reason' => 'Booking is not eligible for exceptional refund (must be confirmed with a positive amount paid).',
    'refunds_success' => 'Refund processed successfully.',
    'refunds_error' => 'Failed to process refund.',
    'refunds_no_results' => 'No bookings found.',
    'dashboard_card_refunds' => 'Exceptional Refunds',
    'dashboard_card_refunds_desc' => 'Process exceptional refunds for confirmed paid bookings.',

];
