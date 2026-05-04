<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin — Coach Approval
    |--------------------------------------------------------------------------
    */

    'coach_approval_title' => 'Validation des coachs',
    'coach_approval_heading' => 'Candidatures de coachs en attente',
    'no_pending_applications' => 'Aucune candidature en attente.',

    'applied_at' => 'Date de candidature',

    'approve' => 'Approuver',
    'reject' => 'Rejeter',
    'approve_confirm' => 'Êtes-vous sûr de vouloir approuver ce coach ?',
    'confirm_reject' => 'Confirmer le rejet',
    'rejection_reason_label' => 'Motif du rejet',
    'rejection_reason_placeholder' => 'Indiquez la raison du rejet…',

    'coach_approved' => 'Le coach a été approuvé avec succès.',
    'coach_rejected' => 'La candidature a été rejetée.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Activity Images
    |--------------------------------------------------------------------------
    */

    'activity_images_title' => 'Images d\'activité',
    'activity_images_heading' => 'Images de couverture des activités',
    'upload_image' => 'Télécharger une nouvelle image',
    'activity_type_label' => 'Type d\'activité',
    'select_activity_type' => 'Sélectionnez un type d\'activité…',
    'alt_text_label' => 'Texte alternatif',
    'alt_text_placeholder' => 'Décrivez l\'image…',
    'image_file_label' => 'Fichier image (JPG, PNG, WebP — max 2 Mo)',
    'upload_button' => 'Télécharger',
    'existing_images' => 'Images existantes',
    'no_images' => 'Aucune image téléchargée.',
    'confirm_delete_image' => 'Êtes-vous sûr de vouloir supprimer cette image ?',
    'image_uploaded' => 'Image téléchargée avec succès.',
    'image_deleted' => 'Image supprimée avec succès.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Database Export
    |--------------------------------------------------------------------------
    */

    'data_export_title' => 'Export de la base de données',
    'data_export_heading' => 'Export de la base de données',
    'data_export_description' => 'Téléchargez des exports CSV complets de la base de données pour les coachs, les séances et les paiements.',

    'export_coaches_title' => 'Coachs',
    'export_coaches_description' => 'Tous les coachs enregistrés et les détails de leur profil.',

    'export_sessions_title' => 'Séances',
    'export_sessions_description' => 'Toutes les séances sportives avec les informations du coach et du planning.',

    'export_payments_title' => 'Paiements',
    'export_payments_description' => 'Toutes les réservations et transactions de paiement.',

    'export_csv' => 'Exporter en CSV',

    /*
    |--------------------------------------------------------------------------
    | Admin — Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard_title' => 'Tableau de bord Admin',
    'dashboard_heading' => 'Tableau de bord Admin',
    'dashboard_subtitle' => 'Vue d\'ensemble des opérations de la plateforme.',
    'dashboard_card_coach_approval' => 'Candidatures de coachs',
    'dashboard_card_coach_approval_desc' => 'Examinez et approuvez les candidatures de coachs en attente.',
    'dashboard_card_users' => 'Gestion des utilisateurs',
    'dashboard_card_users_desc' => 'Gérez les utilisateurs, les rôles et le statut des comptes.',
    'dashboard_suspended_count' => ':count compte(s) suspendu(s)',
    'dashboard_mfa_missing_count' => ':count admin/comptable(s) sans MFA',
    'dashboard_unverified_count' => ':count e-mail(s) non vérifié(s)',
    'dashboard_card_activity_images' => 'Images d\'activité',
    'dashboard_card_activity_images_desc' => 'Gérez les images de couverture pour les types d\'activités.',
    'dashboard_card_data_export' => 'Export de données',
    'dashboard_card_data_export_desc' => 'Téléchargez des exports CSV des données de la plateforme.',

    /*
    |--------------------------------------------------------------------------
    | Admin — User Management
    |--------------------------------------------------------------------------
    */

    'users_title' => 'Gestion des utilisateurs',
    'users_heading' => 'Utilisateurs',
    'no_users_found' => 'Aucun utilisateur trouvé.',

    'create_user_title' => 'Créer un utilisateur',
    'create_user_heading' => 'Créer un utilisateur back-office',
    'create_user_role_label' => 'Rôle',
    'create_user_submit' => 'Créer l\'utilisateur',
    'user_created' => 'Utilisateur créé. Un e-mail d\'intégration a été envoyé.',

    'col_role' => 'Rôle',
    'col_email_verified' => 'E-mail vérifié',
    'col_mfa' => 'MFA',
    'col_status' => 'Statut',
    'col_created_at' => 'Créé le',
    'col_actions' => 'Actions',

    'filter_all_roles' => 'Tous les rôles',
    'filter_all_statuses' => 'Tous les statuts',
    'filter_active' => 'Actif',
    'filter_suspended' => 'Suspendu',

    'status_active' => 'Actif',
    'status_suspended' => 'Suspendu',

    'action_reset_password' => 'Réinitialiser le mot de passe',
    'action_change_role' => 'Changer le rôle',
    'action_suspend' => 'Suspendre',
    'action_reactivate' => 'Réactiver',

    'suspend_confirm_title' => 'Suspendre le compte',
    'suspension_reason_label' => 'Raison',
    'suspension_reason_placeholder' => 'Indiquez la raison de la suspension…',

    'change_role_title' => 'Changer le rôle',

    'user_suspended' => 'Compte suspendu.',
    'user_reactivated' => 'Compte réactivé.',
    'role_changed' => 'Rôle mis à jour.',
    'password_reset_sent' => 'Lien de réinitialisation du mot de passe envoyé.',

    'role_change_coach_blocked' => 'Le rôle Coach ne peut pas être assigné directement. Utilisez le flux d\'approbation des coachs.',
    'role_change_last_admin' => 'Impossible de rétrograder le dernier administrateur actif.',

];
