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
    'image_uploading' => 'Téléchargement du fichier...',
    'uploading' => 'Téléchargement...',
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
    'refunds_error_already_refunded' => 'Cette réservation a déjà été remboursée.',
    'refunds_error_not_confirmed' => 'Seules les réservations confirmées peuvent être remboursées.',
    'refunds_error_no_amount' => 'Aucun montant n\'a été payé pour cette réservation.',
    'refunds_error_missing_payment_intent' => 'Cette réservation n\'a pas d\'intention de paiement Stripe associée. Exécutez payments:reconcile-bookings --repair avant de réessayer.',
    'refunds_error_stripe_failure' => 'La demande de remboursement a été rejetée par Stripe. Consultez le journal d\'audit pour plus de détails.',
    'refunds_missing_payment_intent_label' => 'Réconciliation requise',
    'refunds_no_results' => 'Aucune réservation trouvée.',
    'dashboard_card_refunds' => 'Remboursements exceptionnels',
    'dashboard_card_refunds_desc' => 'Traitez les remboursements exceptionnels pour les réservations confirmées et payées.',

    // Anomalies de paiement (Story #269)
    'anomalies_heading' => 'File d\'anomalies de paiement',
    'anomalies_subtitle' => 'Anomalies de paiement ouvertes nécessitant une attention.',
    'anomalies_col_type' => 'Type',
    'anomalies_col_description' => 'Description',
    'anomalies_col_recommended_action' => 'Action recommandée',
    'anomalies_col_actions' => 'Actions',
    'anomalies_action_resolve' => 'Résoudre',
    'anomalies_action_ignore' => 'Ignorer',
    'anomalies_confirm_resolve' => 'Résoudre l\'anomalie',
    'anomalies_confirm_ignore' => 'Ignorer l\'anomalie',
    'anomalies_resolve_reason_label' => 'Note de résolution',
    'anomalies_resolve_reason_placeholder' => 'Décrivez comment cette anomalie a été résolue…',
    'anomalies_ignore_reason_label' => 'Raison d\'ignorance',
    'anomalies_ignore_reason_placeholder' => 'Indiquez la raison d\'ignorer cette anomalie…',
    'anomalies_no_results' => 'Aucune anomalie ouverte trouvée.',
    'anomalies_filter_all_types' => 'Tous les types',
    'anomalies_type_confirmed_booking_missing_payment' => 'Réservation confirmée sans paiement',
    'anomalies_type_paid_booking_cancelled_without_refund' => 'Réservation payée annulée sans remboursement',
    'anomalies_type_completed_session_without_invoice' => 'Séance terminée sans facture',
    'anomalies_type_invoice_total_mismatch' => 'Écart de total de facture',
    'anomalies_type_coach_stripe_incomplete' => 'Compte Stripe coach incomplet',
    'dashboard_card_anomalies' => 'Anomalies de paiement',
    'dashboard_card_anomalies_desc' => 'Anomalies de paiement ouvertes nécessitant une révision.',

    /*
    |--------------------------------------------------------------------------
    | Admin — Liste de contrôle de disponibilité MVP
    |--------------------------------------------------------------------------
    */

    'dashboard_card_readiness' => 'Disponibilité MVP',
    'dashboard_card_readiness_desc' => 'Vérifiez la disponibilité de la plateforme avant le lancement.',

    'readiness_title' => 'Disponibilité MVP',
    'readiness_heading' => 'Liste de contrôle de disponibilité MVP',
    'readiness_subtitle' => 'Statut vert/jaune/rouge pour chaque prérequis critique de la plateforme. Aucune valeur secrète n\'est exposée.',
    'readiness_back_to_dashboard' => 'Retour au tableau de bord',

    'readiness_status_ok' => 'OK',
    'readiness_status_warning' => 'Avertissement',
    'readiness_status_error' => 'Erreur',

    'readiness_check_stripe' => 'Clés Stripe configurées',
    'readiness_check_mail' => 'Courrier configuré',
    'readiness_check_database' => 'Base de données accessible',
    'readiness_check_cache' => 'Cache accessible',
    'readiness_check_queue' => 'File d\'attente configurée',
    'readiness_check_scheduler' => 'Battements du planificateur',
    'readiness_check_postal_codes' => 'Coordonnées des codes postaux chargées',
    'readiness_check_admin_mfa' => 'Admin avec MFA',
    'readiness_check_accountant' => 'Utilisateur comptable existant',
    'readiness_check_activity_images' => 'Images d\'activité téléchargées',
    'readiness_check_billing_config' => 'Page de configuration de facturation accessible',
    'readiness_check_google_maps_key' => 'Clé API Google Maps',
    'readiness_check_geocoding_cache' => 'Table de cache de géocodage',
    'readiness_check_public_storage' => 'Lien symbolique du stockage public',

    'readiness_stripe_ok' => 'Les clés Stripe publiable et secrète sont configurées.',
    'readiness_stripe_missing' => 'Les clés Stripe ne sont pas configurées. Définissez STRIPE_KEY et STRIPE_SECRET.',
    'readiness_stripe_unexpected_format' => 'Les clés Stripe sont définies mais ne correspondent pas au format pk_/sk_ attendu.',
    'readiness_mail_ok' => 'Le pilote de courrier ":driver" est configuré.',
    'readiness_mail_log_driver' => 'Le courrier utilise le pilote "log". Définissez un fournisseur de messagerie réel pour la production.',
    'readiness_mail_smtp_missing_host' => 'Le pilote SMTP est sélectionné mais MAIL_HOST n\'est pas configuré.',
    'readiness_database_ok' => 'La connexion à la base de données est saine.',
    'readiness_database_error' => 'Impossible de se connecter à la base de données.',
    'readiness_cache_ok' => 'Le pilote de cache ":driver" est accessible.',
    'readiness_cache_error' => 'Le cache n\'est pas accessible.',
    'readiness_queue_ok' => 'Le pilote de file d\'attente ":driver" est configuré.',
    'readiness_queue_error' => 'Le pilote de file d\'attente n\'est pas configuré.',
    'readiness_scheduler_all_ok' => 'Toutes les tâches planifiées critiques ont été exécutées récemment.',
    'readiness_scheduler_warning' => 'Certaines tâches planifiées n\'ont pas été exécutées récemment.',
    'readiness_scheduler_error' => 'Une ou plusieurs tâches planifiées critiques n\'ont jamais été exécutées.',
    'readiness_scheduler_ok' => 'Dernière exécution :time.',
    'readiness_scheduler_stale' => 'Dernière exécution :time — peut être obsolète.',
    'readiness_scheduler_never_run' => 'Jamais exécuté. Assurez-vous que le planificateur est en cours d\'exécution.',
    'readiness_postal_codes_ok' => ':count coordonnées de codes postaux chargées.',
    'readiness_postal_codes_missing' => 'Aucune coordonnée de code postal trouvée. Exécutez le semoir de coordonnées.',
    'readiness_admin_mfa_ok' => ':count administrateur(s) actif(s) avec MFA configuré.',
    'readiness_admin_mfa_missing' => 'Aucun administrateur actif n\'a le MFA configuré.',
    'readiness_accountant_ok' => ':count comptable(s) actif(s) existe(nt).',
    'readiness_accountant_missing' => 'Aucun utilisateur comptable actif trouvé. Créez-en un dans la gestion des utilisateurs.',
    'readiness_activity_images_ok' => ':count image(s) d\'activité téléchargée(s).',
    'readiness_activity_images_missing' => 'Aucune image d\'activité téléchargée. Ajoutez des images dans Images d\'activité.',
    'readiness_billing_config_ok' => 'La page de configuration de facturation est accessible.',
    'readiness_billing_config_missing' => 'La route de configuration de facturation n\'est pas enregistrée.',
    'readiness_google_maps_key_ok' => 'La clé API Google Maps est configurée.',
    'readiness_google_maps_key_missing' => 'GOOGLE_MAPS_API_KEY n\'est pas défini. Le géocodage Google de secours est désactivé. L\'affichage de la carte (MapLibre/OpenFreeMap) fonctionne toujours.',
    'readiness_google_maps_key_format' => 'GOOGLE_MAPS_API_KEY ne correspond pas au format attendu AIza...',
    'readiness_geocoding_cache_ok' => 'La table de cache de géocodage est disponible.',
    'readiness_geocoding_cache_missing' => 'La table de cache de géocodage est manquante. Exécutez php artisan migrate.',
    'readiness_public_storage_ok' => 'Le lien symbolique du stockage public existe.',
    'readiness_public_storage_missing' => 'Le lien symbolique du stockage public est manquant. Exécutez php artisan storage:link ou vérifiez le script de déploiement.',
    'readiness_public_storage_not_symlink' => 'public/storage existe mais n\'est pas un lien symbolique. Les images d\'activité peuvent ne pas être servies correctement.',

    'readiness_scheduler_detail_heading' => 'Battements du planificateur',
    'readiness_scheduler_detail_subtitle' => 'Dernière exécution enregistrée pour chaque tâche planifiée critique.',
    'readiness_scheduler_col_command' => 'Commande',
    'readiness_scheduler_col_status' => 'Dernière exécution',

    'readiness_action_billing' => 'Vérifier la facturation',
    'readiness_action_users' => 'Gérer les utilisateurs',
    'readiness_action_activity_images' => 'Gérer les images',

    /*
    |--------------------------------------------------------------------------
    | Admin — Visionneuse du journal d'audit
    |--------------------------------------------------------------------------
    */

    'audit_events_title' => 'Journal d\'audit',
    'audit_events_heading' => 'Journal des événements d\'audit',
    'audit_events_subtitle' => 'Journal en lecture seule de tous les événements d\'audit du domaine.',
    'audit_events_detail_title' => 'Détail de l\'événement d\'audit',
    'audit_events_detail_heading' => 'Détail de l\'événement d\'audit',
    'audit_events_back' => 'Retour au journal d\'audit',
    'audit_events_action_view' => 'Voir',
    'audit_events_no_results' => 'Aucun événement d\'audit trouvé correspondant à vos filtres.',
    'audit_events_filter_all' => 'Tous',
    'audit_events_filter_occurred_from' => 'Date de début',
    'audit_events_filter_occurred_to' => 'Date de fin',
    'audit_events_filter_event_type' => 'Type d\'événement',
    'audit_events_filter_operation' => 'Opération',
    'audit_events_filter_actor_type' => 'Type d\'acteur',
    'audit_events_filter_actor_id' => 'ID de l\'acteur',
    'audit_events_filter_actor_id_placeholder' => 'ID utilisateur…',
    'audit_events_filter_actor_role' => 'Rôle de l\'acteur',
    'audit_events_filter_source' => 'Source',
    'audit_events_filter_model_type' => 'Type de modèle principal',
    'audit_events_filter_model_type_placeholder' => 'p. ex. SportSession…',
    'audit_events_filter_model_id' => 'ID du modèle principal',
    'audit_events_filter_model_id_placeholder' => 'ID…',
    'audit_events_filter_subject_type' => 'Type de sujet',
    'audit_events_filter_subject_type_placeholder' => 'p. ex. Booking…',
    'audit_events_filter_subject_id' => 'ID du sujet',
    'audit_events_filter_subject_id_placeholder' => 'ID…',
    'audit_events_filter_subject_relation' => 'Relation du sujet',
    'audit_events_filter_subject_relation_placeholder' => 'p. ex. booking…',
    'audit_events_filter_request_id' => 'ID de requête',
    'audit_events_filter_request_id_placeholder' => 'UUID…',
    'audit_events_filter_reset' => 'Réinitialiser les filtres',
    'audit_events_col_occurred_at' => 'Date/heure',
    'audit_events_col_event_type' => 'Type d\'événement',
    'audit_events_col_operation' => 'Opération',
    'audit_events_col_actor' => 'Acteur',
    'audit_events_col_source' => 'Source',
    'audit_events_col_primary_model' => 'Modèle principal',
    'audit_events_col_subjects' => 'Sujets',
    'audit_events_col_request_id' => 'ID de requête',
    'audit_events_detail_actor_type' => 'Type d\'acteur',
    'audit_events_detail_actor_id' => 'ID de l\'acteur',
    'audit_events_detail_actor_role' => 'Rôle de l\'acteur',
    'audit_events_detail_request_id' => 'ID de requête',
    'audit_events_detail_ip_address' => 'Adresse IP',
    'audit_events_detail_route_name' => 'Nom de route',
    'audit_events_detail_job_uuid' => 'UUID de job',
    'audit_events_detail_old_values' => 'Anciennes valeurs',
    'audit_events_detail_new_values' => 'Nouvelles valeurs',
    'audit_events_detail_metadata' => 'Métadonnées',
    'audit_events_detail_subjects' => 'Sujets',
    'audit_events_detail_no_subjects' => 'Aucun sujet lié à cet événement.',
    'audit_events_detail_subject_relation' => 'Relation',
    'audit_events_detail_subject_relation_primary' => 'principal',
    'audit_events_detail_subject_type' => 'Type de sujet',
    'audit_events_detail_subject_id' => 'ID du sujet',
    'dashboard_card_audit_events' => 'Journal d\'audit',
    'dashboard_card_audit_events_desc' => 'Parcourir et filtrer le journal complet des événements d\'audit.',

];
