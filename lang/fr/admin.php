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
    'dashboard_card_billing_config' => 'Configuration de la facturation',
    'dashboard_card_billing_config_desc' => 'Consultez les plans d\'abonnement actifs, les taux de commission et la formule de versement.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Billing Configuration
    |--------------------------------------------------------------------------
    */

    'billing_config_title' => 'Configuration de la facturation',
    'billing_config_heading' => 'Configuration des abonnements & commissions',
    'billing_config_subtitle' => 'Vue en lecture seule des règles de facturation actives. Toute modification nécessite un déploiement de code ou de configuration.',
    'billing_config_read_only_notice' => 'Ces valeurs sont en lecture seule. Pour modifier les règles de facturation, un déploiement est requis.',
    'billing_config_plan_heading' => 'Plans d\'abonnement',
    'billing_config_col_plan' => 'Plan',
    'billing_config_col_commission' => 'Taux de commission',
    'billing_config_col_subscription_fee' => 'Frais mensuels',
    'billing_config_col_description' => 'Description',
    'billing_config_free' => 'Gratuit',
    'billing_config_plan_desc_freemium' => 'Sans frais mensuel. Taux de commission le plus élevé.',
    'billing_config_plan_desc_active' => 'Frais mensuels intermédiaires. Taux de commission réduit.',
    'billing_config_plan_desc_premium' => 'Frais mensuels les plus élevés. Taux de commission le plus bas.',
    'billing_config_vat_heading' => 'Configuration TVA',
    'billing_config_vat_rate_label' => 'Taux de TVA plateforme',
    'billing_config_vat_subject' => 'Coach assujetti à la TVA',
    'billing_config_vat_subject_note' => 'Taux belge standard (art. 21% CTVA).',
    'billing_config_vat_franchise' => 'Régime de franchise',
    'billing_config_vat_franchise_note' => 'Exonéré en vertu de l\'art. 56bis CTVA.',
    'billing_config_vat_source' => 'Source : taux belge standard codé en dur (21 %), appliqué uniquement aux coachs assujettis à la TVA.',
    'billing_config_stripe_heading' => 'Traitement des paiements',
    'billing_config_stripe_fee_label' => 'Taux de frais Stripe (estimé)',
    'billing_config_stripe_source' => 'Source : config au déploiement (STRIPE_COMMISSION_RATE ou valeur par défaut 1,5 %).',
    'billing_config_payout_heading' => 'Formule de versement',
    'billing_config_payout_description' => 'La plateforme sélectionne le plan qui génère le versement net le plus élevé pour le coach (algorithme auto-best-plan). Versement net = Chiffre d\'affaires (HTVA) − Commission − Frais d\'abonnement − Frais Stripe.',
    'billing_config_payout_note' => 'Tous les calculs de versement sont effectués sur des montants HTVA (hors TVA) afin que la marge de Motivya soit identique quelle que soit la situation TVA du coach.',

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

    /*
    |--------------------------------------------------------------------------
    | Admin — Session Supervision
    |--------------------------------------------------------------------------
    */

    'sessions_title' => 'Supervision des séances',
    'sessions_heading' => 'Supervision des séances',
    'sessions_subtitle' => 'Recherchez, inspectez, annulez et terminez des séances sur toute la plateforme.',
    'sessions_filter_status' => 'Statut',
    'sessions_filter_all_statuses' => 'Tous les statuts',
    'sessions_filter_coach' => 'Coach',
    'sessions_filter_coach_placeholder' => 'Nom du coach…',
    'sessions_filter_activity_type' => 'Type d\'activité',
    'sessions_filter_all_types' => 'Tous les types',
    'sessions_filter_date_from' => 'Date de début',
    'sessions_filter_date_to' => 'Date de fin',
    'sessions_filter_pending_payment' => 'Paiement en attente uniquement',
    'sessions_filter_past_end_time' => 'Heure de fin dépassée uniquement',
    'sessions_filter_reset' => 'Réinitialiser les filtres',
    'sessions_col_title' => 'Titre',
    'sessions_col_coach' => 'Coach',
    'sessions_col_activity' => 'Activité',
    'sessions_col_datetime' => 'Date / Heure',
    'sessions_col_status' => 'Statut',
    'sessions_col_participants' => 'Participants',
    'sessions_col_confirmed_bookings' => 'Confirmées',
    'sessions_col_pending_bookings' => 'En attente',
    'sessions_col_actions' => 'Actions',
    'sessions_action_view' => 'Voir',
    'sessions_action_cancel' => 'Annuler',
    'sessions_action_complete' => 'Terminer',
    'sessions_action_complete_confirm' => 'Marquer cette séance comme terminée ? Cela déclenchera la génération d\'une facture.',
    'sessions_cancel_reason_label' => 'Motif d\'annulation',
    'sessions_cancel_reason_placeholder' => 'Indiquez la raison de l\'annulation…',
    'sessions_cancel_confirm_btn' => 'Confirmer l\'annulation',
    'sessions_cancelled_success' => 'Séance annulée avec succès.',
    'sessions_cancelled_error' => 'Échec de l\'annulation de la séance.',
    'sessions_completed_success' => 'Séance marquée comme terminée.',
    'sessions_completed_error' => 'Échec de la clôture de la séance.',
    'sessions_stripe_ready' => 'Stripe configuré',
    'sessions_stripe_not_ready' => 'Stripe non configuré',
    'sessions_no_results' => 'Aucune séance trouvée correspondant à vos filtres.',
    'dashboard_card_sessions' => 'Supervision des séances',
    'dashboard_card_sessions_desc' => 'Recherchez, inspectez, annulez et terminez des séances.',

    /*
    |--------------------------------------------------------------------------
    | Admin — File de remboursements exceptionnels
    |--------------------------------------------------------------------------
    */

    'refunds_title' => 'File de remboursements exceptionnels',
    'refunds_heading' => 'File de remboursements exceptionnels',
    'refunds_subtitle' => 'Consultez les réservations et traitez les remboursements exceptionnels pour les réservations confirmées et payées.',
    'refunds_filter_status' => 'Statut de la réservation',
    'refunds_filter_all_statuses' => 'Tous les statuts',
    'refunds_col_booking' => 'Réservation',
    'refunds_col_athlete' => 'Athlète',
    'refunds_col_session' => 'Séance',
    'refunds_col_status' => 'Statut',
    'refunds_col_amount' => 'Montant payé',
    'refunds_col_date' => 'Date',
    'refunds_col_actions' => 'Actions',
    'refunds_action_refund' => 'Rembourser',
    'refunds_refund_reason_label' => 'Motif du remboursement',
    'refunds_refund_reason_placeholder' => 'Indiquez la raison de ce remboursement exceptionnel…',
    'refunds_confirm_btn' => 'Confirmer le remboursement',
    'refunds_not_eligible' => 'Non éligible',
    'refunds_ineligibility_reason' => 'La réservation ne peut pas faire l\'objet d\'un remboursement exceptionnel (doit être confirmée avec un montant payé positif).',
    'refunds_success' => 'Remboursement traité avec succès.',
    'refunds_error' => 'Échec du traitement du remboursement.',
    'refunds_no_results' => 'Aucune réservation trouvée.',
    'dashboard_card_refunds' => 'Remboursements exceptionnels',
    'dashboard_card_refunds_desc' => 'Traitez les remboursements exceptionnels pour les réservations confirmées et payées.',

];
