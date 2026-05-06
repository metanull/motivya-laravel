<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Session Status Labels
    |--------------------------------------------------------------------------
    */

    'status_draft' => 'Brouillon',
    'status_published' => 'Publiée',
    'status_confirmed' => 'Confirmée',
    'status_completed' => 'Terminée',
    'status_cancelled' => 'Annulée',

    /*
    |--------------------------------------------------------------------------
    | Session Level Labels
    |--------------------------------------------------------------------------
    */

    'level_beginner' => 'Débutant',
    'level_intermediate' => 'Intermédiaire',
    'level_advanced' => 'Avancé',

    /*
    |--------------------------------------------------------------------------
    | Activity Type Labels
    |--------------------------------------------------------------------------
    */

    'activity_yoga' => 'Yoga',
    'activity_strength' => 'Musculation',
    'activity_running' => 'Course à pied',
    'activity_cardio' => 'Cardio',
    'activity_pilates' => 'Pilates',
    'activity_outdoor' => 'Outdoor',
    'activity_boxing' => 'Boxe',
    'activity_dance' => 'Danse',
    'activity_padel' => 'Padel',
    'activity_tennis' => 'Tennis',

    /*
    |--------------------------------------------------------------------------
    | Session Detail Page
    |--------------------------------------------------------------------------
    */

    'by_coach' => 'Par',
    'date' => 'Date',
    'time' => 'Heure',
    'location' => 'Lieu',
    'price' => 'Prix',
    'per_person' => 'par personne',
    'spots' => 'Places',
    'spots_remaining' => '{0} Aucune place disponible|{1} :count place disponible|[2,*] :count places disponibles',
    'description' => 'Description',
    'share_whatsapp' => 'Partager sur WhatsApp',
    'copy_link' => 'Copier le lien',
    'link_copied' => 'Lien copi\u00e9 dans le presse-papiers !',

    /*
    |--------------------------------------------------------------------------
    | Session Creation / Editing Form
    |--------------------------------------------------------------------------
    */

    'create_title' => 'Créer une séance',
    'create_heading' => 'Créer une nouvelle séance',
    'edit_title' => 'Modifier la séance',
    'edit_heading' => 'Modifier la séance',
    'activity_type_label' => 'Type d\'activité',
    'select_activity_type' => 'Sélectionnez un type d\'activité…',
    'level_label' => 'Niveau',
    'select_level' => 'Sélectionnez un niveau…',
    'title_label' => 'Titre',
    'title_placeholder' => 'ex. Yoga matinal au parc',
    'description_label' => 'Description',
    'description_placeholder' => 'Décrivez votre séance…',
    'location_label' => 'Lieu',
    'location_placeholder' => 'ex. Parc du Cinquantenaire',
    'postal_code_label' => 'Code postal',
    'date_label' => 'Date',
    'start_time_label' => 'Heure de début',
    'end_time_label' => 'Heure de fin',
    'price_label' => 'Prix (EUR)',
    'price_hint' => 'Prix par personne, hors commission plateforme.',
    'min_participants_label' => 'Participants min.',
    'min_participants_hint' => 'La séance est confirmée dès que ce seuil est atteint.',
    'max_participants_label' => 'Participants max.',
    'max_participants_hint' => 'Nombre de places disponibles.',
    'cover_image_label' => 'Image de couverture',
    'create_button' => 'Créer la séance',
    'update_button' => 'Mettre à jour',
    'created' => 'Séance créée avec succès.',
    'updated' => 'Séance mise à jour avec succès.',
    'deleted' => 'Séance supprimée.',
    'cancelled' => 'Séance annulée.',
    'published' => 'Séance publiée.',
    'repeat_weekly' => 'Répéter chaque semaine',
    'number_of_weeks' => 'Nombre de semaines',
    'recurring_hint' => 'Crée des séances individuelles pour chaque semaine (max 12 semaines).',
    'recurring_created' => ':count séances récurrentes créées avec succès.',    'recurring_edit_prompt' => 'Cette session fait partie d\'un groupe récurrent. Que souhaitez-vous modifier ?',
    'edit_this_only' => 'Cette session uniquement',
    'edit_all_future' => 'Toutes les sessions futures de ce groupe',
    'group_updated' => ':count sessions mises à jour avec succès.',

    /*
    |--------------------------------------------------------------------------
    | Session Discovery Page
    |--------------------------------------------------------------------------
    */

    'discovery_title' => 'Trouver des séances',
    'discovery_heading' => 'Trouver une séance',
    'discovery_subtitle' => 'Parcourez les prochaines séances près de chez vous.',
    'all_activities' => 'Toutes les activités',
    'all_levels' => 'Tous les niveaux',
    'date_from_label' => 'Du',
    'date_to_label' => 'Au',
    'time_from_label' => 'De',
    'time_to_label' => 'À',
    'reset_filters' => 'Réinitialiser les filtres',
    'no_results' => 'Aucune séance trouvée pour ces filtres.',
    'result_count' => '{0} Aucune séance trouvée|{1} :count séance trouvée|[2,*] :count séances trouvées',
    'view_session' => 'Voir la séance',
    'geo_use' => 'Utiliser ma position',
    'geo_active' => 'Position utilisée',
    'geo_clear' => 'Effacer',
    'geo_not_supported' => 'La géolocalisation n\'est pas prise en charge par votre navigateur.',
    'geo_denied' => 'L\'accès à la position a été refusé. Recherche par code postal utilisée.',
    'location_query_label' => 'Ville ou code postal',
    'location_query_placeholder' => 'ex. Ixelles, Bruxelles, 1050',
    'radius_label' => 'Rayon',
    'radius_km' => ':km km',
    'active_location_radius' => ':location · :km km',
    'geo_denied_state' => 'Accès à la position refusé. Recherchez par ville ou code postal ci-dessus.',
    'no_results_radius' => 'Aucune séance dans un rayon de :km km. Essayez d\'augmenter le rayon ou de modifier les filtres.',
    'invalid_location' => 'Lieu introuvable. Essayez un code postal (ex. 1000, 1050) ou un nom de commune (ex. Bruxelles, Ixelles).',
    'map_title' => 'Séances sur la carte',
    'map_no_markers' => 'Aucune séance cartographiée ne correspond à vos filtres actuels.',
    'directions' => 'Itinéraire',

    /*
    |--------------------------------------------------------------------------
    | Disponibilité de paiement
    |--------------------------------------------------------------------------
    */

    'stripe_not_ready_heading' => 'Configuration des paiements incomplète',
    'stripe_not_ready_body' => 'Vous pouvez enregistrer cette séance, mais les sportifs ne pourront pas la réserver tant que vous n\'avez pas complété votre configuration de paiement Stripe.',
    'stripe_not_ready_action' => 'Configurer les paiements Stripe →',

    /*
    |--------------------------------------------------------------------------
    | Garde de publication
    |--------------------------------------------------------------------------
    */

    'publish_requires_stripe_onboarding' => 'Vous devez compléter l\'intégration Stripe avant de publier une séance.',

    /*
    |--------------------------------------------------------------------------
    | Validation d'adresse
    |--------------------------------------------------------------------------
    */

    'address_label' => 'Adresse',
    'address_placeholder' => 'Ex : Parc du Cinquantenaire, 1000 Bruxelles',
    'validate_address' => 'Valider l\'adresse',
    'address_validated' => 'Adresse validée : :address',
    'address_not_validated' => 'Veuillez valider l\'adresse avant de sauvegarder.',
    'address_not_found' => 'Adresse introuvable. Veuillez préciser.',
    'publish_requires_formatted_address' => 'Une adresse validée avec coordonnées est requise pour publier la séance.',

];
